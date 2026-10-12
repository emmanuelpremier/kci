<?php
/* admin/event-action.php — POST-only delete for Events. */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../kci_db.php';
$admin = require_admin($conn);
if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}
if (!csrf_check()) { csrf_fail(); }
$allowed = array('delete');
$action = (isset($_POST['action']) && is_string($_POST['action'])) ? strtolower(trim($_POST['action'])) : '';
if (!in_array($action, $allowed, true)) { event_action_fail('Unknown action.'); }
$eventId = 0;
if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^[1-9][0-9]{0,9}$/', trim($_POST['id']))) {
    $eventId = (int)trim($_POST['id']);
}
if ($eventId <= 0) { event_action_fail('That event could not be found.'); }
$row = null;
try {
    $find = $conn->prepare('SELECT id, title FROM events WHERE id = ? LIMIT 1');
    if ($find === false) { event_action_fail('That event could not be found.'); }
    $find->bind_param('i', $eventId);
    $find->execute();
    $res = $find->get_result();
    $row = ($res !== false) ? $res->fetch_assoc() : null;
    $find->close();
} catch (Throwable $ignored) { $row = null; }
if (!is_array($row) || !isset($row['title'])) { event_action_fail('That event could not be found.'); }
$title = (string)$row['title'];
$deleted = false;
try {
    $del = $conn->prepare('DELETE FROM events WHERE id = ? LIMIT 1');
    if ($del !== false) {
        $del->bind_param('i', $eventId);
        $del->execute();
        $deleted = ($del->affected_rows > 0);
        $del->close();
    }
} catch (Throwable $ignored) { $deleted = false; }
$adminId = (int)$admin['id'];
if ($deleted) {
    admin_log_action($conn, $adminId, 'event_delete', 'deleted event #' . $eventId . ' (' . $title . ')');
    flash_set('success', 'Event deleted.');
} else {
    flash_set('error', 'That event could not be deleted. Please try again.');
}
header('Location: ' . event_action_target());
exit;
function event_action_target() {
    $raw = (isset($_POST['return']) && is_string($_POST['return'])) ? trim($_POST['return']) : '';
    $base = 'events.php';
    $query = array();
    if ($raw !== '') {
        $parts = explode('?', $raw, 2);
        $candidate = basename(str_replace('\\', '/', strtolower(trim($parts[0]))));
        if ($candidate === 'events.php' || $candidate === 'index.php') { $base = $candidate; }
        if ($base === 'events.php' && isset($parts[1])) {
            parse_str($parts[1], $parsed);
            foreach (array('tab', 'q', 'page') as $keepKey) {
                if (isset($parsed[$keepKey]) && is_string($parsed[$keepKey]) && $parsed[$keepKey] !== '') {
                    $value = $parsed[$keepKey];
                    if ($keepKey === 'tab') {
                        $value = strtolower(trim($value));
                        if ($value === 'upcoming' || $value === '') { continue; }
                        if (!in_array($value, array('past', 'all'), true)) { continue; }
                    } elseif ($keepKey === 'page') {
                        $value = (string)max(1, (int)$value);
                        if ($value === '1') { continue; }
                    } else {
                        $value = substr(trim($value), 0, 100);
                        if ($value === '') { continue; }
                    }
                    $query[$keepKey] = $value;
                }
            }
        }
    }
    $qs = http_build_query($query);
    return $base . ($qs !== '' ? '?' . $qs : '');
}
function event_action_fail($message) {
    http_response_code(400);
    $title = 'Something went wrong';
    $body = '<section class="kci-error"><div class="kci-error__card">'
        . '<span class="kci-error__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>'
        . '<h1>Something went wrong</h1>'
        . '<p>' . e($message) . ' Please go back and try again.</p>'
        . '<a class="kci-btn kci-btn--primary" href="events.php">Back to Events</a>'
        . '</div></section>';
    admin_error_page($title, $body);
    exit;
}
