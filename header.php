<?php
/**
 * header.php — Shared site header for Kingdomite Church International.
 *
 * Included by every public page (index, aboutus, events, event, min,
 * ministry, serve, contact, form, location, quotes, giving) via:
 *   <?php include 'header.php'; ?>
 *
 * Contains the logo link, <nav class="kc3" id="mainNav"> with Home, About,
 * Events (dropdown), Ministries (dropdown), Contact, the Give button and
 * the menu toggle button. Class names, ids and attributes are kept
 * identical so kci.css, mobile-header.css and the page scripts keep working.
 *
 * The Events and Ministries dropdowns follow the database (at most two
 * queries in total) with hard-coded fallbacks when a query fails or
 * returns no rows.
 */

$navEventLimit = 6;
$navMinistryLimit = 10;

include_once __DIR__ . '/site_config.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
	include_once __DIR__ . '/kci_db.php';
}

/* Fallbacks — the previous hard-coded lists. */
$navEventFallback = [
	['title' => 'Oil & Wine Summit', 'slug' => 'oil-wine-summit'],
	['title' => 'June Conference', 'slug' => 'june-conference'],
	['title' => 'Embers Of Glory', 'slug' => 'embers-of-glory'],
];
$navMinistryFallback = [
	['name' => 'Missions', 'slug' => 'global-missions'],
	['name' => "Women's Ministry", 'slug' => 'womens-ministry'],
	['name' => 'YC-The Youth Church', 'slug' => 'youth-church'],
	['name' => 'MTC-Mighty Teens Church', 'slug' => 'mighty-teens'],
	['name' => 'Children Church', 'slug' => 'children-church'],
];

$navEvents = [];
$navMinistries = [];

/* 1) Events: upcoming first (date on or after today, soonest first), then
   the most recent past events to fill the limit. Single query; the split
   and ordering happen in PHP so only one round-trip is needed. */
try {
	$navEventStmt = $conn->prepare("SELECT title, slug, date FROM events ORDER BY date ASC");
	if ($navEventStmt) {
		$navEventStmt->execute();
		$navEventResult = $navEventStmt->get_result();
		$navAllEvents = [];
		if ($navEventResult) {
			while ($navRow = $navEventResult->fetch_assoc()) {
				$navAllEvents[] = $navRow;
			}
		}
		$navEventStmt->close();
		$navToday = date('Y-m-d');
		$navUpcoming = [];
		$navPast = [];
		foreach ($navAllEvents as $navRow) {
			$navDay = date('Y-m-d', strtotime($navRow['date']));
			if ($navDay >= $navToday) {
				$navUpcoming[] = $navRow;
			} else {
				$navPast[] = $navRow;
			}
		}
		usort($navUpcoming, function ($a, $b) { return strtotime($a['date']) - strtotime($b['date']); });
		usort($navPast, function ($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });
		$navLimit = max(1, (int)$navEventLimit);
		$navEvents = array_slice(array_merge($navUpcoming, $navPast), 0, $navLimit);
	}
} catch (Throwable $navE) {
	$navEvents = [];
}

/* 2) Ministries: active only, ordered by id, limited. */
try {
	$navMinLimit = max(1, (int)$navMinistryLimit);
	$navMinStmt = $conn->prepare("SELECT name, slug FROM ministries WHERE status = 'active' ORDER BY id ASC LIMIT " . $navMinLimit);
	if ($navMinStmt) {
		$navMinStmt->execute();
		$navMinResult = $navMinStmt->get_result();
		if ($navMinResult) {
			while ($navMinRow = $navMinResult->fetch_assoc()) {
				$navMinistries[] = $navMinRow;
			}
		}
		$navMinStmt->close();
	}
} catch (Throwable $navE) {
	$navMinistries = [];
}

if (empty($navEvents)) {
	$navEvents = $navEventFallback;
}
if (empty($navMinistries)) {
	$navMinistries = $navMinistryFallback;
}
?>
	<section class="kc1">
		<a href="index.php" class="kc2" aria-label="KCI home">
			<img src="kci_image/im2.webp">
			<span class="kc-logo-text">KCI</span>
		</a>
		<nav class="kc3" id="mainNav">
			<ul>
				<li><a href="index.php">Home</a>
					<div class="line"></div>
				</li>
				<li><a href="aboutus.php">About</a>
					<div class="linea"></div>
				</li>
				<li><a href="events.php">Events<i class="fa-solid fa-chevron-down arrow" aria-hidden="true"></i></a>
					<div class="linec"></div>
					<ul class="dropdown">
<?php foreach ($navEvents as $navEv): ?>
						<li><a href="event.php?slug=<?= urlencode($navEv['slug']) ?>"><?= htmlspecialchars($navEv['title']) ?></a></li>
<?php endforeach; ?>
					</ul>
				</li>
				<li class="linef"><a href="min.php">Ministries<i class="fa-solid fa-chevron-down arrow" aria-hidden="true"></i></a>
					<div class="lined"></div>
					<ul class="dropdown">
<?php foreach ($navMinistries as $navMin): ?>
						<li><a href="ministry.php?slug=<?= urlencode($navMin['slug']) ?>"><?= htmlspecialchars($navMin['name']) ?></a></li>
<?php endforeach; ?>
					</ul>
				</li>
				<li><a href="contact.php">Contact</a>
					<div class="linee"></div>
				</li>
			</ul>
		</nav>
		<div class="kc4">
			<a href="giving.php"><i class="fa-solid fa-hand-holding-heart kc-give-icon"></i>Give</a>
		</div>
		<button class="kc-menu-toggle" id="menuToggle" aria-label="Toggle menu">
			<span></span>
			<span></span>
			<span></span>
		</button>
	</section>
