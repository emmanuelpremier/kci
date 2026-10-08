<?php
/* admin/logout.php — POST + CSRF only. Destroys the session and expires
 * the cookie, then redirects to login.php. Logs the action. */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../kci_db.php';

if (!csrf_check()) {
    csrf_fail();
}

$admin = current_admin($conn);
if ($admin !== null) {
    admin_log_action($conn, (int)$admin['id'], 'logout', 'Signed out from ' . admin_client_ip());
}
admin_session_destroy();
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$kci_path = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
setcookie(session_name(), '', time() - 42000, $kci_path . '/');
header('Location: login.php?out=1');
exit;
