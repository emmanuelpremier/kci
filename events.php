<?php
include_once "site_config.php";
include("kci_db.php");

// Fetch events from the database (cached once per request via kci_query_once)
$allEvents = kci_query_once($conn,
    "SELECT id, title, slug, image, date, location, description FROM events ORDER BY date ASC");
if ($allEvents === false) {
    $allEvents = array();
}

// Split into upcoming (date on or after today) and past (before today).
// Today is computed in PHP in the Africa/Lagos timezone (see site_config.php).
$today = date('Y-m-d');
$upcomingEvents = [];
$pastEvents = [];
foreach ($allEvents as $ev) {
    $evDay = date('Y-m-d', strtotime($ev['date']));
    if ($evDay >= $today) {
        $upcomingEvents[] = $ev;
    } else {
        $pastEvents[] = $ev;
    }
}

// Upcoming: soonest first. Past: newest first.
usort($upcomingEvents, function ($a, $b) { return strtotime($a['date']) - strtotime($b['date']); });
usort($pastEvents, function ($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });

// Featured block shows the NEXT upcoming event (soonest on/after today).
$nextEvent = count($upcomingEvents) > 0 ? $upcomingEvents[0] : null;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Worship services, conferences and gatherings at Kingdomite Church International in Oyigbo.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="kci.css?v=26">
    <link rel="stylesheet" type="text/css" href="mobilefix.css?v=3">
    <link rel="stylesheet" type="text/css" href="cleanup.css">
    <link rel="icon" type="image/png" href="kci_image">
    <title>Events | Kingdomite Church International</title>
    <!-- inserting of icon link from cdjns -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="events-page"
    data-reveal-ready="events-anim-ready"
    data-reveal-targets=".events-page .kc77 .kc78 > *, .events-page .kc80 .kc82, .events-page .kc80 .kc83 img, .events-page .kc80 .kc85, .events-page .kc87 .kc88, .events-page .kc87 .kc89, .events-page .kc87 .kc89 > *, .events-page .kc93 .kc94 > *, .events-page .kc93 .kc97, .events-page .kc99 .kc100 > *, .events-page .kc99 .kc103, .events-page .kc107 .kc108 > *, .events-page .kc107 .kc111, .events-page .kc113 .kc115 > *, .events-page .kc118 .kc119"
    data-reveal-margin="0px 0px -6% 0px">
    <!-- Shared reveal engine (kci-reveal.js): primes the scroll-reveal
         initial state before the first paint, then drives the single
         IntersectionObserver. -->
    <script src="kci-reveal.js" defer></script>

    <!--header-->
    <?php include 'header.php'; ?>

    <main>

    <!-- HERO -->
    <section class="kc73">
        <div class="kc74"></div>
        <div class="kc75">
            <span class="kc76">KINGDOMITE CHURCH INTERNATIONAL</span>
            <h1>Our Events</h1>
            <p>Experience worship. Build connections. Grow in faith.</p>
            <?php if (count($upcomingEvents) > 0): ?>
            <a href="#upcoming-events" class="primary-btn">View Upcoming Events <span>→</span></a>
            <?php else: ?>
            <a href="#upcoming-events" class="primary-btn">View Events <span>→</span></a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Intro -->
    <section class="kc77">
        <div class="kc78">
            <span class="kc79">GATHER • WORSHIP • GROW</span>
            <h2>Our Events</h2>
            <p>
                There is always something happening at Kingdomite.
                Join us as we gather together in worship, fellowship,
                prayer and the Word of God.
            </p>
        </div>
    </section>

    <!-- Events Card -->
    <section class="kc80" id="upcoming-events">
        <?php if (count($upcomingEvents) > 0): ?>
        <h2 class="cleanup-group-title">Upcoming</h2>
        <div class="kc81">
            <?php foreach ($upcomingEvents as $event): ?>
                <article class="kc82">
                    <div class="kc83">
                        <img src="<?= htmlspecialchars($event['image']) ?>"
                             alt="<?= htmlspecialchars($event['title']) ?>" width="800" height="600" loading="lazy" decoding="async">
                        <span class="kc84"><?= htmlspecialchars($event['location']) ?></span>
                    </div>

                    <div class="kc85">
                        <h3><?= htmlspecialchars($event['title']) ?></h3>

                        <div class="kc86">
                            <div>
                                <span class="meta-icon"><i class="fa-regular fa-calendar-days"></i></span>
                                <span><?= htmlspecialchars(date('F j, Y', strtotime($event['date']))) ?></span>
                            </div>
                            <div>
                                <span class="meta-icon"><i class="fa-solid fa-location-crosshairs"></i></span>
                                <span><?= htmlspecialchars($event['location']) ?></span>
                            </div>
                        </div>

                        <p><?= htmlspecialchars($event['description']) ?></p>

                        <a href="event.php?slug=<?= urlencode($event['slug']) ?>" class="text-btn">
                            View Event <span>→</span>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (count($pastEvents) > 0): ?>
        <h2 class="cleanup-group-title">Past Events</h2>
        <div class="kc81">
            <?php foreach ($pastEvents as $event): ?>
                <article class="kc82 event-muted">
                    <div class="kc83">
                        <img src="<?= htmlspecialchars($event['image']) ?>"
                             alt="<?= htmlspecialchars($event['title']) ?>" width="800" height="600" loading="lazy" decoding="async">
                        <span class="kc84"><?= htmlspecialchars($event['location']) ?></span>
                    </div>

                    <div class="kc85">
                        <h3><?= htmlspecialchars($event['title']) ?> <span class="past-event-badge">Past event</span></h3>

                        <div class="kc86">
                            <div>
                                <span class="meta-icon"><i class="fa-regular fa-calendar-days"></i></span>
                                <span><?= htmlspecialchars(date('F j, Y', strtotime($event['date']))) ?></span>
                            </div>
                            <div>
                                <span class="meta-icon"><i class="fa-solid fa-location-crosshairs"></i></span>
                                <span><?= htmlspecialchars($event['location']) ?></span>
                            </div>
                        </div>

                        <p><?= htmlspecialchars($event['description']) ?></p>

                        <a href="event.php?slug=<?= urlencode($event['slug']) ?>" class="text-btn">
                            View Event <span>→</span>
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Featured Events: shows the NEXT upcoming event from the database -->
    <?php if ($nextEvent): ?>
     <section class="kc87">
        <div class="kc88">
            <img src="<?= htmlspecialchars($nextEvent['image']) ?>" alt="<?= htmlspecialchars($nextEvent['title']) ?>" width="800" height="600" loading="lazy" decoding="async">
        </div>

        <div class="kc89">
            <span class="kc90">NEXT EVENT</span>
            <h2><?= htmlspecialchars($nextEvent['title']) ?></h2>
            <p class="kc91">
                <?= htmlspecialchars($nextEvent['description']) ?>
            </p>

            <div class="kc92">
                <div>
                    <span class="detail-icon"><i class="fa-regular fa-calendar-days"></i></span>
                    <div>
                        <small>Date</small>
                        <strong><?= htmlspecialchars(date('F j, Y', strtotime($nextEvent['date']))) ?></strong>
                    </div>
                </div>

                <div>
                    <span class="detail-icon"><i class="fa-solid fa-location-crosshairs"></i></span>
                    <div>
                        <small>Location</small>
                        <strong><?= htmlspecialchars($nextEvent['location']) ?></strong>
                    </div>
                </div>
            </div>

            <a href="event.php?slug=<?= urlencode($nextEvent['slug']) ?>" class="primary-btn">
                Learn More <span>→</span>
            </a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Event Category -->
     <section class="kc93">
        <div class="kc94">
            <span class="kc95">EXPLORE</span>
            <h2>Something For Everyone</h2>
            <p>
                Our events create opportunities to worship, connect,
                pray and grow together.
            </p>
        </div>

        <div class="kc96">
            <div class="kc97">
                <div class="kc98">✦</div>
                <h3>Worship</h3>
                <p>Gather with us in an atmosphere of praise and worship.</p>
            </div>

            <div class="kc97">
                <div class="kc98">◉</div>
                <h3>Conferences</h3>
                <p>Receive biblical teaching and practical encouragement.</p>
            </div>

            <div class="kc97">
                <div class="kc98">♡</div>
                <h3>Prayer</h3>
                <p>Join others as we seek God together through prayer.</p>
            </div>

            <div class="kc97">
                <div class="kc98">⬡</div>
                <h3>Community</h3>
                <p>Build meaningful relationships with fellow believers.</p>
            </div>
        </div>
    </section>

      <!-- UPCOMING DATES: database-driven, upcoming events only -->
    <?php if (count($upcomingEvents) > 0): ?>
    <section class="kc99">
        <div class="kc100">
            <span class="kc101">MARK YOUR CALENDAR</span>
            <h2>Upcoming Dates</h2>
            <p>Keep up with the gatherings and programs coming your way.</p>
        </div>

        <div class="kc102">
            <?php foreach ($upcomingEvents as $event): ?>
            <a href="event.php?slug=<?= urlencode($event['slug']) ?>" class="kc103">
                <div class="kc104">
                    <strong><?= htmlspecialchars(date('j', strtotime($event['date']))) ?></strong>
                    <span><?= htmlspecialchars(strtoupper(date('M', strtotime($event['date'])))) ?></span>
                </div>
                <div class="kc105">
                    <span><?= htmlspecialchars($event['title']) ?></span>
                    <small><?= htmlspecialchars($event['location']) ?></small>
                </div>
                <span class="kc106">→</span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

        <!-- WHY ATTEND -->
    <section class="kc107">
        <div class="kc108">
            <span class="kc109">WHY JOIN US?</span>
            <h2>Come As You Are</h2>
            <p>
                Our events are opportunities to encounter God,
                connect with people and discover your place in His purpose.
            </p>
        </div>

        <div class="kc110">
            <div class="kc111">
                <span class="kc112">01</span>
                <h3>Worship</h3>
                <p>Make room for God's presence through heartfelt worship.</p>
            </div>

            <div class="kc111">
                <span class="kc112">02</span>
                <h3>Connection</h3>
                <p>Meet people, build relationships and grow together.</p>
            </div>

            <div class="kc111">
                <span class="kc112">03</span>
                <h3>Growth</h3>
                <p>Be strengthened through God's Word, prayer and fellowship.</p>
            </div>
        </div>
    </section>

        <!-- CTA -->
    <section class="kc113">
        <div class="kc114"></div>
        <div class="kc115">
            <span class="kc116">YOU ARE WELCOME HERE</span>
            <h2>Be Part of What God Is Doing</h2>
            <p>Come and experience our next gathering with us.</p>
            <a href="contact.php" class="kc117">Get In Touch <span>→</span></a>
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
    <!-- The scroll-reveal that used to live here now comes from the shared
         kci-reveal.js engine included right after <body> (see the
         data-reveal-* attributes on <body> for its configuration). -->
</body>
</html>