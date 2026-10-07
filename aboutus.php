<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Learn why Kingdomite Church International was started in Oyigbo and what we believe, how we worship and how we serve.">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="kci.css">
	<link rel="stylesheet" type="text/css" href="mobilefix.css?v=1">
	<link rel="stylesheet" type="text/css" href="cleanup.css">
	<link rel="icon" type="image/png" href="kci_image">
	<title>About Us - Kingdomite Church Int'l</title>

	<!-- inserting of icon link from cdjns -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>
<body class="about-page">
	<!-- About page: prime the scroll-reveal initial state before the first
	     paint. Skipped for reduced-motion users and when IntersectionObserver
	     is unavailable, so the content is never left hidden. -->
	<script type="text/javascript">
		(function () {
			var reduced = window.matchMedia &&
				window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduced || !('IntersectionObserver' in window)) return;
			document.body.classList.add('about-anim-ready');
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

	<!-- hero section -->
	<section class="kc42">
		<div class="kc43">
			<span class="kc44">ABOUT US</span>
			<h1>Why We Started<br>This Church</h1>
			<p>We started Kingdomite Church International to build Christ-focused values in Oyigbo, for married and single people, for families, and for everyone looking for a home.</p>
			<a href="#first" class="btn-primary">Explore <span>?</span></a>
		</div>
	</section>

	<!-- Contrast section -->

	<section class="kc45" id="first">
		<div class="kc46">
			<div class="kc47">
				<div class="kc48">
					<span class="kc49">WHO WE ARE</span>
					<h2>A Spirit-Filled Family</h2>
					<p>At Kingdomite Church, we are a spirit-filled family. Your call, your message, your connection and your care describe our vision and mission: a heartfelt church built on the Word.</p>
				</div>
				<div class="kc50">
					<img src="kci_image/img20.webp">
					<img src="kci_image/img17.webp">
				</div>
			</div>

			<!-- Card grid style -->

			<div class="kc51">
				<div class="kc52">
					<div>
						<i class="fa-regular fa-star kc53" aria-hidden="true"></i>
						<h3>You're Expected</h3>
					</div>
					<span class="kc54">Welcome</span>
					<p><i class="fa-regular fa-circle-check" aria-hidden="true"></i> Everyone is welcome here. Come as you are and become part of our community.</p>
				</div>
				<div class="kc52">
					<div>
						<i class="fa-regular fa-circle-check kc53" aria-hidden="true"></i>
						<h3>What to Expect</h3>
					</div>
					<span class="kc54">Worship</span>
					<p><i class="fa-regular fa-circle-check" aria-hidden="true"></i> Warm worship, practical teaching from the Word, and real friendships, a place to feel close to family.</p>
				</div>
				<div class="kc52">
					<div>
						<i class="fa-solid fa-cube kc53" aria-hidden="true"></i>
						<h3>Community</h3>
					</div>
					<span class="kc54">Family</span>
					<p><i class="fa-regular fa-circle-check" aria-hidden="true"></i> We welcome every kind of heart, broken or whole. No religiosity, just a team of people who connect and care.</p>
				</div>
			</div>
			<div class="kc55">
				<span class="kc56"></span>
				<span class="kc56"></span>
				<span class="kc56"></span>
			</div>
		</div>
	</section>

	<!-- team section -->
	<section class="kc57">
		<div class="kc58">
			<span>WHAT WE DO</span>
			<h2>How We Serve</h2>
			<p>Love and family, every day.</p>
		</div>

		<div class="kc59">

			<div class="kc60">
				<div class="kc61" style="background-image: url('kci_image/img24.webp');"></div>
				<div class="kc62">01</div>
				<div class="kc63">
				<h3>Worship</h3>
				<p>Worship that lifts our hearts and welcomes the presence of God.</p>
				</div>
				<div class="kc64"> <!-- white footer -->
					<img src="kci_image/img16.webp" alt="Worship" class="kcsam">
					<div>
					<h4>Worship</h4>
					<p>Love from within, expressed in song.</p>
				</div>
			</div>

			<div class="kc60">
				<div class="kc61" style="background-image: url('kci_image/img23.webp');"></div>
				<div class="kc62">02</div>
				<div class="kc63">
					<h3>Teaching</h3>
				<p>Clear teaching from the Word that sinks deep and shapes daily life.</p>
				</div>
				<div class="kc64">
					<img src="kci_image/img25.webp" alt="Teaching" class="kcsam">
					<div>
					<h4>Teaching</h4>
				<p>A heartfelt message, week after week.</p>
				</div>
			</div>

			<div class="kc60">
				<div class="kc61" style="background-image: url('kci_image/img22.webp');"></div>
				<div class="kc62">03</div>
				<div class="kc63">
					<h3>Testimony</h3>
				<p>Stories of what God is doing among us, to help you believe.</p>
				</div>
				<div class="kc64">
					<img src="kci_image/img26.webp" alt="Community" class="kcsam">
					<div>
					<h4>Community</h4>
				<p>A family where faith grows and lives are shared.</p>
				</div>
			</div>	
		</div>
	</section>
	
	<!-- footer section -->

	<section class="kc65">
		<div class="kc66">
			<div class="kc67">
				<span class="kc68">FROM OUR FAMILY</span>
				<div class="kc69">
					<div class="kc70">
						<div class="kc71"><img src="kci_image/img27.webp"><div><h4>David & Chisom</h4><p>Members for 3 years</p></div></div>
						<h5>Vision To See People Connected</h5>
						<p>Growing together. Serving together.<br>Because Community is where faith comes alive.<br><br>This church has helped us grow and find faith in our real community, a family where faith grows and lives are shared, connected to God, connected to each other.</p>
						<span>Sunday Service 8:00 AM</span>
					</div>
					<div class="kc70">
						<div class="kc71"><img src="kci_image/img28.webp"><div><h4>Bro Micheal</h4><p>First time visitor</p></div></div>
						<h5>Weekly Teaching Quotes</h5>
						<p>The teaching is practical and helps me apply the Bible to my daily life.<br>Teaching that shapes your heart and direct your steps.<br><br>Fresh insight from God's word every week. Learn, grow, and apply the word each week.</p>
						<span>Wednesday Service 5:00 PM</span>
					</div>
				</div>
			</div>
			<div class="kc72">
				<h3>Join Us This Sunday</h3>
				<p><i class="fa-solid fa-location-dot" style="font-size: 38px; margin-left: 10px;"></i> Beside Jumbo Close off ogboso road,Obaema,Oyigbo,Rivers State,Nigeria.</p>
				<p><i class="fa-solid fa-clock" style="font-size: 35px; margin-left: 6px;"></i> 8:00 AM</p>
				<a href="location.php" class="btn-primary">Find Us <span>→</span></a>
				<a href="form.php" class="btn-primary">First-Time Guest Form <span>→</span></a>
			</div>
		</div>
	</section>
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
		// About page: lightweight IntersectionObserver scroll-reveal.
		// Fully self-contained � it does NOT touch the mobile menu, the
		// dropdown or the header scroll/glassmorphism code above.
		(function () {
			var body = document.body;
			if (!body || !body.classList.contains('about-page')) return;

			// The pre-paint script (see top of <body>) only primes the page
			// when motion is allowed and IntersectionObserver exists. If it
			// was skipped the content simply stays visible.
			if (!body.classList.contains('about-anim-ready')) return;

			var targets = document.querySelectorAll(
				'.about-page .kc45 .kc48, ' +
				'.about-page .kc45 .kc50 > img, ' +
				'.about-page .kc51 .kc52, ' +
				'.about-page .kc57 .kc58, ' +
				'.about-page .kc59 .kc60, ' +
				'.about-page .kc65 .kc70, ' +
				'.about-page .kc65 .kc72, ' +
				'.about-page .kc118 .kc119'
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
				body.classList.remove('about-anim-ready');
			}
		})();
	</script>

</body>
</html>

