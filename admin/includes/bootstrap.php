<?php
/* admin/includes/bootstrap.php — session hardening + security headers.
 * Loaded first by every admin page. Never accessed directly. */
if (!defined('KCI_ADMIN')) { http_response_code(403); exit; }

include_once '../site_config.php';
if (function_exists('date_default_timezone_set')) {
    @date_default_timezone_set('Africa/Lagos');
}

/* --- Session cookie hardening (before session_start) --- */
$__kci_script = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '/admin/index.php';
$__kci_admin_path = '/admin';
if (strpos($__kci_script, '/admin/') !== false) {
    $__kci_admin_path = substr($__kci_script, 0, strpos($__kci_script, '/admin/') + strlen('/admin'));
} elseif (strpos($__kci_script, '/admin') !== false) {
    $__kci_admin_path = substr($__kci_script, 0, strpos($__kci_script, '/admin') + strlen('/admin'));
} else {
    $__kci_dir = str_replace('\\', '/', dirname($__kci_script));
    $__kci_admin_path = ($__kci_dir === '' || $__kci_dir === '.') ? '/' : $__kci_dir;
    unset($__kci_dir);
}
if ($__kci_admin_path === '') { $__kci_admin_path = '/'; }

$__kci_is_https = false;
if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
    $__kci_is_https = true;
}
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $__kci_proto_parts = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_PROTO']);
    if (strtolower(trim($__kci_proto_parts[0])) === 'https') {
        $__kci_is_https = true;
    }
    unset($__kci_proto_parts);
}

session_name('kci_admin');
@ini_set('session.use_strict_mode', '1');
@ini_set('session.use_only_cookies', '1');
@ini_set('session.use_trans_sid', '0');
@ini_set('session.cookie_httponly', '1');
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path'     => $__kci_admin_path,
        'secure'   => $__kci_is_https,
        'httponly' => true,
        'samesite' => 'Strict',
    ));
} else {
    session_set_cookie_params(0, $__kci_admin_path . '; samesite=Strict', '', $__kci_is_https, true);
}
unset($__kci_script, $__kci_admin_path, $__kci_is_https);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* --- Security headers on every admin response --- */
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; script-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
