<?php
/* admin/giving-action.php — POST-only handler for giving confirmations.
 *
 * Actions: confirm, reject, reopen, delete. Transitions are validated
 * server-side (pending to confirmed or rejected; confirmed or rejected
 * back to pending via reopen; delete from any status). Every success
 * redirects back to a whitelisted target with a flash message; unknown
 * actions, bad ids and bad CSRF tokens are rejected with HTTP 400. Only
 * the id, old/new status and amount are written to the admin log — never
 * the giver's contact details. Tables already exist and are never
 * created or altered. */
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

$giveAllowed = array('confirm', 'reject', 'reopen', 'delete');
$giveAction = isset($_POST['action']) && is_string($_POST['action']) ? strtolower(trim($_POST['action'])) : '';
if (!in_array($giveAction, $giveAllowed, true)) {
    giving_action_fail('Unknown action.');
}

/* Id must be a positive integer naming an existing confirmation. */
$giveId = 0;
if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^[1-9][0-9]{0,9}$/', trim($_POST['id']))) {
    $giveId = (int)trim($_POST['id']);
}
if ($giveId <= 0) {
    giving_action_fail('That confirmation could not be found.');
}

$giveRow = null;
try {
    $giveFind = $conn->prepare('SELECT id, status, amount FROM giving_proofs WHERE id = ? LIMIT 1');
    if ($giveFind === false) {
        giving_action_fail('That confirmation could not be found.');
    }
    $giveFind->bind_param('i', $giveId);
    $giveFind->execute();
    $giveRes = $giveFind->get_result();
    $giveRow = ($giveRes !== false) ? $giveRes->fetch_assoc() : null;
    $giveFind->close();
} catch (Throwable $ignored) {
    $giveRow = null;
}
if (!is_array($giveRow) || !isset($giveRow['status'])) {
    giving_action_fail('That confirmation could not be found.');
}
$oldStatus = (string)$giveRow['status'];
$giveAmount = isset($giveRow['amount']) ? (string)$giveRow['amount'] : '0';

/* Allowed status transitions (reopen returns any status to pending). */
$newStatus = null;
if ($giveAction === 'confirm') {
    $newStatus = ($oldStatus === 'pending') ? 'confirmed' : null;
} elseif ($giveAction === 'reject') {
    $newStatus = ($oldStatus === 'pending') ? 'rejected' : null;
} elseif ($giveAction === 'reopen') {
    $newStatus = 'pending';
}
if ($giveAction !== 'delete' && $newStatus === null) {
    giving_action_fail('That action is not available for this confirmation.');
}

/* Optional admin note from the confirm dialog (max 500 characters). */
$giveNote = isset($_POST['admin_note']) && is_string($_POST['admin_note']) ? trim($_POST['admin_note']) : '';
if (function_exists('mb_substr')) {
    $giveNote = mb_substr($giveNote, 0, 500, 'UTF-8');
} else {
    $giveNote = substr($giveNote, 0, 500);
}

$adminId = (int)$admin['id'];
$saved = false;
try {
    if ($giveAction === 'delete') {
        $giveStmt = $conn->prepare('DELETE FROM giving_proofs WHERE id = ? LIMIT 1');
        if ($giveStmt !== false) {
            $giveStmt->bind_param('i', $giveId);
            $giveStmt->execute();
            $saved = ($giveStmt->affected_rows === 1);
            $giveStmt->close();
        }
    } else {
        $giveStmt = $conn->prepare(
            'UPDATE giving_proofs SET status = ?, reviewed_by = ?, reviewed_at = NOW(), admin_note = ? WHERE id = ? LIMIT 1'
        );
        if ($giveStmt !== false) {
            $giveStmt->bind_param('sisi', $newStatus, $adminId, $giveNote, $giveId);
            $giveStmt->execute();
            $saved = ($giveStmt->affected_rows === 1);
            $giveStmt->close();
        }
    }
} catch (Throwable $ignored) {
    $saved = false;
}

/* Log id + old/new status + amount only — never contact details. */
if ($saved) {
    $logAction = 'giving_' . $giveAction;
    $logDetails = ($giveAction === 'delete')
        ? 'giving #' . $giveId . ' deleted (was ' . $oldStatus . ', ' . kci_naira($giveAmount) . ')'
        : 'giving #' . $giveId . ': ' . $oldStatus . ' -> ' . $newStatus . ' (' . kci_naira($giveAmount) . ')';
    admin_log_action($conn, $adminId, $logAction, $logDetails);

    $flashText = array(
        'confirm' => 'Giving confirmed.',
        'reject'  => 'Giving rejected.',
        'reopen'  => 'Giving reopened.',
        'delete'  => 'Giving confirmation deleted.',
    );
    flash_set('success', isset($flashText[$giveAction]) ? $flashText[$giveAction] : 'Done.');
    header('Location: ' . giving_action_target());
    exit;
}

flash_set('error', 'That action could not be saved. Please try again.');
header('Location: ' . giving_action_target());
exit;

/* Whitelisted return target: giving-confirmations.php or index.php, keeping a
 * safely rebuilt filter query string. */
function giving_action_target() {
    $raw = isset($_POST['return']) && is_string($_POST['return']) ? trim($_POST['return']) : '';
    $allowed = array('giving-confirmations.php', 'index.php');
    $base = 'giving-confirmations.php';
    $query = array();
    if ($raw !== '') {
        $parts = explode('?', $raw, 2);
        $candidate = basename(str_replace('\\', '/', strtolower(trim($parts[0]))));
        if (in_array($candidate, $allowed, true)) {
            $base = $candidate;
            if (isset($parts[1])) {
                parse_str($parts[1], $parsed);
                $keep = array('status', 'q', 'type', 'page');
                foreach ($keep as $keepKey) {
                    if (isset($parsed[$keepKey]) && is_string($parsed[$keepKey]) && $parsed[$keepKey] !== '') {
                        $value = $parsed[$keepKey];
                        if ($keepKey === 'status') {
                            $value = strtolower(trim($value));
                            if ($value === 'all') {
                                continue;
                            }
                            if (!in_array($value, array('pending', 'confirmed', 'rejected'), true)) {
                                continue;
                            }
                        } elseif ($keepKey === 'type') {
                            $value = trim($value);
                            if (!in_array($value, array('Tithe', 'Offering', 'Seed', 'Building and Projects', 'Missions and Outreach', 'Other'), true)) {
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
function giving_action_fail($message) {
    http_response_code(400);
    $title = 'Something went wrong';
    $body = '<section class="kci-error"><div class="kci-error__card">'
        . '<span class="kci-error__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>'
        . '<h1>Something went wrong</h1>'
        . '<p>' . e($message) . ' Please go back and try again.</p>'
        . '<a class="kci-btn kci-btn--primary" href="giving-confirmations.php">Back to Giving confirmations</a>'
        . '</div></section>';
    admin_error_page($title, $body);
    exit;
}

