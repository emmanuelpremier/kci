<?php
/* admin/login.php — sign-in with throttle, generic errors, dummy hash. */
define('KCI_ADMIN', true);
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../kci_db.php';

if (!empty($_SESSION['admin_id'])) {
    $existing = current_admin($conn);
    if ($existing !== null) {
        header('Location: index.php');
        exit;
    }
    admin_session_destroy();
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

$loginError = '';
$loginInfo = '';
if (isset($_GET['expired']) && $_GET['expired'] === '1') {
    $loginInfo = 'Your session has expired. Please sign in again.';
}
$justLoggedOut = (isset($_GET['out']) && $_GET['out'] === '1');

/* Dummy hash so timing never reveals whether a username exists. */
define('KCI_DUMMY_HASH', '$2y$10$usesomesillystringfore7hnbRJHxXVLeako7saae8uQztAkS2ow9eJedM');

/* Minutes until the oldest failure leaves the 15-minute window. */
function kci_login_wait($conn, $mode, $value) {
    try {
        if ($mode === 'ip') {
            $w = 'SELECT MIN(attempted_at) AS f FROM admin_login_attempts WHERE ip = ? AND success = 0 AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)';
        } else {
            $w = 'SELECT MIN(attempted_at) AS f FROM admin_login_attempts WHERE LOWER(username) = LOWER(?) AND success = 0 AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)';
        }
        $stmt = $conn->prepare($w);
        if ($stmt === false) {
            return 15;
        }
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$r || empty($r['f'])) {
            return 15;
        }
        $wait = (int)ceil((strtotime((string)$r['f']) + 15 * 60 - time()) / 60);
        if ($wait < 1) { $wait = 1; }
        if ($wait > 15) { $wait = 15; }
        return $wait;
    } catch (Throwable $ignored) {
        return 15;
    }
}

/* About 1% of requests delete attempts older than 30 days. */
function kci_login_gc() {
    global $conn;
    try {
        if (random_int(1, 100) === 1) {
            $conn->query('DELETE FROM admin_login_attempts WHERE attempted_at < (NOW() - INTERVAL 30 DAY)');
        }
    } catch (Throwable $ignored) {
    }
}

/* Sign-in handling (POST). */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        csrf_fail();
    }
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $ip = admin_client_ip();
    $blockedMinutes = 0;
    try {
        $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM admin_login_attempts WHERE ip = ? AND success = 0 AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)');
        if ($stmt !== false) {
            $stmt->bind_param('s', $ip);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row && (int)$row['c'] >= 5) {
                $blockedMinutes = kci_login_wait($conn, 'ip', $ip);
            }
        }
        if ($blockedMinutes <= 0 && $username !== '') {
            $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM admin_login_attempts WHERE LOWER(username) = LOWER(?) AND success = 0 AND attempted_at >= (NOW() - INTERVAL 15 MINUTE)');
            if ($stmt !== false) {
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row && (int)$row['c'] >= 5) {
                    $blockedMinutes = kci_login_wait($conn, 'username', $username);
                }
            }
        }
    } catch (Throwable $ignored) {
        $blockedMinutes = 0;
    }

    if ($blockedMinutes > 0) {
        $loginError = 'Too many attempts. Try again in ' . $blockedMinutes . ' minute' . ($blockedMinutes === 1 ? '' : 's') . '.';
    } else {
        $adminRow = null;
        try {
            if ($username !== '') {
                $stmt = $conn->prepare('SELECT id, username, display_name, password_hash, role, must_change_password, is_active FROM admin_users WHERE LOWER(username) = LOWER(?) LIMIT 1');
                if ($stmt !== false) {
                    $stmt->bind_param('s', $username);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $found = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
                    $stmt->close();
                    if ($found && (int)$found['is_active'] === 1) {
                        $adminRow = $found;
                    }
                }
            }
        } catch (Throwable $ignored) {
            $adminRow = null;
        }

        $hash = ($adminRow !== null) ? (string)$adminRow['password_hash'] : KCI_DUMMY_HASH;
        $ok = password_verify($password, $hash);

        if ($adminRow !== null && $ok) {
            /* Record success, clear old failures for this username. */
            try {
                $stmt = $conn->prepare('INSERT INTO admin_login_attempts (username, ip, success) VALUES (?, ?, 1)');
                if ($stmt !== false) {
                    $stmt->bind_param('ss', $username, $ip);
                    $stmt->execute();
                    $stmt->close();
                }
                $stmt = $conn->prepare('DELETE FROM admin_login_attempts WHERE LOWER(username) = LOWER(?) AND success = 0');
                if ($stmt !== false) {
                    $stmt->bind_param('s', $username);
                    $stmt->execute();
                    $stmt->close();
                }
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
                    if ($stmt !== false) {
                        $aid = (int)$adminRow['id'];
                        $stmt->bind_param('si', $newHash, $aid);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                $stmt = $conn->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?');
                if ($stmt !== false) {
                    $aid = (int)$adminRow['id'];
                    $stmt->bind_param('i', $aid);
                    $stmt->execute();
                    $stmt->close();
                }
            } catch (Throwable $ignored) {
                /* Login still succeeds; bookkeeping must never block it. */
            }

            kci_login_gc();
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$adminRow['id'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            admin_log_action($conn, (int)$adminRow['id'], 'login', 'Sign-in from ' . $ip);
            header('Location: ' . (!empty($adminRow['must_change_password']) ? 'password.php' : 'index.php'));
            exit;
        }

        /* Failure: record the attempt, always show the same generic message. */
        try {
            $stmt = $conn->prepare('INSERT INTO admin_login_attempts (username, ip, success) VALUES (?, ?, 0)');
            if ($stmt !== false) {
                $stmt->bind_param('ss', $username, $ip);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Throwable $ignored) {
        }
        kci_login_gc();
        $loginError = 'Incorrect username or password.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | KCI Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="admin.css">
</head>
<body class="kci-login">
<main class="kci-login__wrap">
<section class="kci-login__panel" aria-label="Welcome">
<img src="../kci_image/im2.webp" alt="KCI logo" class="kci-login__logo">
<h1>Kingdomite Church International</h1>
<p>Private admin area for the pastor. Manage the Giving account securely.</p>
<p class="kci-login__secure"><i class="fa-solid fa-lock"></i> Protected sign-in</p>
</section>
<section class="kci-login__cardwrap">
<form class="kci-login__card" method="post" action="login.php" autocomplete="off">
<h2><span class="kci-login__lock"><i class="fa-solid fa-lock"></i></span> Admin sign in</h2>
<p class="kci-login__sub">Enter your username and password to continue.</p>
<?php if ($justLoggedOut): ?>
<p class="kci-flash kci-flash--success" role="status">You have been signed out.</p>
<?php endif; ?>
<?php if ($loginInfo !== ''): ?>
<p class="kci-flash kci-flash--info" role="status"><?php echo e($loginInfo); ?></p>
<?php endif; ?>
<?php if ($loginError !== ''): ?>
<p class="kci-flash kci-flash--error" role="alert"><?php echo e($loginError); ?></p>
<?php endif; ?>
<?php csrf_field(); ?>
<label class="kci-field">
<span>Username</span>
<input type="text" name="username" autocomplete="username" required maxlength="100" value="<?php echo e(isset($_POST['username']) ? (string)$_POST['username'] : ''); ?>">
</label>
<label class="kci-field">
<span>Password</span>
<span class="kci-passwrap">
<input type="password" name="password" id="kci-password" autocomplete="current-password" required>
<button type="button" class="kci-showpass" id="kci-showpass" aria-label="Show password" aria-pressed="false"><i class="fa-solid fa-eye"></i></button>
</span>
</label>
<button type="submit" class="kci-btn kci-btn--primary kci-btn--block">Sign in <i class="fa-solid fa-arrow-right"></i></button>
</form>
</section>
</main>
<script src="admin.js"></script>
</body>
</html>
