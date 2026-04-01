<?php
require_once __DIR__ . '/../app/includes/auth.php';
$pageStylesheets = ['assets/css/parking_transport.css'];
$pageScripts = ['assets/js/parking_transport.js'];
include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="transport-page">
    <section class="transport-hero">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <h1 class="transport-title">Parking &amp; Transport</h1>
                    <p class="transport-subtitle mb-0">
                        Everything guests need for a smooth arrival — parking options, airport transfers,
                        local travel notes, and nearby travel times.
                    </p>
                </div>

                <div class="col-lg-5">
                    <div class="transport-hero-card">
                        <div class="hero-stat-grid">
                            <div class="hero-stat-box">
                                <span class="hero-stat-number">24/7</span>
                                <span class="hero-stat-label">Front desk support</span>
                            </div>
                            <div class="hero-stat-box">
                                <span class="hero-stat-number">3</span>
                                <span class="hero-stat-label">Main arrival options</span>
                            </div>
                            <div class="hero-stat-box hero-stat-box-wide">
                                <span class="hero-stat-number">Easy</span>
                                <span class="hero-stat-label">Arrival planning for guests and visitors</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="transport-main-section">
        <div class="container">

            <div id="getting-to-horizon" class="transport-info-card">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6">
                        <h2 class="section-title mb-2">Getting to Horizon Sands Bali</h2>
                        <p class="section-text mb-0">
                            Choose the most convenient option based on how you are arriving. Check out the
                            information below to view details for parking, airport transfers, and local travel.
                        </p>
                    </div>
                    <div class="col-lg-6">
                        <div class="hotel-address-box">
                            <span class="address-label">Hotel Address</span>
                            <div class="address-line-wrap">
                                <p id="hotelAddress" class="hotel-address mb-0">
                                    Horizon Sands Bali, Sunset Bay Drive, Azure Coast, Bali 80361
                                </p>
                                <button type="button" id="copyAddressBtn" class="btn btn-outline-dark rounded-pill btn-sm">
                                    Copy Address
                                </button>
                            </div>
                            <small id="copyStatus" class="copy-status"></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-1">
                <div class="col-lg-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon">🚗</div>
                        <h3>On-Site Parking</h3>
                        <p>
                            Secure self-parking is available for hotel guests and visitors, with clear access instructions and convenient drop-off zones.
                        </p>
                        <ul class="feature-list">
                            <li>Self-parking available</li>
                            <li>Guest drop-off point</li>
                            <li>Accessible parking lots</li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon">✈️</div>
                        <h3>Airport Transfer</h3>
                        <p>
                            Guests may arrange airport transfers through the concierge for a more seamless and premium arrival experience.
                        </p>
                        <ul class="feature-list">
                            <li>Pre-arranged pickup</li>
                            <li>Concierge coordination</li>
                            <li>Suitable for early arrivals</li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="feature-card h-100">
                        <div class="feature-icon">🚕</div>
                        <h3>Local Travel</h3>
                        <p>
                            Bali is easiest to explore by ride-hailing, private driver, or arranged harbour transfer depending on your plans.
                        </p>
                        <ul class="feature-list">
                            <li>Ride-hailing friendly</li>
                            <li>Private driver options</li>
                            <li>Harbour and ferry links</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="transport-tabs-card mt-4">
                <div class="transport-tab-buttons" id="transportTabButtons">
                    <button type="button" class="transport-tab-btn active" data-target="parkingPanel">Parking</button>
                    <button type="button" class="transport-tab-btn" data-target="airportPanel">Airport Transfer</button>
                    <button type="button" class="transport-tab-btn" data-target="publicPanel">Local Travel</button>
                </div>

                <div class="transport-tab-content">
                    <div class="transport-tab-panel active" id="parkingPanel">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <div class="detail-card h-100">
                                    <h3>Parking Information</h3>
                                    <div class="status-badges">
                                        <span class="status-badge">Self-Park Available</span>
                                        <span class="status-badge">Accessible Lots</span>
                                        <span class="status-badge">Guest Drop-off</span>
                                    </div>

                                    <div class="info-grid">
                                        <div class="info-row">
                                            <span class="info-label">Parking Type</span>
                                            <span class="info-value">On-site self-parking</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Access</span>
                                            <span class="info-value">Via main driveway entrance</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Guest Use</span>
                                            <span class="info-value">Available for in-house guests</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Accessibility</span>
                                            <span class="info-value">Accessible parking near lobby access</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="detail-card h-100">
                                    <h3>Arrival Notes</h3>
                                    <div class="mini-alert">
                                        Please follow on-site directional signage upon arrival for parking and guest drop-off.
                                    </div>
                                    <ul class="detail-list">
                                        <li>Use the main hotel driveway for arrival</li>
                                        <li>Lobby access is clearly signposted</li>
                                        <li>Parking availability may vary during peak periods</li>
                                        <li>Contact the hotel in advance for accessible or oversized-vehicle support</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="transport-tab-panel" id="airportPanel">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <div class="detail-card h-100">
                                    <h3>Airport Transfer Options</h3>
                                    <div class="status-badges">
                                        <span class="status-badge">Advance Booking Recommended</span>
                                        <span class="status-badge">Concierge Assisted</span>
                                    </div>

                                    <div class="info-grid">
                                        <div class="info-row">
                                            <span class="info-label">Booking</span>
                                            <span class="info-value">Arranged through concierge or front desk before arrival</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Suitable For</span>
                                            <span class="info-value">Airport arrivals, families, evening flights, premium transfers</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Recommendation</span>
                                            <span class="info-value">Reserve before arrival for smoother Ngurah Rai pickup coordination</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Support</span>
                                            <span class="info-value">24/7 front desk assistance and arrival guidance</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="detail-card h-100">
                                    <h3>Before You Arrive</h3>
                                    <ul class="detail-list">
                                        <li>Share your flight details in advance</li>
                                        <li>Confirm expected arrival timing</li>
                                        <li>Check luggage and child-seat requirements if needed</li>
                                        <li>Allow extra road time during late afternoon and evening traffic</li>
                                    </ul>
                                    <button type="button" class="btn btn-dark rounded-pill px-4 mt-2 toggle-extra-btn" data-extra="airportExtra">
                                        Show More
                                    </button>
                                    <div class="extra-content" id="airportExtra">
                                        <p class="mb-0 mt-3">
                                            Transfers are ideal for guests who want a direct and stress-free journey from Ngurah Rai International Airport to the hotel.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="transport-tab-panel" id="publicPanel">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <div class="detail-card h-100">
                                    <h3>Local Travel &amp; Transfers</h3>
                                    <div class="status-badges">
                                        <span class="status-badge">Ride-Hailing Friendly</span>
                                        <span class="status-badge">Driver Ready</span>
                                        <span class="status-badge">Harbour Connections</span>
                                    </div>

                                    <div class="info-grid">
                                        <div class="info-row">
                                            <span class="info-label">Best Modes</span>
                                            <span class="info-value">Ride-hailing, taxi, private driver, or arranged transfer</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Harbour Access</span>
                                            <span class="info-value">Useful for Sanur and other ferry departure points</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Best For</span>
                                            <span class="info-value">Beach outings, sunset plans, day trips, island transfers</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Convenience</span>
                                            <span class="info-value">Easy hotel pickup and flexible local routing</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="detail-card h-100">
                                    <h3>Local Travel Tips</h3>
                                    <ul class="detail-list">
                                        <li>Use ride-hailing for short coastal and dining trips</li>
                                        <li>Choose a private driver for longer outings like Ubud or Uluwatu</li>
                                        <li>Leave earlier for ferry departures and sunset destinations</li>
                                        <li>Ask the front desk for route guidance and pickup help</li>
                                    </ul>
                                    <button type="button" class="btn btn-dark rounded-pill px-4 mt-2 toggle-extra-btn" data-extra="publicExtra">
                                        Show More
                                    </button>
                                    <div class="extra-content" id="publicExtra">
                                        <p class="mb-0 mt-3">
                                            Local transfers are often the most practical option in Bali, especially if you are planning day trips or travelling with bags.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="travel-times-card mt-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h2 class="section-title mb-1">Nearby Travel Times</h2>
                        <p class="section-text mb-0">
                            Estimated travel times from the hotel for popular destinations.
                        </p>
                    </div>
                    <span class="travel-note-badge">Estimated times only</span>
                </div>

                <div class="travel-time-grid">
                    <div class="travel-time-item">
                        <h3>Ngurah Rai Airport</h3>
                        <p>30–45 mins by car</p>
                    </div>
                    <div class="travel-time-item">
                        <h3>Seminyak Beach</h3>
                        <p>20–30 mins by car</p>
                    </div>
                    <div class="travel-time-item">
                        <h3>Sanur Harbour</h3>
                        <p>30–45 mins by car</p>
                    </div>
                    <div class="travel-time-item">
                        <h3>Uluwatu Area</h3>
                        <p>40–50 mins by car</p>
                    </div>
                    <div class="travel-time-item">
                        <h3>Ubud Centre</h3>
                        <p>70–90 mins by car</p>
                    </div>
                    <div class="travel-time-item">
                        <h3>Beach Clubs &amp; Dining</h3>
                        <p>15–25 mins by car</p>
                    </div>
                </div>
            </div>

            <div class="arrival-accordion-card mt-4">
                <h2 class="section-title mb-3">Useful Arrival Notes</h2>

                <div class="accordion" id="arrivalAccordion">
                    <div class="accordion-item custom-accordion-item">
                        <h2 class="accordion-header" id="arrivalHeadingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#arrivalCollapseOne" aria-expanded="false" aria-controls="arrivalCollapseOne">
                                Accessible parking and guest assistance
                            </button>
                        </h2>
                        <div id="arrivalCollapseOne" class="accordion-collapse collapse" role="region" aria-labelledby="arrivalHeadingOne" data-bs-parent="#arrivalAccordion">
                            <div class="accordion-body">
                                Accessible parking spaces are positioned near primary access routes where available. Guests requiring additional arrival assistance are encouraged to contact the hotel before check-in.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item custom-accordion-item">
                        <h2 class="accordion-header" id="arrivalHeadingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#arrivalCollapseTwo" aria-expanded="false" aria-controls="arrivalCollapseTwo">
                                Peak-hour and day-trip guidance
                            </button>
                        </h2>
                        <div id="arrivalCollapseTwo" class="accordion-collapse collapse" role="region" aria-labelledby="arrivalHeadingTwo" data-bs-parent="#arrivalAccordion">
                            <div class="accordion-body">
                                During peak periods, allow additional time for airport pickups, sunset journeys, and harbour departures, especially on weekends and public holidays. For local day-trip inspiration, check out the latest happenings <a href="whats_happening.php">here</a> or let our concierge help plan the perfect outing for you!
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item custom-accordion-item">
                        <h2 class="accordion-header" id="arrivalHeadingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#arrivalCollapseThree" aria-expanded="false" aria-controls="arrivalCollapseThree">
                                Special requests before arrival
                            </button>
                        </h2>
                        <div id="arrivalCollapseThree" class="accordion-collapse collapse" role="region" aria-labelledby="arrivalHeadingThree" data-bs-parent="#arrivalAccordion">
                            <div class="accordion-body">
                                For mobility support, larger vehicle access, luggage assistance, or transfer coordination, guests should contact the hotel ahead of time for smoother arrangements.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-cta-card mt-4">
                <div class="row g-3">
                    <div class="col-12">
                        <h3 class="mb-2">Need arrival help?</h3>
                        <p class="mb-0">
                            Our team can assist with transport planning, hotel directions, special access requests, and pre-arrival support.
                        </p>
                    </div>
                    <div class="col-12">
                        <div class="transport-cta-actions">
                            <a href="FAQs.php" class="btn btn-outline-dark rounded-pill px-4 transport-cta-btn">View FAQ</a>
                            <a href="contact.php" class="btn btn-dark rounded-pill px-4 transport-cta-btn">Contact Us</a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
