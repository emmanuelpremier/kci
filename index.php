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
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Kingdomite Church Int'l">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
	<link rel="stylesheet" type="text/css" href="kci.css?v=20">
	<link rel="icon" type="image/png" href="kci_image">
	<title>Kingdomite Church Int'l</title>
</head>
<body class="home-page">
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

	<div class="kc5">
		<h2>Welcome to the<span class="spe"><br>Kingdomite Church International</span></h2>
		<h4>A Place of destiny discovery</h4>
		<p>We are glad you're here!</p>
	</div>
	<div class="kc6 reveal active">
		<div class="kc7">
			<div class="kc9">
				<span class="kc9-icon" aria-hidden="true"><i class="fa-solid fa-hands-praying"></i></span>
				<h2>WELCOME</h2>
				<p class="kc7-title">TO THE FAMILY!</p>
			</div>
		</div>
		<div class="kc8">
			<p>Experience Love, Experience the Life of God, Experience direction for purposeful living! Welcome to Kingdomite Church International, a church where people genuinely encounter God.</p>
		</div>
	</div>
	<script type="text/javascript">
		window.addEventListener('scroll', reveal)

			function reveal(){
				var reveals = document.querySelectorAll('.reveal');

				for (var i = 0; i < reveals.length; i++) {
					var windowheight = window.innerHeight;
					var revealTop = reveals[i].getBoundingClientRect().top;
					var revealpoint = 120;

					if (revealTop < windowheight - revealpoint) {
						reveals[i].classList.add('active');
					}
					else{
						reveals[i].classList.remove('active');
					}
				}
			}
	</script>
	<div class="kc10">
		<div class="kc11">
			<span class="kc11-icon" aria-hidden="true"><i class="fa-solid fa-hand-sparkles"></i></span>
			<h2>Is it your first time worshiping with us? If it is, kindly fill out our</h2>
			<a href="form.php" class="kc11-btn">Form Now!</a>
		</div>
		<div class="kc12">
			<span class="kc12-icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
			<h2>Don't Know our Church?</h2>
			<a href="location.php" class="kc12-btn">Find Now!</a>
		</div>
	</div>
	<div class="kc13">
		<div class="kc18 real action">
			<div class="kc19"><p>RECENT</p></div>
			<h2>QUOTES</h2>
			<span class="kc18-rule" aria-hidden="true"></span>
		</div>
		<div class="kc21 all in">
			<div class="kc14">
				<img src="kci_image/im4.webp" alt="Recent quote graphic">
			</div>
			<div class="kc15">
				<img src="kci_image/im7.webp" alt="Recent quote graphic">
			</div>
			<div class="kc16">
				<img src="kci_image/im6.webp" alt="Recent quote graphic">
			</div>
			<div class="kc17">
				<img src="kci_image/im5.webp" alt="Recent quote graphic">
			</div>
			<div class="kc20">
				<a href="quotes.php">View More</a>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		window.addEventListener('scroll', real)

		function real() {
			var reveals = document.querySelectorAll('.real');

			for (var i = 0; i < reveals.length; i++){
				var windowheight = window.innerHeight;
				var revealTop = reveals[i].getBoundingClientRect().top;
				var revealpoint = 160;

				if (revealTop < windowheight - revealpoint) {
					reveals[i].classList.add('action');
				}
				else{
					reveals[i].classList.remove('action');
				}
			}
		}
	</script>
	<script type="text/javascript">
		window.addEventListener('scroll', all)

		function all() {
			var reveals = document.querySelectorAll('.all');

			for (var i = 0; i < reveals.length; i++){
				var windowheight = window.innerHeight;
				var revealTop = reveals[i].getBoundingClientRect().top;
				var revealpoint = 120;

				if (revealTop < windowheight - revealpoint) {
					reveals[i].classList.add('in');
				}
				else{
					reveals[i].classList.remove('in');
				}
			}
		}
	</script>
	<section id="main">
		<div class="kc22 reveal active">
			<div class="kc23">
				<span class="kc23-frame" aria-hidden="true"></span>
				<img src="kci_image/im8.webp" alt="Pst. David Vincent, Lead Pastor of Kingdomite Church International">
			</div>
			<div class="kc24">
				<div class="kc25">
					<div class="kc26"><h2>CONNECT WITH</h2></div>
					<h4>OUR LEAD PASTOR</h4>
				</div>
				<div class="kc27">
					<p>I'm David Vincent, Lead Pastor of Kingdomite Church International. I personally invite you to our official website, I would love to hear from and pray with you. Get in touch with me via any of personal social media handles to stay connected with me, Thank you and remain blessed.</p>
				</div>
				<div class="kc28">
					<a href="<?= $PASTOR_FACEBOOK_URL ?>" class="kc28-link kc28-link--fb" aria-label="Follow Pastor David Vincent on Facebook" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f"></i></a>
					<a href="<?= $PASTOR_WHATSAPP_URL ?>" class="kc28-link kc28-link--wa" aria-label="Message Pastor David Vincent on WhatsApp" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i></a>
				</div>
			</div>
		</div>
	</section>
	<script type="text/javascript">
		window.addEventListener('scroll', reveal);
		function reveal(){
			var reveals = document.querySelectorAll('.reveal');
			for (var i = 0; i < reveals.length; i++) {
				var windowheight = window.innerHeight;
				var revealTop = reveals[i].getBoundingClientRect().top;
				var revealpoint = 150;
				if (revealTop < windowheight - revealpoint) {
					reveals[i].classList.add('active');
				}
				else{
					reveals[i].classList.remove('active');
				}
			}	
		}
	</script>
	<div class="kc29">
		<div class="kc30">
			<div class="kc31">
				<div class="kc32"><h2>CONNECT</h2><p>________________</p></div>
				<h3>WITH US</h3>
			</div>
			<div class="testimoney">
				<div class="testimoney-slide active">
					<i class="fa-solid fa-quote-left quote-icon"></i>
					<p>During the embers of glory program, i wrote my prayer points as my pastor requested. all together i ended up writing four, my last request was very urgent being the fourth one. i asked GOD to provide money for me so i will be able to pay my childrens school fees which was already an disturbing issue because the term will soon start. So during the program i recieve a call that i least expected from and it solve the disturing issue that was on ground, before the embers of glory program end. So i just want to thank God for what he has done may his name be glorified in Jesus Name Amen.</p>
					<span class="testimonial-author">— Pst. Mrs. Lucy David</span>
					<i class="fa-solid fa-quote-right quote-icon"></i>
				</div>
				<div class="testimoney-slide">
					<i class="fa-solid fa-quote-left quote-icon"></i>
					<p>Praise the Lord Hallelujah........ My elder sister has this particular pain in her stomach and it has been for a very long time, recently finding out that it is fibroid so anytime i make a video call with her she is always crying, then i meet my pastor because she was about to have an operation the following day and she was scared so i told my pator to pray and my pastor said she will come out of the operation room without any harm and i say amen. The following morning my elder sister was the first person to call me how am i doing, i was suprised because this is the same person that anytime we talk on phone she is always crying, and she said the operation was successful.</p>
					<span class="testimonial-author">— Bro. Prince</span>
					<i class="fa-solid fa-quote-right quote-icon"></i>
				</div>
				<div class="testimoney-slide">
					<i class="fa-solid fa-quote-left quote-icon"></i>
					<p>I have been asking God for to make my daughter pass her exams that she wont rewrite her jamb exam twice, and she will also pass her post utme exam and to God be the glory he answered the prayer. I am so happy because i have been struggling to pay her fees right from waec and God has been so faithful may his name be praised forever in Jesus name Amen.</p>
					<span class="testimonial-author">— Mrs. Emmanuel</span>
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
            <p>The Kingdomite Church International</p>
            <p>Beside Jumbo Close, off Ogboso road, Obeama, Oyigbo, Rivers State, Nigeria</p>
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
</body>
</html>
