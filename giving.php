<?php
session_start();
/* giving.php — Give page (Kingdomite Church International).
 * Reached from the "Give" button in the main header on every page.
 * Shows the church's Opay account on a flip-able 3D bank card and a
 * "Confirm your giving" form that saves into the `giving_proofs`
 * table through kci_db.php (id, status, created_at are automatic).
 * The table already exists and is never created or altered here.
 * Header/footer are the KCI markup reused exactly as on location.php.
 * No payment processing, file uploads, tracking or external scripts.
 */
include_once("kci_validate.php");


/* Account + contact details — edit here only; the page reads these. */
$bankName = "Opay";
$accountNumber = "9013194092";
$accountName = "Lucy David Vincent";
$whatsappUrl = "https://wa.me/2348064979241";
$churchEmail = "dkcifamily@gmail.com";
$churchPhone = "+234 806 497 9241";

/* Grouped for display only ("9013 194 092"); copying uses raw digits. */
$accountNumberGrouped = preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1 $2 $3', $accountNumber);
if (!is_string($accountNumberGrouped) || $accountNumberGrouped === '') {
	$accountNumberGrouped = $accountNumber;
}

function giving_esc($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}

/* Shared validation messages from kci_validate.php: the phone message
   also appears under its field, the date message in the error summary. */
$givePhoneError = 'Please enter a valid phone number, for example 0803 123 4567 or +234 803 123 4567.';
$giveDateError = 'Please choose the real date you gave (within the last 90 days).';

/* Email rule (kci_validate.php) — filled in during validation and
   shown under the email field when it fails. */
$giveEmailError = '';

/* Giving types — value-for-value as the chips in the form below. */
$givingTypes = array(
	'Tithe',
	'Offering',
	'Seed',
	'Building and Projects',
	'Missions and Outreach',
	'Other',
);

/* Throttle: at most $maxSubmissions successful confirmations per session
   inside a rolling $windowMinutes-minute window. Only rows that actually
   reach the database are recorded — failed validations never count. */
$maxSubmissions = 5;
$windowMinutes = 60;

/* CSRF token for the confirmation form (one per session). */
if (empty($_SESSION['giving_csrf_token']) || !is_string($_SESSION['giving_csrf_token'])) {
	$_SESSION['giving_csrf_token'] = bin2hex(random_bytes(32));
}

$giveErrors = array();
$giveValues = array(
	'full_name'           => '',
	'phone'               => '',
	'email'               => '',
	'amount'              => '',
	'giving_type'         => '',
	'give_date'           => '',
	'sender_account_name' => '',
	'reference'           => '',
	'note'                => '',
);
$giveSent = false;
$giveFlash = null;
$today = date('Y-m-d');
/* Earliest acceptable giving date: 90 days back (kci_valid_recent_date). */
$giveMinDate = (new DateTimeImmutable('today'))->modify('-90 days')->format('Y-m-d');

/* Post-redirect-get landing: show the one-time success flash. */
if (isset($_GET['sent']) && isset($_SESSION['giving_flash']) && is_array($_SESSION['giving_flash'])) {
	$giveSent = true;
	$giveFlash = $_SESSION['giving_flash'];
	unset($_SESSION['giving_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	/* Honeypot: a real visitor never sees this field, so anything in
	   it is a bot. Answer with the normal success state, store nothing. */
	$honeypot = trim($_POST['website'] ?? '');
	if ($honeypot !== '') {
		$_SESSION['giving_flash'] = array(
			'generic'    => true,
			'first_name' => '',
			'full_name'  => '',
			'amount'     => '',
			'reference'  => '',
		);
		header('Location: giving.php?sent=1#confirm');
		exit;
	}
	$giveValues['full_name']           = trim($_POST['full_name'] ?? '');
	$giveValues['phone']               = trim($_POST['phone'] ?? '');
	$giveValues['email']               = trim($_POST['email'] ?? '');
	$giveValues['amount']              = trim(str_replace(array(',', 'NGN', 'ngn'), '', $_POST['amount'] ?? ''));
	$giveValues['giving_type']         = trim($_POST['giving_type'] ?? '');
	$giveValues['give_date']           = trim($_POST['give_date'] ?? '');
	$giveValues['sender_account_name'] = trim($_POST['sender_account_name'] ?? '');
	$giveValues['reference']           = trim($_POST['reference'] ?? '');
	$giveValues['note']                = trim($_POST['note'] ?? '');

	/* Throttle — rolling window of successful saves: discard timestamps
	   older than $windowMinutes, then block once $maxSubmissions rows
	   are still inside the window. The timestamp itself is recorded
	   only after the INSERT succeeds, below. */
	$now = time();
	$windowSeconds = $windowMinutes * 60;
	$recentSaves = array();
	foreach ((array)($_SESSION['giving_attempts'] ?? array()) as $savedAt) {
		if (is_int($savedAt) && ($now - $savedAt) < $windowSeconds) {
			$recentSaves[] = $savedAt;
		}
	}
	$_SESSION['giving_attempts'] = $recentSaves;

	if (count($recentSaves) >= $maxSubmissions) {
		/* Minutes (rounded up) until the oldest counted save leaves
		   the window. */
		$oldestSave = min($recentSaves);
		$waitMinutes = (int)ceil(($oldestSave + $windowSeconds - $now) / 60);
		if ($waitMinutes < 1) {
			$waitMinutes = 1;
		}
		$giveErrors[] = 'You have sent several confirmations recently. Please try again in ' . $waitMinutes . ' minutes.';
	} else {
		/* CSRF check — the token lives in the session, never in the DB. */
		$postedToken = $_POST['giving_csrf'] ?? '';
		if (!is_string($postedToken) || !hash_equals($_SESSION['giving_csrf_token'], $postedToken)) {
			$giveErrors[] = 'Your session has expired. Please reload the page and try again.';
		}

		if (strlen($giveValues['full_name']) < 2) {
			$giveErrors[] = 'Please enter your full name.';
		} elseif (strlen($giveValues['full_name']) > 150) {
			$giveErrors[] = 'Please keep your name under 150 characters.';
		}
		/* Phone is required — shared rule from kci_validate.php; this message
		   also appears directly under the phone field below. */
		$givePhoneNormalized = null;
		if ($giveValues['phone'] === '') {
			$giveErrors[] = 'Please enter your phone number - it is required so we can reach you.';
		} else {
			$givePhoneNormalized = kci_normalize_phone($giveValues['phone']);
			if ($givePhoneNormalized === null) {
				$giveErrors[] = $givePhoneError;
			}
		}
		/* Email is optional, but must be valid when it is given. */
		/* Email is optional — shared rule from kci_validate.php; the
		   message also appears directly under the email field below. */
		$giveEmailCheck = kci_check_email($giveValues['email'], false);
		if (!$giveEmailCheck['ok']) {
			$giveEmailError = $giveEmailCheck['error'];
			$giveErrors[] = $giveEmailError;
		}
		/* Amount: number above 0, at most 100,000,000, up to 2 decimals. */
		$giveAmount = 0.0;
		if ($giveValues['amount'] === '' || !preg_match('/^\d+(\.\d{1,2})?$/', $giveValues['amount'])) {
			$giveErrors[] = 'Please enter a valid amount in Naira (numbers only, up to 2 decimals).';
		} else {
			$giveAmount = (float)$giveValues['amount'];
			if ($giveAmount <= 0 || $giveAmount > 100000000) {
				$giveErrors[] = 'Please enter an amount between 1 and 100,000,000 Naira.';
			}
		}
		if ($giveValues['giving_type'] === '' || !in_array($giveValues['giving_type'], $givingTypes, true)) {
			$giveErrors[] = 'Please choose what your giving is for.';
		}
		if ($giveValues['give_date'] === '' || !kci_valid_recent_date($giveValues['give_date'], 90)) {
			$giveErrors[] = $giveDateError;
		}
		if (strlen($giveValues['sender_account_name']) > 150) {
			$giveErrors[] = 'Please keep the sender account name under 150 characters.';
		}
		if (strlen($giveValues['reference']) > 100) {
			$giveErrors[] = 'Please keep the transaction reference under 100 characters.';
		}
		if (strlen($giveValues['note']) > 500) {
			$giveErrors[] = 'Please keep your note under 500 characters.';
		}
		if (empty($giveErrors)) {
			include("kci_db.php");
			try {
				$stmt = $conn->prepare("INSERT INTO giving_proofs (full_name, phone, email, amount, giving_type, give_date, sender_account_name, reference, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
				if ($stmt === false) {
					throw new Exception('prepare failed');
				}
				$emailOrNull  = $giveEmailCheck['email'] === '' ? null : $giveEmailCheck['email'];
				$amountOrNull = number_format($giveAmount, 2, '.', '');
				$senderOrNull = $giveValues['sender_account_name'] === '' ? null : $giveValues['sender_account_name'];
				$refOrNull    = $giveValues['reference'] === '' ? null : $giveValues['reference'];
				$noteOrNull   = $giveValues['note'] === '' ? null : $giveValues['note'];
				$stmt->bind_param(
					"sssssssss",
					$giveValues['full_name'],
					$givePhoneNormalized,
					$emailOrNull,
					$amountOrNull,
					$giveValues['giving_type'],
					$giveValues['give_date'],
					$senderOrNull,
					$refOrNull,
					$noteOrNull
				);
				$stmt->execute();
				$newId = $stmt->insert_id;
				$stmt->close();

				/* Only a confirmation that actually reached the database
				   counts toward the throttle. */
				$_SESSION['giving_attempts'][] = time();

				/* Only the first name is used in the thank-you message. */
				$giveNameParts = preg_split('/\s+/', $giveValues['full_name']);
				$giveFirstName = $giveNameParts[0] ?? $giveValues['full_name'];
				$_SESSION['giving_flash'] = array(
					'generic'    => false,
					'first_name' => $giveFirstName,
					'full_name'  => $giveValues['full_name'],
					'amount'     => number_format($giveAmount, 2),
					'reference'  => 'KCI-' . str_pad((string)$newId, 6, '0', STR_PAD_LEFT),
				);
				header('Location: giving.php?sent=1#confirm');
				exit;
			} catch (Throwable $e) {
				/* Never echo database errors to the visitor. */
				$giveErrors[] = 'Sorry — something went wrong while saving your confirmation. Please try again.';
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
	<meta name="description" content="<?= giving_esc('Give to Kingdomite Church International — our Opay account details and a simple form to confirm your tithe, offering, seed or gift.') ?>">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="kci.css?v=21">
	<link rel="stylesheet" type="text/css" href="mobilefix.css?v=1">
	<link rel="stylesheet" href="giving.css?v=2">
	<link rel="icon" type="image/png" href="kci_image">
	<title>Give | Kingdomite Church International</title>

	<!-- inserting of icon link from cdjns -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="giving-page">
	<!-- Giving page: prime the scroll-reveal initial state before the
	     first paint. Skipped for reduced-motion users and when
	     IntersectionObserver is unavailable, so content is never hidden. -->
	<script type="text/javascript">
		(function () {
			var reduced = window.matchMedia &&
				window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduced || !('IntersectionObserver' in window)) return;
			document.body.classList.add('giving-anim-ready');
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
	<!-- Hero -->
	<section class="giving-hero">
		<span class="giving-hero__glow giving-hero__glow--one" aria-hidden="true"></span>
		<span class="giving-hero__glow giving-hero__glow--two" aria-hidden="true"></span>
		<span class="giving-hero__glow giving-hero__glow--three" aria-hidden="true"></span>
		<div class="giving-hero__inner giving-reveal">
			<span class="giving-hero__label">Give</span>
			<h1 class="giving-hero__title">Give Cheerfully</h1>
			<p class="giving-hero__verse">&ldquo;Every man according as he purposeth in his heart, so let him give; not grudgingly, or of necessity: for God loveth a cheerful giver.&rdquo;</p>
			<p class="giving-hero__ref">2 Corinthians 9:7 (KJV)</p>
		</div>
	</section>
	<!-- Account + How to give -->
	<section class="giving-main">
		<div class="giving-main__inner">
			<div class="giving-cardzone giving-reveal">
				<div class="giving-cardscene">
					<div class="giving-card" id="giving-card" tabindex="0" role="button" aria-label="Church giving account card. Press Enter or Space to flip it." aria-pressed="false">
						<div class="giving-card__inner" id="giving-card-inner">
							<div class="giving-card__face giving-card__face--front" aria-hidden="false">
								<span class="giving-card__shine" aria-hidden="true"></span>
								<div class="giving-card__top">
									<span class="giving-card__chip" aria-hidden="true"></span>
									<span class="giving-card__bank"><?= giving_esc($bankName) ?></span>
									<span class="giving-card__wave" aria-hidden="true"><i class="fa-solid fa-wifi"></i></span>
								</div>
								<p class="giving-card__number"><?= giving_esc($accountNumberGrouped) ?></p>
								<div class="giving-card__bottom">
									<div class="giving-card__holder">
										<span class="giving-card__label">Account Name</span>
										<strong class="giving-card__name"><?= giving_esc(strtoupper($accountName)) ?></strong>
									</div>
									<span class="giving-card__tag">Giving</span>
								</div>
							</div>
							<div class="giving-card__face giving-card__face--back" aria-hidden="true">
								<span class="giving-card__stripe" aria-hidden="true"></span>
								<div class="giving-card__sign">
									<span class="giving-card__signtext">Kingdomite Church International</span>
								</div>
								<button type="button" class="giving-card__copy" data-copy="<?= giving_esc($accountNumber) ?>" data-copy-message="Account number copied">
									<i class="fa-regular fa-copy"></i> Copy Account Number
								</button>
								<p class="giving-card__hint">Tap or hover to flip</p>
							</div>
						</div>
					</div>
				</div>
				<button type="button" class="giving-flip" id="giving-flip-btn">Flip card</button>
				<div class="giving-copyrow">
					<button type="button" class="giving-copy" data-copy="<?= giving_esc($accountNumber) ?>" data-copy-message="Account number copied">
						<i class="fa-regular fa-copy"></i> Copy Account Number
					</button>
					<button type="button" class="giving-copy giving-copy--ghost" data-copy="<?= giving_esc($accountName) ?>" data-copy-message="Account name copied">
						<i class="fa-regular fa-copy"></i> Copy Account Name
					</button>
				</div>
				<p class="giving-toast" id="giving-toast" role="status" aria-live="polite" hidden></p>
			</div>
			<aside class="giving-how giving-reveal">
				<span class="giving-how__label">How to give</span>
				<h2 class="giving-how__title">Give in a minute</h2>
				<ol class="giving-steps">
					<li class="giving-step giving-reveal">
						<span class="giving-step__no">1</span>
						<span class="giving-step__icon"><i class="fa-solid fa-mobile-screen-button"></i></span>
						<span class="giving-step__text"><strong>Open your bank or Opay app</strong> on your phone.</span>
					</li>
					<li class="giving-step giving-reveal">
						<span class="giving-step__no">2</span>
						<span class="giving-step__icon"><i class="fa-solid fa-money-bill-transfer"></i></span>
						<span class="giving-step__text"><strong>Transfer to the account shown</strong> on the card.</span>
					</li>
					<li class="giving-step giving-reveal">
						<span class="giving-step__no">3</span>
						<span class="giving-step__icon"><i class="fa-solid fa-pen-to-square"></i></span>
						<span class="giving-step__text"><strong>Add what it is for</strong> in the narration, e.g. Tithe or Offering.</span>
					</li>
				</ol>
				<a href="#confirm" class="giving-btn giving-btn--solid giving-scroll" data-scroll="#confirm">I have made a transfer</a>
				<p class="giving-how__note"><i class="fa-solid fa-circle-check"></i> Always confirm the account name matches before you send.</p>
			</aside>
		</div>
	</section>
	<!-- Ways to give -->
	<section class="giving-ways">
		<div class="giving-ways__inner">
			<div class="giving-head giving-reveal">
				<span class="giving-head__label">Ways to give</span>
				<h2 class="giving-head__title">Every seed has a place</h2>
				<p class="giving-head__text">Choose what your gift is for — every gift goes to the work of God.</p>
			</div>
			<div class="giving-ways__grid">
				<article class="giving-way giving-reveal">
					<span class="giving-way__no">01</span>
					<span class="giving-way__icon"><i class="fa-solid fa-scale-balanced"></i></span>
					<h3 class="giving-way__title">Tithe</h3>
					<p class="giving-way__text">Honour God first with a tenth of your increase.</p>
				</article>
				<article class="giving-way giving-reveal">
					<span class="giving-way__no">02</span>
					<span class="giving-way__icon"><i class="fa-solid fa-hand-holding-heart"></i></span>
					<h3 class="giving-way__title">Offering</h3>
					<p class="giving-way__text">Give freely with a joyful and willing heart.</p>
				</article>
				<article class="giving-way giving-reveal">
					<span class="giving-way__no">03</span>
					<span class="giving-way__icon"><i class="fa-solid fa-seedling"></i></span>
					<h3 class="giving-way__title">Seed</h3>
					<p class="giving-way__text">Sow a seed of faith towards what you believe God for.</p>
				</article>
				<article class="giving-way giving-reveal">
					<span class="giving-way__no">04</span>
					<span class="giving-way__icon"><i class="fa-solid fa-church"></i></span>
					<h3 class="giving-way__title">Building and Projects</h3>
					<p class="giving-way__text">Help us build a house where lives are changed.</p>
				</article>
				<article class="giving-way giving-reveal">
					<span class="giving-way__no">05</span>
					<span class="giving-way__icon"><i class="fa-solid fa-earth-africa"></i></span>
					<h3 class="giving-way__title">Missions and Outreach</h3>
					<p class="giving-way__text">Carry the gospel to Oyigbo and beyond.</p>
				</article>
			</div>
		</div>
	</section>
	<!-- Confirmation form -->
	<section class="giving-confirm" id="confirm">
		<div class="giving-confirm__inner">
			<div class="giving-split giving-reveal">
				<aside class="giving-side">
					<span class="giving-side__icon"><i class="fa-solid fa-shield-halved"></i></span>
					<h2 class="giving-side__title">Confirm Your Giving</h2>
					<p class="giving-side__text">Let us know you have given so our finance team can confirm it and thank you.</p>
					<p class="giving-side__privacy"><i class="fa-solid fa-lock"></i> We only ask for what we need. Never send us your card number, PIN, password or OTP.</p>
				</aside>
				<div class="giving-formwrap">
				<?php if ($giveSent && is_array($giveFlash) && empty($giveFlash['generic'])): ?>
					<?php
						$flashRef = (string)($giveFlash['reference'] ?? '');
						$flashName = (string)($giveFlash['full_name'] ?? '');
						$flashAmount = (string)($giveFlash['amount'] ?? '');
						$waMessage = 'Hello, I have just given to Kingdomite Church International. Reference: ' . $flashRef . '. Name: ' . $flashName . '. Amount: N' . $flashAmount . '. Sending my proof of payment.';
						$waLink = $whatsappUrl . '?text=' . urlencode($waMessage);
					?>
					<div class="giving-success">
						<span class="giving-success__icon"><i class="fa-solid fa-circle-check"></i></span>
						<h3 class="giving-success__title">Thank you, <?= giving_esc($giveFlash['first_name']) ?>!</h3>
						<p class="giving-success__text">We received your gift of <strong>&#8358;<?= giving_esc($giveFlash['amount']) ?></strong>.</p>
						<p class="giving-success__ref">Reference: <strong><?= giving_esc($giveFlash['reference']) ?></strong></p>
						<p class="giving-success__text">Our team will confirm your gift shortly.</p>
						<a class="giving-btn giving-btn--wa" href="<?= giving_esc($waLink) ?>" target="_blank" rel="noopener">
							<i class="fa-brands fa-whatsapp"></i> Send Screenshot on WhatsApp
						</a>
					</div>
				<?php else: ?>
					<?php if (!empty($giveErrors)): ?>
					<div class="giving-alert" role="alert">
						<strong>Please check the following:</strong>
						<ul>
							<?php foreach ($giveErrors as $error): ?>
							<li><?= giving_esc($error) ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php endif; ?>
					<form class="giving-form" method="post" action="giving.php#confirm" novalidate>
						<input type="hidden" name="giving_csrf" value="<?= giving_esc($_SESSION['giving_csrf_token']) ?>">
						<p class="giving-honey" aria-hidden="true">
							<label>Leave this field empty
								<input type="text" name="website" value="" tabindex="-1" autocomplete="off">
							</label>
						</p>
						<div class="giving-field">
							<label for="giving-name">Full Name *</label>
							<input type="text" id="giving-name" name="full_name" maxlength="150" required value="<?= giving_esc($giveValues['full_name']) ?>">
						</div>
						<div class="giving-field">
							<label for="giving-phone">Phone / WhatsApp *</label>
							<input type="tel" id="giving-phone" name="phone" maxlength="20" inputmode="tel" autocomplete="tel" placeholder="0803 123 4567" required value="<?= giving_esc($giveValues['phone']) ?>">
						<?php if (in_array($givePhoneError, $giveErrors, true)): ?>
						<p style="margin:6px 0 0;color:#c0392b;font-size:.8rem;line-height:1.4;"><?= giving_esc($givePhoneError) ?></p>
						<?php endif; ?>
						</div>
						<div class="giving-field">
							<label for="giving-email">Email (optional)</label>
							<input type="email" id="giving-email" name="email" maxlength="254" inputmode="email" autocomplete="email" value="<?= giving_esc($giveValues['email']) ?>">
							<?php if ($giveEmailError !== ''): ?>
							<p style="margin:6px 0 0;color:#c0392b;font-size:.8rem;line-height:1.4;"><?= giving_esc($giveEmailError) ?></p>
							<?php endif; ?>
						</div>
						<div class="giving-field">
							<label for="giving-amount">Amount in Naira *</label>
							<span class="giving-amount">
								<span class="giving-amount__cur" aria-hidden="true">&#8358;</span>
								<input type="text" id="giving-amount" name="amount" inputmode="decimal" required value="<?= giving_esc($giveValues['amount']) ?>">
							</span>
						</div>
						<fieldset class="giving-field giving-chips">
							<legend>Giving Type *</legend>
							<?php $chipIndex = 0; foreach ($givingTypes as $type): $chipIndex++; ?>
							<label class="giving-chip<?php echo ($giveValues['giving_type'] === $type) ? ' is-active' : ''; ?>">
								<input type="radio" name="giving_type" value="<?= giving_esc($type) ?>"<?php echo ($giveValues['giving_type'] === $type) ? ' checked' : ''; ?>>
								<span><?= giving_esc($type) ?></span>
							</label>
							<?php endforeach; ?>
						</fieldset>
						<div class="giving-field">
							<label for="giving-date">Date of Giving *</label>
							<input type="date" id="giving-date" name="give_date" required min="<?= giving_esc($giveMinDate) ?>" max="<?= giving_esc($today) ?>" value="<?= giving_esc($giveValues['give_date']) ?>">
						</div>
						<div class="giving-field">
							<label for="giving-sender">Name on the account you sent from (optional)</label>
							<input type="text" id="giving-sender" name="sender_account_name" maxlength="150" value="<?= giving_esc($giveValues['sender_account_name']) ?>">
						</div>
						<div class="giving-field">
							<label for="giving-ref">Transaction reference or narration (optional)</label>
							<input type="text" id="giving-ref" name="reference" maxlength="100" value="<?= giving_esc($giveValues['reference']) ?>">
						</div>
						<div class="giving-field">
							<label for="giving-note">Note (optional)</label>
							<textarea id="giving-note" name="note" rows="4" maxlength="500"><?= giving_esc($giveValues['note']) ?></textarea>
						</div>
						<button type="submit" class="giving-btn giving-btn--solid giving-submit">Confirm My Giving</button>
					</form>
				<?php endif; ?>
				</div>
			</div>
		</div>
	</section>
	<!-- Scripture strip -->
	<section class="giving-verse">
		<div class="giving-verse__inner giving-reveal">
			<span class="giving-verse__icon" aria-hidden="true"><i class="fa-solid fa-quote-left"></i></span>
			<p class="giving-verse__text">&ldquo;Honour the LORD with thy substance, and with the firstfruits of all thine increase:&rdquo;</p>
			<p class="giving-verse__ref">Proverbs 3:9 (KJV)</p>
		</div>
	</section>

	<!-- Questions about giving -->
	<section class="giving-help">
		<div class="giving-help__inner giving-reveal">
			<h2 class="giving-help__title">Questions about giving?</h2>
			<p class="giving-help__text">We are happy to help — reach out any time.</p>
			<div class="giving-help__actions">
				<a class="giving-pill giving-pill--wa" href="<?= giving_esc($whatsappUrl) ?>" target="_blank" rel="noopener">
					<span class="giving-pill__icon"><i class="fa-brands fa-whatsapp"></i></span>
					Chat on WhatsApp
				</a>
				<a class="giving-pill giving-pill--mail" href="mailto:<?= giving_esc($churchEmail) ?>">
					<span class="giving-pill__icon"><i class="fa-regular fa-envelope"></i></span>
					Email Us
				</a>
				<a class="giving-pill giving-pill--visit" href="contact.php">
					<span class="giving-pill__icon"><i class="fa-regular fa-address-book"></i></span>
					Contact Us
				</a>
			</div>
		</div>
	</section>
	<!-- Closing band -->
	<section class="giving-band">
		<div class="giving-band__inner giving-reveal">
			<h2 class="giving-band__title">Thank you for sowing into the Kingdom</h2>
			<div class="giving-band__actions">
				<a href="index.php" class="giving-btn giving-btn--orange">Back to Home</a>
				<a href="contact.php" class="giving-btn giving-btn--outline">Contact Us</a>
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
		// Giving page: lightweight IntersectionObserver scroll-reveal.
		// It only adds a reveal class and can never leave content
		// hidden (validation-error reloads included).
		(function () {
			var page = document.querySelector('.giving-page');
			if (!page) return;
			if (!document.body.classList.contains('giving-anim-ready')) return;
			var targets = page.querySelectorAll('.giving-reveal, .kc118 .kc119');
			if (!targets.length) return;
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
						entry.target.classList.add('is-visible');
						observer.unobserve(entry.target);
					});
				}, { threshold: 0.15, rootMargin: '0px 0px -6% 0px' });
				targets.forEach(function (target) {
					observer.observe(target);
				});
				revealInView();
			} catch (error) {
				document.body.classList.remove('giving-anim-ready');
				return;
			}
			var resizeTimer = null;
			window.addEventListener('resize', function () {
				if (resizeTimer) window.clearTimeout(resizeTimer);
				resizeTimer = window.setTimeout(revealInView, 120);
			});
		})();
	</script>
	<script type="text/javascript">
		// 3D card flip: tap/click plus keyboard (Enter/Space).
		// Hover flip is handled in CSS on hover-capable devices only.
		// Buttons inside the card (e.g. Copy) never trigger a flip.
		(function () {
			var card = document.getElementById('giving-card');
			var flipBtn = document.getElementById('giving-flip-btn');
			if (!card) return;
			function setFlipped(flipped) {
				card.classList.toggle('is-flipped', flipped);
				card.setAttribute('aria-pressed', flipped ? 'true' : 'false');
			}
			card.addEventListener('click', function (event) {
				if (event.target.closest('button')) return;
				setFlipped(!card.classList.contains('is-flipped'));
			});
			card.addEventListener('keydown', function (event) {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					setFlipped(!card.classList.contains('is-flipped'));
				}
			});
			if (flipBtn) {
				flipBtn.addEventListener('click', function () {
					setFlipped(!card.classList.contains('is-flipped'));
				});
			}
		})();
	</script>
	<script type="text/javascript">
		// Copy buttons + toast: clipboard API with a textarea fallback.
		// The toast slides up, then fades after 2 seconds.
		(function () {
			var toast = document.getElementById('giving-toast');
			var buttons = document.querySelectorAll('.giving-page [data-copy]');
			if (!buttons.length) return;
			var hideTimer = null;
			function showToast(message) {
				if (!toast) return;
				toast.textContent = message;
				toast.hidden = false;
				requestAnimationFrame(function () {
					toast.classList.add('is-show');
				});
				if (hideTimer) window.clearTimeout(hideTimer);
				hideTimer = window.setTimeout(function () {
					toast.classList.remove('is-show');
					window.setTimeout(function () {
						toast.hidden = true;
					}, 320);
				}, 2000);
			}
			function fallbackCopy(text, message) {
				var area = document.createElement('textarea');
				area.value = text;
				area.setAttribute('readonly', '');
				area.style.position = 'fixed';
				area.style.left = '-9999px';
				document.body.appendChild(area);
				area.select();
				try {
					document.execCommand('copy');
					showToast(message);
				} catch (error) {
					/* Details stay on screen — nothing else we can do. */
				}
				document.body.removeChild(area);
			}
			buttons.forEach(function (button) {
				button.addEventListener('click', function () {
					var text = button.getAttribute('data-copy') || '';
					var message = button.getAttribute('data-copy-message') || 'Copied';
					if (!text) return;
					if (navigator.clipboard && navigator.clipboard.writeText) {
						navigator.clipboard.writeText(text).then(function () {
							showToast(message);
						}).catch(function () {
							fallbackCopy(text, message);
						});
					} else {
						fallbackCopy(text, message);
					}
				});
			});
		})();
	</script>
	<script type="text/javascript">
		// Giving-type chips: mirror the checked radio onto its pill so the
		// violet-fill styling survives keyboard use too. Cosmetic only.
		(function () {
			var chips = document.querySelectorAll('.giving-page .giving-chip input[type="radio"]');
			chips.forEach(function (input) {
				input.addEventListener('change', function () {
					chips.forEach(function (other) {
						other.parentNode.classList.toggle('is-active', other.checked);
					});
				});
			});
		})();
	</script>
	<script type="text/javascript">
		// "I have made a transfer": smooth-scroll to the confirm form
		// (respects reduced motion — jumps instantly instead).
		(function () {
			var link = document.querySelector('.giving-page .giving-scroll');
			if (!link) return;
			link.addEventListener('click', function (event) {
				var target = document.querySelector(link.getAttribute('data-scroll') || '#confirm');
				if (!target) return;
				event.preventDefault();
				var reduced = window.matchMedia &&
					window.matchMedia('(prefers-reduced-motion: reduce)').matches;
				target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
				if (history.replaceState) history.replaceState(null, '', '#confirm');
			});
		})();
	</script>
</body>
</html>

