<?php
/* admin/message-action.php — POST-only handler for contact messages.
 *
 * Actions: read, unread, delete, read_all.
 * read sets is_read = 1 and unread sets is_read = 0 (from any state),
 * read_all marks every unread message as read, delete removes the row.
 * read_all needs no id; every other action needs an existing message.
 * Every success redirects back to a whitelisted target with a flash message;
 * unknown actions, bad ids and bad CSRF tokens are rejected with HTTP 400.
 * The sender's contact details are never written to the admin log — only the
 * message id and the action. Tables already exist and are never altered. */
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

$msgAllowed = array('read', 'unread', 'delete', 'read_all');
$msgAction = isset($_POST['action']) && is_string($_POST['action']) ? strtolower(trim($_POST['action'])) : '';
if (!in_array($msgAction, $msgAllowed, true)) {
    message_action_fail('Unknown action.');
}

/* read_all needs no id; every other action needs an existing message. */
$msgId = 0;
$msgRow = null;
$oldRead = '';
if ($msgAction !== 'read_all') {
    if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^[1-9][0-9]{0,9}$/', trim($_POST['id']))) {
        $msgId = (int)trim($_POST['id']);
    }
    if ($msgId <= 0) {
        message_action_fail('That message could not be found.');
    }

    try {
        $msgFind = $conn->prepare('SELECT id, is_read FROM contact_messages WHERE id = ? LIMIT 1');
        if ($msgFind === false) {
            message_action_fail('That message could not be found.');
        }
        $msgFind->bind_param('i', $msgId);
        $msgFind->execute();
        $msgRes = $msgFind->get_result();
        $msgRow = ($msgRes !== false) ? $msgRes->fetch_assoc() : null;
        $msgFind->close();
    } catch (Throwable $ignored) {
        $msgRow = null;
    }
    if (!is_array($msgRow) || !isset($msgRow['is_read'])) {
        message_action_fail('That message could not be found.');
    }
    $oldRead = (string)$msgRow['is_read'];
}


$adminId = (int)$admin['id'];
$saved = false;
$msgMarked = 0;
try {
    if ($msgAction === 'delete') {
        $msgStmt = $conn->prepare('DELETE FROM contact_messages WHERE id = ? LIMIT 1');
        if ($msgStmt !== false) {
            $msgStmt->bind_param('i', $msgId);
            $msgStmt->execute();
            $saved = ($msgStmt->affected_rows === 1);
            $msgStmt->close();
        }
    } elseif ($msgAction === 'read_all') {
        $msgStmt = $conn->prepare('UPDATE contact_messages SET is_read = 1 WHERE is_read = 0');
        if ($msgStmt !== false) {
            $msgStmt->execute();
            $msgMarked = max(0, (int)$msgStmt->affected_rows);
            $saved = true;
            $msgStmt->close();
        }
    } elseif ($msgAction === 'read') {
        $msgStmt = $conn->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?');
        if ($msgStmt !== false) {
            $msgStmt->bind_param('i', $msgId);
            $msgStmt->execute();
            $saved = ($msgStmt->affected_rows >= 0);
            $msgStmt->close();
        }
    } else {
        $msgStmt = $conn->prepare('UPDATE contact_messages SET is_read = 0 WHERE id = ?');
        if ($msgStmt !== false) {
            $msgStmt->bind_param('i', $msgId);
            $msgStmt->execute();
            $saved = ($msgStmt->affected_rows >= 0);
            $msgStmt->close();
        }
    }
} catch (Throwable $ignored) {
    $saved = false;
}

if ($saved) {
    $logAction = 'contact_message_' . $msgAction;
    if ($msgAction === 'delete') {
        $logDetails = 'message #' . $msgId . ' deleted';
    } elseif ($msgAction === 'read_all') {
        $logDetails = $msgMarked . ' message(s) marked read';
    } elseif ($msgAction === 'read') {
        $logDetails = 'message #' . $msgId . ' marked read (was is_read=' . $oldRead . ')';
    } else {
        $logDetails = 'message #' . $msgId . ' marked unread (was is_read=' . $oldRead . ')';
    }
    admin_log_action($conn, $adminId, $logAction, $logDetails);

    $flashText = array(
        'read' => 'Message marked as read.',
        'unread' => 'Message marked as unread.',
        'delete' => 'Message deleted.',
        'read_all' => 'All messages marked as read.',
    );
    flash_set('success', isset($flashText[$msgAction]) ? $flashText[$msgAction] : 'Done.');
    header('Location: ' . message_action_target());
    exit;
}

flash_set('error', 'That action could not be saved. Please try again.');
header('Location: ' . message_action_target());
exit;

/* Whitelisted return target: message.php (with a valid id), messages.php
 * or index.php, keeping a safely rebuilt filter query string. */
function message_action_target() {
    $raw = isset($_POST['return']) && is_string($_POST['return']) ? trim($_POST['return']) : '';
    $allowed = array('message.php', 'messages.php', 'index.php');
    $base = 'messages.php';
    $query = array();
    if ($raw !== '') {
        $parts = explode('?', $raw, 2);
        $candidate = basename(str_replace('\\', '/', strtolower(trim($parts[0]))));
        if (in_array($candidate, $allowed, true)) {
            $base = $candidate;
            if (isset($parts[1])) {
                parse_str($parts[1], $parsed);
                $keep = array('id', 'status', 'q', 'page');
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
                            if (!in_array($value, array('read', 'unread'), true)) {
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
function message_action_fail($message) {
    http_response_code(400);
    $title = 'Something went wrong';
    $body = '<section class="kci-error"><div class="kci-error__card">'
        . '<span class="kci-error__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>'
        . '<h1>Something went wrong</h1>'
        . '<p>' . e($message) . ' Please go back and try again.</p>'
        . '<a class="kci-btn kci-btn--primary" href="messages.php">Back to messages</a>'
        . '</div></section>';
    admin_error_page($title, $body);
    exit;
}
