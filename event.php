<?php
$events = [
    'oil-wine-summit' => [
        'title' => 'Oil & Wine Summit',
        'image' => 'assets/images/events/oil-wine-summit.jpg',
        'date' => 'August 15–17, 2026',
        'location' => 'Kingdomite Church International',
        'category' => 'Conference',
        'description' => 'A powerful gathering of worship, prayer, teaching and fellowship designed to draw hearts closer to God.',
    ],
    'june-conference' => [
        'title' => 'June Conference',
        'image' => 'assets/images/events/june-conference.jpg',
        'date' => 'June 2027',
        'location' => 'Kingdomite Church International',
        'category' => 'Conference',
        'description' => 'A season of teaching, connection and spiritual growth for individuals, families and the wider church community.',
    ],
    'embers-of-glory' => [
        'title' => 'Embers of Glory',
        'image' => 'assets/images/events/embers-of-glory.jpg',
        'date' => 'Coming Soon',
        'location' => 'Kingdomite Church International',
        'category' => 'Worship',
        'description' => 'A special atmosphere of worship, prayer and renewed passion for the presence and purpose of God.',
    ],
];

$slug = $_GET['event'] ?? '';
$event = $events[$slug] ?? null;
if (!$event) { http_response_code(404); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $event ? htmlspecialchars($event['title']) : 'Event Not Found' ?> | Kingdomite Church International</title>
<link rel="stylesheet" href="css/events.css">
</head>
<body>
<?php if ($event): ?>
<main class="event-detail-page">
<section class="event-detail-hero">
<div class="event-detail-hero-image"><img src="<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['title']) ?>"></div>
<div class="event-detail-hero-overlay"></div>
<div class="event-detail-hero-content">
<span class="event-detail-hero-category"><?= htmlspecialchars($event['category']) ?></span>
<h1><?= htmlspecialchars($event['title']) ?></h1>
<p><?= htmlspecialchars($event['date']) ?></p>
</div>
</section>
<section class="event-detail-content">
<div class="event-detail-container">
<div class="event-detail-main">
<span class="event-detail-label">About This Event</span>
<h2><?= htmlspecialchars($event['title']) ?></h2>
<p><?= htmlspecialchars($event['description']) ?></p>
</div>
<aside class="event-detail-sidebar">
<div class="event-detail-card">
<h3>Event Details</h3>
<div class="event-detail-item"><span class="event-detail-item-label">Date</span><span class="event-detail-item-value"><?= htmlspecialchars($event['date']) ?></span></div>
<div class="event-detail-item"><span class="event-detail-item-label">Location</span><span class="event-detail-item-value"><?= htmlspecialchars($event['location']) ?></span></div>
<div class="event-detail-item"><span class="event-detail-item-label">Category</span><span class="event-detail-item-value"><?= htmlspecialchars($event['category']) ?></span></div>
</div>
</aside>
</div>
</section>
<section class="event-detail-cta">
<div class="event-detail-cta-content"><h2>Join Us</h2><p>We look forward to experiencing this special time together.</p><a href="contact.php" class="event-detail-cta-button">Contact Us</a></div>
</section>
</main>
<?php else: ?>
<main class="event-not-found"><div class="event-not-found-content"><span class="event-not-found-label">404</span><h1>Event Not Found</h1><p>Sorry, the event you are looking for does not exist or may have been removed.</p><a href="events.php" class="event-not-found-button">Back to Events</a></div></main>
<?php endif; ?>
</body>
</html>
