<?php
require_once __DIR__ . '/../app/includes/auth.php';
$pageStylesheets = ['assets/css/whats_happening.css'];
include __DIR__ . '/../app/includes/navbar.php';

function wh_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function wh_image_src(string $path, string $fallback): string
{
    $absolutePath = __DIR__ . '/' . ltrim($path, '/');
    if (!is_file($absolutePath) || filesize($absolutePath) < 1024) {
        return $fallback;
    }

    return $path;
}

$highlights = [
    [
        'title'       => 'Kecak Fire Dance',
        'category'    => 'Cultural Experience',
        'description' => 'Watch the iconic Kecak dance performed at sunset with a backdrop of Uluwatu Temple perched on the cliff edge above the Indian Ocean — one of Bali\'s most unforgettable cultural performances.',
        'location'    => 'Uluwatu Temple, Pecatu',
        'timing'      => 'Daily, 6:00 PM – 7:00 PM',
        'distance'    => '35 min from hotel',
        'booking'     => 'Tickets available at the temple gate (arrive early). Hotel concierge can arrange transport.',
        'image'       => 'assets/images/whats_happening/kecak_dance.webp',
        'fallback'    => 'assets/images/AboutUs.webp',
        'alt'         => 'Performers in traditional costume dancing the Kecak fire dance at Uluwatu cliff temple at sunset',
        'badge'       => 'Nightly',
    ],
    [
        'title'       => 'Tegallalang Rice Terraces',
        'category'    => 'Nature & Scenery',
        'description' => 'Wander through the cascading emerald rice paddies of Tegallalang, a UNESCO-recognised cultural landscape. Explore on foot, enjoy a swing over the terraces, or grab a coffee at one of the clifftop cafes.',
        'location'    => 'Tegallalang, Ubud',
        'timing'      => 'Daily, 8:00 AM – 6:00 PM',
        'distance'    => '1 hr 15 min from hotel',
        'booking'     => 'No booking required. Small entrance donation at site. Concierge can arrange guided day tours.',
        'image'       => 'assets/images/whats_happening/rice_terraces.webp',
        'fallback'    => 'assets/images/Adventure.webp',
        'alt'         => 'Lush green cascading rice terraces in Tegallalang, Ubud, Bali under a partly cloudy sky',
        'badge'       => 'Open Daily',
    ],
    [
        'title'       => 'Seminyak Beach Sunset',
        'category'    => 'Beach & Leisure',
        'description' => 'Seminyak Beach offers a wide stretch of golden sand and dramatic sunsets. Settle in at a beachfront bar for cocktails as the sky turns pink and orange — a quintessential Bali evening ritual.',
        'location'    => 'Seminyak Beach',
        'timing'      => 'Best sunset viewing: 5:30 PM – 7:00 PM',
        'distance'    => '20 min from hotel',
        'booking'     => 'Walk-in at beach bars. Reservations recommended for sunset dinners at beachfront restaurants.',
        'image'       => 'assets/images/whats_happening/seminyak_sunset.webp',
        'fallback'    => 'assets/images/HotelHomePage.webp',
        'alt'         => 'Golden hour sunset over Seminyak Beach with silhouettes of palm trees and beachgoers',
        'badge'       => 'Daily',
    ],
    [
        'title'       => 'Ubud Art Market & Palace',
        'category'    => 'Arts & Culture',
        'description' => 'Browse hundreds of local artisan stalls at Ubud Art Market selling handwoven textiles, woodcarvings, jewellery, and batik. Just across the road sits the royal Ubud Palace, a beautifully preserved traditional compound.',
        'location'    => 'Ubud Market, Jalan Raya Ubud',
        'timing'      => 'Daily, 8:00 AM – 5:00 PM',
        'distance'    => '1 hr 10 min from hotel',
        'booking'     => 'No booking required. Bargaining is expected at the market. Palace entry is free.',
        'image'       => 'assets/images/whats_happening/ubud_market.webp',
        'fallback'    => 'assets/images/Hospitality.webp',
        'alt'         => 'Colourful handcraft stalls at Ubud Art Market with traditional Balinese architecture in the background',
        'badge'       => 'Open Daily',
    ],
];

$localExperiences = [
    [
        'icon'  => '🎨',
        'title' => 'Batik & Craft Workshops',
        'desc'  => 'Learn traditional Balinese batik dyeing or silver jewellery-making with local artisans. Half-day workshops available near Ubud.',
        'info'  => 'Concierge can book',
    ],
    [
        'icon'  => '🍳',
        'title' => 'Balinese Cooking Class',
        'desc'  => 'Join a morning cooking class starting with a visit to a local market, followed by hands-on preparation of traditional Balinese dishes.',
        'info'  => 'From ~IDR 350,000 / person',
    ],
    [
        'icon'  => '🌿',
        'title' => 'Jungle Trekking',
        'desc'  => 'Guided treks through the Munduk highlands or Campuhan Ridge offer stunning forest trails with local wildlife and rice fields.',
        'info'  => '2–4 hrs, guide recommended',
    ],
    [
        'icon'  => '🏄',
        'title' => 'Surf Lessons',
        'desc'  => 'Beginner to intermediate surf lessons at Kuta or Canggu Beach with certified instructors. Great for first-timers.',
        'info'  => 'Hotel can arrange pickup',
    ],
    [
        'icon'  => '🚴',
        'title' => 'Cycling Tours',
        'desc'  => 'Scenic downhill bike tours through Kintamani volcano and Batur Lake take you through rice fields and traditional villages.',
        'info'  => 'Full day, lunch included',
    ],
    [
        'icon'  => '🐠',
        'title' => 'Snorkelling & Diving',
        'desc'  => 'The crystal-clear waters off Amed, Padangbai and Menjangan Island offer some of Southeast Asia\'s finest diving and snorkelling.',
        'info'  => '1–2 hrs from hotel',
    ],
];

$ferryRoutes = [
    [
        'title'       => 'Bali → Nusa Penida',
        'operator'    => 'Various speed boat operators',
        'from'        => 'Sanur Harbour, Bali',
        'to'          => 'Toyapakeh or Banjar Nyuh Pier, Nusa Penida',
        'duration'    => '~30–45 minutes',
        'schedule'    => 'Departures roughly every 30–60 min, 7:00 AM – 5:00 PM (last return ~4:30 PM)',
        'fare'        => 'IDR 75,000–150,000 per person one-way (varies by operator)',
        'booking'     => 'Walk-up at Sanur Beach pier, or book through hotel concierge for convenience. Online booking also available via Rocky Fast Cruise and Maruti Express.',
        'highlights'  => ['Kelingking Beach', "Angel's Billabong", 'Broken Beach', 'Crystal Bay'],
        'image'       => 'assets/images/whats_happening/nusa_penida_ferry.webp',
        'fallback'    => 'assets/images/HotelHomePage.webp',
        'alt'         => 'A white speed boat departing Sanur Harbour with turquoise water and a clear blue sky',
    ],
    [
        'title'       => 'Bali → Gili Islands (Lombok)',
        'operator'    => 'Eka Jaya Fast Boat / Gili Getaway',
        'from'        => 'Padangbai Harbour or Sanur, Bali',
        'to'          => 'Gili Trawangan, Gili Meno, or Gili Air',
        'duration'    => '~1 hr 30 min – 2 hrs',
        'schedule'    => 'Departures 8:00 AM and 11:00 AM from Padangbai; 9:00 AM from Sanur (selected days). Return services from Gili Trawangan 1:00 PM and 4:00 PM.',
        'fare'        => 'IDR 350,000–600,000 per person one-way depending on operator and departure point',
        'booking'     => 'Advance booking recommended. Book online via Gili Getaway, Eka Jaya, or through the hotel concierge for round-trip packages.',
        'highlights'  => ['No motorised vehicles', 'World-class snorkelling', 'Sea turtles at Gili Meno', 'Sunset strip, Gili T'],
        'image'       => 'assets/images/whats_happening/gili_islands_ferry.webp',
        'fallback'    => 'assets/images/AboutUs.webp',
        'alt'         => 'Turquoise waters and white sandy beach of Gili Trawangan with a traditional wooden boat moored offshore',
    ],
];
?>

<main class="whats-happening-page">

    <!-- ===== HERO ===== -->
    <section class="wh-hero">
        <div class="container">
            <div class="wh-hero-inner reveal-up">
                <p class="wh-eyebrow">Explore the Region</p>
                <h1 class="wh-hero-title">What's Happening<br>Around Bali</h1>
                <p class="wh-hero-text">
                    From sacred sea temples and cultural performances to island-hopping adventures
                    and jungle treks — discover the best experiences within reach of Horizon Sands Bali.
                </p>
            </div>
            <ul class="wh-hero-stats" aria-label="Quick facts">
                <li class="wh-stat-item">
                    <strong>4</strong>
                    <span>featured attractions</span>
                </li>
                <li class="wh-stat-item">
                    <strong>2</strong>
                    <span>island ferry routes</span>
                </li>
                <li class="wh-stat-item">
                    <strong>Concierge</strong>
                    <span>can arrange transport &amp; tickets</span>
                </li>
            </ul>
        </div>
    </section>

    <!-- ===== ANCHOR NAV ===== -->
    <nav class="wh-anchor-nav" aria-label="Page sections">
        <div class="container">
            <div class="wh-anchor-links">
                <a href="#attractions">Attractions &amp; Events</a>
                <a href="#local-experiences">Local Experiences</a>
                <a href="#ferry-services">Ferry Services</a>
                <a href="#concierge-cta">Concierge Help</a>
            </div>
        </div>
    </nav>

    <!-- ===== ATTRACTIONS & EVENTS ===== -->
    <section id="attractions" class="wh-section">
        <div class="container">
            <div class="wh-section-heading reveal-up">
                <p class="wh-section-label">Attractions &amp; Events</p>
                <h2>Highlights within reach of the hotel.</h2>
                <p class="wh-section-subtext">
                    All distances and travel times are approximate from Horizon Sands Bali.
                    The concierge team can arrange transport, guides, and tickets for any of the below.
                </p>
            </div>

            <div class="wh-feature-list">
                <?php foreach ($highlights as $item): ?>
                    <article class="wh-feature-row reveal-up">
                        <div class="wh-feature-media">
                            <img
                                src="<?php echo wh_e(wh_image_src($item['image'], $item['fallback'] ?? 'assets/images/DiscoverMore.webp')); ?>"
                                alt="<?php echo wh_e($item['alt']); ?>"
                                class="wh-feature-image"
                                loading="lazy"
                                decoding="async"
                            >
                            <span class="wh-feature-badge"><?php echo wh_e($item['badge']); ?></span>
                        </div>
                        <div class="wh-feature-content">
                            <p class="wh-feature-category"><?php echo wh_e($item['category']); ?></p>
                            <h3><?php echo wh_e($item['title']); ?></h3>
                            <p class="wh-feature-description"><?php echo wh_e($item['description']); ?></p>
                            <dl class="wh-detail-row">
                                <div class="wh-detail-item">
                                    <dt>Location</dt>
                                    <dd><?php echo wh_e($item['location']); ?></dd>
                                </div>
                                <div class="wh-detail-item">
                                    <dt>Timing</dt>
                                    <dd><?php echo wh_e($item['timing']); ?></dd>
                                </div>
                                <div class="wh-detail-item">
                                    <dt>Distance</dt>
                                    <dd><?php echo wh_e($item['distance']); ?></dd>
                                </div>
                                <div class="wh-detail-item wh-detail-item--full">
                                    <dt>Booking</dt>
                                    <dd><?php echo wh_e($item['booking']); ?></dd>
                                </div>
                            </dl>
                            <a href="contact.php" class="btn btn-dark rounded-pill px-4 wh-cta-btn">Ask Concierge</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== LOCAL EXPERIENCES ===== -->
    <section id="local-experiences" class="wh-section wh-section-muted">
        <div class="container">
            <div class="wh-section-heading reveal-up">
                <p class="wh-section-label">Local Experiences</p>
                <h2>Hands-on activities curated for guests.</h2>
                <p class="wh-section-subtext">
                    All experiences can be arranged through the hotel concierge. Prices and availability
                    are subject to change — speak to the front desk for current rates and scheduling.
                </p>
            </div>

            <div class="wh-experience-grid reveal-up">
                <?php foreach ($localExperiences as $exp): ?>
                    <article class="wh-experience-card">
                        <div class="wh-exp-icon" aria-hidden="true"><?php echo $exp['icon']; ?></div>
                        <h3><?php echo wh_e($exp['title']); ?></h3>
                        <p class="wh-exp-desc"><?php echo wh_e($exp['desc']); ?></p>
                        <span class="wh-exp-info"><?php echo wh_e($exp['info']); ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== FERRY SERVICES ===== -->
    <section id="ferry-services" class="wh-section">
        <div class="container">
            <div class="wh-section-heading reveal-up">
                <p class="wh-section-label">Ferry Services</p>
                <h2>Island-hopping from Bali.</h2>
                <p class="wh-section-subtext">
                    Bali's harbour network connects you to some of the region's most stunning islands.
                    Routes and schedules below reflect typical services — always confirm times directly
                    with operators before travel, especially during peak season.
                </p>
            </div>

            <div class="wh-ferry-list">
                <?php foreach ($ferryRoutes as $i => $route): ?>
                    <article class="wh-ferry-card reveal-up <?php echo ($i % 2 === 1) ? 'wh-ferry-card--reverse' : ''; ?>">
                        <div class="wh-ferry-media">
                            <img
                                src="<?php echo wh_e(wh_image_src($route['image'], $route['fallback'] ?? 'assets/images/DiscoverMore.webp')); ?>"
                                alt="<?php echo wh_e($route['alt']); ?>"
                                class="wh-ferry-image"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>
                        <div class="wh-ferry-content">
                            <p class="wh-feature-category"><?php echo wh_e($route['operator']); ?></p>
                            <h3><?php echo wh_e($route['title']); ?></h3>

                            <dl class="wh-ferry-details">
                                <div class="wh-ferry-detail-item">
                                    <dt>Departs From</dt>
                                    <dd><?php echo wh_e($route['from']); ?></dd>
                                </div>
                                <div class="wh-ferry-detail-item">
                                    <dt>Arrives At</dt>
                                    <dd><?php echo wh_e($route['to']); ?></dd>
                                </div>
                                <div class="wh-ferry-detail-item">
                                    <dt>Journey Time</dt>
                                    <dd><?php echo wh_e($route['duration']); ?></dd>
                                </div>
                                <div class="wh-ferry-detail-item">
                                    <dt>Typical Fare</dt>
                                    <dd><?php echo wh_e($route['fare']); ?></dd>
                                </div>
                                <div class="wh-ferry-detail-item wh-ferry-detail--full">
                                    <dt>Schedule</dt>
                                    <dd><?php echo wh_e($route['schedule']); ?></dd>
                                </div>
                                <div class="wh-ferry-detail-item wh-ferry-detail--full">
                                    <dt>Booking</dt>
                                    <dd><?php echo wh_e($route['booking']); ?></dd>
                                </div>
                            </dl>

                            <div class="wh-ferry-highlights">
                                <p class="wh-highlights-label">Island highlights</p>
                                <ul class="wh-highlights-list">
                                    <?php foreach ($route['highlights'] as $hl): ?>
                                        <li><?php echo wh_e($hl); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <a href="contact.php" class="btn btn-dark rounded-pill px-4 wh-cta-btn">Ask Concierge</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="wh-ferry-note reveal-up">
                <p>
                    <strong>Please note:</strong> Ferry schedules and fares are subject to change, particularly during
                    peak holiday periods and adverse weather conditions. We strongly recommend confirming times with
                    your operator before travel. The hotel concierge is happy to assist with up-to-date information
                    and transport coordination.
                </p>
            </div>
        </div>
    </section>

    <!-- ===== CONCIERGE CTA ===== -->
    <section id="concierge-cta" class="wh-cta-section reveal-up">
        <div class="container">
            <div class="wh-cta-card">
                <div class="wh-cta-text">
                    <p class="wh-section-label">We're Here to Help</p>
                    <h2>Let our concierge plan your perfect day out.</h2>
                    <p>
                        From booking ferry tickets and arranging private transfers to recommending the
                        best local guides — our concierge team is available 24 hours a day to help you
                        make the most of your time in Bali.
                    </p>
                    <div class="wh-cta-actions">
                        <a href="contact.php" class="btn btn-dark rounded-pill px-5">Contact Concierge</a>
                        <a href="parking_and_transport.php" class="btn btn-outline-dark rounded-pill px-5">Transport Guide</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
