<?php
/**
 * location.php — "Find Us" location page (Kingdomite Church International).
 *
 * Linked from the "Find Now!" button on index.php (and the "Find Us" button
 * on form.php) for people who do not know where the church is. No database
 * is needed — every value below is plain church information, kept in one
 * place so it is easy to change later.
 *
 * The header and footer are the existing KCI header/footer markup, reused
 * exactly as it appears on contact.php, aboutus.php, form.php and the rest
 * of the site. Only the content between them is built here.
 */

/* ------------------------------------------------------------------
   Church details — edit here only; the whole page reads these.
   ------------------------------------------------------------------ */
$churchAddress = "Beside Jumbo Close off Ogboso Road, Obaema, Oyigbo, Rivers State, Nigeria";
$mapEmbedUrl = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1521.1747839290097!2d7.191822834333331!3d4.851104171328535!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1068310078ef6fd7%3A0xf3edb863e1dbc4fb!2sObeama%20Market!5e0!3m2!1sen!2sng!4v1791215938413!5m2!1sen!2sng";
$directionsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($churchAddress);
$whatsappUrl = "https://wa.me/2348064979241";
$facebookUrl = "https://www.facebook.com/profile.php?id=100083099004068";
$churchEmail = "dkcifamily@gmail.com";
$churchPhone = "+234 806 497 9241";

function location_esc($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="<?= location_esc('Find Kingdomite Church International — map, directions, service times and contact details for our Oyigbo, Rivers State location.') ?>">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="kci.css?v=21">
	<link rel="stylesheet" href="location.css?v=1">
	<link rel="icon" type="image/png" href="kci_image">
	<title>Find Us | Kingdomite Church International</title>

	<!-- inserting of icon link from cdjns -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="location-page">
	<!-- Location page: prime the scroll-reveal initial state before the
	     first paint. Skipped for reduced-motion users and when
	     IntersectionObserver is unavailable, so content is never hidden. -->
	<script type="text/javascript">
		(function () {
			var reduced = window.matchMedia &&
				window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduced || !('IntersectionObserver' in window)) return;
			document.body.classList.add('location-anim-ready');
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
		<!-- HERO: short dark navy banner -->
		<section class="location-hero">
			<div class="location-hero__inner location-reveal">
				<span class="location-hero__label"><?= location_esc('KINGDOMITE CHURCH INTERNATIONAL') ?></span>
				<h1 class="location-hero__title">Find Us</h1>
				<p class="location-hero__text">
					Wherever you are traveling from, we will be glad to see you
					here &mdash; plan your route below and come as you are.
				</p>
			</div>
		</section>

		<!-- MAP: large full-width rounded Google Map + floating address card -->
		<section class="location-map">
			<div class="location-map__inner">
				<div class="location-map__frame location-reveal">
					<iframe
						src="<?= location_esc($mapEmbedUrl) ?>"
						title="<?= location_esc('Google Map showing Kingdomite Church International, Oyigbo, Rivers State, Nigeria') ?>"
						loading="lazy"
						referrerpolicy="no-referrer-when-downgrade"
						allowfullscreen></iframe>
				</div>
				<div class="location-card location-reveal">
					<span class="location-card__icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
					<h2 class="location-card__name">Kingdomite Church International</h2>
					<p class="location-card__address"><?= location_esc($churchAddress) ?></p>
					<div class="location-card__actions">
						<a class="location-btn location-btn--solid" href="<?= location_esc($directionsUrl) ?>" target="_blank" rel="noopener">
							<i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i> Get Directions
						</a>
						<button type="button" class="location-btn location-btn--ghost" id="location-copy-btn" data-address="<?= location_esc($churchAddress) ?>">
							<i class="fa-regular fa-copy" aria-hidden="true"></i>
							<span data-copy-label aria-live="polite">Copy Address</span>
						</button>
					</div>
				</div>
			</div>
		</section>

		<!-- TWO-CARD ROW: Join Us + Plan Your Visit -->
		<section class="location-info">
			<div class="location-info__grid">
				<article class="location-block location-reveal">
					<div class="location-block__head">
						<span class="location-block__icon" aria-hidden="true"><i class="fa-solid fa-church"></i></span>
						<h2 class="location-block__title">Join Us</h2>
					</div>
					<ul class="location-times">
						<li>
							<span>Sunday Service</span>
							<strong>8:00 AM</strong>
						</li>
						<li>
							<span>Midweek Service</span>
							<strong>Wednesdays, 5:00 PM</strong>
						</li>
					</ul>
				</article>

				<article class="location-block location-block--accent location-reveal">
					<div class="location-block__head">
						<span class="location-block__icon" aria-hidden="true"><i class="fa-solid fa-star"></i></span>
						<h2 class="location-block__title">Plan Your Visit</h2>
					</div>
					<ul class="location-tips">
						<li>
							<i class="fa-regular fa-circle-check" aria-hidden="true"></i>
							<span>Arrive a few minutes early so we can greet you and help you get settled.</span>
						</li>
						<li>
							<i class="fa-regular fa-circle-check" aria-hidden="true"></i>
							<span>Everyone is welcome &mdash; come exactly as you are.</span>
						</li>
						<li>
							<i class="fa-regular fa-circle-check" aria-hidden="true"></i>
							<span>First-time guests can fill our <a href="form.php">welcome form</a> so we know to expect you.</span>
						</li>
					</ul>
				</article>
			</div>
		</section>

		<!-- NEED HELP? strip -->
		<section class="location-help">
			<div class="location-help__inner location-reveal">
				<h2 class="location-help__title">Need help finding us?</h2>
				<div class="location-help__actions">
					<a class="location-pill" href="<?= location_esc($whatsappUrl) ?>" target="_blank" rel="noopener">
						<span class="location-pill__icon location-pill__icon--wa" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></span>
						Chat on WhatsApp
					</a>
					<a class="location-pill" href="mailto:<?= location_esc($churchEmail) ?>" target="_blank" rel="noopener">
						<span class="location-pill__icon location-pill__icon--mail" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
						Email Us
					</a>
					<a class="location-pill" href="<?= location_esc($facebookUrl) ?>" target="_blank" rel="noopener">
						<span class="location-pill__icon location-pill__icon--fb" aria-hidden="true"><i class="fa-brands fa-facebook-f"></i></span>
						Follow on Facebook
					</a>
				</div>
			</div>
		</section>

		<!-- CLOSING BAND -->
		<section class="location-band">
			<div class="location-band__inner location-reveal">
				<h2 class="location-band__title">We can&rsquo;t wait to meet you</h2>
				<div class="location-band__actions">
					<a href="contact.php" class="location-btn location-btn--solid">Contact Us</a>
					<a href="form.php" class="location-btn location-btn--outline">First-Time Guest Form</a>
				</div>
			</div>
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
				<p>Phone / WhatsApp: <a href="<?= location_esc($whatsappUrl) ?>" target="_blank" rel="noopener"><?= location_esc($churchPhone) ?></a></p>
				<p>Email: <a href="mailto:<?= location_esc($churchEmail) ?>"><?= location_esc($churchEmail) ?></a></p>
			</div>

			<div class="kc124">
				<h4>Location</h4>
				<p>The Kingdomite Church International</p>
				<p>Beside Jumbo Close, off Ogboso road, Obeama, Oyigbo, Rivers State, Nigeria</p>
			</div>
		</div>

		<div class="kc125">
			<p>&copy; <?= location_esc(date('Y')) ?> Kingdomite Church International. All Rights Reserved.</p>
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
		// Location page: lightweight IntersectionObserver scroll-reveal.
		// Fully self-contained - it does NOT touch the mobile menu, the
		// dropdowns or the header scroll code above. It only adds a reveal
		// class.
		(function () {
			var page = document.querySelector('.location-page');
			if (!page) return;

			// The pre-paint script (see top of <body>) only primes the page
			// when motion is allowed and IntersectionObserver exists. If it
			// was skipped, nothing is hidden and this can stop here.
			if (!document.body.classList.contains('location-anim-ready')) return;

			var targets = page.querySelectorAll('.location-reveal, .kc118 .kc119');

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
			} catch (error) {
				// Never leave content hidden if anything goes wrong.
				document.body.classList.remove('location-anim-ready');
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
		// "Copy Address": copy the church address to the clipboard and
		// briefly confirm it on the button itself.
		(function () {
			var button = document.getElementById('location-copy-btn');
			if (!button) return;

			var label = button.querySelector('[data-copy-label]');
			if (!label) return;

			var address = button.getAttribute('data-address') || '';
			var resetTimer = null;

			function confirmCopied() {
				label.textContent = 'Copied!';
				if (resetTimer) window.clearTimeout(resetTimer);
				resetTimer = window.setTimeout(function () {
					label.textContent = 'Copy Address';
				}, 1800);
			}

			function fallbackCopy() {
				// Older / non-secure contexts: temporary textarea + execCommand.
				var area = document.createElement('textarea');
				area.value = address;
				area.setAttribute('readonly', '');
				area.style.position = 'fixed';
				area.style.left = '-9999px';
				document.body.appendChild(area);
				area.select();
				try {
					document.execCommand('copy');
					confirmCopied();
				} catch (error) {
					/* Nothing else we can do — the address stays on screen. */
				}
				document.body.removeChild(area);
			}

			button.addEventListener('click', function () {
				if (!address) return;
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(address).then(confirmCopied).catch(fallbackCopy);
				} else {
					fallbackCopy();
				}
			});
		})();
	</script>
</body>
</html>



