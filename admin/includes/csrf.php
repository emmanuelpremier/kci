<?php
/* admin/includes/csrf.php — per-session CSRF token helpers. */
if (!defined('KCI_ADMIN')) { http_response_code(403); exit; }

/* Return the session token, creating it once with random_bytes(32). */
function csrf_token() {
    if (empty($_SESSION['kci_csrf']) || !is_string($_SESSION['kci_csrf'])) {
        $_SESSION['kci_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['kci_csrf'];
}

/* Print the hidden input used by every admin POST form. */
function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/* Validate the posted token with hash_equals. */
function csrf_check() {
    $posted = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    $stored = isset($_SESSION['kci_csrf']) ? (string)$_SESSION['kci_csrf'] : '';
    if ($stored === '' || $posted === '') {
        return false;
    }
    return hash_equals($stored, $posted);
}

/* Friendly 400 page for a missing/invalid token (no inline script). */
function csrf_fail() {
    http_response_code(400);
    $title = 'Something went wrong';
    $body = '<section class="kci-error"><div class="kci-error__card">'
        . '<span class="kci-error__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>'
        . '<h1>Something went wrong</h1>'
        . '<p>Your session has expired or the form was invalid. Please go back and try again.</p>'
        . '<a class="kci-btn kci-btn--primary" href="login.php">Back to sign in</a>'
        . '</div></section>';
    admin_error_page($title, $body);
    exit;
}
