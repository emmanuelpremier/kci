<?php
/**
 * serve.php — Serving application page (Kingdomite Church International).
 *
 * Reads ?ministry=<slug>, verifies it against the existing `ministries`
 * table (active only), and stores applications in the existing
 * `serve_applications` table. Uses the existing connection in kci_db.php.
 */
include("kci_db.php");

function serve_esc($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}

$availabilityOptions = ['Weekdays', 'Weekends', 'Evenings', 'Flexible'];

/* Active ministries for the Ministry dropdown. */
$ministryOptions = [];
try {
	$ministryList = mysqli_query($conn, "SELECT slug, name FROM ministries WHERE status = 'active' ORDER BY name ASC");
	if ($ministryList) {
		while ($ministryRow = mysqli_fetch_assoc($ministryList)) {
			$ministryOptions[] = $ministryRow;
		}
	}
} catch (Throwable $e) {
	$ministryOptions = [];
}

/* Verify one ministry slug: only active ministries are accepted. */
function serve_find_ministry($conn, $slug) {
	$slug = trim((string)$slug);
	if ($slug === '') {
		return null;
	}
	try {
		$ministryStmt = $conn->prepare("SELECT slug, name, image FROM ministries WHERE slug = ? AND status = 'active' LIMIT 1");
		$ministryStmt->bind_param("s", $slug);
		$ministryStmt->execute();
		$ministryResult = $ministryStmt->get_result();
		$ministryRow = ($ministryResult && $ministryResult->num_rows > 0) ? $ministryResult->fetch_assoc() : null;
		$ministryStmt->close();
		return $ministryRow;
	} catch (Throwable $e) {
		return null;
	}
}

$requestedSlug = trim($_GET['ministry'] ?? '');
$requestedMinistry = serve_find_ministry($conn, $requestedSlug);

/* Dropdown options: every active ministry, plus the requested one if the
   list query above ever misses it. */
$selectOptions = $ministryOptions;
if ($requestedMinistry) {
	$alreadyListed = false;
	foreach ($selectOptions as $option) {
		if ($option['slug'] === $requestedMinistry['slug']) {
			$alreadyListed = true;
			break;
		}
	}
	if (!$alreadyListed) {
		$selectOptions[] = $requestedMinistry;
	}
}

$serveErrors = [];
$serveValues = [
	'full_name'     => '',
	'email'         => '',
	'phone'         => '',
	'ministry_slug' => $requestedMinistry ? $requestedMinistry['slug'] : '',
	'availability'  => '',
	'message'       => '',
];
$chosenMinistry = null;
$serveSubmitted = false;
$serveSubmittedName = '';
$serveSubmittedMinistry = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$serveValues['full_name'] = trim($_POST['full_name'] ?? '');
	$serveValues['email'] = trim($_POST['email'] ?? '');
	$serveValues['phone'] = trim($_POST['phone'] ?? '');
	$serveValues['ministry_slug'] = trim($_POST['ministry_slug'] ?? '');
	$serveValues['availability'] = trim($_POST['availability'] ?? '');
	$serveValues['message'] = trim($_POST['message'] ?? '');

	if (strlen($serveValues['full_name']) < 2) {
		$serveErrors[] = 'Please enter your full name.';
	}
	if (!filter_var($serveValues['email'], FILTER_VALIDATE_EMAIL) || strlen($serveValues['email']) > 190) {
		$serveErrors[] = 'Please enter a valid email address.';
	}
	$phoneDigits = preg_replace('/\D+/', '', $serveValues['phone']);
	if (strlen($phoneDigits) < 7 || strlen($serveValues['phone']) > 25 || !preg_match('/^[+()\-.\s0-9]+$/', $serveValues['phone'])) {
		$serveErrors[] = 'Please enter a valid phone number.';
	}
	$chosenMinistry = serve_find_ministry($conn, $serveValues['ministry_slug']);
	if (!$chosenMinistry) {
		$serveErrors[] = 'Please choose a valid ministry from the list.';
	}
	if ($serveValues['availability'] !== '' && !in_array($serveValues['availability'], $availabilityOptions, true)) {
		$serveErrors[] = 'Please choose a valid availability option.';
	}

	if (empty($serveErrors)) {
		try {
			$serveStmt = $conn->prepare("INSERT INTO serve_applications (full_name, email, phone, ministry_slug, availability, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
			$serveName = substr($serveValues['full_name'], 0, 120);
			$serveEmail = substr($serveValues['email'], 0, 190);
			$servePhone = substr($serveValues['phone'], 0, 40);
			$serveMinistrySlug = $chosenMinistry['slug'];
			$serveAvailability = $serveValues['availability'] === '' ? null : $serveValues['availability'];
			$serveMessage = $serveValues['message'] === '' ? null : substr($serveValues['message'], 0, 2000);
			$serveStmt->bind_param("ssssss", $serveName, $serveEmail, $servePhone, $serveMinistrySlug, $serveAvailability, $serveMessage);
			$serveStmt->execute();
			$serveStmt->close();
			$serveSubmitted = true;
			$serveSubmittedName = $serveName;
			$serveSubmittedMinistry = $chosenMinistry['name'];
		} catch (Throwable $e) {
			/* Never expose database details to visitors. */
			$serveErrors[] = 'We could not save your application right now. Please try again later.';
		}
	}
}

$displayMinistry = $chosenMinistry ? $chosenMinistry : $requestedMinistry;

/* Right-side card background: the selected ministry's stored image path,
   used exactly as it appears in the ministries.image column. */
$serveSideImage = '';
if ($displayMinistry && !empty($displayMinistry['image'])) {
	$serveSideImage = trim((string)$displayMinistry['image']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Serve with a ministry at Kingdomite Church International">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="kci.css?v=14">
	<link rel="icon" type="image/png" href="kci_image">
	<title>Serve With Us | Kingdomite Church International</title>

	<!-- inserting of icon link from cdjns -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="serve-page">
	<!-- Prime the reveal initial state before the first paint. Skipped for
	     reduced-motion users and when IntersectionObserver is unavailable,
	     so the hero, form, messages and footer are never left hidden. -->
	<script type="text/javascript">
		(function () {
			var reduced = window.matchMedia &&
				window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduced || !('IntersectionObserver' in window)) return;
			document.body.classList.add('serve-anim-ready');
		})();
	</script>
	<!--header-->
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
		<!-- SERVE HERO -->
		<section class="serve-hero">
			<div class="serve-hero__inner serve-reveal">
				<span class="serve-hero__badge">Serve With Us</span>
				<h1 class="serve-hero__title">Your Gifts Can Make a Difference</h1>
				<p class="serve-hero__text">
					Every believer is a minister. Tell us where your heart is,
					and we will help you find your place to serve in the house of God.
				</p>
			</div>
		</section>

		<!-- SERVE FORM -->
		<section class="serve-wrap">
			<div class="serve-card serve-reveal">
				<?php if ($serveSubmitted): ?>
				<div class="serve-success" role="status">
					<span class="serve-success__icon" aria-hidden="true">
						<i class="fa-solid fa-circle-check"></i>
					</span>
					<h2>Thank you for your willingness to serve!</h2>
					<p>
						Your serving application has been received<?= $serveSubmittedMinistry !== '' ? ' for <strong>' . serve_esc($serveSubmittedMinistry) . '</strong>' : '' ?>.
						We are grateful for your heart to serve and will be in touch with the next steps.
					</p>
					<div class="serve-actions">
						<a href="min.php" class="serve-btn serve-btn--solid">Explore Ministries <span>&rarr;</span></a>
					</div>
				</div>
				<?php else: ?>
				<div class="serve-intro">
					<span class="serve-intro__eyebrow">Serving Application</span>
					<h2 class="serve-intro__title">Tell Us Where You Would Like to Serve</h2>
					<?php if ($displayMinistry): ?>
					<p class="serve-intro__ministry">
						<i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>
						I'd like to serve with:
						<strong><?= serve_esc($displayMinistry['name']) ?></strong>
					</p>
					<?php else: ?>
					<p class="serve-intro__text">
						Choose the ministry on your heart below and send your application.
						You can also start from any <a href="min.php">ministry page</a>.
					</p>
					<?php endif; ?>
				</div>

				<?php if (!empty($serveErrors)): ?>
				<div class="serve-alert" role="alert">
					<strong>Please review the following:</strong>
					<ul>
						<?php foreach ($serveErrors as $serveError): ?>
						<li><?= serve_esc($serveError) ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>

				<?php if ($requestedSlug !== '' && !$requestedMinistry): ?>
				<div class="serve-note" role="note">
					We could not find that ministry, so please choose one from the list below.
				</div>
				<?php endif; ?>

				<form class="serve-form" method="post" action="serve.php<?= $requestedMinistry ? '?ministry=' . urlencode($requestedMinistry['slug']) : '' ?>" novalidate>
					<div class="serve-row">
						<div class="serve-field">
							<label for="serve-name">Full Name *</label>
							<input class="serve-input" type="text" id="serve-name" name="full_name" value="<?= serve_esc($serveValues['full_name']) ?>" maxlength="120" required autocomplete="name" placeholder="Your full name">
						</div>
						<div class="serve-field">
							<label for="serve-email">Email Address *</label>
							<input class="serve-input" type="email" id="serve-email" name="email" value="<?= serve_esc($serveValues['email']) ?>" maxlength="190" required autocomplete="email" placeholder="you@example.com">
						</div>
					</div>

					<div class="serve-row">
						<div class="serve-field">
							<label for="serve-phone">Phone Number *</label>
							<input class="serve-input" type="tel" id="serve-phone" name="phone" value="<?= serve_esc($serveValues['phone']) ?>" maxlength="40" required autocomplete="tel" placeholder="+234 ...">
						</div>
						<div class="serve-field">
							<label for="serve-ministry">Ministry *</label>
							<select class="serve-input" id="serve-ministry" name="ministry_slug" required>
								<option value="">Select a ministry</option>
								<?php foreach ($selectOptions as $option): ?>
								<option value="<?= serve_esc($option['slug']) ?>"<?= $serveValues['ministry_slug'] === $option['slug'] ? ' selected' : '' ?>><?= serve_esc($option['name']) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="serve-field">
						<label for="serve-availability">Availability <span class="serve-optional">(optional)</span></label>
						<select class="serve-input" id="serve-availability" name="availability">
							<option value="">Select your availability</option>
							<?php foreach ($availabilityOptions as $availabilityOption): ?>
							<option value="<?= serve_esc($availabilityOption) ?>"<?= $serveValues['availability'] === $availabilityOption ? ' selected' : '' ?>><?= serve_esc($availabilityOption) ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="serve-field">
						<label for="serve-message">Message <span class="serve-optional">(optional)</span></label>
						<textarea class="serve-input serve-textarea" id="serve-message" name="message" rows="5" maxlength="2000" placeholder="Share why you would like to serve or anything else we should know."><?= serve_esc($serveValues['message']) ?></textarea>
					</div>

					<div class="serve-actions">
						<button type="submit" class="serve-btn serve-btn--solid">Submit Serving Application <span>&rarr;</span></button>
					</div>
				</form>
				<?php endif; ?>
			</div>

			<aside class="serve-side serve-reveal<?= $serveSideImage !== '' ? ' has-bg' : '' ?>"<?= $serveSideImage !== '' ? ' style="background-image: url(\'' . str_replace("'", "%27", $serveSideImage) . '\')"' : '' ?>>
				<div class="serve-side__content">
					<span class="serve-side__icon" aria-hidden="true">
						<i class="fa-solid fa-hands-holding-circle"></i>
					</span>
					<h2>Not Sure Where You Fit?</h2>
					<p>Browse all nine ministry families, meet the teams and discover where your gifts belong.</p>
					<a href="min.php" class="serve-btn serve-btn--ghost">View All Ministries</a>
				</div>
			</aside>
		</section>
	</main>

	<!-- Footer -->
	<footer class="kc118">
	    <div class="kc119">
	        <div class="kc120">
	            <div class="kc121">
	                <div class="kc122"><img src="kci_image/img13.webp"></div>
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
	            <p>Phone / WhatsApp: <a href="https://wa.me/2348064979241" target="_blank" rel="noopener">+234 806 497 9241</a></p>
	            <p>Email: <a href="mailto:dkcifamily@gmail.com">dkcifamily@gmail.com</a></p>
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
	</script>
	<script type="text/javascript">
		// Serve page: lightweight IntersectionObserver scroll reveal.
		// Self-contained — it does not touch the mobile menu, the dropdowns
		// or the header scroll/glassmorphism code above.
		(function () {
			var body = document.body;
			if (!body || !body.classList.contains('serve-anim-ready')) return;

			// Only the three reveal blocks plus the footer. Everything inside
			// them (hero copy, intro, form rows, success/alert, side panel)
			// is animated by CSS once its parent gets .is-visible, so the
			// observer stays cheap and the form is never individually tracked.
			var targets = document.querySelectorAll(
				'.serve-page .serve-hero__inner.serve-reveal, ' +
				'.serve-page .serve-card.serve-reveal, ' +
				'.serve-page .serve-side.serve-reveal, ' +
				'.serve-page .kc118 .kc119'
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
				body.classList.remove('serve-anim-ready');
			}
		})();
	</script>
</body>
</html>

