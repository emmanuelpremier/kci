<?php
/**
 * quotes.php — Quotes page (Kingdomite Church International).
 *
 * Reached from the "View More" button in the Recent Quotes section of
 * index.php. No database: every card below comes from the two arrays in
 * this file, so adding content later means adding one array element.
 *
 * The header and footer are the existing KCI header/footer markup, reused
 * exactly as it appears on location.php (which carries the current church
 * contact details). Only the content between them is built here.
 *
 * ADDING CONTENT
 *   $quoteImages  -> add a new ['src' => 'kci_image/name.webp',
 *                     'alt' => 'Short description'] line for another image card.
 *                     Drop the file in /kci_image first.
 *   $scriptureQuotes -> add a new ['text' => 'The verse wording',
 *                     'ref' => 'Reference'] line for another text card.
 */

/* Image cards — quote graphics, 4 across on desktop. */
$quoteImages = [
	['src' => 'kci_image/im4.webp', 'alt' => 'Recent quote graphic'],
	['src' => 'kci_image/im7.webp', 'alt' => 'Recent quote graphic'],
	['src' => 'kci_image/im6.webp', 'alt' => 'Recent quote graphic'],
	['src' => 'kci_image/im5.webp', 'alt' => 'Recent quote graphic'],
];

/* Scripture cards — shown as text, 3 across on desktop. */
$scriptureQuotes = [
	['text' => 'But seek ye first the kingdom of God, and his righteousness; and all these things shall be added unto you.', 'ref' => 'Matthew 6:33'],
	['text' => 'Trust in the LORD with all thine heart; and lean not unto thine own understanding.', 'ref' => 'Proverbs 3:5'],
	['text' => 'I can do all things through Christ which strengtheneth me.', 'ref' => 'Philippians 4:13'],
	['text' => 'But they that wait upon the LORD shall renew their strength; they shall mount up with wings as eagles; they shall run, and not be weary; and they shall walk, and not faint.', 'ref' => 'Isaiah 40:31'],
	['text' => 'God is our refuge and strength, a very present help in trouble.', 'ref' => 'Psalm 46:1'],
	['text' => 'Thy word is a lamp unto my feet, and a light unto my path.', 'ref' => 'Psalm 119:105'],
	['text' => 'And we know that all things work together for good to them that love God, to them who are the called according to his purpose.', 'ref' => 'Romans 8:28'],
	['text' => 'Have not I commanded thee? Be strong and of a good courage; be not afraid, neither be thou dismayed: for the LORD thy God is with thee whithersoever thou goest.', 'ref' => 'Joshua 1:9'],
	['text' => 'Come unto me, all ye that labour and are heavy laden, and I will give you rest.', 'ref' => 'Matthew 11:28'],
	['text' => 'Now faith is the substance of things hoped for, the evidence of things not seen.', 'ref' => 'Hebrews 11:1'],
	['text' => 'Rejoice evermore. Pray without ceasing. In every thing give thanks: for this is the will of God in Christ Jesus concerning you.', 'ref' => '1 Thessalonians 5:16-18'],
	['text' => 'The LORD is my shepherd; I shall not want.', 'ref' => 'Psalm 23:1'],
];

/* Church details — the same values location.php uses. */
$whatsappUrl = "https://wa.me/2348064979241";
$churchEmail = "dkcifamily@gmail.com";
$churchPhone = "+234 806 497 9241";
/* This page's own address, appended to every WhatsApp share. */
$quotesPageUrl = "http://localhost/kci/quotes.php";

function quotes_esc($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="<?= quotes_esc('Words of faith, hope and encouragement from Kingdomite Church International — recent quote graphics and favourite scriptures.') ?>">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="kci.css?v=21">
	<link rel="stylesheet" type="text/css" href="mobilefix.css?v=1">
	<link rel="stylesheet" href="quotes.css?v=1">
	<link rel="icon" type="image/png" href="kci_image">
	<title>Quotes | Kingdomite Church International</title>

	<!-- inserting of icon link from cdjns -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="quotes-page">
	<!-- Quotes page: prime the scroll-reveal initial state before the
	     first paint. Skipped for reduced-motion users and when
	     IntersectionObserver is unavailable, so content is never hidden. -->
	<script type="text/javascript">
		(function () {
			var reduced = window.matchMedia &&
				window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduced || !('IntersectionObserver' in window)) return;
			document.body.classList.add('quotes-anim-ready');
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
		<!-- HERO: short dark navy banner, same style as location.php -->
		<section class="quotes-hero">
			<div class="quotes-hero__inner quotes-reveal">
				<span class="quotes-hero__label"><?= quotes_esc('KINGDOMITE CHURCH INTERNATIONAL') ?></span>
				<h1 class="quotes-hero__title">Quotes</h1>
				<p class="quotes-hero__text">
					Words of faith, hope and encouragement to carry through your week.
				</p>
			</div>
		</section>
		<!-- SECTION 1: recent quote images, same heading block as the home page -->
		<section class="quotes-section quotes-section--images">
			<div class="quotes-section__inner">
				<div class="quotes-head quotes-reveal">
					<div class="quotes-head__label"><p>RECENT</p></div>
					<h2>QUOTES</h2>
					<span class="quotes-head__rule" aria-hidden="true"></span>
				</div>

				<div class="quotes-grid quotes-grid--images">
					<?php foreach ($quoteImages as $i => $image): ?>
						<div class="quotes-card quotes-card--image quotes-reveal" style="--quotes-delay: <?= (int)$i * 0.07 ?>s">
							<button type="button" class="quotes-card__btn" data-quotes-lightbox="<?= (int)$i ?>" aria-label="<?= quotes_esc('View ' . $image['alt'] . ' large') ?>">
								<img src="<?= quotes_esc($image['src']) ?>" alt="<?= quotes_esc($image['alt']) ?>">
							</button>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- SECTION 2: scripture text cards -->
		<section class="quotes-section quotes-section--scripture">
			<div class="quotes-section__inner">
				<div class="quotes-head quotes-reveal">
					<div class="quotes-head__label"><p>SCRIPTURE</p></div>
					<h2>Words to Live By</h2>
					<span class="quotes-head__rule" aria-hidden="true"></span>
					<span class="quotes-head__version">KJV</span>
				</div>

				<div class="quotes-grid quotes-grid--scripture">
					<?php foreach ($scriptureQuotes as $i => $verse):
						$shareText = $verse['text'] . ' — ' . $verse['ref'] . ' (' . $quotesPageUrl . ')';
					?>
						<article class="quotes-card quotes-card--verse quotes-reveal" style="--quotes-delay: <?= (int)($i % 3) * 0.07 ?>s">
							<span class="quotes-verse__icon" aria-hidden="true"><i class="fa-solid fa-quote-left"></i></span>
							<p class="quotes-verse__text"><?= quotes_esc($verse['text']) ?></p>
							<div class="quotes-verse__foot">
								<span class="quotes-verse__ref"><?= quotes_esc($verse['ref']) ?></span>
								<a class="quotes-verse__share"
								   href="https://wa.me/?text=<?= quotes_esc(rawurlencode($shareText)) ?>"
								   target="_blank" rel="noopener"
								   aria-label="<?= quotes_esc('Share ' . $verse['ref'] . ' on WhatsApp') ?>">
									<i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- CLOSING BAND: same band as location.php -->
		<section class="quotes-band">
			<div class="quotes-band__inner quotes-reveal">
				<h2 class="quotes-band__title">Be encouraged. Come worship with us.</h2>
				<div class="quotes-band__actions">
					<a href="location.php" class="quotes-btn quotes-btn--solid">Find Us</a>
					<a href="contact.php" class="quotes-btn quotes-btn--outline">Contact Us</a>
				</div>
			</div>
		</section>
	</main>

	<!-- LIGHTBOX: dark overlay with the large image, driven by plain JS -->
	<div class="quotes-lightbox" id="quotes-lightbox" hidden>
		<div class="quotes-lightbox__backdrop" data-quotes-close></div>
		<div class="quotes-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Quote image viewer">
			<button type="button" class="quotes-lightbox__close" data-quotes-close aria-label="Close image viewer">
				<i class="fa-solid fa-xmark" aria-hidden="true"></i>
			</button>
			<button type="button" class="quotes-lightbox__nav quotes-lightbox__nav--prev" data-quotes-prev aria-label="Previous image">
				<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
			</button>
			<img class="quotes-lightbox__img" id="quotes-lightbox-img" src="" alt="">
			<button type="button" class="quotes-lightbox__nav quotes-lightbox__nav--next" data-quotes-next aria-label="Next image">
				<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
			</button>
			<p class="quotes-lightbox__count" id="quotes-lightbox-count" aria-live="polite"></p>
		</div>
	</div>
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
		// Quotes page: lightweight IntersectionObserver scroll-reveal.
		// Fully self-contained - it does NOT touch the mobile menu, the
		// dropdowns, the header scroll code or the lightbox below. It
		// only adds a reveal class.
		(function () {
			var page = document.querySelector('.quotes-page');
			if (!page) return;

			// The pre-paint script (see top of <body>) only primes the page
			// when motion is allowed and IntersectionObserver exists. If it
			// was skipped, nothing is hidden and this can stop here.
			if (!document.body.classList.contains('quotes-anim-ready')) return;

			var targets = page.querySelectorAll('.quotes-reveal, .kc118 .kc119');

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
				document.body.classList.remove('quotes-anim-ready');
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
		// Image lightbox — plain JavaScript, no libraries.
		// Esc closes, Left/Right navigate, clicking the dark backdrop
		// closes, and focus returns to the card that opened it.
		(function () {
			var box = document.getElementById('quotes-lightbox');
			var img = document.getElementById('quotes-lightbox-img');
			var count = document.getElementById('quotes-lightbox-count');
			if (!box || !img) return;

			var cards = document.querySelectorAll('.quotes-page [data-quotes-lightbox]');
			if (!cards.length) return;

			var total = cards.length;
			var current = 0;
			var opener = null;

			function show(index) {
				// Wrap around at both ends.
				current = (index + total) % total;
				var source = cards[current].querySelector('img');
				if (!source) return;
				img.src = source.getAttribute('src');
				img.alt = source.getAttribute('alt') || '';
				if (count) count.textContent = (current + 1) + ' / ' + total;
			}

			function open(index) {
				opener = cards[index] || null;
				show(index);
				box.hidden = false;
				document.body.style.overflow = 'hidden';
				// Start focus somewhere useful inside the dialog.
				var closeBtn = box.querySelector('[data-quotes-close]');
				if (closeBtn) closeBtn.focus();
			}

			function close() {
				if (box.hidden) return;
				box.hidden = true;
				document.body.style.overflow = '';
				img.src = '';
				// Focus returns to the card that opened the lightbox.
				if (opener) opener.focus();
				opener = null;
			}

			cards.forEach(function (card) {
				card.addEventListener('click', function () {
					open(parseInt(card.getAttribute('data-quotes-lightbox'), 10) || 0);
				});
			});

			box.querySelectorAll('[data-quotes-close]').forEach(function (el) {
				el.addEventListener('click', close);
			});

			var prev = box.querySelector('[data-quotes-prev]');
			var next = box.querySelector('[data-quotes-next]');
			if (prev) prev.addEventListener('click', function () { show(current - 1); });
			if (next) next.addEventListener('click', function () { show(current + 1); });

			document.addEventListener('keydown', function (e) {
				if (box.hidden) return;
				if (e.key === 'Escape') { close(); }
				else if (e.key === 'ArrowLeft') { show(current - 1); }
				else if (e.key === 'ArrowRight') { show(current + 1); }
				else if (e.key === 'Tab') {
					// Keep Tab inside the dialog while it is open.
					var focusables = box.querySelectorAll('button');
					if (!focusables.length) return;
					var first = focusables[0];
					var last = focusables[focusables.length - 1];
					if (e.shiftKey && document.activeElement === first) {
						e.preventDefault();
						last.focus();
					} else if (!e.shiftKey && document.activeElement === last) {
						e.preventDefault();
						first.focus();
					}
				}
			});
		})();
	</script>
</body>
</html>
