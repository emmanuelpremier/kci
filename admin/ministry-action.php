<?php
/* admin/ministry-action.php — POST-only hide / show / delete for Ministries.
 * No visible page: validates action, id and CSRF (400 on failure), logs,
 * then redirects back to ministries.php with a flash message. */
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
$allowed = array('hide', 'show', 'delete');
$action = (isset($_POST['action']) && is_string($_POST['action'])) ? strtolower(trim($_POST['action'])) : '';
if (!in_array($action, $allowed, true)) { ministry_action_fail('Unknown action.'); }
$ministryId = 0;
if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^[1-9][0-9]{0,9}$/', trim($_POST['id']))) {
    $ministryId = (int)trim($_POST['id']);
}
if ($ministryId <= 0) { ministry_action_fail('That ministry could not be found.'); }
$row = null;
try {
    $find = $conn->prepare('SELECT id, name, slug, status FROM ministries WHERE id = ? LIMIT 1');
    if ($find === false) { ministry_action_fail('That ministry could not be found.'); }
    $find->bind_param('i', $ministryId);
    $find->execute();
    $res = $find->get_result();
    $row = ($res !== false) ? $res->fetch_assoc() : null;
    $find->close();
} catch (Throwable $ignored) { $row = null; }
if (!is_array($row) || !isset($row['name'])) { ministry_action_fail('That ministry could not be found.'); }
$name = (string)$row['name'];
$slug = isset($row['slug']) ? (string)$row['slug'] : '';
$adminId = (int)$admin['id'];
$done = false;

if ($action === 'delete') {
    try {
        $del = $conn->prepare('DELETE FROM ministries WHERE id = ? LIMIT 1');
        if ($del !== false) {
            $del->bind_param('i', $ministryId);
            $del->execute();
            $done = ($del->affected_rows > 0);
            $del->close();
        }
    } catch (Throwable $ignored) { $done = false; }
    if ($done) {
        admin_log_action($conn, $adminId, 'ministry_delete', 'ministry #' . $ministryId . ' (' . $name . ')');
        flash_set('success', 'Ministry deleted.');
    } else {
        flash_set('error', 'That ministry could not be deleted. Please try again.');
    }
} else {
    /* hide / show — idempotent: the target status is set either way. */
    $newStatus = ($action === 'hide') ? 'inactive' : 'active';
    try {
        $upd = $conn->prepare('UPDATE ministries SET status = ? WHERE id = ? LIMIT 1');
        if ($upd !== false) {
            $upd->bind_param('si', $newStatus, $ministryId);
            $upd->execute();
            $done = ($upd->affected_rows > 0);
            $upd->close();
            if (!$done) {
                /* Already in the requested state — still a success. */
                $done = true;
            }
        }
    } catch (Throwable $ignored) { $done = false; }
    if ($done) {
        admin_log_action($conn, $adminId, ($action === 'hide') ? 'ministry_hide' : 'ministry_show', 'ministry #' . $ministryId . ' (' . $name . ')');
        flash_set('success', ($action === 'hide')
            ? 'Ministry hidden — it no longer appears on the site.'
            : 'Ministry is now visible on the site.');
    } else {
        flash_set('error', 'That ministry could not be updated. Please try again.');
    }
}
header('Location: ' . ministry_action_target());
exit;

/* Redirect target: ministries.php (or index.php) with whitelisted filters. */
function ministry_action_target() {
    $raw = (isset($_POST['return']) && is_string($_POST['return'])) ? trim($_POST['return']) : '';
    $base = 'ministries.php';
    $query = array();
    if ($raw !== '') {
        $parts = explode('?', $raw, 2);
        $candidate = basename(str_replace('\\', '/', strtolower(trim($parts[0]))));
        if ($candidate === 'ministries.php' || $candidate === 'index.php') { $base = $candidate; }
        if ($base === 'ministries.php' && isset($parts[1])) {
            parse_str($parts[1], $parsed);
            foreach (array('tab', 'q', 'cat', 'page') as $keepKey) {
                if (isset($parsed[$keepKey]) && is_string($parsed[$keepKey]) && $parsed[$keepKey] !== '') {
                    $value = $parsed[$keepKey];
                    if ($keepKey === 'tab') {
                        $value = strtolower(trim($value));
                        if (!in_array($value, array('hidden', 'all'), true)) { continue; }
                    } elseif ($keepKey === 'page') {
                        $value = (string)max(1, (int)$value);
                        if ($value === '1') { continue; }
                    } elseif ($keepKey === 'cat') {
                        $value = substr(trim($value), 0, 40);
                        if ($value === '') { continue; }
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

/* Friendly 400 page (HTTP 400 on failure, no visible page otherwise). */
function ministry_action_fail($message) {
    http_response_code(400);
    $title = 'Something went wrong';
    $body = '<section class="kci-error"><div class="kci-error__card">'
        . '<span class="kci-error__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>'
        . '<h1>Something went wrong</h1>'
        . '<p>' . e($message) . ' Please go back and try again.</p>'
        . '<a class="kci-btn kci-btn--primary" href="ministries.php">Back to Ministries</a>'
        . '</div></section>';
    admin_error_page($title, $body);
    exit;
}