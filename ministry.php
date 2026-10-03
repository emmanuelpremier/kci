<?php
/**
 * ministry.php — Dynamic ministry detail page (Kingdomite Church International).
 * Content comes from the existing `ministries` table in the `kingdomite`
 * database, fetched by URL slug, e.g. ministry.php?slug=children-church
 * Uses the existing connection in kci_db.php, same as event.php.
 */
include("kci_db.php");

$slug = trim($_GET['slug'] ?? '');
$ministry = null;
$loadError = false;

if ($slug !== '') {
	try {
		$stmt = $conn->prepare("SELECT id, name, slug, category, icon, image, description, meeting, status FROM ministries WHERE slug = ? LIMIT 1");
		$stmt->bind_param("s", $slug);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result && $result->num_rows > 0) {
			$ministry = $result->fetch_assoc();
		}
		$stmt->close();
	} catch (Throwable $e) {
		/* Never expose database details to visitors. */
		$loadError = true;
	}
}

$isActive = !$loadError && $ministry && strtolower(trim((string)($ministry['status'] ?? ''))) === 'active';

/* Escape text for HTML while respecting entities already stored in the DB. */
function ministry_esc($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}

/* A stored image path is usable when set and (for local files) on disk. */
function ministry_image_ok($path) {
	$path = trim((string)$path);
	if ($path === '') {
		return false;
	}
	if (preg_match('#^(https?://|data:)#i', $path)) {
		return true;
	}
	return is_file(__DIR__ . '/' . ltrim($path, '/'));
}

$hasImage = $isActive && ministry_image_ok($ministry['image'] ?? '');

if ($slug === '') {
	$errorTitle = 'No Ministry Selected';
	$errorText = 'Please choose a ministry from the Ministries page to see its details here.';
} elseif ($loadError) {
	$errorTitle = 'Something Went Wrong';
	$errorText = 'We could not load this ministry right now. Please try again later.';
} elseif (!$ministry) {
	$errorTitle = 'Ministry Not Found';
	$errorText = 'We could not find the ministry you are looking for. It may have been moved or renamed.';
} else {
	$errorTitle = 'Ministry Currently Unavailable';
	$errorText = 'This ministry is not available at the moment. Please check back soon.';
}

if ($slug !== '' && !$isActive) {
	http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="<?= $isActive ? ministry_esc($ministry['name']) . ' ministry' : 'Ministry' ?> at Kingdomite Church International">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="kci.css?v=12">
	<link rel="icon" type="image/png" href="kci_image">
	<title><?= $isActive ? ministry_esc($ministry['name']) : ministry_esc($errorTitle) ?> | Kingdomite Church International</title>

	<!-- inserting of icon link from cdjns -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="ministry-page">
	<!-- Prime the reveal initial state before the first paint. Skipped for
	     reduced-motion users and when IntersectionObserver is unavailable,
	     so content is never left hidden. -->
	<script type="text/javascript">
		(function () {
			var reduced = window.matchMedia &&
				window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduced || !('IntersectionObserver' in window)) return;
			document.body.classList.add('ministry-anim-ready');
		})();
	</script>
	<!--header-->
	<section class="kc1">
		<a href="index.php" class="kc2" aria-label="KCI home">
			<img src="kci_image/im2.jpg">
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
						<li><a href="event.php?slug=oil-wine-summit">Oil & Wine Summit</a></li>
						<li><a href="event.php?slug=june-conference">June Conference</a></li>
						<li><a href="event.php?slug=embers-of-glory">Embers Of Glory</a></li>
					</ul>
				</li>
				<li class="linef"><a href="min.php">Ministries<i class="fa-solid fa-chevron-down arrow" aria-hidden="true"></i></a>
					<div class="lined"></div>
					<ul class="dropdown">
						<li><a href="ministry.php?slug=global-missions">Missions</a></li>
						<li><a href="ministry.php?slug=womens-ministry">Women's Ministry</a></li>
						<li><a href="ministry.php?slug=youth-church">YC-The Youth Church</a></li>
						<li><a href="ministry.php?slug=mighty-teens">MTC-Mighty Teens Church</a></li>
						<li><a href="ministry.php?slug=children-church">Children Church</a></li>
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

	<main>
		<?php if ($isActive): ?>
		<!-- MINISTRY HERO -->
		<section class="ministry-hero">
			<div class="ministry-hero__inner ministry-reveal">
				<p class="ministry-hero__crumbs">
					<a href="min.php">Ministries</a>
					<span aria-hidden="true">/</span>
					<?= ministry_esc($ministry['category']) ?>
				</p>
				<span class="ministry-hero__badge"><?= ministry_esc($ministry['category']) ?></span>
				<h1 class="ministry-hero__title"><?= ministry_esc($ministry['name']) ?></h1>
				<?php if (!empty($ministry['meeting'])): ?>
				<p class="ministry-hero__meta">
					<i class="fa-regular fa-clock" aria-hidden="true"></i>
					<?= ministry_esc($ministry['meeting']) ?>
				</p>
				<?php endif; ?>
			</div>
		</section>
		<!-- MINISTRY DETAIL -->
		<section class="ministry-detail">
			<div class="ministry-detail__grid">
				<div class="ministry-media ministry-reveal">
					<?php if ($hasImage): ?>
					<img src="<?= ministry_esc($ministry['image']) ?>"
					     alt="<?= ministry_esc($ministry['name']) ?>"
					     data-ministry-detail-img>
					<?php else: ?>
					<span class="ministry-media__fallback" aria-hidden="true">
						<i class="<?= !empty($ministry['icon']) ? ministry_esc($ministry['icon']) : 'fa-solid fa-church' ?>"></i>
					</span>
					<?php endif; ?>
				</div>

				<div class="ministry-content ministry-reveal">
					<span class="ministry-content__eyebrow"><?= ministry_esc($ministry['category']) ?></span>
					<h2 class="ministry-content__title"><?= ministry_esc($ministry['name']) ?></h2>
					<p class="ministry-content__text"><?= ministry_esc($ministry['description']) ?></p>

					<?php if (!empty($ministry['meeting'])): ?>
					<div class="ministry-meet">
						<span class="ministry-meet__icon" aria-hidden="true">
							<i class="fa-regular fa-clock"></i>
						</span>
						<div>
							<strong>Meeting Time</strong>
							<span><?= ministry_esc($ministry['meeting']) ?></span>
						</div>
					</div>
					<?php endif; ?>

					<div class="ministry-actions">
						<a href="serve.php?ministry=<?= urlencode($ministry['slug']) ?>" class="ministry-btn ministry-btn--solid">
							I'd Like to Serve <span>&rarr;</span>
						</a>
						<a href="min.php" class="ministry-btn ministry-btn--ghost">
							&larr; All Ministries
						</a>
					</div>
				</div>
			</div>
		</section>
		<!--MINISTRY-DETAIL-->
		<?php else: ?>
		<!-- MINISTRY ERROR -->
		<section class="ministry-error">
			<div class="ministry-error__card ministry-reveal">
				<span class="ministry-error__icon" aria-hidden="true">
					<i class="fa-solid fa-circle-exclamation"></i>
				</span>
				<h1><?= ministry_esc($errorTitle) ?></h1>
				<p><?= ministry_esc($errorText) ?></p>
				<a href="min.php" class="ministry-btn ministry-btn--solid">
					&larr; Back to Ministries
				</a>
			</div>
		</section>
		<!--MINISTRY-ERROR-->
		<?php endif; ?>
	</main>

	<!-- Footer -->
	<footer class="kc118">
	    <div class="kc119">
	        <div class="kc120">
	            <div class="kc121">
	                <div class="kc122"><img src="kci_image/img13.png"></div>
	                <div class="kc123">
	                    <strong>KINGDOMITE</strong>
	                    <span>CHURCH INTERNATIONAL</span>
	                </div>
	            </div>
	            <p>
	                Building a people who know God, love people
	                and live out His purpose.
	            </p>
	        </div>

	        <div class="kc124">
	            <h4>Quick Links</h4>
	            <a href="index.php">Home</a>
	            <a href="aboutus.php">About</a>
	            <a href="events.php">Events</a>
	            <a href="contact.php">Contact</a>
	        </div>

	        <div class="kc124">
	            <h4>Contact</h4>
	            <p>Phone: +234 806 497 9241</p>
	            <p>Email: info@kingdomitechurch@gmail.com</p>
	        </div>

	        <div class="kc124">
	            <h4>Location</h4>
	            <p>Kingdomite Church International</p>
	            <p>Nigeria</p>
	        </div>
	    </div>

	    <div class="kc125">
	        <p>© <?= date('Y') ?> Kingdomite Church International. All Rights Reserved.</p>
	    </div>
	</footer>

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

		// Ministry detail image: hide a missing file so the styled
		// icon fallback shows instead of a broken image.
		(function() {
			var page = document.querySelector('.ministry-page');
			if (!page) {
				return;
			}
			page.querySelectorAll('img[data-ministry-detail-img]').forEach(function(image) {
				function useFallback() {
					image.classList.add('is-missing');
				}
				if (image.complete) {
					if (image.naturalWidth === 0) {
						useFallback();
					}
				} else {
					image.addEventListener('load', function() {
						if (image.naturalWidth === 0) {
							useFallback();
						}
					});
					image.addEventListener('error', useFallback);
				}
			});
		})();
	</script>
	<script type="text/javascript">
		// Ministry page: lightweight IntersectionObserver scroll reveal.
		// Self-contained — it does not touch the mobile menu, dropdown or
		// header scroll code above.
		(function () {
			var body = document.body;
			if (!body || !body.classList.contains('ministry-anim-ready')) return;

			var targets = document.querySelectorAll(
				'.ministry-page .ministry-hero__inner.ministry-reveal, ' +
				'.ministry-page .ministry-media.ministry-reveal, ' +
				'.ministry-page .ministry-content.ministry-reveal, ' +
				'.ministry-page .ministry-error__card.ministry-reveal, ' +
				'.ministry-page .kc118 .kc119'
			);

			if (!targets.length) return;

			try {
				var observer = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (entry.isIntersecting) {
							entry.target.classList.add('is-visible');
							observer.unobserve(entry.target);
						}
					});
				}, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });

				targets.forEach(function (target) {
					observer.observe(target);
				});
			} catch (error) {
				// Never leave content hidden if anything goes wrong.
				body.classList.remove('ministry-anim-ready');
			}
		})();
	</script>
	<!--MINISTRY-FOOTER-->
</body>
</html>
