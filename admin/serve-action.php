<?php
/* admin/serve-action.php — POST-only handler for Serve applications.
 *
 * Actions: accept, reject, reopen, contacted, completed, delete.
 * Transitions are validated server-side (pending to accepted or rejected;
 * accepted to contacted; contacted to completed; any status back to pending
 * via reopen; delete from any status). Every success redirects back to a
 * whitelisted target with a flash message; unknown actions, bad ids and
 * bad CSRF tokens are rejected with HTTP 400. The applicant's contact
 * details are never written to the admin log — only the application id and
 * the old/new status. Tables already exist and are never created or altered. */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../kci_db.php';

$admin = require_admin($conn);

/* POST only — no visible page. */
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}

/* A POST without a valid CSRF token is rejected with 400. */
if (!csrf_check()) {
    csrf_fail();
}

$serveAllowed = array('accept', 'reject', 'reopen', 'contacted', 'completed', 'delete');
$serveAction = isset($_POST['action']) && is_string($_POST['action']) ? strtolower(trim($_POST['action'])) : '';
if (!in_array($serveAction, $serveAllowed, true)) {
    serve_action_fail('Unknown action.');
}

/* Id must be a positive integer naming an existing application. */
$serveId = 0;
if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^[1-9][0-9]{0,9}$/', trim($_POST['id']))) {
    $serveId = (int)trim($_POST['id']);
}
if ($serveId <= 0) {
    serve_action_fail('That application could not be found.');
}

$serveRow = null;
try {
    $serveFind = $conn->prepare('SELECT id, status FROM serve_applications WHERE id = ? LIMIT 1');
    if ($serveFind === false) {
        serve_action_fail('That application could not be found.');
    }
    $serveFind->bind_param('i', $serveId);
    $serveFind->execute();
    $serveRes = $serveFind->get_result();
    $serveRow = ($serveRes !== false) ? $serveRes->fetch_assoc() : null;
    $serveFind->close();
} catch (Throwable $ignored) {
    $serveRow = null;
}
if (!is_array($serveRow) || !isset($serveRow['status'])) {
    serve_action_fail('That application could not be found.');
}
$oldStatus = (string)$serveRow['status'];

/* Allowed status transitions (reopen returns any status to pending). */
$newStatus = null;
if ($serveAction === 'accept') {
    $newStatus = ($oldStatus === 'pending') ? 'accepted' : null;
} elseif ($serveAction === 'reject') {
    $newStatus = ($oldStatus === 'pending') ? 'rejected' : null;
} elseif ($serveAction === 'contacted') {
    $newStatus = ($oldStatus === 'accepted') ? 'contacted' : null;
} elseif ($serveAction === 'completed') {
    $newStatus = ($oldStatus === 'contacted') ? 'completed' : null;
} elseif ($serveAction === 'reopen') {
    $newStatus = 'pending';
}
if ($serveAction !== 'delete' && $newStatus === null) {
    serve_action_fail('That action is not available for this application.');
}

/* Optional admin note from the confirm dialog (max 500 characters). */
$serveNote = isset($_POST['admin_note']) && is_string($_POST['admin_note']) ? trim($_POST['admin_note']) : '';
if (function_exists('mb_substr')) {
    $serveNote = mb_substr($serveNote, 0, 500, 'UTF-8');
} else {
    $serveNote = substr($serveNote, 0, 500);
}

$adminId = (int)$admin['id'];
$saved = false;
try {
    if ($serveAction === 'delete') {
        $serveStmt = $conn->prepare('DELETE FROM serve_applications WHERE id = ? LIMIT 1');
        if ($serveStmt !== false) {
            $serveStmt->bind_param('i', $serveId);
            $serveStmt->execute();
            $saved = ($serveStmt->affected_rows === 1);
            $serveStmt->close();
        }
    } else {
        $serveStmt = $conn->prepare(
            'UPDATE serve_applications SET status = ?, reviewed_by = ?, reviewed_at = NOW(), admin_note = ? WHERE id = ? LIMIT 1'
        );
        if ($serveStmt !== false) {
            $serveStmt->bind_param('sisi', $newStatus, $adminId, $serveNote, $serveId);
            $serveStmt->execute();
            $saved = ($serveStmt->affected_rows === 1);
            $serveStmt->close();
        }
    }
} catch (Throwable $ignored) {
    $saved = false;
}

/* Log id + old/new status only — never the applicant's contact details. */
if ($saved) {
    $logAction = 'serve_application_' . $serveAction;
    $logDetails = ($serveAction === 'delete')
        ? 'application #' . $serveId . ' deleted (was ' . $oldStatus . ')'
        : 'application #' . $serveId . ': ' . $oldStatus . ' -> ' . $newStatus;
    admin_log_action($conn, $adminId, $logAction, $logDetails);

    $flashText = array(
        'accept'    => 'Application accepted.',
        'reject'    => 'Application rejected.',
        'reopen'    => 'Application reopened.',
        'contacted' => 'Application marked as contacted.',
        'completed' => 'Application marked as completed.',
        'delete'    => 'Application deleted.',
    );
    flash_set('success', isset($flashText[$serveAction]) ? $flashText[$serveAction] : 'Done.');
    header('Location: ' . serve_action_target());
    exit;
}

flash_set('error', 'That action could not be saved. Please try again.');
header('Location: ' . serve_action_target());
exit;
flash_set('error', 'That action could not be saved. Please try again.');
header('Location: ' . serve_action_target());
exit;

/* Whitelisted return target: serve-applications.php or index.php, keeping a
 * safely rebuilt filter query string. */
function serve_action_target() {
    $raw = isset($_POST['return']) && is_string($_POST['return']) ? trim($_POST['return']) : '';
    $allowed = array('serve-applications.php', 'index.php');
    $base = 'serve-applications.php';
    $query = array();
    if ($raw !== '') {
        $parts = explode('?', $raw, 2);
        $candidate = basename(str_replace('\\', '/', strtolower(trim($parts[0]))));
        if (in_array($candidate, $allowed, true)) {
            $base = $candidate;
            if (isset($parts[1])) {
                parse_str($parts[1], $parsed);
                $keep = array('status', 'q', 'ministry', 'page');
                foreach ($keep as $keepKey) {
                    if (isset($parsed[$keepKey]) && is_string($parsed[$keepKey]) && $parsed[$keepKey] !== '') {
                        $value = $parsed[$keepKey];
                        if ($keepKey === 'status') {
                            $value = strtolower(trim($value));
                            if ($value === 'all') {
                                continue;
                            }
                            if (!in_array($value, array('pending', 'accepted', 'rejected', 'contacted', 'completed'), true)) {
                                continue;
                            }
                        } elseif ($keepKey === 'page') {
                            $value = (string)max(1, (int)$value);
                            if ($value === '1') {
                                continue;
                            }
                        } else {
                            $value = substr(trim($value), 0, 100);
                            if ($value === '') {
                                continue;
                            }
                        }
                        $query[$keepKey] = $value;
                    }
                }
            }
        }
    }
    $qs = http_build_query($query);
    return $base . ($qs !== '' ? '?' . $qs : '');
}

/* Friendly 400 page (no inline script). */
function serve_action_fail($message) {
    http_response_code(400);
    $title = 'Something went wrong';
    $body = '<section class="kci-error"><div class="kci-error__card">'
        . '<span class="kci-error__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>'
        . '<h1>Something went wrong</h1>'
        . '<p>' . e($message) . ' Please go back and try again.</p>'
        . '<a class="kci-btn kci-btn--primary" href="serve-applications.php">Back to Serve applications</a>'
        . '</div></section>';
    admin_error_page($title, $body);
    exit;
}