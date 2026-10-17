<?php
/* admin/first-timer-action.php — POST-only handler for first-timer records.
 *
 * Actions: contacted, reopen, delete.
 * contacted sets status = 'contacted' and stamps contacted_at (only from
 * 'new'); reopen returns the record to 'new' and clears contacted_at (only
 * from 'contacted'); delete removes the row. Every success redirects back to
 * a whitelisted target with a flash message; unknown actions, bad ids and
 * bad CSRF tokens are rejected with HTTP 400. The visitor's contact details
 * are never written to the admin log — only the record id and the old/new
 * status. The first_timers table already exists and is never altered. */
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

$ftAllowed = array('contacted', 'reopen', 'delete');
$ftAction = isset($_POST['action']) && is_string($_POST['action']) ? strtolower(trim($_POST['action'])) : '';
if (!in_array($ftAction, $ftAllowed, true)) {
    first_timer_action_fail('Unknown action.');
}

/* Id must be a positive integer naming an existing record. */
$ftId = 0;
if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^[1-9][0-9]{0,9}$/', trim($_POST['id']))) {
    $ftId = (int)trim($_POST['id']);
}
if ($ftId <= 0) {
    first_timer_action_fail('That first-timer record could not be found.');
}

$ftRow = null;
try {
    $ftFind = $conn->prepare('SELECT id, status FROM first_timers WHERE id = ? LIMIT 1');
    if ($ftFind === false) {
        first_timer_action_fail('That first-timer record could not be found.');
    }
    $ftFind->bind_param('i', $ftId);
    $ftFind->execute();
    $ftRes = $ftFind->get_result();
    $ftRow = ($ftRes !== false) ? $ftRes->fetch_assoc() : null;
    $ftFind->close();
} catch (Throwable $ignored) {
    $ftRow = null;
}
if (!is_array($ftRow) || !isset($ftRow['status'])) {
    first_timer_action_fail('That first-timer record could not be found.');
}
$oldStatus = (string)$ftRow['status'];

/* Allowed transitions (contacted only from new, reopen only from contacted). */
$isNew = ($oldStatus === 'new');
$isContacted = ($oldStatus === 'contacted');
if ($ftAction === 'contacted' && !$isNew) {
    first_timer_action_fail('That action is not available for this visitor.');
}
if ($ftAction === 'reopen' && !$isContacted) {
    first_timer_action_fail('That action is not available for this visitor.');
}

$adminId = (int)$admin['id'];
$saved = false;
$newStatus = $oldStatus;
try {
    if ($ftAction === 'delete') {
        $ftStmt = $conn->prepare('DELETE FROM first_timers WHERE id = ? LIMIT 1');
        if ($ftStmt !== false) {
            $ftStmt->bind_param('i', $ftId);
            $ftStmt->execute();
            $saved = ($ftStmt->affected_rows === 1);
            $ftStmt->close();
        }
    } elseif ($ftAction === 'contacted') {
        $ftStmt = $conn->prepare('UPDATE first_timers SET status = \'contacted\', contacted_at = NOW() WHERE id = ? AND status = \'new\'');
        if ($ftStmt !== false) {
            $ftStmt->bind_param('i', $ftId);
            $ftStmt->execute();
            $saved = ($ftStmt->affected_rows === 1);
            $ftStmt->close();
            $newStatus = 'contacted';
        }
    } elseif ($ftAction === 'reopen') {
        $ftStmt = $conn->prepare('UPDATE first_timers SET status = \'new\', contacted_at = NULL WHERE id = ? AND status = \'contacted\'');
        if ($ftStmt !== false) {
            $ftStmt->bind_param('i', $ftId);
            $ftStmt->execute();
            $saved = ($ftStmt->affected_rows === 1);
            $ftStmt->close();
            $newStatus = 'new';
        }
    }
} catch (Throwable $ignored) {
    $saved = false;
}

if ($saved) {
    /* Log only the record id and the status change — never the visitor's
       name, phone number, email or prayer need. */
    $logAction = 'first_timer_' . $ftAction;
    $logDetails = 'First-timer #' . $ftId . ' · ' . $oldStatus;
    if ($ftAction !== 'delete' && $newStatus !== $oldStatus) {
        $logDetails .= ' → ' . $newStatus;
    } elseif ($ftAction === 'delete') {
        $logDetails .= ' → deleted';
    }
    admin_log_action($conn, $adminId, $logAction, $logDetails);

    $flashText = array(
        'contacted' => 'Marked as contacted. The contacted time has been recorded.',
        'reopen'    => 'First-timer record reopened and marked as new.',
        'delete'    => 'First-timer record deleted.',
    );
    flash_set('success', isset($flashText[$ftAction]) ? $flashText[$ftAction] : 'Done.');
    header('Location: ' . first_timer_action_target());
    exit;
}

flash_set('error', 'That action could not be saved. Please try again.');
header('Location: ' . first_timer_action_target());
exit;

/* Whitelisted return target: first-timer.php (with a valid id),
 * first-timers.php or index.php, keeping a safely rebuilt filter query
 * string. */
function first_timer_action_target() {
    $raw = isset($_POST['return']) && is_string($_POST['return']) ? trim($_POST['return']) : '';
    $allowed = array('first-timer.php', 'first-timers.php', 'index.php');
    $base = 'first-timers.php';
    $query = array();
    if ($raw !== '') {
        $parts = explode('?', $raw, 2);
        $candidate = basename(str_replace('\\', '/', strtolower(trim($parts[0]))));
        if (in_array($candidate, $allowed, true)) {
            $base = $candidate;
            if (isset($parts[1])) {
                parse_str($parts[1], $parsed);
                $keep = array('id', 'status', 'visit', 'q', 'page');
                foreach ($keep as $keepKey) {
                    if (isset($parsed[$keepKey]) && is_string($parsed[$keepKey]) && $parsed[$keepKey] !== '') {
                        $value = $parsed[$keepKey];
                        if ($keepKey === 'id') {
                            if (!preg_match('/^[1-9][0-9]{0,9}$/', trim($value))) {
                                continue;
                            }
                            $value = (string)(int)$value;
                        } elseif ($keepKey === 'status') {
                            $value = strtolower(trim($value));
                            if ($value === 'all') {
                                continue;
                            }
                            if (!in_array($value, array('new', 'contacted'), true)) {
                                continue;
                            }
                        } elseif ($keepKey === 'visit') {
                            $value = strtolower(trim($value));
                            if (!in_array($value, array('upcoming', 'past', 'all'), true)) {
                                continue;
                            }
                            if ($value === 'all') {
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
function first_timer_action_fail($message) {
    http_response_code(400);
    $title = 'Something went wrong';
    $body = '<section class="kci-error"><div class="kci-error__card">'
        . '<span class="kci-error__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>'
        . '<h1>Something went wrong</h1>'
        . '<p>' . e($message) . ' Please go back and try again.</p>'
        . '<a class="kci-btn kci-btn--primary" href="first-timers.php">Back to first-timers</a>'
        . '</div></section>';
    admin_error_page($title, $body);
    exit;
}