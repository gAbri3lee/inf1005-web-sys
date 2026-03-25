<?php
session_start();
include __DIR__ . '/../app/includes/navbar.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$featuredAmenities = [
    [
        'title' => 'Club Lounge',
        'category' => 'Dining & Lounge',
        'description' => 'An elegant social lounge for cocktails, light bites, and relaxation in a calm indoor setting.',
        'hours' => 'Daily, 2:00 PM - 11:00 PM',
        'image' => 'assets/images/amenities/club_lounge.png',
        'alt' => 'Elegant lounge with chandelier lighting, seated guests, and a polished cocktail bar',
        'href' => '#lounge-section',
        'cta' => 'Learn More',
    ],
    [
        'title' => 'Outdoor Pool',
        'category' => 'Recreation',
        'description' => 'Cool off by the pool to relax and unwind, take a refreshing dip or lounge on a sunbed with a drink in hand. The pool is a great place to escape from the busy hustle and bustle.',
        'hours' => 'Daily, 7:00 AM - 9:00 PM',
        'image' => 'assets/images/amenities/outdoor_pool.png',
        'alt' => 'Outdoor pool lined with palms and loungers for a relaxing view',
        'href' => '#pool-section',
        'cta' => 'Learn More',
    ],
    [
        'title' => 'Fitness Studio',
        'category' => 'Wellness',
        'description' => 'Start off your day or unwind from a hectic day with our modern 24-hour fitness studio featuring state-of-the-art equipment. Stay active away from home and enjoy a seamless workout experience.',
        'hours' => 'Open 24 hours for guests',
        'image' => 'assets/images/amenities/fitness_studio.png',
        'alt' => 'Fitness studio with treadmills, strength equipment, towels, and resort view',
        'href' => '#fitness-section',
        'cta' => 'Learn More',
    ],
    [
        'title' => 'Spa Sanctuary',
        'category' => 'Wellness',
        'description' => 'Rest and rejuvenate in our Spa Sanctuary. Enjoy a luxurious spa treatment where soothing treatments and serene surroundings relax your body and mind.',
        'hours' => 'Daily, 10:00 AM - 9:00 PM',
        'image' => 'assets/images/amenities/spa.png',
        'alt' => 'Spa treatment room with twin massage beds, soft lighting, and a garden-facing window',
        'href_primary' => 'contact.php',
        'href_secondary' => '#spa-section',
        'cta_primary' => 'Book Now',
        'cta_secondary' => 'Learn More',
    ],

];

$hotelFeatures = [
    ['label' => 'Housekeeping', 'value' => 'Daily service'],
    ['label' => 'Wi-Fi', 'value' => 'Complimentary'],
    ['label' => 'Parking', 'value' => 'On-site'],
    ['label' => 'Pool', 'value' => 'Outdoor'],
    ['label' => 'Fitness Center', 'value' => '24-hour access'],
    ['label' => 'Lounge', 'value' => 'Evening service'],
    ['label' => 'Accessibility', 'value' => 'Support available'],
    ['label' => 'Public transport', 'value' => 'Nearby access'],
];

$hotelDetails = [
    ['label' => 'Front Desk', 'value' => '24-hour guest assistance'],
    ['label' => 'Check-in', 'value' => 'From 3:00 PM'],
    ['label' => 'Check-out', 'value' => 'By 11:00 AM'],
    ['label' => 'Languages', 'value' => 'English, Mandarin'],
    ['label' => 'Payment', 'value' => 'Major cards accepted'],
    ['label' => 'Accessibility', 'value' => 'Wheelchair access available, assistance on request'],
    ['label' => 'Hotel Type', 'value' => 'Luxury resort stay'],
    ['label' => 'Guest Convenience', 'value' => 'Concierge, luggage support, housekeeping'],
    ['label' => 'Attractions', 'value' => 'Rental services available'],
];

$fitnessDetails = [
    ['label' => 'Hours', 'value' => 'Open 24 hours for in-house guests'],
    ['label' => 'Equipment', 'value' => 'Treadmills, bikes, free weights, benches, mats'],
    ['label' => 'Support', 'value' => 'Guided wellness sessions available on selected mornings'],
    ['label' => 'Access', 'value' => 'Room key required for entry'],
];

$parkingDetails = [
    ['label' => 'Parking Type', 'value' => 'On-site self-parking'],
    ['label' => 'Accessible Parking', 'value' => 'Available near primary access routes'],
    ['label' => 'Transport Support', 'value' => 'Airport transfer coordination through concierge'],
    ['label' => 'Arrival Note', 'value' => 'Advance notice recommended for special access assistance'],
];

$poolDetails = [
    ['label' => 'Hours', 'value' => '7:00 AM - 9:00 PM'],
    ['label' => 'Pool Type', 'value' => 'Outdoor skyline pool'],
    ['label' => 'Included', 'value' => 'Loungers, towels, shaded seating'],
    ['label' => 'Best For', 'value' => 'Morning swims and sunset relaxation'],
];

$serviceGroups = [
    [
        'title' => 'Guest Services',
        'items' => [
            '24-hour concierge and front desk support',
            'Celebration and itinerary assistance',
            'Luggage assistance on request',
        ],
    ],
    [
        'title' => 'Housekeeping & Laundry',
        'items' => [
            'Daily housekeeping service',
            'Laundry Service available on request',
            'Dry cleaning pickup (request from concierge)',
        ],
    ],
    [
        'title' => 'Dining Support',
        'items' => [
            'Club lounge evening service',
            'In-house dining available during select hours',
            'Restaurant reservations through concierge',
        ],
    ],
];
?>

<link rel="stylesheet" href="assets/css/amenities.css">

<main class="amenities-page">
    <section class="amenities-hero">
        <div class="container">
            <div class="amenities-hero-shell reveal-up">
                <p class="amenities-kicker">At This Hotel</p>
                <h1 class="amenities-hero-title">Amenities at Horizon Sands Bali</h1>
                <p class="amenities-hero-text">
                    Everything you need for a relaxing stay is right here. 
                    Discover the facilities, services, and guest conveniences available 
                    from comfort to recreational activities, all designed to enhance your experience at Horizon Sands Bali
                </p>
            </div>
                <div class="amenities-hero-meta" aria-label="Amenities summary">
                    <div class="amenities-meta-item">
                        <strong>4</strong>
                        <span>featured amenities</span>
                    </div>
                    <div class="amenities-meta-item">
                        <strong>24/7</strong>
                        <span>guest support</span>
                    </div>
                    <div class="amenities-meta-item">
                        <strong>On-site</strong>
                        <span>parking and leisure facilities</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <nav class="amenities-anchor-nav" aria-label="Amenities sections">
        <div class="container">
            <div class="amenities-anchor-links">
                <a href="#featured-amenities">Featured Amenities</a>
                <a href="#hotel-features">Hotel Features</a>
                <a href="#at-this-hotel">At This Hotel</a>
                <a href="#hotel-details">Hotel Details</a>
                <a href="#services-section">Services</a>
            </div>
        </div>
    </nav>

    <section id="featured-amenities" class="amenities-section">
        <div class="container">
            <div class="amenities-section-heading reveal-up">
                <p class="amenities-section-label">Featured Amenities</p>
                <h2>Explore the signature spaces across the resort.</h2>
            </div>

            <div class="amenity-feature-list">
                <?php foreach ($featuredAmenities as $amenity): ?>
                    <article class="amenity-feature-row reveal-up">
                        <div class="amenity-feature-media">
                            <img
                                src="<?php echo e($amenity['image']); ?>"
                                alt="<?php echo e($amenity['alt']); ?>"
                                class="amenity-feature-image"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>

                        <div class="amenity-feature-content">
                            <p class="amenity-feature-category"><?php echo e($amenity['category']); ?></p>
                            <h3><?php echo e($amenity['title']); ?></h3>
                            <p class="amenity-feature-description"><?php echo e($amenity['description']); ?></p>
                            <p class="amenity-feature-hours"><strong>Hours:</strong> <?php echo e($amenity['hours']); ?></p>
                            <?php if (isset($amenity['cta_primary'], $amenity['href_primary'], $amenity['cta_secondary'], $amenity['href_secondary'])): ?>
                                <div class="amenity-feature-actions">
                                    <a href="<?php echo e($amenity['href_primary']); ?>" class="btn btn-dark rounded-pill px-4"><?php echo e($amenity['cta_primary']); ?></a>
                                    <a href="<?php echo e($amenity['href_secondary']); ?>" class="btn btn-outline-dark rounded-pill px-4"><?php echo e($amenity['cta_secondary']); ?></a>
                                </div>
                            <?php else: ?>
                                <a href="<?php echo e($amenity['href']); ?>" class="amenity-feature-link"><?php echo e($amenity['cta']); ?></a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="hotel-features" class="amenities-section amenities-section-muted">
        <div class="container">
            <div class="amenities-section-heading reveal-up">
                <p class="amenities-section-label">Hotel Features</p>
                <h2>Key amenities and conveniences at a glance.</h2>
            </div>

            <div class="hotel-features-grid reveal-up">
                <?php foreach ($hotelFeatures as $feature): ?>
                    <article class="hotel-feature-item">
                        <h3><?php echo e($feature['label']); ?></h3>
                        <p><?php echo e($feature['value']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="at-this-hotel" class="amenities-section">
        <div class="container">
            <div class="amenities-section-heading reveal-up">
                <p class="amenities-section-label">At This Hotel</p>
                <h2>Detailed information about facilities, access, and guest services.</h2>
            </div>

            <div class="amenity-detail-stack">
                <section id="hotel-details" class="detail-block reveal-up">
                    <div class="detail-block-heading">
                        <h3>Hotel Details</h3>
                        <p>Essential information for planning arrival, check-in, payment, and accessibility needs.</p>
                    </div>
                    <dl class="detail-grid">
                        <?php foreach ($hotelDetails as $detail): ?>
                            <div class="detail-item">
                                <dt><?php echo e($detail['label']); ?></dt>
                                <dd><?php echo e($detail['value']); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>

                <section id="fitness-section" class="detail-block reveal-up">
                    <div class="detail-block-heading">
                        <h3>Fitness Center</h3>
                        <p>A guest-only fitness facility designed for flexible workouts throughout the day.</p>
                    </div>
                    <dl class="detail-grid">
                        <?php foreach ($fitnessDetails as $detail): ?>
                            <div class="detail-item">
                                <dt><?php echo e($detail['label']); ?></dt>
                                <dd><?php echo e($detail['value']); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                    <a href="contact.php" class="detail-link">Ask about wellness sessions</a>
                </section>

                <section id="parking-section" class="detail-block reveal-up">
                    <div class="detail-block-heading">
                        <h3>Parking and Transportation</h3>
                        <p>Arrival support for parking, accessible access, and pre-arranged transfers.</p>
                    </div>
                    <dl class="detail-grid">
                        <?php foreach ($parkingDetails as $detail): ?>
                            <div class="detail-item">
                                <dt><?php echo e($detail['label']); ?></dt>
                                <dd><?php echo e($detail['value']); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                    <a href="parking_and_transport.php" class="detail-link">View parking and transport page</a>
                </section>

                <section id="pool-section" class="detail-block reveal-up">
                    <div class="detail-block-heading">
                        <h3>Pool</h3>
                        <p>The outdoor pool remains one of the resort’s signature leisure spaces for both active and relaxed use.</p>
                    </div>
                    <dl class="detail-grid">
                        <?php foreach ($poolDetails as $detail): ?>
                            <div class="detail-item">
                                <dt><?php echo e($detail['label']); ?></dt>
                                <dd><?php echo e($detail['value']); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                    <a href="rooms_and_suites.php" class="detail-link">Explore poolside stays</a>
                </section>

                <section id="lounge-section" class="detail-block reveal-up">
                    <div class="detail-block-heading">
                        <h3>Lounge</h3>
                        <p>An evening lounge experience with cocktails, light dining, and a refined indoor social setting.</p>
                    </div>
                    <dl class="detail-grid">
                        <div class="detail-item">
                            <dt>Hours</dt>
                            <dd>Daily, 2:00 PM - 11:00 PM</dd>
                        </div>
                        <div class="detail-item">
                            <dt>Experience</dt>
                            <dd>Curated drinks and small plates</dd>
                        </div>
                        <div class="detail-item">
                            <dt>Atmosphere</dt>
                            <dd>Relaxed evening setting</dd>
                        </div>
                        <div class="detail-item">
                            <dt>Best For</dt>
                            <dd>Pre-dinner or after-dinner gatherings</dd>
                        </div>
                    </dl>
                    <a href="Dining.php" class="detail-link">See dining and lounge details</a>
                </section>

                <section id="spa-section" class="detail-block reveal-up">
                    <div class="detail-block-heading">
                        <h3>Spa Sanctuary</h3>
                        <p>A calm treatment environment focused on restorative therapies and quiet reset time.</p>
                    </div>
                    <div class="detail-list-block">
                        <h4>Wellness highlights</h4>
                        <ul>
                            <li>Private treatment rooms</li>
                            <li>Advance reservations recommended</li>
                            <li>Relaxation tea service after treatment</li>
                            <li>Accessibility support available on request</li>
                        </ul>
                    </div>
                    <a href="contact.php" class="detail-link">Reserve a spa treatment</a>
                </section>

                <section id="services-section" class="detail-block reveal-up">
                    <div class="detail-block-heading">
                        <h3>Services</h3>
                        <p>Support services throughout the stay covering guest assistance, housekeeping, and dining coordination.</p>
                    </div>
                    <div class="service-groups">
                        <?php foreach ($serviceGroups as $group): ?>
                            <article class="service-group">
                                <h4><?php echo e($group['title']); ?></h4>
                                <ul>
                                    <?php foreach ($group['items'] as $item): ?>
                                        <li><?php echo e($item); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="detail-actions">
                        <a href="contact.php" class="btn btn-dark rounded-pill px-4">Contact Us</a>
                        <a href="Dining.php" class="btn btn-outline-dark rounded-pill px-4">Dining Page</a>
                    </div>
                </section>
            </div>
        </div>
    </section>
</main>

<script src="assets/js/amenities.js"></script>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
