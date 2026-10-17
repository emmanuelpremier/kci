<?php
/* media.php — public database image endpoint (no login).
 * Serves rows from `media` with long-term caching. No output but image. */
@ini_set('display_errors', '0');
error_reporting(0);

$id = 0;
if (isset($_GET['id']) && is_string($_GET['id']) && preg_match('/^[1-9][0-9]{0,9}$/', trim($_GET['id']))) {
    $id = (int)trim($_GET['id']);
}
if ($id <= 0) {
    http_response_code(404);
    exit;
}
require __DIR__ . '/kci_db.php';
$row = null;
try {
    $stmt = $conn->prepare('SELECT mime, data, created_at FROM media WHERE id = ? LIMIT 1');
    if ($stmt === false) { http_response_code(404); exit; }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = ($res !== false) ? $res->fetch_assoc() : null;
    $stmt->close();
} catch (Throwable $ignored) { $row = null; }
if (!is_array($row) || !isset($row['data'], $row['mime'])) {
    http_response_code(404);
    exit;
}
$mime = strtolower(trim((string)$row['mime']));
$allowed = array('image/webp', 'image/jpeg', 'image/png');
if (!in_array($mime, $allowed, true)) {
    http_response_code(404);
    exit;
}
$data = (string)$row['data'];
$len = strlen($data);
if ($len <= 0) {
    http_response_code(404);
    exit;
}
$etag = '"kci-media-' . $id . '-' . $len . '-' . sha1($data) . '"';
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . $len);
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=31536000, immutable');
$created = isset($row['created_at']) ? strtotime((string)$row['created_at']) : false;
if ($created !== false && $created > 0) {
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $created) . ' GMT');
}
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string)$_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
    http_response_code(304);
    exit;
}
if ($created !== false && $created > 0 && isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $ims = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($ims !== false && $ims >= $created) {
        http_response_code(304);
        exit;
    }
}
echo $data;
exit;
