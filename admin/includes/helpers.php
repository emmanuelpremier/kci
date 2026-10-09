<?php
/* admin/includes/helpers.php — escaping, flash, logging, IP + misc helpers. */
if (!defined('KCI_ADMIN')) { http_response_code(403); exit; }

/* Escape output for HTML (always ENT_QUOTES + UTF-8). */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* --- Flash messages (post-redirect-get) --- */
function flash_set($type, $message) {
    $_SESSION['kci_flash'] = array('type' => (string)$type, 'message' => (string)$message);
}

function flash_get() {
    if (empty($_SESSION['kci_flash']) || !is_array($_SESSION['kci_flash'])) {
        return null;
    }
    $flash = $_SESSION['kci_flash'];
    unset($_SESSION['kci_flash']);
    return $flash;
}

/* --- Client IP: first valid X-Forwarded-For address, else REMOTE_ADDR --- */
function admin_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($parts as $part) {
            $candidate = trim($part);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }
    $remote = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
    if ($remote !== '' && filter_var($remote, FILTER_VALIDATE_IP)) {
        return $remote;
    }
    return 'unknown';
}

/* Write one row to admin_log (prepared statement, never throws). */
function admin_log_action($conn, $adminId, $action, $details) {
    try {
        if (!$conn instanceof mysqli) {
            return;
        }
        $ip = admin_client_ip();
        $a = substr((string)$action, 0, 100);
        $d = $details === null ? null : substr((string)$details, 0, 2000);
        $stmt = $conn->prepare('INSERT INTO admin_log (admin_id, action, details, ip) VALUES (?, ?, ?, ?)');
        if ($stmt === false) {
            return;
        }
        $adminParam = $adminId === null ? null : (int)$adminId;
        $stmt->bind_param('isss', $adminParam, $a, $d, $ip);
        $stmt->execute();
        $stmt->close();
    } catch (Throwable $ignored) {
        /* Logging must never break the page. */
    }
}

/* Friendly standalone error page (login/logout CSRF failure, etc). */
function admin_error_page($title, $bodyHtml) {
    $safeTitle = e($title);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . $safeTitle . ' | KCI Admin</title>'
        . '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">'
        . '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">'
        . '<link rel="stylesheet" href="admin.css">'
        . '</head><body class="kci-login">' . $bodyHtml
        . '<script src="admin.js"></script>'
        . '</body></html>';
}

/* Relative time ("3 minutes ago") for the activity list. */
function admin_relative_time($datetime) {
    try {
        $then = new DateTimeImmutable((string)$datetime, new DateTimeZone('Africa/Lagos'));
    } catch (Throwable $ignored) {
        return '';
    }
    $now = new DateTimeImmutable('now', new DateTimeZone('Africa/Lagos'));
    $diff = $now->getTimestamp() - $then->getTimestamp();
    if ($diff < 0) { $diff = 0; }
    if ($diff < 60) { return 'just now'; }
    $minutes = (int)floor($diff / 60);
    if ($minutes < 60) { return $minutes . ($minutes === 1 ? ' minute ago' : ' minutes ago'); }
    $hours = (int)floor($minutes / 60);
    if ($hours < 24) { return $hours . ($hours === 1 ? ' hour ago' : ' hours ago'); }
    $days = (int)floor($hours / 24);
    if ($days < 30) { return $days . ($days === 1 ? ' day ago' : ' days ago'); }
    return $then->format('d M Y');
}

/* Initials for the avatar circle (first letters of first + last word). */
function admin_initials($displayName) {
    $words = preg_split('/\s+/', trim((string)$displayName));
    $words = array_values(array_filter($words, function ($w) { return $w !== ''; }));
    if (count($words) === 0) { return 'K'; }
    if (count($words) === 1) {
        return strtoupper(substr($words[0], 0, 1));
    }
    return strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
}

/* Group an account number like 9013 194 092 (display only). */
function admin_group_number($number) {
    $digits = (string)$number;
    if (preg_match('/^(\d{4})(\d{3})(\d{3})$/', $digits, $m)) {
        return $m[1] . ' ' . $m[2] . ' ' . $m[3];
    }
    return $digits;
}

/* Last 4 digits for log lines (never log the full account number). */
function admin_last4($number) {
    $digits = preg_replace('/\D/', '', (string)$number);
    if (strlen($digits) < 4) { return '****'; }
    return '****' . substr($digits, -4);
}

/* Validate a candidate password. Returns an empty string when acceptable,
 * otherwise a clear error message for direct display. Reused by
 * admin/password.php; kept here so any future admin form can share it. */
function admin_password_error($new, $username, $displayName) {
    $new = (string)$new;
    if ($new === '') {
        return 'Please choose a new password.';
    }
    if (strlen($new) < 12) {
        return 'Choose a new password of at least 12 characters.';
    }
    if (preg_match('/^[0-9]+$/', $new)) {
        return 'Your new password cannot be made up of numbers only.';
    }
    if (preg_match('/0123|1234|2345|3456|4567|5678|6789|0000|1111|2222|3333|4444|5555|6666|7777|8888|9999/', $new)) {
        return 'Your new password cannot contain a simple number run such as 1234 or 0000.';
    }
    $lower = strtolower($new);
    foreach (array(trim((string)$username), trim((string)$displayName)) as $name) {
        $name = strtolower($name);
        if ($name !== '' && strpos($lower, $name) !== false) {
            return 'Your new password cannot contain your username or display name.';
        }
    }
    $common = array('password', 'welcome', 'qwerty', 'church', 'kingdomite', 'emmanuel',
        'admin', 'letmein', 'login', 'passw0rd', 'iloveyou', 'monkey', 'dragon',
        'football', 'baseball', 'sunshine', 'princess', 'trustno1', 'abc123',
        '123456', '1234567', '12345678', '123456789', '1234567890', 'qwerty123',
        'qwertyuiop', 'changeme', 'secret', 'master', 'superman');
    foreach ($common as $bad) {
        if (strpos($lower, $bad) !== false) {
            return 'Your new password is too common. Please choose something less predictable.';
        }
    }
    if (!preg_match('/[A-Za-z]/', $new) || !preg_match('/[^A-Za-z]/', $new)) {
        return 'Your new password must mix letters with at least one number or symbol.';
    }
    return '';
}
