<?php
/**
 * form.php — First-Time Visitor form (Kingdomite Church International).
 *
 * The header and footer below are the existing KCI header/footer markup,
 * reused exactly as it appears on contact.php, serve.php, index.php and the
 * rest of the site. Only the content between them is built here.
 *
 * Submitted responses are stored in the `first_timers` table through the
 * shared connection in kci_db.php, exactly the way contact.php stores
 * contact messages. `id` and `created_at` are left to their defaults.
 */
include("kci_db.php");
include_once("kci_validate.php");

function form_esc($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}

/* How did you hear about us? — value-for-value as the chips below. */
$heardFromOptions = [
	'A friend or family member',
	'Social media',
	'Passing by',
	'Church event',
	'Other',
];

/* Shared phone rule (kci_validate.php) — shown under the field and in the summary. */
$formPhoneError = 'Please enter a valid phone number, for example 0803 123 4567 or +234 803 123 4567.';

$formErrors = [];
$formValues = [
	'full_name'   => '',
	'phone'       => '',
	'email'       => '',
	'visit_date'  => '',
	'heard_from'  => '',
	'prayer_need' => '',
];
$formSubmitted = false;
$formSubmittedFirstName = '';

/* Optional visit date — today … 180 days ahead. Matches
   kci_valid_upcoming_date() in kci_validate.php and also sets the
   min/max window on the date input below. */
$formMinDate = (new DateTimeImmutable('today'))->format('Y-m-d');
$formMaxDate = (new DateTimeImmutable('today'))->modify('+180 days')->format('Y-m-d');

/* Email / visit-date rules (kci_validate.php) — filled in during
   validation and shown under their fields when they fail. */
$formEmailError = '';
$formDateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	/* Honeypot: a real visitor never sees this field, so anything in it is
	   a bot. We answer with the normal success state and store nothing. */
	$honeypot = trim($_POST['website'] ?? '');

	$formValues['full_name']   = trim($_POST['full_name'] ?? '');
	$formValues['phone']       = trim($_POST['phone'] ?? '');
	$formValues['email']       = trim($_POST['email'] ?? '');
	$formValues['visit_date']  = trim($_POST['visit_date'] ?? '');
	$formValues['heard_from']  = trim($_POST['heard_from'] ?? '');
	$formValues['prayer_need'] = trim($_POST['prayer_need'] ?? '');

	if (strlen($formValues['full_name']) < 2) {
		$formErrors[] = 'Please enter your full name.';
	}
	/* Phone is required — shared rule from kci_validate.php; this message
	   also appears directly under the phone field below. */
	$formPhoneNormalized = null;
	if ($formValues['phone'] === '') {
		$formErrors[] = 'Please enter your phone number - it is required so we can reach you.';
	} else {
		$formPhoneNormalized = kci_normalize_phone($formValues['phone']);
		if ($formPhoneNormalized === null) {
			$formErrors[] = $formPhoneError;
		}
	}
	/* Email is optional — shared rule from kci_validate.php; the
	   message also appears directly under the email field below. */
	$formEmailCheck = kci_check_email($formValues['email'], false);
	if (!$formEmailCheck['ok']) {
		$formEmailError = $formEmailCheck['error'];
		$formErrors[] = $formEmailError;
	}
	/* Visit date is optional, but when given it must be a real date
	   from today onwards, within the next 6 months. */
	if ($formValues['visit_date'] !== '' && !kci_valid_upcoming_date($formValues['visit_date'], 180)) {
		$formDateError = 'Please choose a date from today onwards (within the next 6 months).';
		$formErrors[] = $formDateError;
	}
	if ($formValues['heard_from'] !== '' && !in_array($formValues['heard_from'], $heardFromOptions, true)) {
		$formErrors[] = 'Please choose one of the options for how you heard about us.';
	}
	if (strlen($formValues['prayer_need']) > 1000) {
		$formErrors[] = 'Please keep your message under 1000 characters.';
	}

	if (empty($formErrors)) {
		/* Only the first name is used in the welcome message. */
		$formNameParts = preg_split('/\s+/', $formValues['full_name']);
		$formSubmittedFirstName = $formNameParts[0] ?? '';

		if ($honeypot !== '') {
			/* Bot detected: say thank you, save nothing. */
			$formSubmitted = true;
		} else {
			try {
				$formStmt = $conn->prepare("INSERT INTO first_timers (full_name, email, phone, visit_date, heard_from, prayer_need) VALUES (?, ?, ?, ?, ?, ?)");
				$formName = substr($formValues['full_name'], 0, 150);
				$formEmail = $formEmailCheck['email'] === '' ? null : substr($formEmailCheck['email'], 0, 255);
				$formPhone = substr((string)$formPhoneNormalized, 0, 30);
				$formVisitDate = $formValues['visit_date'] === '' ? null : $formValues['visit_date'];
				$formHeardFrom = $formValues['heard_from'] === '' ? null : substr($formValues['heard_from'], 0, 150);
				$formPrayerNeed = $formValues['prayer_need'] === '' ? null : substr($formValues['prayer_need'], 0, 1000);
				$formStmt->bind_param("ssssss", $formName, $formEmail, $formPhone, $formVisitDate, $formHeardFrom, $formPrayerNeed);
				$formStmt->execute();
				$formStmt->close();
				$formSubmitted = true;
			} catch (Throwable $e) {
				/* Never expose database details to visitors. */
				$formErrors[] = 'We could not save your details right now. Please try again in a moment.';
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Planning your first visit to Kingdomite Church International? Fill the first-timer form and we will be expecting you.">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="kci.css?v=13">
	<link rel="stylesheet" type="text/css" href="mobilefix.css?v=1">
	<link rel="stylesheet" href="form.css?v=1">
	<link rel="stylesheet" type="text/css" href="mobile-header.css?v=1">
	<link rel="icon" type="image/png" href="kci_image">
	<title>Plan Your First Visit | Kingdomite Church International</title>

	<!-- inserting of icon link from cdjns -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="firsttimer-page">
	<!-- First-Time Visitor page: prime the scroll-reveal initial state
	     before the first paint. It runs at the top of <body> (exactly
	     like location.php) so document.body already exists when the
	     class is added. Skipped for reduced-motion users and when
	     IntersectionObserver is unavailable, so content is never hidden. -->
	<script type="text/javascript">
		(function () {
			var reduced = window.matchMedia &&
				window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduced || !('IntersectionObserver' in window)) return;
			document.body.classList.add('firsttimer-anim-ready');
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
						<li><a href="event.php?slug=oil-wine-summit">Oil &amp; Wine Summit</a></li>
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
		<!-- SPLIT HERO -->
		<section class="firsttimer-hero">
			<div class="firsttimer-hero__inner">
				<div class="firsttimer-hero__copy firsttimer-reveal">
					<span class="firsttimer-eyebrow">Kingdomite Church International</span>
					<h1 class="firsttimer-hero__title">Welcome, First-Time Guest</h1>
					<p class="firsttimer-hero__text">
						You are not walking into a room of strangers &mdash; you are walking into
						a family that has been expecting you. Tell us a little about yourself
						and we will make sure someone is ready to greet you by name.
					</p>
					<div class="firsttimer-hero__actions">
						<a href="#firsttimer-form" class="firsttimer-btn firsttimer-btn--solid">Start My First Visit Form <span>&rarr;</span></a>
						<a href="tel:+2348064979241" class="firsttimer-btn firsttimer-btn--ghost"><i class="fa-solid fa-phone" aria-hidden="true"></i> Call Instead</a>
					</div>
					<ul class="firsttimer-hero__facts">
						<li><i class="fa-regular fa-clock" aria-hidden="true"></i> Sunday Service &mdash; 8:00am &amp; 10:30am</li>
						<li><i class="fa-solid fa-children" aria-hidden="true"></i> Children&rsquo;s Church for every age</li>
					</ul>
				</div>
				<div class="firsttimer-hero__media firsttimer-reveal">
					<img src="kci_image/img20.webp" alt="A congregation gathered in worship at Kingdomite Church International">
					<span class="firsttimer-hero__badge">
						<i class="fa-solid fa-chair" aria-hidden="true"></i> We saved you a seat
					</span>
				</div>
			</div>
		</section>
<!-- YOUR FIRST SUNDAY — 3 STEPS -->
		<section class="firsttimer-steps">
			<div class="firsttimer-steps__inner">
				<div class="firsttimer-steps__head firsttimer-reveal">
					<span class="firsttimer-eyebrow firsttimer-eyebrow--dark">Your First Sunday</span>
					<h2 class="firsttimer-steps__title">Three Small Steps, One Warm Welcome</h2>
				</div>
				<ol class="firsttimer-steps__list">
					<li class="firsttimer-step firsttimer-reveal">
						<span class="firsttimer-step__num">1</span>
						<span class="firsttimer-step__icon" aria-hidden="true"><i class="fa-solid fa-door-open"></i></span>
						<h3>Arrive and be welcomed</h3>
						<p>Come ten minutes early. Someone from the hospitality team will meet you at the door and walk you in.</p>
					</li>
					<li class="firsttimer-step firsttimer-reveal">
						<span class="firsttimer-step__num">2</span>
						<span class="firsttimer-step__icon" aria-hidden="true"><i class="fa-solid fa-music"></i></span>
						<h3>Worship and the Word</h3>
						<p>Settle in for praise, a short welcome and a message that will help you find your place in God&rsquo;s family.</p>
					</li>
					<li class="firsttimer-step firsttimer-reveal">
						<span class="firsttimer-step__num">3</span>
						<span class="firsttimer-step__icon" aria-hidden="true"><i class="fa-solid fa-people-group"></i></span>
						<h3>Connect and fellowship</h3>
						<p>Stay for coffee after service. We will introduce you to people you will be glad to know.</p>
					</li>
				</ol>
			</div>
		</section>

		<!-- FIRST-TIMER FORM -->
		<section class="firsttimer-wrap" id="firsttimer-form">
			<div class="firsttimer-card firsttimer-reveal">
				<div class="firsttimer-card__head">
					<span class="firsttimer-eyebrow firsttimer-eyebrow--dark">First-Timer Form</span>
					<h2 class="firsttimer-card__title">Let&rsquo;s Get You Ready</h2>
					<p class="firsttimer-card__text">
						Two minutes now saves you any awkwardness later. Fill in what you can
						and press send &mdash; our welcome team will do the rest.
					</p>
				</div>

				<?php if ($formSubmitted): ?>
					<div class="firsttimer-success" role="status">
						<span class="firsttimer-success__icon" aria-hidden="true">
							<i class="fa-solid fa-circle-check"></i>
						</span>
						<h3>We&rsquo;re glad you&rsquo;re coming<?= $formSubmittedFirstName !== '' ? ', ' . form_esc($formSubmittedFirstName) : '' ?>!</h3>
						<p>
							Your details are with our welcome team<?= $formValues['visit_date'] !== '' && kci_valid_upcoming_date($formValues['visit_date']) ? ' and we have pencilled in ' . form_esc(date('l, j F Y', strtotime($formValues['visit_date']))) : '' ?>.
							Someone will call you before then to confirm and answer any question
							you have. Until Sunday &mdash; you are already family here.
						</p>
						<div class="firsttimer-success__actions">
							<a href="location.php" class="firsttimer-btn firsttimer-btn--solid">Find Us <span>&rarr;</span></a>
							<a href="index.php" class="firsttimer-btn firsttimer-btn--plain">Back to Home</a>
						</div>
					</div>
				<?php else: ?>
					<?php if (!empty($formErrors)): ?>
						<div class="firsttimer-alert" role="alert">
							<strong>Please review the following:</strong>
							<ul>
								<?php foreach ($formErrors as $formError): ?>
									<li><?= form_esc($formError) ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<form class="firsttimer-form" action="form.php#firsttimer-form" method="POST" novalidate>
						<!-- Honeypot: hidden from people, irresistible to bots. -->
						<div class="firsttimer-hp" aria-hidden="true">
							<label for="website">Website</label>
							<input type="text" id="website" name="website" value="" tabindex="-1" autocomplete="off">
						</div>
<!-- SECTION 1: ABOUT YOU -->
						<fieldset class="firsttimer-section">
							<legend class="firsttimer-section__title">
								<span class="firsttimer-section__icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
								About you
							</legend>
							<div class="firsttimer-grid">
								<div class="firsttimer-field">
									<label for="full_name">Full Name *</label>
									<input class="firsttimer-input" type="text" id="full_name" name="full_name" value="<?= form_esc($formValues['full_name']) ?>" maxlength="150" placeholder="John Okafor" autocomplete="name" required>
								</div>
								<div class="firsttimer-field">
									<label for="phone">Phone / WhatsApp *</label>
									<input class="firsttimer-input" type="tel" id="phone" name="phone" value="<?= form_esc($formValues['phone']) ?>" maxlength="20" inputmode="tel" placeholder="0803 123 4567" autocomplete="tel" required>
								<?php if (in_array($formPhoneError, $formErrors, true)): ?>
								<p style="margin:6px 0 0;color:#c0392b;font-size:.8rem;line-height:1.4;"><?= form_esc($formPhoneError) ?></p>
								<?php endif; ?>
								</div>
								<div class="firsttimer-field">
									<label for="email">Email <span class="firsttimer-optional">(optional)</span></label>
									<input class="firsttimer-input" type="email" id="email" name="email" value="<?= form_esc($formValues['email']) ?>" maxlength="254" inputmode="email" placeholder="you@example.com" autocomplete="email">
									<?php if ($formEmailError !== ''): ?>
									<p style="margin:6px 0 0;color:#c0392b;font-size:.8rem;line-height:1.4;"><?= form_esc($formEmailError) ?></p>
									<?php endif; ?>
								</div>
							</div>
						</fieldset>

						<!-- SECTION 2: YOUR VISIT -->
						<fieldset class="firsttimer-section">
							<legend class="firsttimer-section__title">
								<span class="firsttimer-section__icon" aria-hidden="true"><i class="fa-regular fa-calendar-check"></i></span>
								Your visit
							</legend>
							<div class="firsttimer-grid">
								<div class="firsttimer-field">
									<label for="visit_date">Which Sunday will you join us? <span class="firsttimer-optional">(optional)</span></label>
									<input class="firsttimer-input" type="date" id="visit_date" name="visit_date" min="<?= form_esc($formMinDate) ?>" max="<?= form_esc($formMaxDate) ?>" value="<?= form_esc($formValues['visit_date']) ?>">
									<?php if ($formDateError !== ''): ?>
									<p style="margin:6px 0 0;color:#c0392b;font-size:.8rem;line-height:1.4;"><?= form_esc($formDateError) ?></p>
									<?php endif; ?>
								</div>
								<div class="firsttimer-field">
									<span class="firsttimer-legendish">How did you hear about us? <span class="firsttimer-optional">(optional)</span></span>
									<div class="firsttimer-chips" role="radiogroup" aria-label="How did you hear about us?">
										<?php foreach ($heardFromOptions as $heardFromOption): ?>
											<label class="firsttimer-chip<?= $formValues['heard_from'] === $heardFromOption ? ' is-active' : '' ?>">
												<input type="radio" name="heard_from" value="<?= form_esc($heardFromOption) ?>"<?= $formValues['heard_from'] === $heardFromOption ? ' checked' : '' ?>>
												<span><?= form_esc($heardFromOption) ?></span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						</fieldset>

						<!-- SECTION 3: PRAYER -->
						<fieldset class="firsttimer-section">
							<legend class="firsttimer-section__title">
								<span class="firsttimer-section__icon" aria-hidden="true"><i class="fa-solid fa-hands-praying"></i></span>
								How can we pray for you?
							</legend>
							<div class="firsttimer-grid">
								<div class="firsttimer-field firsttimer-field--wide">
									<label for="prayer_need">Prayer need or message <span class="firsttimer-optional">(optional, up to 1000 characters)</span></label>
									<textarea class="firsttimer-input firsttimer-textarea" id="prayer_need" name="prayer_need" rows="6" maxlength="1000" placeholder="Share anything you would like us to pray over, or just say hello."><?= form_esc($formValues['prayer_need']) ?></textarea>
								</div>
							</div>
						</fieldset>

						<div class="firsttimer-actions">
							<button type="submit" class="firsttimer-btn firsttimer-btn--solid">Send My Details <span>&rarr;</span></button>
							<p class="firsttimer-actions__note">
								<i class="fa-solid fa-lock" aria-hidden="true"></i>
								Your details are only used to welcome you &mdash; never shared or sold.
							</p>
						</div>
					</form>
				<?php endif; ?>
			</div>
		</section>

		<!-- CLOSING BAND -->
		<section class="firsttimer-band">
			<div class="firsttimer-band__inner">
				<h2 class="firsttimer-band__title firsttimer-reveal">There Is a Place for You Here</h2>
				<p class="firsttimer-band__text firsttimer-reveal">
					Dress comfortably, bring the whole family, and come as you are.
					We will handle the rest.
				</p>
				<div class="firsttimer-band__actions firsttimer-reveal">
					<a href="location.php" class="firsttimer-btn firsttimer-btn--orange">Find Us <span>&rarr;</span></a>
					<a href="index.php" class="firsttimer-btn firsttimer-btn--ghost">Back to Home</a>
				</div>
			</div>
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
	<script type="text/javascript">
		// First-Timer page: lightweight IntersectionObserver scroll-reveal.
		// Fully self-contained - it does NOT touch the mobile menu, the
		// dropdowns, the header scroll code, or the form POST/validation
		// above. It only adds a reveal class.
		(function () {
			var page = document.querySelector('.firsttimer-page');
			if (!page) return;

			// The pre-paint script (see top of <body>) only primes the page
			// when motion is allowed and IntersectionObserver exists. If it
			// was skipped, nothing is hidden and this can stop here.
			if (!document.body.classList.contains('firsttimer-anim-ready')) return;

			var targets = page.querySelectorAll('.firsttimer-reveal, .kc118 .kc119');

			if (!targets.length) return;

			// Safety net: nothing is ever left invisible. Anything already
			// on screen after a layout change (an image, font or viewport
			// resize shifting content) is revealed at once.
			function revealInView() {
				var viewport = window.innerHeight || document.documentElement.clientHeight;
				targets.forEach(function (element) {
					if (element.classList.contains('is-visible')) return;
					var rect = element.getBoundingClientRect();
					if (rect.width === 0 && rect.height === 0) return;
					if (rect.top <= viewport * 0.94 && rect.bottom >= 0) element.classList.add('is-visible');
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

				// Reveal anything already in view on load (hero, top of
				// the form after a validation-error reload with #anchor).
				revealInView();
			} catch (error) {
				// Never leave content hidden if anything goes wrong.
				document.body.classList.remove('firsttimer-anim-ready');
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
	<script type="text/javascript">
		// "How did you hear about us?" chips: mirror the selected radio's
		// state onto its pill so the styling survives keyboard use too.
		// (Purely cosmetic - the real <input type="radio"> is untouched.)
		(function () {
			var chips = document.querySelectorAll('.firsttimer-page .firsttimer-chip input[type="radio"]');
			chips.forEach(function (input) {
				input.addEventListener('change', function () {
					chips.forEach(function (other) {
						other.parentNode.classList.toggle('is-active', other.checked);
					});
				});
			});
		})();
	</script>
</body>
</html>
