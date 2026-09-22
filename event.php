<?php
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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="kci.css">
<link rel="icon" type="image/png" href="kci_image">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<title><?= $event ? htmlspecialchars($event['title']) : 'Events' ?> | Kingdomite Church International</title>
</head>
<body class="events-page">
	<!--header-->
	<section class="kc1">
		<div class="kc2">
			<img src="kci_image/im2.jpg">
			<span class="kc-logo-text">KCI</span>
		</div>
		<nav class="kc3" id="mainNav">
			<ul>
				<li><a href="index.php">Home</a>
					<div class="line"></div>
				</li>
				<li><a href="aboutus.php">About</a>
					<div class="linea"></div>
				</li>
				<li><a href="events.php">Events<span class="arrow">&#709;</span></a>
					<div class="linec"></div>
					<ul class="dropdown">
						<?php foreach ($allEvents as $ev): ?>
							<li><a href="event.php?slug=<?= urlencode($ev['slug']) ?>"><?= htmlspecialchars($ev['title']) ?></a></li>
						<?php endforeach; ?>
					</ul>
				</li>
				<li class="linef"><a href="min.php">Ministries<span class="arrow">&#709;</span></a>
					<div class="lined"></div>
					<ul class="dropdown">
						<li><a href="miss.php">Missions</a></li>
						<li><a href="wom.php">Women's Ministry</a></li>
						<li><a href="yot.php">YC-The Youth Church</a></li>
						<li><a href="mte.php">MTC-Mighty Teens Church</a></li>
						<li><a href="chi.php">Children Church</a></li>
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
							<img src="<?= htmlspecialchars($ev['image']) ?>" alt="<?= htmlspecialchars($ev['title']) ?>">
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
				<p class="event-detail-hero-meta">
					<span><i class="fa-regular fa-calendar-days"></i> <?= htmlspecialchars(date('F j, Y', strtotime($event['date']))) ?></span>
					<span><i class="fa-solid fa-location-crosshairs"></i> <?= htmlspecialchars($event['location']) ?></span>
				</p>
				<a href="contact.php" class="event-detail-register-btn">Register Now <span>→</span></a>
			</div>
			<div class="event-detail-hero-image">
				<img src="<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
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
					<p><?= htmlspecialchars($event['content']) ?></p>
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
		<div class="event-detail-cta-content"><h2>Join Us</h2><p>We look forward to experiencing this special time together.</p><a href="contact.php" class="event-detail-cta-button">Contact Us</a></div>
	</section>
	<?php endif; ?>

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
</body>
</html>
