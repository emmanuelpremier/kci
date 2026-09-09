<?php
// events.php
// Main events overview page for Kingdomite Church International.
// Replace the image paths below with your actual event images.

$events = [
    [
        'title' => 'Oil & Wine Summit',
        'slug' => 'oil-wine-summit.php',
        'image' => 'kci_image/img37cc.jpg',
        'time' => 'Passed',
        'date' => 'August 15–17, 2026',
        'location' => 'Kingdomite Church International',
        'description' => 'A powerful gathering of worship, prayer, teaching and fellowship designed to draw hearts closer to God.'
    ],
    [
        'title' => 'June Conference',
        'slug' => 'june-conference.php',
        'image' => 'kci_image/img36cc.jpg',
        'time' => 'Passed',
        'date' => 'June 2027',
        'location' => 'Kingdomite Church International',
        'description' => 'A season of teaching, connection and spiritual growth for individuals, families and the wider church community.'
    ],
    [
        'title' => 'Embers of Glory',
        'slug' => 'embers-of-glory.php',
        'image' => 'kci_image/img39.jpg',
        'time' => 'Ongoing',
        'date' => 'Coming Soon',
        'location' => 'Kingdomite Church International',
        'description' => 'A special atmosphere of worship, prayer and renewed passion for the presence and purpose of God.'
    ]
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="https://www.w3school.com/lib/w3.com">
    <meta name="description" content="Kingdomite Church Int'l">
    <title>Events | Kingdomite Church International</title>
    <link rel="stylesheet" type="text/css" href="kci.css">
    <link rel="icon" type="image/jpg" href="icon">
    <!-- inserting of icon link from cdjns -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <section class="kc1">
        <div class="kc2">
            <img src="kci_image/im2.jpg">
        </div>
        <div class="kc3">
            <ul>
                <li><a href="index.php">Home</a>
                    <div class="line"></div>
                </li>
                <li><a href="aboutus.php">About</a>
                    <div class="linea"></div>
                </li>
                <li ><a href="events.php">Events   v</a>
                    <div class="linec"></div>
                    <ul>
                        <li><a href="events/oil.php">Oil & Wine Summit</a></li>
                        <li><a href="events/conf.php">June Confrence</a></li>
                        <li><a href="events/embers.php">Embers Of Glory</a></li>
                    </ul>
                </li>
                <li class="linef"><a href="min.php">Ministries         v</a>
                    <div class="lined"></div>
                    <ul>
                        <li><a href="miss.php">Missions</a></li>
                        <li><a href="wom.php">Women's Ministry</a></li>
                        <li><a href="yot.php">YC-The Youth Church</a></li>
                        <li><a href="mte.php">MTC-Mighty Teens Church</a></li>
                        <li><a href="chi.php">Children Church</a></li>
                    </ul>
                </li>
                <li><a href="contact.php">Contact</a>
                    <div class="linee"></div>
                </li>
            </ul>
        </div>
        <div class="kc4">
            <a href="giving.php">Give</a>
        </div>
    </section>

    <main>

    <!-- HERO -->
    <section class="kc73">
        <div class="kc74"></div>
        <div class="kc75">
            <span class="kc76">KINGDOMITE CHURCH INTERNATIONAL</span>
            <h1>Our Events</h1>
            <p>Experience worship. Build connections. Grow in faith.</p>
            <a href="#upcoming-events" class="primary-btn">View Upcoming Events <span>→</span></a>
        </div>
    </section>

    <!-- Intro -->
    <section class="kc77">
        <div class="kc78">
            <span class="kc79">GATHER • WORSHIP • GROW</span>
            <h2>Upcoming Events</h2>
            <p>
                There is always something happening at Kingdomite.
                Join us as we gather together in worship, fellowship,
                prayer and the Word of God.
            </p>
        </div>
    </section>

    <!-- Events Card -->
    <section class="kc80" id="upcoming-events">
        <div class="kc81">
            <?php foreach ($events as $event): ?>
                <article class="kc82">
                    <div class="kc83">
                        <img src="<?= htmlspecialchars($event['image']) ?>"
                             alt="<?= htmlspecialchars($event['title']) ?>">
                        <span class="kc84"><?= htmlspecialchars($event['time']) ?></span>
                    </div>

                    <div class="kc85">
                        <h3><?= htmlspecialchars($event['title']) ?></h3>

                        <div class="kc86">
                            <div>
                                <span class="meta-icon"><i class="fa-regular fa-calendar-days"></i></span>
                                <span><?= htmlspecialchars($event['date']) ?></span>
                            </div>
                            <div>
                                <span class="meta-icon"><i class="fa-solid fa-location-crosshairs"></i></span>
                                <span><?= htmlspecialchars($event['location']) ?></span>
                            </div>
                        </div>

                        <p><?= htmlspecialchars($event['description']) ?></p>

                        <a href="events/<?= htmlspecialchars($event['slug']) ?>" class="text-btn">
                            View Event <span>→</span>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Featured Events -->
     <section class="kc87">
        <div class="kc88">
            <img src="kci_image/img37p.jpg" alt="Oil & Wine Summit">
        </div>

        <div class="kc89">
            <span class="kc90">FEATURED EVENT</span>
            <h2>Oil &amp; Wine Summit</h2>
            <p class="kc91">
                A special gathering created for worship, prayer, teaching
                and meaningful fellowship.
            </p>

            <div class="kc92">
                <div>
                    <span class="detail-icon"><i class="fa-regular fa-calendar-days"></i></span>
                    <div>
                        <small>Date</small>
                        <strong>August 15–17, 2026</strong>
                    </div>
                </div>

                <div>
                    <span class="detail-icon"><i class="fa-solid fa-location-crosshairs"></i></span>
                    <div>
                        <small>Location</small>
                        <strong>Kingdomite Church International</strong>
                    </div>
                </div>
            </div>

            <a href="events/oil-wine-summit.php" class="primary-btn">
                Learn More <span>→</span>
            </a>
        </div>
    </section>

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

      <!-- UPCOMING DATES -->
    <section class="kc99">
        <div class="kc100">
            <span class="kc101">MARK YOUR CALENDAR</span>
            <h2>Upcoming Dates</h2>
            <p>Keep up with the gatherings and programs coming your way.</p>
        </div>

        <div class="kc102">
            <a href="events/oil-wine-summit.php" class="kc103">
                <div class="kc104">
                    <strong>15</strong>
                    <span>AUG</span>
                </div>
                <div class="kc105">
                    <span>Oil &amp; Wine Summit</span>
                    <small>Kingdomite Church International</small>
                </div>
                <span class="kc106">→</span>
            </a>

            <a href="events/june-conference.php" class="kc103">
                <div class="kc104">
                    <strong>2027</strong>
                    <span>JUNE</span>
                </div>
                <div class="kc105">
                    <span>June Conference</span>
                    <small>Details coming soon</small>
                </div>
                <span class="kc106">→</span>
            </a>

            <a href="events/embers-of-glory.php" class="kc103">
                <div class="kc104">
                    <strong>—</strong>
                    <span>TBA</span>
                </div>
                <div class="kc105">
                    <span>Embers of Glory</span>
                    <small>Date and venue to be announced</small>
                </div>
                <span class="kc106">→</span>
            </a>
        </div>
    </section>

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

    

    <!-- Footer -->
    <footer class="kc118">
    <div class="kc119">
        <div class="kc120">
            <div class="kc121">
                <div class="kc122"><img src="kci_image/img13.png"></div>
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
            <p>Phone: +234 806 497 9241</p>
            <p>Email: info@kingdomitechurch@gmail.com</p>
        </div>

        <div class="kc124">
            <h4>Location</h4>
            <p>Kingdomite Church International</p>
            <p>Nigeria</p>
        </div>
    </div>

    <div class="kc125">
        <p>© <?= date('Y') ?> Kingdomite Church International. All Rights Reserved.</p>
    </div>
</footer>
</body>
</html>