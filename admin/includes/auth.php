<?php
/* admin/includes/auth.php — current_admin() + require_admin() with
 * idle (30 min) and absolute (8 h) timeouts and must_change_password. */
if (!defined('KCI_ADMIN')) { http_response_code(403); exit; }

define('KCI_IDLE_TIMEOUT', 30 * 60);
define('KCI_ABSOLUTE_TIMEOUT', 8 * 60 * 60);

/* Destroy the session (used on timeout). */
function admin_session_destroy() {
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        $path = isset($params['path']) ? (string)$params['path'] : '/';
        setcookie(session_name(), '', time() - 42000, $path, '', false, true);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function admin_redirect_login($expired = false) {
    header('Location: login.php' . ($expired ? '?expired=1' : ''));
    exit;
}

/* Load the logged-in admin from the DB on every request (is_active = 1). */
function current_admin($conn) {
    if (empty($_SESSION['admin_id']) || !is_numeric($_SESSION['admin_id'])) {
        return null;
    }
    try {
        if (!$conn instanceof mysqli) {
            return null;
        }
        $stmt = $conn->prepare('SELECT id, username, display_name, password_hash, role, must_change_password, is_active FROM admin_users WHERE id = ? AND is_active = 1 LIMIT 1');
        if ($stmt === false) {
            return null;
        }
        $id = (int)$_SESSION['admin_id'];
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
        $stmt->close();
        return $admin;
    } catch (Throwable $ignored) {
        return null;
    }
}

/* Guard for every protected page. */
function require_admin($conn) {
    if (empty($_SESSION['admin_id'])) {
        admin_redirect_login(false);
    }
    $now = time();
    $loginTime = isset($_SESSION['login_time']) ? (int)$_SESSION['login_time'] : 0;
    $lastActivity = isset($_SESSION['last_activity']) ? (int)$_SESSION['last_activity'] : 0;
    if ($loginTime <= 0 || $lastActivity <= 0) {
        admin_session_destroy();
        admin_redirect_login(true);
    }
    if (($now - $loginTime) > KCI_ABSOLUTE_TIMEOUT || ($now - $lastActivity) > KCI_IDLE_TIMEOUT) {
        admin_session_destroy();
        admin_redirect_login(true);
    }
    $_SESSION['last_activity'] = $now;

    $admin = current_admin($conn);
    if ($admin === null) {
        admin_session_destroy();
        admin_redirect_login(true);
    }

    /* Forced password change: everything except password.php/logout.php. */
    if (!empty($admin['must_change_password'])) {
        $script = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
        $base = strtolower(basename($script));
        if ($base !== 'password.php' && $base !== 'logout.php') {
            header('Location: password.php');
            exit;
        }
    }
    return $admin;
}
