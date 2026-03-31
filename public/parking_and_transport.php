<?php
require_once __DIR__ . '/../app/includes/auth.php';
include __DIR__ . '/../app/includes/navbar.php';
?>

<link rel="stylesheet" href="assets/css/parking_transport.css">

<main class="transport-page">
    <section class="transport-hero">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <span class="transport-eyebrow">Arrival Guide</span>
                    <h1 class="transport-title">Parking &amp; Transport</h1>
                    <p class="transport-subtitle mb-0">
                        Everything guests need for a smooth arrival — parking options, airport transfers,
                        public transport access, and nearby travel times.
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

            <div class="transport-info-card">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6">
                        <h2 class="section-title mb-2">Getting to Horizon Sands Bali</h2>
                        <p class="section-text mb-0">
                            Choose the most convenient option based on how you are arriving. Use the tabs below
                            to view key details for parking, airport transfers, and public transport.
                        </p>
                    </div>
                    <div class="col-lg-6">
                        <div class="hotel-address-box">
                            <span class="address-label">Hotel Address</span>
                            <div class="address-line-wrap">
                                <p id="hotelAddress" class="hotel-address mb-0">
                                    Horizon Sands Bali, 123 Marina View, Singapore 018960
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
                        <div class="feature-icon">🚆</div>
                        <h3>Public Transport</h3>
                        <p>
                            The hotel is well-connected to nearby MRT stations and bus routes, making city travel simple and efficient.
                        </p>
                        <ul class="feature-list">
                            <li>Nearby MRT access</li>
                            <li>Bus connectivity</li>
                            <li>Quick city links</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="transport-tabs-card mt-4">
                <div class="transport-tab-buttons" id="transportTabButtons">
                    <button type="button" class="transport-tab-btn active" data-target="parkingPanel">Parking</button>
                    <button type="button" class="transport-tab-btn" data-target="airportPanel">Airport Transfer</button>
                    <button type="button" class="transport-tab-btn" data-target="publicPanel">Public Transport</button>
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
                                        <li>Contact the hotel in advance for special assistance</li>
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
                                            <span class="info-value">Arranged through concierge or front desk</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Suitable For</span>
                                            <span class="info-value">Late-night arrivals, families, premium transfers</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Recommendation</span>
                                            <span class="info-value">Reserve before arrival for smoother pickup coordination</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Support</span>
                                            <span class="info-value">24/7 front desk assistance available</span>
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
                                        <li>Check luggage requirements if needed</li>
                                        <li>Contact the hotel for urgent coordination</li>
                                    </ul>
                                    <button type="button" class="btn btn-dark rounded-pill px-4 mt-2 toggle-extra-btn" data-extra="airportExtra">
                                        Show More
                                    </button>
                                    <div class="extra-content" id="airportExtra">
                                        <p class="mb-0 mt-3">
                                            Transfers are ideal for guests who want a more direct and stress-free journey from the airport to the hotel.
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
                                    <h3>Public Transport Access</h3>
                                    <div class="status-badges">
                                        <span class="status-badge">MRT Nearby</span>
                                        <span class="status-badge">Bus Connectivity</span>
                                        <span class="status-badge">City-Friendly Location</span>
                                    </div>

                                    <div class="info-grid">
                                        <div class="info-row">
                                            <span class="info-label">Nearest MRT</span>
                                            <span class="info-value">Within short walking distance</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Bus Access</span>
                                            <span class="info-value">Multiple nearby bus stops available</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Best For</span>
                                            <span class="info-value">Solo travellers, light luggage, city exploration</span>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Convenience</span>
                                            <span class="info-value">Fast access to major shopping and downtown areas</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="detail-card h-100">
                                    <h3>Public Transport Tips</h3>
                                    <ul class="detail-list">
                                        <li>Travel lighter for smoother station access</li>
                                        <li>Check train timings during late-night travel</li>
                                        <li>Use ride-hailing for the final leg if needed</li>
                                        <li>Ask the front desk for local route guidance</li>
                                    </ul>
                                    <button type="button" class="btn btn-dark rounded-pill px-4 mt-2 toggle-extra-btn" data-extra="publicExtra">
                                        Show More
                                    </button>
                                    <div class="extra-content" id="publicExtra">
                                        <p class="mb-0 mt-3">
                                            Public transport is one of the most practical ways to move around the city if you are travelling without bulky luggage.
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
                        <h4>Airport</h4>
                        <p>20–25 mins by car</p>
                    </div>
                    <div class="travel-time-item">
                        <h4>Marina Bay</h4>
                        <p>10 mins by car</p>
                    </div>
                    <div class="travel-time-item">
                        <h4>Orchard Road</h4>
                        <p>15–20 mins by MRT</p>
                    </div>
                    <div class="travel-time-item">
                        <h4>Business District</h4>
                        <p>10–15 mins by car</p>
                    </div>
                    <div class="travel-time-item">
                        <h4>Nearby MRT</h4>
                        <p>Short walk from hotel</p>
                    </div>
                    <div class="travel-time-item">
                        <h4>Shopping Area</h4>
                        <p>15 mins by public transport</p>
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
                        <div id="arrivalCollapseOne" class="accordion-collapse collapse" aria-labelledby="arrivalHeadingOne" data-bs-parent="#arrivalAccordion">
                            <div class="accordion-body">
                                Accessible parking spaces are positioned near primary access routes where available. Guests requiring additional arrival assistance are encouraged to contact the hotel before check-in.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item custom-accordion-item">
                        <h2 class="accordion-header" id="arrivalHeadingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#arrivalCollapseTwo" aria-expanded="false" aria-controls="arrivalCollapseTwo">
                                Peak-hour arrival guidance
                            </button>
                        </h2>
                        <div id="arrivalCollapseTwo" class="accordion-collapse collapse" aria-labelledby="arrivalHeadingTwo" data-bs-parent="#arrivalAccordion">
                            <div class="accordion-body">
                                During peak periods, guests may wish to allow additional time for traffic flow, parking access, and lobby arrivals, especially on weekends and public holidays.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item custom-accordion-item">
                        <h2 class="accordion-header" id="arrivalHeadingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#arrivalCollapseThree" aria-expanded="false" aria-controls="arrivalCollapseThree">
                                Special requests before arrival
                            </button>
                        </h2>
                        <div id="arrivalCollapseThree" class="accordion-collapse collapse" aria-labelledby="arrivalHeadingThree" data-bs-parent="#arrivalAccordion">
                            <div class="accordion-body">
                                For mobility support, larger vehicle access, luggage assistance, or transfer coordination, guests should contact the hotel ahead of time for smoother arrangements.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-cta-card mt-4">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-8">
                        <h3 class="mb-2">Need arrival help?</h3>
                        <p class="mb-0">
                            Our team can assist with transport planning, special access requests, and pre-arrival support.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="FAQs.php" class="btn btn-outline-dark rounded-pill px-4 me-2 mb-2 mb-lg-0">View FAQ</a>
                        <a href="contact.php" class="btn btn-dark rounded-pill px-4">Contact Us</a>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>

<script src="assets/js/parking_transport.js"></script>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
