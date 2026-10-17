<?php
/* ------------------------------------------------------------------
   KCI — Social links (Home page)
   VERIFIED: the values below are the church's real, confirmed links.
      · Pastor David Vincent's personal Facebook profile -> $PASTOR_FACEBOOK_URL
      · Kingdomite Church Int'l official Facebook page    -> $CHURCH_FACEBOOK_URL
      · Pastor David Vincent's WhatsApp chat link         -> $PASTOR_WHATSAPP_URL
   ------------------------------------------------------------------ */
$PASTOR_FACEBOOK_URL = 'https://www.facebook.com/profile.php?id=100083099004068';
$CHURCH_FACEBOOK_URL = 'https://www.facebook.com/profile.php?id=100083099004068';
$PASTOR_WHATSAPP_URL = 'https://wa.me/2348064979241';

require __DIR__ . '/kci_db.php';
require __DIR__ . '/kci_settings.php';

$homeDefaults = array(
    'pastor_image' => 'kci_image/im8.webp',
    'pastor_bio' => "I'm David Vincent, Lead Pastor of Kingdomite Church International. I personally invite you to our official website, I would love to hear from and pray with you. Get in touch with me via any of personal social media handles to stay connected with me, Thank you and remain blessed.",
    'quote_images' => array(
        'kci_image/im4.webp',
        'kci_image/im7.webp',
        'kci_image/im6.webp',
        'kci_image/im5.webp',
    ),
    'quote_texts' => array(
        'During the embers of glory program, i wrote my prayer points as my pastor requested. all together i ended up writing four, my last request was very urgent being the fourth one. i asked GOD to provide money for me so i will be able to pay my childrens school fees which was already an disturbing issue because the term will soon start. So during the program i recieve a call that i least expected from and it solve the disturing issue that was on ground, before the embers of glory program end. So i just want to thank God for what he has done may his name be glorified in Jesus Name Amen.',
        'Praise the Lord Hallelujah........ My elder sister has this particular pain in her stomach and it has been for a very long time, recently finding out that it is fibroid so anytime i make a video call with her she is always crying, then i meet my pastor because she was about to have an operation the following day and she was scared so i told my pator to pray and my pastor said she will come out of the operation room without any harm and i say amen. The following morning my elder sister was the first person to call me how am i doing, i was suprised because this is the same person that anytime we talk on phone she is always crying, and she said the operation was successful.',
        'I have been asking God for to make my daughter pass her exams that she wont rewrite her jamb exam twice, and she will also pass her post utme exam and to God be the glory he answered the prayer. I am so happy because i have been struggling to pay her fees right from waec and God has been so faithful may his name be praised forever in Jesus name Amen.',
    ),
    'quote_authors' => array(
        'Pst. Mrs. Lucy David',
        'Bro. Prince',
        'Mrs. Emmanuel',
    ),
);

/* Overlay the admin editor's saved site_settings on top of the defaults. */
$homePastorImage = kci_setting($conn, 'home_pastor_image', $homeDefaults['pastor_image']);
$homePastorBio = kci_setting($conn, 'home_pastor_bio', $homeDefaults['pastor_bio']);
for ($i = 1; $i <= 4; $i++) {
    $homeQuoteImages[$i - 1] = kci_setting($conn, 'home_quote_image_' . $i, $homeDefaults['quote_images'][$i - 1]);
}
for ($i = 1; $i <= 3; $i++) {
    $homeQuoteTexts[$i - 1] = kci_setting($conn, 'home_quote_text_' . $i, $homeDefaults['quote_texts'][$i - 1]);
    $homeQuoteAuthors[$i - 1] = kci_setting($conn, 'home_quote_author_' . $i, $homeDefaults['quote_authors'][$i - 1]);
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Kingdomite Church Int'l">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="preload" as="font" href="https://fonts.gstatic.com/s/poppins/v20/pxiEyp8kv8JHgFVrJJfecg.ttf" crossorigin="anonymous">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'">
	<link rel="stylesheet" type="text/css" href="kci.css?v=26">
	<link rel="stylesheet" type="text/css" href="mobilefix.css?v=3">
	<link rel="icon" type="image/png" href="kci_image">
	<link rel="preload" as="image" href="kci_image/im2.webp" fetchpriority="high">
	<title>Kingdomite Church Int'l</title>
</head>
<body class="home-page"
	data-reveal-ready="home-anim-ready"
	data-reveal-targets=".home-page .home-reveal, .home-page .kc118 .kc119"
	data-reveal-margin="0px 0px -6% 0px"
	data-reveal-safety="idle">
	<!-- Shared reveal engine (kci-reveal.js): primes the scroll-reveal
	     initial state before the first paint, then drives the single
	     IntersectionObserver at the bottom of the page. Skipped for
	     reduced-motion users and when IntersectionObserver is
	     unavailable, so content is never left hidden. -->
	<script src="kci-reveal.js" defer></script>

	<!--header-->
	<?php include 'header.php'; ?>

	<div class="kc5">
		<h2>Welcome to the<span class="spe"><br>Kingdomite Church International</span></h2>
		<h4>A Place of destiny discovery</h4>
		<p>We are glad you're here!</p>
	</div>
	<div class="kc6">
		<div class="kc7 home-reveal">
			<div class="kc9">
				<span class="kc9-icon" aria-hidden="true"><i class="fa-solid fa-hands-praying"></i></span>
				<h2>WELCOME</h2>
				<p class="kc7-title">TO THE FAMILY!</p>
			</div>
		</div>
		<div class="kc8 home-reveal">
			<p>Experience Love, Experience the Life of God, Experience direction for purposeful living! Welcome to Kingdomite Church International, a church where people genuinely encounter God.</p>
		</div>
	</div>
	<div class="kc10">
		<div class="kc11 home-reveal">
			<span class="kc11-icon" aria-hidden="true"><i class="fa-solid fa-hand-sparkles"></i></span>
			<h2>Is it your first time worshiping with us? If it is, kindly fill out our</h2>
			<a href="form.php" class="kc11-btn">Form Now!</a>
		</div>
		<div class="kc12 home-reveal">
			<span class="kc12-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
			<h2>Don't Know our Church?</h2>
			<a href="location.php" class="kc12-btn">Find Now!</a>
		</div>
	</div>
	<div class="kc13">
		<div class="kc18 home-reveal">
			<div class="kc19"><p>RECENT</p></div>
			<h2>QUOTES</h2>
			<span class="kc18-rule" aria-hidden="true"></span>
		</div>
		<div class="kc21">
			<div class="kc14 home-reveal">
				<img src="<?php echo htmlspecialchars($homeQuoteImages[0], ENT_QUOTES, 'UTF-8'); ?>" alt="Recent quote graphic" width="1280" height="1280" loading="lazy" decoding="async">
			</div>
			<div class="kc15 home-reveal">
				<img src="<?php echo htmlspecialchars($homeQuoteImages[1], ENT_QUOTES, 'UTF-8'); ?>" alt="Recent quote graphic" width="1280" height="1280" loading="lazy" decoding="async">
			</div>
			<div class="kc16 home-reveal">
				<img src="<?php echo htmlspecialchars($homeQuoteImages[2], ENT_QUOTES, 'UTF-8'); ?>" alt="Recent quote graphic" width="1280" height="1280" loading="lazy" decoding="async">
			</div>
			<div class="kc17 home-reveal">
				<img src="<?php echo htmlspecialchars($homeQuoteImages[3], ENT_QUOTES, 'UTF-8'); ?>" alt="Recent quote graphic" width="1200" height="1200" loading="lazy" decoding="async">
			</div>
			<div class="kc20 home-reveal">
				<a href="quotes.php">View More</a>
			</div>
		</div>
	</div>
	<section id="main">
		<div class="kc22">
			<div class="kc23 home-reveal">
				<span class="kc23-frame" aria-hidden="true"></span>
				<img src="<?php echo htmlspecialchars($homePastorImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Pst. David Vincent, Lead Pastor of Kingdomite Church International" width="738" height="1080" loading="lazy" decoding="async">
			</div>
			<div class="kc24 home-reveal">
				<div class="kc25">
					<div class="kc26"><h2>CONNECT WITH</h2></div>
					<h4>OUR LEAD PASTOR</h4>
				</div>
				<div class="kc27">
					<p><?php echo htmlspecialchars($homePastorBio, ENT_QUOTES, 'UTF-8'); ?></p>
				</div>
				<div class="kc28">
					<a href="<?= $PASTOR_FACEBOOK_URL ?>" class="kc28-link kc28-link--fb" aria-label="Follow Pastor David Vincent on Facebook" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f"></i></a>
					<a href="<?= $PASTOR_WHATSAPP_URL ?>" class="kc28-link kc28-link--wa" aria-label="Message Pastor David Vincent on WhatsApp" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i></a>
				</div>
			</div>
		</div>
	</section>
	<div class="kc29">
		<div class="kc30">
			<div class="kc31 home-reveal">
				<div class="kc32"><h2>CONNECT</h2><p>________________</p></div>
				<h3>WITH US</h3>
			</div>
			<div class="testimoney home-reveal">
				<div class="testimoney-slide active">
					<i class="fa-solid fa-quote-left quote-icon"></i>
					<p><?php echo htmlspecialchars($homeQuoteTexts[0], ENT_QUOTES, 'UTF-8'); ?></p>
					<span class="testimonial-author">— <?php echo htmlspecialchars($homeQuoteAuthors[0], ENT_QUOTES, 'UTF-8'); ?></span>
					<i class="fa-solid fa-quote-right quote-icon"></i>
				</div>
				<div class="testimoney-slide">
					<i class="fa-solid fa-quote-left quote-icon"></i>
					<p><?php echo htmlspecialchars($homeQuoteTexts[1], ENT_QUOTES, 'UTF-8'); ?></p>
					<span class="testimonial-author">— <?php echo htmlspecialchars($homeQuoteAuthors[1], ENT_QUOTES, 'UTF-8'); ?></span>
					<i class="fa-solid fa-quote-right quote-icon"></i>
				</div>
				<div class="testimoney-slide">
					<i class="fa-solid fa-quote-left quote-icon"></i>
					<p><?php echo htmlspecialchars($homeQuoteTexts[2], ENT_QUOTES, 'UTF-8'); ?></p>
					<span class="testimonial-author">— <?php echo htmlspecialchars($homeQuoteAuthors[2], ENT_QUOTES, 'UTF-8'); ?></span>
					<i class="fa-solid fa-quote-right quote-icon"></i>
				</div>
				<div class="testimoney-dots">
					<span class="dot active" data-index="0"></span>
					<span class="dot" data-index="1"></span>
					<span class="dot" data-index="2"></span>
				</div>
			</div>
		</div>
	</div>
	<div class="kc33">
		<a href="<?= $CHURCH_FACEBOOK_URL ?>" class="fb-link" aria-label="Like and follow Kingdomite Church International on Facebook" target="_blank" rel="noopener">
			<div class="kc34">
				<span class="kc35" aria-hidden="true"><i class="fa-brands fa-facebook-f"></i></span>
				<div class="kc36">
					<h2>FACEBOOK</h2>
					<p>Like &amp; Follow us on Facebook</p>
					<div class="kc-fb-icons">
						<span class="social-icon like" id="likeIcon" aria-hidden="true"><i class="fa-solid fa-thumbs-up"></i></span>
						<span class="social-icon share" id="shareIcon" aria-hidden="true"><i class="fa-solid fa-share-nodes"></i></span>
						<span class="social-icon follow" id="followIcon" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
					</div>
				</div>
				<span class="kc37" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
			</div>
		</a>
	</div>
	<script>
		/* Subtle one-shot entrance for the Facebook card (.kc33).
		   A dedicated IntersectionObserver reveals the card when the
		   section scrolls into view -- no polling, no 6s restart, and
		   it fully respects prefers-reduced-motion. */
		(function () {
			var fbSection = document.querySelector('.kc33');
			if (!fbSection) return;

			var reduceMotion = window.matchMedia &&
				window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			var canObserve = 'IntersectionObserver' in window;

			/* No observer support, or the user prefers reduced motion:
			   leave the card fully visible (nothing to animate). */
			if (reduceMotion || !canObserve) return;

			fbSection.classList.add('kc33-anim');

			var observer = new IntersectionObserver(function (entries, obs) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('is-visible');
						obs.unobserve(entry.target); /* run once, never restart */
					}
				});
			}, { threshold: 0.2 });

			observer.observe(fbSection);
		})();
	</script>

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

		// Testimonial slider
		(function() {
			var slides = document.querySelectorAll('.testimoney-slide');
			var dots = document.querySelectorAll('.testimoney-dots .dot');
			var currentSlide = 0;
			var totalSlides = slides.length;
			var intervalTime = 5000;
			var slideInterval;

			function showSlide(index) {
				slides.forEach(function(slide) {
					slide.classList.remove('active', 'exit-left');
				});
				dots.forEach(function(dot) {
					dot.classList.remove('active');
				});
				if (slides[currentSlide] && currentSlide !== index) {
					slides[currentSlide].classList.add('exit-left');
				}
				slides[index].classList.add('active');
				dots[index].classList.add('active');
				currentSlide = index;
			}

			function nextSlide() {
				var next = (currentSlide + 1) % totalSlides;
				showSlide(next);
			}

			function startAutoSlide() {
				slideInterval = setInterval(nextSlide, intervalTime);
			}

			function stopAutoSlide() {
				clearInterval(slideInterval);
			}

			showSlide(0);
			startAutoSlide();

			dots.forEach(function(dot) {
				dot.addEventListener('click', function() {
					var index = parseInt(this.getAttribute('data-index'));
					showSlide(index);
					stopAutoSlide();
					startAutoSlide();
				});
			});

			var testimonialContainer = document.querySelector('.testimoney');
			if (testimonialContainer) {
				testimonialContainer.addEventListener('mouseenter', stopAutoSlide);
				testimonialContainer.addEventListener('mouseleave', startAutoSlide);
			}
		})();
	</script>
	<!-- The scroll-reveal that used to live here now comes from the shared
	     kci-reveal.js engine included right after <body> (see the
	     data-reveal-* attributes on <body> for its configuration). -->
</body>
</html>
