<?php
/**
 * contact.php — Contact page (Kingdomite Church International).
 *
 * The header and footer below are the existing KCI header/footer markup,
 * reused exactly as it appears on index.php, aboutus.php, events.php,
 * min.php and serve.php. Only the content between them is built here.
 *
 * Submitted messages are stored in the existing `contact_messages` table
 * through the shared connection in kci_db.php, exactly the way serve.php
 * stores serving applications. `created_at` is left to its column default.
 */
include("kci_db.php");

function contact_esc($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}

/* Subject options, value-for-value as they appear in the form below. */
$contactSubjectOptions = ['General Enquiry', 'Prayer Request', 'Plan My First Visit', 'Partnership & Giving', 'Something Else'];

$contactErrors = [];
$contactValues = [
	'full_name' => '',
	'email'     => '',
	'phone'     => '',
	'subject'   => '',
	'message'   => '',
];
$contactSubmitted = false;
$contactSubmittedName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$contactValues['full_name'] = trim($_POST['full_name'] ?? '');
	$contactValues['email'] = trim($_POST['email'] ?? '');
	$contactValues['phone'] = trim($_POST['phone'] ?? '');
	$contactValues['subject'] = trim($_POST['subject'] ?? '');
	$contactValues['message'] = trim($_POST['message'] ?? '');

	if (strlen($contactValues['full_name']) < 2) {
		$contactErrors[] = 'Please enter your full name.';
	}
	if (!filter_var($contactValues['email'], FILTER_VALIDATE_EMAIL) || strlen($contactValues['email']) > 255) {
		$contactErrors[] = 'Please enter a valid email address.';
	}
	/* Phone is required — checked server-side, not only by the browser. */
	if ($contactValues['phone'] === '') {
		$contactErrors[] = 'Please enter your phone number - it is required so we can reach you.';
	} else {
		$contactPhoneDigits = preg_replace('/\D+/', '', $contactValues['phone']);
		if (strlen($contactPhoneDigits) < 7 || strlen($contactValues['phone']) > 30 || !preg_match('/^[+()\-.\s0-9]+$/', $contactValues['phone'])) {
			$contactErrors[] = 'Please enter a valid phone number.';
		}
	}
	if ($contactValues['subject'] === '') {
		$contactErrors[] = 'Please choose a subject.';
	} elseif (!in_array($contactValues['subject'], $contactSubjectOptions, true)) {
		$contactErrors[] = 'Please choose a valid subject from the list.';
	}
	if ($contactValues['message'] === '') {
		$contactErrors[] = 'Please write your message.';
	}

	if (empty($contactErrors)) {
		try {
			$contactStmt = $conn->prepare("INSERT INTO contact_messages (full_name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
			$contactName = substr($contactValues['full_name'], 0, 150);
			$contactEmail = substr($contactValues['email'], 0, 255);
			$contactPhone = substr($contactValues['phone'], 0, 30);
			$contactSubject = substr($contactValues['subject'], 0, 150);
			$contactMessage = substr($contactValues['message'], 0, 2000);
			$contactStmt->bind_param("sssss", $contactName, $contactEmail, $contactPhone, $contactSubject, $contactMessage);
			$contactStmt->execute();
			$contactStmt->close();
			$contactSubmitted = true;
			$contactSubmittedName = $contactName;
		} catch (Throwable $e) {
			/* Never expose database details to visitors. */
			$contactErrors[] = 'We could not send your message right now. Please try again later.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Contact Kingdomite Church International — questions, prayer requests or planning your first visit.">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="kci.css?v=20">
	<link rel="icon" type="image/png" href="kci_image">
	<title>Contact Us | Kingdomite Church International</title>
	<!-- Contact page: prime the scroll-reveal initial state before the first
	     paint. Skipped for reduced-motion users and when IntersectionObserver
	     is unavailable, so the content is never left hidden. -->
	<script type="text/javascript">
		(function () {
			var reduced = window.matchMedia &&
				window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduced || !('IntersectionObserver' in window)) return;
			document.body.classList.add('contact-anim-ready');
		})();
	</script>


	<!-- inserting of icon link from cdjns -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="contact-page">
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
		<!-- CONTACT HERO -->
		<section class="contact-hero">
			<!-- Background photo: kci_image/img50.webp ("Contact Us" flat-lay).
			     It is painted by .contact-page .hero-bg in kci.css. -->
			<div class="hero-bg" aria-hidden="true"></div>
			<div class="contact-hero__inner">
				<span class="contact-hero__eyebrow">Kingdomite Church International</span>
				<h1 class="contact-hero__title">Contact Us</h1>
				<p class="contact-hero__text">
					We would love to hear from you. Whether you have a question, a prayer
					request or want to plan your first visit, our doors and hearts are
					always open.
				</p>
				<div class="contact-hero__actions">
					<a href="#contact-form" class="contact-btn contact-btn--solid">Send a Message</a>
					<a href="#service-times" class="contact-btn contact-btn--ghost">Plan Your Visit</a>
				</div>
			</div>
		</section>
		<!-- FLOATING INFO CARDS -->
		<section class="contact-info">
			<div class="contact-info__card">
				<article class="contact-info__item">
					<span class="contact-info__icon" aria-hidden="true"><i class="fa-solid fa-phone"></i></span>
					<h3>Call Us</h3>
					<p>+234 816 4617 1024</p>
				</article>
				<article class="contact-info__item">
					<span class="contact-info__icon" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
					<h3>Email Us</h3>
					<p>info@<wbr>kingdomitechurch@gmail.com</p>
					<p>kingdomitechurch@gmail.com</p>
				</article>
				<article class="contact-info__item">
					<span class="contact-info__icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
					<h3>Visit Us</h3>
					<p>Kingdomite Church International</p>
					<p>Nigeria</p>
				</article>
				<article class="contact-info__item">
					<span class="contact-info__icon" aria-hidden="true"><i class="fa-solid fa-clock"></i></span>
					<h3>Office Hours</h3>
					<p>Mon &ndash; Fri: 9:00am &ndash; 5:00pm</p>
					<p>Sat: 10:00am &ndash; 2:00pm</p>
				</article>
			</div>
		</section>

		<!-- MESSAGE FORM + SIDE CARDS -->
		<section class="contact-body">
			<div class="contact-body__grid">
				<div class="contact-form-card" id="contact-form">
					<span class="contact-eyebrow">Reach Out</span>
					<h2 class="contact-form-card__title">Send Us a Message</h2>
					<p class="contact-form-card__text">
						Fill the form below and a member of our team will get back to you
						within 48 hours.
					</p>

					<?php if ($contactSubmitted): ?>
					<div class="contact-success" role="status">
						<span class="contact-success__icon" aria-hidden="true">
							<i class="fa-solid fa-circle-check"></i>
						</span>
						<h2>Thank you<?= $contactSubmittedName !== '' ? ', ' . contact_esc($contactSubmittedName) : '' ?>!</h2>
						<p>
							Your message has been received. Our team will get back to you
							soon &mdash; thank you for reaching out to Kingdomite Church
							International.
						</p>
					</div>
					<?php else: ?>
					<?php if (!empty($contactErrors)): ?>
					<div class="contact-alert" role="alert">
						<strong>Please review the following:</strong>
						<ul>
							<?php foreach ($contactErrors as $contactError): ?>
							<li><?= contact_esc($contactError) ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php endif; ?>

					<form class="contact-form" action="contact.php#contact-form" method="POST">
						<div class="contact-form__row">
							<div class="contact-field">
								<label for="full_name">Full Name</label>
								<input class="contact-input" type="text" id="full_name" name="full_name" value="<?= contact_esc($contactValues['full_name']) ?>" maxlength="150" placeholder="John Okafor" autocomplete="name" required>
							</div>
							<div class="contact-field">
								<label for="email">Email Address</label>
								<input class="contact-input" type="email" id="email" name="email" value="<?= contact_esc($contactValues['email']) ?>" maxlength="255" placeholder="you@example.com" autocomplete="email" required>
							</div>
						</div>

						<div class="contact-form__row">
							<div class="contact-field">
								<label for="phone">Phone</label>
								<input class="contact-input" type="tel" id="phone" name="phone" value="<?= contact_esc($contactValues['phone']) ?>" maxlength="30" placeholder="+234 800 000 0000" autocomplete="tel" required>
							</div>
							<div class="contact-field">
								<label for="subject">Subject</label>
								<div class="contact-select">
									<select class="contact-input" id="subject" name="subject" required>
										<option value="">Select a subject</option>
										<?php foreach ($contactSubjectOptions as $contactSubjectOption): ?>
										<option value="<?= contact_esc($contactSubjectOption) ?>"<?= $contactValues['subject'] === $contactSubjectOption ? ' selected' : '' ?>><?= contact_esc($contactSubjectOption) ?></option>
										<?php endforeach; ?>
									</select>
									<i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
								</div>
							</div>
						</div>

						<div class="contact-field">
							<label for="message">Message</label>
							<textarea class="contact-input contact-textarea" id="message" name="message" rows="6" maxlength="2000" placeholder="How can we serve you?" required><?= contact_esc($contactValues['message']) ?></textarea>
						</div>

						<div class="contact-form__actions">
							<button type="submit" class="contact-btn contact-btn--solid">Send Message</button>
						</div>
					</form>
					<?php endif; ?>
				</div>
				<aside class="contact-side">
					<div class="contact-times" id="service-times">
						<span class="contact-eyebrow contact-eyebrow--light">Plan Your Visit</span>
						<h2 class="contact-times__title">Service Times</h2>
						<ul class="contact-times__list">
							<li class="contact-times__row">
								<span>Sunday Service</span>
								<strong>8:00am &amp; 10:30am</strong>
							</li>
							<li class="contact-times__row">
								<span>Midweek Service</span>
								<strong>Wednesdays, 6:00pm</strong>
							</li>
							<li class="contact-times__row">
								<span>Prayer Meeting</span>
								<strong>Fridays, 6:30am</strong>
							</li>
						</ul>
						<div class="contact-times__location">
							<span class="contact-times__location-label">Location</span>
							<p>Kingdomite Church International, Nigeria</p>
						</div>
					</div>

					<div class="contact-expect">
						<span class="contact-eyebrow">What to Expect</span>
						<ul class="contact-expect__list">
							<li><i class="fa-solid fa-check" aria-hidden="true"></i> A warm welcome from our hospitality team</li>
							<li><i class="fa-solid fa-check" aria-hidden="true"></i> Spirit-filled worship and the Word of God</li>
							<li><i class="fa-solid fa-check" aria-hidden="true"></i> A safe, fun kids church for your children</li>
						</ul>
					</div>
				</aside>
			</div>
		</section>

		<!-- CTA -->
		<section class="contact-cta">
			<h2 class="contact-cta__title">Be Part of What God Is Doing</h2>
			<p class="contact-cta__text">
				Come and experience our next gathering with us &mdash; you are always
				welcome here.
			</p>
			<a href="#contact-form" class="contact-btn contact-btn--orange">Get In Touch</a>
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
	</script>
	<script type="text/javascript">
		// Contact page: lightweight IntersectionObserver scroll-reveal.
		// Fully self-contained - it does NOT touch the mobile menu, the
		// dropdowns, the header scroll code, or the contact form POST/
		// validation/submission above. It only adds a reveal class.
		(function () {
			var page = document.querySelector('.contact-page');
			if (!page) return;

			// The pre-paint script (see top of <body>) only primes the page
			// when motion is allowed and IntersectionObserver exists. If it
			// was skipped, nothing is hidden and this can stop here.
			if (!document.body.classList.contains('contact-anim-ready')) return;

			var targets = page.querySelectorAll(
				'.contact-info__item, ' +
				'.contact-form-card, ' +
				'.contact-form-card .contact-eyebrow, ' +
				'.contact-form-card__title, ' +
				'.contact-form-card__text, ' +
				'.contact-times, ' +
				'.contact-expect, ' +
				'.contact-cta__title, ' +
				'.contact-cta__text, ' +
				'.contact-cta .contact-btn, ' +
				'.kc118 .kc119'
			);

			if (!targets.length) return;

			function reveal(element) {
				if (element.classList.contains('is-visible')) return;
				element.classList.add('is-visible');
			}

			// Safety net: nothing is ever left invisible. Anything already
			// on screen after a layout change (an image, font or viewport
			// resize shifting content) is revealed at once.
			function revealInView() {
				var viewport = window.innerHeight || document.documentElement.clientHeight;
				targets.forEach(function (element) {
					if (element.classList.contains('is-visible')) return;
					var rect = element.getBoundingClientRect();
					if (rect.width === 0 && rect.height === 0) return;
					if (rect.top <= viewport * 0.94 && rect.bottom >= 0) reveal(element);
				});
			}

			var observer;

			try {
				observer = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (!entry.isIntersecting) return;
						// Each element reveals once and is unobserved straight
						// afterwards; the observer itself stays in use for the
						// elements that have not arrived yet.
						entry.target.classList.add('is-visible');
						observer.unobserve(entry.target);
					});
				}, { threshold: 0.15, rootMargin: '0px 0px -6% 0px' });

				targets.forEach(function (target) {
					observer.observe(target);
				});
			} catch (error) {
				// Never leave content hidden if anything goes wrong.
				document.body.classList.remove('contact-anim-ready');
				return;
			}

			// Same safety net for a viewport that changes after priming
			// (rotating a phone, resizing a window).
			var resizeTimer = null;
			window.addEventListener('resize', function () {
				if (resizeTimer) window.clearTimeout(resizeTimer);
				resizeTimer = window.setTimeout(revealInView, 120);
			});
		})();
	</script>
</body>
</html>
