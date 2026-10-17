<?php
include_once "site_config.php";
include("kci_db.php");

// Get the slug from the URL
$slug = $_GET['slug'] ?? '';

// Fetch all events for dropdown and listing
$allEvents = [];
$sql = "SELECT id, title, slug, image, date, location, description FROM events ORDER BY date DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $allEvents[] = $row;
    }
}

// Initialize event as null
$event = null;

// If slug is provided, fetch the event from the database using prepared statement
if (!empty($slug)) {
    $stmt = $conn->prepare("SELECT id, title, slug, image, date, location, description, content FROM events WHERE slug = ?");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $event = $result->fetch_assoc();
    }
    $stmt->close();
}

// If no event found with slug, return 404
if (!empty($slug) && !$event) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Kingdomite Church Int'l">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="kci.css?v=26">
<link rel="stylesheet" type="text/css" href="mobilefix.css?v=3">
<link rel="stylesheet" type="text/css" href="cleanup.css">
<link rel="icon" type="image/png" href="kci_image">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<title><?= $event ? htmlspecialchars($event['title']) : 'Events' ?> | Kingdomite Church International</title>
</head>
<body class="events-page"<?php if ($event): ?>
	data-reveal-ready="event-detail-anim-ready"
	data-reveal-targets=".event-detail-content .event-detail-main .event-detail-label, .event-detail-content .event-detail-main h2, .event-detail-content .event-detail-main > p, .event-detail-content .event-detail-main h3, .event-detail-content .event-detail-main h3 + p, .event-detail-content .event-detail-sidebar .event-detail-card, .event-detail-cta .event-detail-cta-content h2, .event-detail-cta .event-detail-cta-content p, .event-detail-cta .event-detail-cta-button, .kc118 .kc119"
	data-reveal-margin="0px 0px -8% 0px"<?php endif; ?>>
	<!-- Shared reveal engine (kci-reveal.js): primes the scroll-reveal
	     initial state before the first paint, then drives the single
	     IntersectionObserver. The data-reveal-* attributes above are only
	     rendered for the single-event view, so the listing view is never
	     put into its hidden starting state. -->
	<script src="kci-reveal.js" defer></script>

	<!--header-->
	<?php include 'header.php'; ?>

	<!-- HERO & LISTING (only show when no specific event slug requested) -->
<?php if (empty($slug) || (!$event && !empty($slug))): ?>
	<!-- HERO SECTION -->
	<section class="event-hero">
		<div class="event-hero-content">
			<span class="event-hero-label">KINGDOMITE CHURCH INTERNATIONAL</span>
			<h1>Our Events</h1>
			<p>Experience worship. Build connections. Grow in faith.</p>
			<a href="#all-events" class="primary-btn">View All Events <span>→</span></a>
		</div>
	</section>

	<!-- ALL EVENTS GRID -->
	<section class="event-listing" id="all-events">
		<div class="event-listing-header">
			<span class="event-listing-tag">GATHER • WORSHIP • GROW</span>
			<h2>Upcoming Events</h2>
			<p>There is always something happening at Kingdomite. Join us as we gather together in worship, fellowship, prayer and the Word of God.</p>
		</div>
		<div class="event-listing-grid">
			<?php if (count($allEvents) > 0): ?>
				<?php foreach ($allEvents as $ev): ?>
					<article class="event-card">
						<div class="event-card-image">
							<img src="<?= htmlspecialchars($ev['image']) ?>" alt="<?= htmlspecialchars($ev['title']) ?>" width="800" height="600" loading="lazy" decoding="async">
			
							
							<span class="event-card-location"><i class="fa-solid fa-location-crosshairs"></i> <?= htmlspecialchars($ev['location']) ?></span>
						</div>
						<div class="event-card-body">
							<h3><?= htmlspecialchars($ev['title']) ?></h3>
							<div class="event-card-meta">
								<div>
									<span class="event-meta-icon"><i class="fa-regular fa-calendar-days"></i></span>
									<span><?= htmlspecialchars(date('F j, Y', strtotime($ev['date']))) ?></span>
								</div>
							</div>
							<p><?= htmlspecialchars($ev['description']) ?></p>
							<a href="event.php?slug=<?= urlencode($ev['slug']) ?>" class="event-card-btn">View Event <span>→</span></a>
						</div>
					</article>
				<?php endforeach; ?>
			<?php else: ?>
				<div class="event-no-events">
					<h3>No Events Available</h3>
					<p>Check back soon for upcoming events.</p>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<!-- SINGLE EVENT DETAIL (shown only when a specific slug is requested) -->
	<?php if ($event): ?>
	<section class="event-detail-hero">
		<div class="event-detail-hero-inner">
			<div class="event-detail-hero-text">
				<span class="event-detail-hero-tag">EVENT</span>
				<h1><?= htmlspecialchars($event['title']) ?></h1>
				<?php $isPastEvent = (date('Y-m-d', strtotime($event['date'])) < date('Y-m-d')); ?>
				<?php if ($isPastEvent): ?>
					<p><span class="past-event-badge">Past event</span></p>
				<?php endif; ?>
				<p class="event-detail-hero-meta">
					<span><i class="fa-regular fa-calendar-days"></i> <?= htmlspecialchars(date('F j, Y', strtotime($event['date']))) ?></span>
					<span><i class="fa-solid fa-location-crosshairs"></i> <?= htmlspecialchars($event['location']) ?></span>
				</p>
				<?php if ($isPastEvent): ?>
					<p class="cleanup-ended-note">This event has ended</p>
					<p><a href="events.php" class="event-detail-register-btn">See our events <span>→</span></a></p>
				<?php else: ?>
					<a href="contact.php" class="event-detail-register-btn">Register Now <span>→</span></a>
				<?php endif; ?>
			</div>
			<div class="event-detail-hero-image">
				<img src="<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['title']) ?>" width="1200" height="800" loading="eager" decoding="async" fetchpriority="high">
			</div>
		</div>
	</section>

	<section class="event-detail-content">
		<div class="event-detail-container">
			<div class="event-detail-main">
				<span class="event-detail-label">About This Event</span>
				<h2><?= htmlspecialchars($event['title']) ?></h2>
				<p><?= htmlspecialchars($event['description']) ?></p>
				<?php if (!empty($event['content'])): ?>
					<h3>Additional Details</h3>
					<p><?= nl2br(htmlspecialchars($event['content'])) ?></p>
				<?php endif; ?>
			</div>
			<aside class="event-detail-sidebar">
				<div class="event-detail-card">
					<h3>Event Details</h3>
					<div class="event-detail-item">
						<span class="event-detail-item-label"><i class="fa-regular fa-calendar-days"></i>Date</span>
						<span class="event-detail-item-value"><?= htmlspecialchars(date('F j, Y', strtotime($event['date']))) ?></span>
					</div>
					<div class="event-detail-item">
						<span class="event-detail-item-label"><i class="fa-solid fa-location-crosshairs"></i>Location</span>
						<span class="event-detail-item-value"><?= htmlspecialchars($event['location']) ?></span>
					</div>
				</div>
			</aside>
		</div>
	</section>
	<section class="event-detail-cta">
		<?php if ($isPastEvent): ?>
		<div class="event-detail-cta-content"><h2>Stay connected for our next gathering.</h2><p>We look forward to experiencing this special time together.</p><a href="contact.php" class="event-detail-cta-button">Contact Us</a></div>
		<?php else: ?>
		<div class="event-detail-cta-content"><h2>Join Us</h2><p>We look forward to experiencing this special time together.</p><a href="contact.php" class="event-detail-cta-button">Contact Us</a></div>
		<?php endif; ?>
	</section>
	<?php endif; ?>

	<?php include 'footer.php'; ?>

	<script type="text/javascript">
		// Mobile menu toggle
		var menuToggle = document.getElementById('menuToggle');
		var mainNav = document.getElementById('mainNav');
		menuToggle.addEventListener('click', function() {
			mainNav.classList.toggle('open');
			menuToggle.classList.toggle('active');
		});

		// Mobile dropdown toggle
		var dropdownParents = document.querySelectorAll('#mainNav > ul > li.linef, #mainNav > ul > li:nth-child(3)');
		dropdownParents.forEach(function(parent) {
			var link = parent.querySelector('a');
			link.addEventListener('click', function(e) {
				if (window.innerWidth <= 900 && e.target.closest('.arrow')) {
					e.preventDefault();
					parent.classList.toggle('dropdown-open');
				}
			});
		});

		// Header scroll behavior - glassmorphism on scroll
		window.addEventListener('scroll', function() {
			var header = document.querySelector('.kc1');
			if (window.scrollY > 50) {
				header.classList.add('scrolled');
			} else {
				header.classList.remove('scrolled');
			}
		});
	</script>
	<!-- The scroll-reveal that used to live here now comes from the shared
	     kci-reveal.js engine included right after <body> (see the
	     data-reveal-* attributes on <body> for its configuration). It is
	     only configured when a valid \ is rendered. -->
</body>
</html>
