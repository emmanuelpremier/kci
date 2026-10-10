<?php
/* admin/includes/layout_top.php — shared sidebar + topbar opening.
 * Expects $admin (row), $pageTitle, $activeNav before include. */
if (!defined('KCI_ADMIN')) { http_response_code(403); exit; }

$__nav = isset($activeNav) ? (string)$activeNav : '';
$__script = strtolower((string)basename((string)(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '')));
$__title = isset($pageTitle) ? (string)$pageTitle : 'Dashboard';
$__display = isset($admin['display_name']) ? (string)$admin['display_name'] : 'Admin';
$__initials = admin_initials($__display);
$__flash = flash_get();

/* Sidebar registry: every admin section with its label, icon and file.
 * An item is a real link when its file exists in admin/ (and the current
 * page is highlighted); otherwise it is faded with a "Soon" pill. Labels
 * wrap and are never truncated. */
$__navItems = array(
    array('key' => 'dashboard', 'label' => 'Dashboard',     'icon' => 'fa-gauge-high',         'file' => 'index.php'),
    array('key' => 'giving',    'label' => 'Giving Account', 'icon' => 'fa-building-columns',  'file' => 'giving-settings.php'),
    array('key' => 'events',    'label' => 'Events',        'icon' => 'fa-calendar-days',      'file' => 'events.php'),
    array('key' => 'ministries','label' => 'Ministries',    'icon' => 'fa-people-group',       'file' => 'ministries.php'),
    array('key' => 'home',      'label' => 'Home Page',     'icon' => 'fa-house',              'file' => 'home-page.php'),
    array('key' => 'serve',     'label' => 'Serve',         'icon' => 'fa-hand-holding-heart', 'file' => 'serve-applications.php'),
    array('key' => 'messages',  'label' => 'Messages',      'icon' => 'fa-envelope',           'file' => 'messages.php'),
);

/* Pending Serve applications for the Serve badge (0 on any failure). */
$__servePending = 0;
try {
    if (isset($conn) && $conn instanceof mysqli) {
        $__serveStmt = $conn->prepare('SELECT COUNT(*) AS c FROM serve_applications WHERE status = ?');
        if ($__serveStmt !== false) {
            $__serveWanted = 'pending';
            $__serveStmt->bind_param('s', $__serveWanted);
            $__serveStmt->execute();
            $__serveRes = $__serveStmt->get_result();
            $__serveRow = ($__serveRes !== false) ? $__serveRes->fetch_assoc() : null;
            if (is_array($__serveRow) && isset($__serveRow['c'])) {
                $__servePending = max(0, (int)$__serveRow['c']);
            }
            $__serveStmt->close();
        }
    }
} catch (Throwable $ignored) {
    $__servePending = 0;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo e($__title); ?> | KCI Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="admin.css?v=8">
</head>
<body class="kci-admin">
<div class="kci-overlay" id="kci-overlay" hidden></div>
<aside class="kci-sidebar" id="kci-sidebar" aria-label="Admin navigation">
<div class="kci-brand">
<img src="../kci_image/im2.webp" alt="KCI logo" class="kci-brand__logo">
<span class="kci-brand__text">KCI Admin</span>
</div>
<nav class="kci-nav">
<?php foreach ($__navItems as $__navItem): ?>
<?php
    $__navFile = (string)$__navItem['file'];
    $__navExists = file_exists(__DIR__ . '/../' . $__navFile);
    $__navKey = (string)$__navItem['key'];
    /* Highlight by matching the running script file (Dashboard only on index.php;
     * Serve also matches any future serve-* detail page). Falls back to $activeNav
     * only when SCRIPT_NAME is unavailable (e.g. CLI). */
    $__navActive = ($__script !== '' && $__script === strtolower($__navFile));
    if (!$__navActive && $__navKey === 'serve' && $__script !== '' && strpos($__script, 'serve-') === 0) {
        $__navActive = true;
    }
    if (!$__navActive && $__script === '' && $__nav !== '' && $__nav === $__navKey) {
        $__navActive = true;
    }
?>
<?php if ($__navExists): ?>
<a class="kci-nav__item<?php echo $__navActive ? ' is-active' : ''; ?>"<?php echo $__navActive ? ' aria-current="page"' : ''; ?> href="<?php echo e($__navFile); ?>">
<span class="kci-nav__icon"><i class="fa-solid <?php echo e((string)$__navItem['icon']); ?>"></i></span>
<span class="kci-nav__label"><?php echo e((string)$__navItem['label']); ?></span>
<?php if ((string)$__navItem['key'] === 'serve'): ?>
<span class="kci-nav__badge" title="<?php echo (int)$__servePending; ?> pending application<?php echo ((int)$__servePending === 1) ? '' : 's'; ?>"><?php echo (int)$__servePending; ?></span>
<?php endif; ?>
</a>
<?php else: ?>
<span class="kci-nav__item is-disabled" aria-disabled="true" tabindex="-1">
<span class="kci-nav__icon"><i class="fa-solid <?php echo e((string)$__navItem['icon']); ?>"></i></span>
<span class="kci-nav__label"><?php echo e((string)$__navItem['label']); ?></span>
<span class="kci-pill">Soon</span>
</span>
<?php endif; ?>
<?php endforeach; ?>
</nav>
<p class="kci-sidebar__foot">Kingdomite Church International</p>
</aside>
<div class="kci-main">
<header class="kci-topbar">
<button type="button" class="kci-hamburger" id="kci-hamburger" aria-label="Open navigation" aria-expanded="false" aria-controls="kci-sidebar">
<span></span><span></span><span></span>
</button>
<h1 class="kci-topbar__title"><?php echo e($__title); ?></h1>
<div class="kci-user">
<button type="button" class="kci-user__btn" id="kci-user-btn" aria-haspopup="true" aria-expanded="false">
<span class="kci-avatar"><?php echo e($__initials); ?></span>
<span class="kci-user__name"><?php echo e($__display); ?></span>
<i class="fa-solid fa-chevron-down kci-user__chev" aria-hidden="true"></i>
</button>
<div class="kci-user__menu" id="kci-user-menu" hidden>
<a href="password.php"><i class="fa-solid fa-key"></i> Change password</a>
<form method="post" action="logout.php">
<?php csrf_field(); ?>
<button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Log out</button>
</form>
</div>
</div>
</header>
<main class="kci-content">
<?php if (is_array($__flash) && isset($__flash['message'])): ?>
<p class="kci-flash kci-flash--<?php echo e(isset($__flash['type']) ? $__flash['type'] : 'info'); ?>" role="status"><?php echo e($__flash['message']); ?></p>
<?php endif; ?>
<?php unset($__nav, $__script, $__title, $__display, $__initials, $__flash, $__navItems, $__navItem, $__navFile, $__navKey, $__navExists, $__navActive, $__servePending, $__serveStmt, $__serveRes, $__serveRow, $__serveWanted); ?>
