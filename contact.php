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
include_once("kci_validate.php");
include_once("site_config.php");
include_once __DIR__ . '/notify.php';

function contact_esc($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}

/* Subject options, value-for-value as they appear in the form below. */
$contactSubjectOptions = ['General Enquiry', 'Prayer Request', 'Plan My First Visit', 'Partnership & Giving', 'Something Else'];

/* Shared phone rule (kci_validate.php) — shown under the field and in the summary. */
$contactPhoneError = 'Please enter a valid phone number, for example 0803 123 4567 or +234 803 123 4567.';

/* Email rule (kci_validate.php) — filled in during validation and
   shown under the email field when it fails. */
$contactEmailError = '';

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
	/* Email is required — shared rule from kci_validate.php; this
	   message also appears directly under the email field below. */
	$contactEmailCheck = kci_check_email($contactValues['email'], true);
	if (!$contactEmailCheck['ok']) {
		$contactEmailError = $contactEmailCheck['error'];
		$contactErrors[] = $contactEmailError;
	}
	/* Phone is required — shared rule from kci_validate.php; this message
	   also appears directly under the phone field below. */
	$contactPhoneNormalized = null;
	if ($contactValues['phone'] === '') {
		$contactErrors[] = 'Please enter your phone number - it is required so we can reach you.';
	} else {
		$contactPhoneNormalized = kci_normalize_phone($contactValues['phone']);
		if ($contactPhoneNormalized === null) {
			$contactErrors[] = $contactPhoneError;
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
			$contactEmail = substr($contactEmailCheck['email'], 0, 255);
			$contactPhone = substr((string)$contactPhoneNormalized, 0, 30);
			$contactSubject = substr($contactValues['subject'], 0, 150);
			$contactMessage = substr($contactValues['message'], 0, 2000);
			$contactStmt->bind_param("sssss", $contactName, $contactEmail, $contactPhone, $contactSubject, $contactMessage);
			$contactStmt->execute();
			$contactStmt->close();
			$contactSubmitted = true;
			$contactSubmittedName = $contactName;
			try {
				$nName = function_exists('contact_esc') ? contact_esc($contactName) : htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8');
				$nEmail = function_exists('contact_esc') ? contact_esc($contactEmail) : htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8');
				$nPhone = function_exists('contact_esc') ? contact_esc($contactPhone) : htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8');
				$nSubj = function_exists('contact_esc') ? contact_esc($contactSubject) : htmlspecialchars($contactSubject, ENT_QUOTES, 'UTF-8');
				$nMsg = nl2br(function_exists('contact_esc') ? contact_esc($contactMessage) : htmlspecialchars($contactMessage, ENT_QUOTES, 'UTF-8'));
				$nSub = 'New Contact message from ' . trim(preg_replace('/[\r\n]+/', ' ', $contactName));
				$nUrl = function_exists('kci_admin_url') ? kci_admin_url('messages.php') : 'admin/messages.php';
				$nUrlE = htmlspecialchars($nUrl, ENT_QUOTES, 'UTF-8');
				$nBody = '<h2>New contact message</h2><table cellpadding="6">'
					. '<tr><td><b>Name</b></td><td>' . $nName . '</td></tr>'
					. '<tr><td><b>Email</b></td><td>' . $nEmail . '</td></tr>'
					. '<tr><td><b>Phone</b></td><td>' . $nPhone . '</td></tr>'
					. '<tr><td><b>Subject</b></td><td>' . $nSubj . '</td></tr>'
					. '<tr><td><b>Message</b></td><td>' . $nMsg . '</td></tr></table>'
					. '<p><a href="' . $nUrlE . '">View in admin</a></p>';
				if (function_exists('notify_admin')) { notify_admin($nSub, $nBody, $contactEmail); }
			} catch (Throwable $ignored) {}
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
	<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="kci.css?v=26">
	<link rel="stylesheet" type="text/css" href="mobilefix.css?v=3">
	<link rel="stylesheet" type="text/css" href="mobile-header.css?v=3">
	<link rel="icon" type="image/png" href="kci_image">
	<title>Contact Us | Kingdomite Church International</title>
	<!-- The scroll-reveal is primed by the shared kci-reveal.js engine,
	     included right after <body> with the page's data-reveal-*
	     configuration (it must not run from <head>, where document.body
	     does not exist yet). -->


	<!-- inserting of icon link from cdjns -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="contact-page"
	data-reveal-ready="contact-anim-ready"
	data-reveal-targets=".contact-info__item, .contact-form-card, .contact-form-card .contact-eyebrow, .contact-form-card__title, .contact-form-card__text, .contact-times, .contact-expect, .contact-cta__title, .contact-cta__text, .contact-cta .contact-btn, .kc118 .kc119"
	data-reveal-margin="0px 0px -6% 0px"
	data-reveal-safety="resize">
	<!-- Shared reveal engine (kci-reveal.js): primes the scroll-reveal
	     initial state before the first paint, then drives the single
	     IntersectionObserver. -->
	<script src="kci-reveal.js" defer></script>

	<!--header-->
	<?php include 'header.php'; ?>

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
					<p><a href="https://wa.me/2348064979241" target="_blank" rel="noopener">+234 806 497 9241</a></p>
				</article>
				<article class="contact-info__item">
					<span class="contact-info__icon" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
					<h3>Email Us</h3>
					<p><a href="mailto:<?= contact_esc(CHURCH_EMAIL) ?>"><?= contact_esc(CHURCH_EMAIL) ?></a></p>
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
								<input class="contact-input" type="email" id="email" name="email" value="<?= contact_esc($contactValues['email']) ?>" maxlength="254" inputmode="email" placeholder="you@example.com" autocomplete="email" required>
								<?php if ($contactEmailError !== ''): ?>
								<p style="margin:6px 0 0;color:#c0392b;font-size:.8rem;line-height:1.4;"><?= contact_esc($contactEmailError) ?></p>
								<?php endif; ?>
							</div>
						</div>

						<div class="contact-form__row">
							<div class="contact-field">
								<label for="phone">Phone</label>
								<input class="contact-input" type="tel" id="phone" name="phone" value="<?= contact_esc($contactValues['phone']) ?>" maxlength="20" inputmode="tel" placeholder="0803 123 4567" autocomplete="tel" required>
								<?php if (in_array($contactPhoneError, $contactErrors, true)): ?>
								<p style="margin:6px 0 0;color:#c0392b;font-size:.8rem;line-height:1.4;"><?= contact_esc($contactPhoneError) ?></p>
								<?php endif; ?>
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
								<strong>Sundays, 8:00am</strong>
							</li>
							<li class="contact-times__row">
								<span>Midweek Service</span>
								<strong>Wednesdays, 5:00pm</strong>
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
	     data-reveal-* attributes on <body> for its configuration). -->
</body>
</html>
