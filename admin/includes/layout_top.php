<?php
/* admin/includes/layout_top.php — shared sidebar + topbar opening.
 * Expects $admin (row), $pageTitle, $activeNav before include. */
if (!defined('KCI_ADMIN')) { http_response_code(403); exit; }

$__nav = isset($activeNav) ? (string)$activeNav : 'dashboard';
$__title = isset($pageTitle) ? (string)$pageTitle : 'Dashboard';
$__display = isset($admin['display_name']) ? (string)$admin['display_name'] : 'Admin';
$__initials = admin_initials($__display);
$__flash = flash_get();
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
<link rel="stylesheet" href="admin.css">
</head>
<body class="kci-admin">
<div class="kci-overlay" id="kci-overlay" hidden></div>
<aside class="kci-sidebar" id="kci-sidebar" aria-label="Admin navigation">
<div class="kci-brand">
<img src="../kci_image/im2.webp" alt="KCI logo" class="kci-brand__logo">
<span class="kci-brand__text">KCI Admin</span>
</div>
<nav class="kci-nav">
<a class="kci-nav__item<?php echo ($__nav === 'dashboard') ? ' is-active' : ''; ?>" href="index.php">
<span class="kci-nav__icon"><i class="fa-solid fa-gauge-high"></i></span>
<span>Dashboard</span>
</a>
<a class="kci-nav__item<?php echo ($__nav === 'giving') ? ' is-active' : ''; ?>" href="giving-settings.php">
<span class="kci-nav__icon"><i class="fa-solid fa-building-columns"></i></span>
<span>Giving Account</span>
</a>
<span class="kci-nav__item is-disabled" aria-disabled="true" tabindex="-1">
<span class="kci-nav__icon"><i class="fa-solid fa-calendar-days"></i></span>
<span>Events</span>
<span class="kci-pill">Soon</span>
</span>
<span class="kci-nav__item is-disabled" aria-disabled="true" tabindex="-1">
<span class="kci-nav__icon"><i class="fa-solid fa-people-group"></i></span>
<span>Ministries</span>
<span class="kci-pill">Soon</span>
</span>
<span class="kci-nav__item is-disabled" aria-disabled="true" tabindex="-1">
<span class="kci-nav__icon"><i class="fa-solid fa-hand-holding-heart"></i></span>
<span>Serve Applications</span>
<span class="kci-pill">Soon</span>
</span>
<span class="kci-nav__item is-disabled" aria-disabled="true" tabindex="-1">
<span class="kci-nav__icon"><i class="fa-solid fa-house"></i></span>
<span>Home Page</span>
<span class="kci-pill">Soon</span>
</span>
<span class="kci-nav__item is-disabled" aria-disabled="true" tabindex="-1">
<span class="kci-nav__icon"><i class="fa-solid fa-envelope"></i></span>
<span>Messages &amp; Forms</span>
<span class="kci-pill">Soon</span>
</span>
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
<?php unset($__nav, $__title, $__display, $__initials, $__flash); ?>
