<?php
/**
 * min.php — Ministries page
 * Kingdomite Church International
 *
 * The header and footer used below are the existing KCI header/footer markup,
 * reused exactly as they appear on index.php, aboutus.php, events.php and event.php.
 * Only the content between them is designed for this page.
 */

/* ------------------------------------------------------------------
   Ministry data — the single source of truth is the existing
   `ministries` table in the `kingdomite` database. Active ministries
   (status = 'active') are loaded below, ordered by id ASC; the page,
   the category filters, the card grid and the featured ministry all
   follow automatically.
   ------------------------------------------------------------------ */
include("kci_db.php");

$ministries = [];

try {
	$ministryStmt = $conn->prepare("SELECT id, name, slug, category, icon, image, description, meeting FROM ministries WHERE status = 'active' ORDER BY id ASC");
	$ministryStmt->execute();
	$ministryResult = $ministryStmt->get_result();
	if ($ministryResult) {
		while ($ministryRow = $ministryResult->fetch_assoc()) {
			/* Card rendering below expects each ministry's slug under the
			   'anchor' key and a fixed 'View Ministry' link label. */
			$ministryRow['anchor'] = $ministryRow['slug'];
			$ministryRow['link_label'] = 'View Ministry';
			$ministries[] = $ministryRow;
		}
	}
	$ministryStmt->close();
} catch (Throwable $e) {
	/* Never expose database details to visitors. */
	$ministries = [];
}
/* Category filter buttons are built from the data above. */
$ministryCategories = [];
foreach ($ministries as $ministry) {
    if (!in_array($ministry['category'], $ministryCategories, true)) {
        $ministryCategories[] = $ministry['category'];
    }
}

/* Every ministry card routes to the single reusable detail page:
   ministry.php?slug=<the ministry's database slug>. */

/* Featured ministry block — identity and image come from the
   community-outreach database record; the stats stay as display-only
   values since they are not stored in the ministries table. */
$featuredMinistry = [
    'badge'       => 'Outreach · Featured Ministry',
    'name'        => 'Community Outreach',
    'image'       => 'kci_image/img42.webp',
    'description' => 'A dependable team of kingdomites carrying the love of Jesus beyond the walls of the church — sharing food, visiting homes, praying with families and meeting real needs around us.',
    'stats'       => [
        ['value' => '120+',  'label' => 'Families Served'],
        ['value' => '48',    'label' => 'Volunteers'],
        ['value' => '6 yrs', 'label' => 'Serving Oyigbo'],
    ],
    'leader'      => 'Outreach Servant-Lead Team',
    'schedule'    => 'Every Saturday · 10:00 AM',
    'cta_label'   => "I'd Like to Serve",
    'cta_link'    => 'serve.php?ministry=community-outreach',
];
foreach ($ministries as $ministry) {
    if (($ministry['slug'] ?? '') === 'community-outreach') {
        $featuredMinistry['badge'] = $ministry['category'] . ' · Featured Ministry';
        $featuredMinistry['name'] = $ministry['name'];
        $featuredMinistry['image'] = $ministry['image'];
        $featuredMinistry['description'] = $ministry['description'];
        $featuredMinistry['schedule'] = $ministry['meeting'];
        break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Ministries at Kingdomite Church International">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="kci.css?v=20">
	<link rel="stylesheet" type="text/css" href="mobilefix.css?v=1">
	<link rel="icon" type="image/png" href="kci_image">
	<title>Ministries | Kingdomite Church International</title>

	<!-- inserting of icon link from cdjns -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="ministries-page">
	<!-- Ministries page: prime the scroll-reveal initial state before the first
	     paint. Skipped for reduced-motion users and when IntersectionObserver
	     is unavailable, so the content is never left hidden. -->
	<script type="text/javascript">
		(function () {
			var reduced = window.matchMedia &&
				window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			if (reduced || !('IntersectionObserver' in window)) return;
			document.body.classList.add('ministries-anim-ready');
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
		<!-- MINISTRIES HERO -->
		<section class="ministries-hero">
			<div class="ministries-hero__inner">
				<div class="ministries-hero__content">
					<span class="ministries-eyebrow">FIND YOUR PLACE TO SERVE</span>
					<h1 class="ministries-hero__title">Discover the Ministry God Prepared for You</h1>
					<p class="ministries-hero__text">
						At Kingdomite Church International every believer is a minister.
						From Children Church to Global Missions, find the team where your
						gift fits, your faith grows and your service builds the Kingdom.
					</p>
					<div class="ministries-hero__actions">
						<a href="#all-ministries" class="ministries-btn ministries-btn--solid">
							Explore All Ministries <span>&rarr;</span>
						</a>
						<a href="aboutus.php" class="ministries-btn ministries-btn--ghost">
							Meet Our Leaders <span>&rarr;</span>
						</a>
					</div>
				</div>

				<div class="ministries-hero__image">
					<i class="fa-solid fa-people-group ministries-img-fallback" aria-hidden="true"></i>
					<img src="kci_image/img40.webp"
					     alt="Kingdomite Church International ministry team serving together"
					     data-ministry-img>
				</div>
			</div>
		</section>

		<!-- ALL MINISTRIES -->
		<section class="ministries-all" id="all-ministries">
			<div class="ministries-all__head">
				<div class="ministries-all__intro">
					<span class="ministries-eyebrow">MINISTRY FAMILIES</span>
					<h2 class="ministries-all__title">All Ministries</h2>
					<p class="ministries-all__text">
						<?= count($ministries) ?> teams. One body. One purpose.
					</p>
				</div>

				<div class="ministries-filters" role="group" aria-label="Filter ministries by category">
					<button type="button" class="ministries-filter is-active" data-filter="all">All</button>
					<?php foreach ($ministryCategories as $category): ?>
						<button type="button" class="ministries-filter"
						        data-filter="<?= htmlspecialchars(strtolower($category)) ?>">
							<?= htmlspecialchars($category) ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="ministries-grid">
				<?php foreach ($ministries as $ministry): ?>
					<article class="ministry-card"
					         id="<?= htmlspecialchars($ministry['anchor']) ?>"
					         data-category="<?= htmlspecialchars(strtolower($ministry['category'])) ?>">
						<span class="ministry-card__icon" aria-hidden="true">
							<i class="<?= htmlspecialchars($ministry['icon']) ?>"></i>
					</span>
						<div class="ministry-card__tile">
							<img src="<?= htmlspecialchars($ministry['image']) ?>"
							     alt="<?= htmlspecialchars($ministry['name']) ?>"
							     data-ministry-img>
						</div>

						<span class="ministry-card__category"><?= htmlspecialchars($ministry['category']) ?></span>
						<h3 class="ministry-card__title"><?= htmlspecialchars($ministry['name']) ?></h3>
						<p class="ministry-card__description"><?= htmlspecialchars($ministry['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false) ?></p>

						<div class="ministry-card__foot">
							<div class="ministry-card__meta">
								<i class="fa-regular fa-clock" aria-hidden="true"></i>
								<span><?= htmlspecialchars($ministry['meeting']) ?></span>
							</div>

							<a href="ministry.php?slug=<?= urlencode($ministry['anchor']) ?>" class="ministry-card__link">
								<?= htmlspecialchars($ministry['link_label']) ?> <span>&rarr;</span>
							</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

			<p class="ministries-all__empty" data-ministries-empty hidden>
				No ministry in this category yet — please try another category.
			</p>
		</section>

		<!-- FEATURED MINISTRY -->
		<section class="featured-ministry">
			<div class="featured-ministry__inner">
				<div class="featured-ministry__media">
					<i class="fa-solid fa-hand-holding-heart ministries-img-fallback" aria-hidden="true"></i>
					<img src="<?= htmlspecialchars($featuredMinistry['image']) ?>"
					     alt="<?= htmlspecialchars($featuredMinistry['name']) ?> at Kingdomite Church International"
					     data-ministry-img>
				</div>

				<div class="featured-ministry__content">
					<span class="featured-ministry__badge"><?= htmlspecialchars($featuredMinistry['badge']) ?></span>
					<h2 class="featured-ministry__title"><?= htmlspecialchars($featuredMinistry['name']) ?></h2>
					<p class="featured-ministry__text"><?= htmlspecialchars($featuredMinistry['description']) ?></p>

					<div class="featured-ministry__stats">
						<?php foreach ($featuredMinistry['stats'] as $stat): ?>
							<?php
							$statValue = (string)($stat['value'] ?? '');
							$statNum = '';
							$statSuffix = '';
							if (preg_match('/^\s*(\d+)(.*)$/', $statValue, $statParts)) {
								$statNum = $statParts[1];
								$statSuffix = $statParts[2];
							} else {
								$statNum = $statValue;
								$statSuffix = '';
							}
							?>
							<div class="featured-ministry__stat">
								<strong class="featured-ministry__num" data-count-to="<?= htmlspecialchars($statNum) ?>" data-count-suffix="<?= htmlspecialchars($statSuffix) ?>"><?= htmlspecialchars($stat['value']) ?></strong>
								<span><?= htmlspecialchars($stat['label']) ?></span>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="featured-ministry__foot">
						<div class="featured-ministry__leader">
							<span class="featured-ministry__avatar">
								<i class="fa-solid fa-people-group" aria-hidden="true"></i>
							</span>
							<span class="featured-ministry__leader-text">
								<strong><?= htmlspecialchars($featuredMinistry['leader']) ?></strong>
								<small><?= htmlspecialchars($featuredMinistry['schedule']) ?></small>
							</span>
						</div>

						<a href="<?= htmlspecialchars($featuredMinistry['cta_link']) ?>" class="ministries-btn ministries-btn--solid">
							<?= htmlspecialchars($featuredMinistry['cta_label']) ?> <span>&rarr;</span>
						</a>
					</div>
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

		// Ministries page: category filter (front-end only, scoped to this page)
		(function() {
			var page = document.querySelector('.ministries-page');
			if (!page) {
				return;
			}

			var filterButtons = page.querySelectorAll('.ministries-filter');
			var cards = page.querySelectorAll('.ministry-card');
			var emptyNote = page.querySelector('[data-ministries-empty]');

			function applyFilter(filter) {
				var visible = 0;

				cards.forEach(function(card) {
					var match = (filter === 'all') || (card.getAttribute('data-category') === filter);
					card.classList.toggle('is-hidden', !match);
					if (match) {
						visible++;
					}
				});

				filterButtons.forEach(function(button) {
					button.classList.toggle('is-active', button.getAttribute('data-filter') === filter);
				});

				if (emptyNote) {
					emptyNote.hidden = visible > 0;
				}
			}

			filterButtons.forEach(function(button) {
				button.addEventListener('click', function() {
					applyFilter(button.getAttribute('data-filter'));
				});
			});

			// Ministry images are added manually — hide a missing image so the
			// styled placeholder behind it shows instead of a broken image.
			// A grid card only reveals its photo tile once a photo really loads;
			// until then the card keeps its icon as the background.
			page.querySelectorAll('img[data-ministry-img]').forEach(function(image) {
				var card = image.closest('.ministry-card');

				function useFallback() {
					image.classList.add('is-missing');
					if (card) {
						card.classList.remove('has-photo');
					}
				}

				function usePhoto() {
					if (card) {
						card.classList.add('has-photo');
					}
				}

				if (image.complete) {
					// Settled before this script ran.
					if (image.naturalWidth === 0) {
						useFallback();
					} else {
						usePhoto();
					}
				} else {
					image.addEventListener('load', usePhoto);
					image.addEventListener('error', useFallback);
				}
			});

			applyFilter('all');
		})();
	</script>

	<script type="text/javascript">
		// Ministries page: lightweight IntersectionObserver scroll-reveal.
		// Fully self-contained - it does NOT touch the mobile menu, the
		// dropdowns, the header scroll code, the category filter or the
		// ministry image fallback above. It only adds a reveal class.
		(function () {
			var page = document.querySelector('.ministries-page');
			if (!page) return;

			// The pre-paint script (see top of <body>) only primes the page
			// when motion is allowed and IntersectionObserver exists. If it
			// was skipped, nothing is hidden and this can stop here.
			if (!document.body.classList.contains('ministries-anim-ready')) return;

			var targets = page.querySelectorAll(
				'.ministries-all__intro, ' +
				'.ministries-all__intro .ministries-eyebrow, ' +
				'.ministries-all__title, ' +
				'.ministries-all__text, ' +
				'.ministries-filters, ' +
				'.ministry-card, ' +
				'.featured-ministry__media, ' +
				'.featured-ministry__content, ' +
				'.featured-ministry__badge, ' +
				'.featured-ministry__title, ' +
				'.featured-ministry__text, ' +
				'.featured-ministry__stats, ' +
				'.featured-ministry__stat, ' +
				'.featured-ministry__foot, ' +
				'.kc118 .kc119'
			);

			if (!targets.length) return;

			function reveal(element) {
				if (element.classList.contains('is-visible')) return;
				element.classList.add('is-visible');
			}

			// Safety net: nothing is ever left invisible. Anything that is
			// already on screen after the layout changes - switching a
			// category filter reflows and re-shows cards - is revealed at
			// once. Cards hidden by the filter are skipped, so a filtered
			// card can never appear before it matches the active category.
			function revealInView() {
				var viewport = window.innerHeight || document.documentElement.clientHeight;
				targets.forEach(function (element) {
					if (element.classList.contains('is-visible')) return;
					if (element.classList.contains('is-hidden')) return;
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
				document.body.classList.remove('ministries-anim-ready');
				return;
			}

			// After a category filter runs (the script above always runs
			// first) the grid reflows: reveal whatever is on screen now and
			// once more a moment later, in case an image or font shifts the
			// layout. The filter logic itself is never touched.
			var filters = page.querySelector('.ministries-filters');
			if (filters) {
				filters.addEventListener('click', function () {
					window.requestAnimationFrame(revealInView);
					window.setTimeout(revealInView, 350);
				});
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
	<script src="countup.js"></script>
</body>
</html>


