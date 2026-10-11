<?php
/* admin/media-action.php — POST-only delete for Media pictures. */
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
if (!in_array($action, $allowed, true)) { media_action_fail('Unknown action.'); }

$mid = 0;
if (isset($_POST['id']) && is_string($_POST['id']) && preg_match('/^[1-9][0-9]{0,9}$/', trim($_POST['id']))) {
    $mid = (int)trim($_POST['id']);
}
if ($mid <= 0) { media_action_fail('That picture could not be found.'); }

$row = null;
try {
    $find = $conn->prepare('SELECT id, filename, size_bytes FROM media WHERE id = ? LIMIT 1');
    if ($find === false) { media_action_fail('That picture could not be found.'); }
    $find->bind_param('i', $mid);
    $find->execute();
    $res = $find->get_result();
    $row = ($res !== false) ? $res->fetch_assoc() : null;
    $find->close();
} catch (Throwable $ignored) { $row = null; }
if (!is_array($row) || !isset($row['filename'])) { media_action_fail('That picture could not be found.'); }

/* Refuse delete while media.php?id=N appears in any image column. */
$needle = 'media.php?id=' . $mid;
try {
    foreach (array('events', 'ministries') as $table) {
        $desc = $conn->query('SHOW COLUMNS FROM `' . $table . '`');
        if ($desc === false) { continue; }
        while ($crow = $desc->fetch_assoc()) {
            if (!isset($crow['Field']) || strtolower((string)$crow['Field']) !== 'image') { continue; }
            $stmt = $conn->prepare('SELECT id FROM `' . $table . '` WHERE `image` LIKE ? LIMIT 1');
            if ($stmt === false) { continue; }
            $like = '%' . $needle . '%';
            $stmt->bind_param('s', $like);
            $stmt->execute();
            $rres = $stmt->get_result();
            $used = ($rres !== false && $rres->fetch_assoc() !== null);
            $stmt->close();
            if ($used) {
                flash_set('error', 'This picture is still in use and cannot be deleted. Remove it from the event or ministry first.');
                header('Location: ' . media_action_target());
                exit;
            }
        }
    }
} catch (Throwable $ignored) { /* ignore probe errors */ }

$adminId = (int)$admin['id'];
$deleted = false;
try {
    $del = $conn->prepare('DELETE FROM media WHERE id = ? LIMIT 1');
    if ($del !== false) {
        $del->bind_param('i', $mid);
        $del->execute();
        $deleted = ($del->affected_rows > 0);
        $del->close();
    }
} catch (Throwable $ignored) { $deleted = false; }

if ($deleted) {
    admin_log_action($conn, $adminId, 'media_delete', 'deleted picture #' . $mid . ' (' . (string)$row['filename'] . ', ' . (int)$row['size_bytes'] . ' bytes)');
    flash_set('success', 'Picture deleted.');
} else {
    flash_set('error', 'That picture could not be deleted. Please try again.');
}
header('Location: ' . media_action_target());
exit;

function media_action_target() {
    $raw = (isset($_POST['return']) && is_string($_POST['return'])) ? trim($_POST['return']) : '';
    $base = 'media.php';
    $query = array();
    if ($raw !== '') {
        $parts = explode('?', $raw, 2);
        $candidate = basename(str_replace('\\', '/', strtolower(trim($parts[0]))));
        if ($candidate === 'media.php' || $candidate === 'index.php') { $base = $candidate; }
        if ($base === 'media.php' && isset($parts[1])) {
            parse_str($parts[1], $parsed);
            if (isset($parsed['page']) && is_string($parsed['page'])) {
                $p = max(1, (int)$parsed['page']);
                if ($p > 1) { $query['page'] = (string)$p; }
            }
        }
    }
    $qs = http_build_query($query);
    return $base . ($qs !== '' ? '?' . $qs : '');
}

function media_action_fail($message) {
    http_response_code(400);
    $title = 'Something went wrong';
    $body = '<section class="kci-error"><div class="kci-error__card">'
        . '<span class="kci-error__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>'
        . '<h1>Something went wrong</h1>'
        . '<p>' . e($message) . ' Please go back and try again.</p>'
        . '<a class="kci-btn kci-btn--primary" href="media.php">Back to Media</a>'
        . '</div></section>';
    admin_error_page($title, $body);
    exit;
}
