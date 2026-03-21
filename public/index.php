<?php
session_start();
include __DIR__ . '/../app/includes/header.php';
?>

<main>
    <section class="hero-home">
        <div class="container hero-content">
            <div class="row">
                <div class="col-xl-8 col-lg-10">
                    <h1 class="hero-title">Dive Into Bliss</h1>
                    <p class="hero-subtitle">
                        Where every stay begins by the sea with coastal luxury, tranquil comfort and unforgettable moments.
                    </p>

                    <div class="hero-actions">
                        <a href="rooms_and_suites.php" class="btn btn-gold">Explore Suites & Villas</a>
                        <a href="Dining.php" class="btn btn-outline-light hero-dining-btn">Discover Dining</a>
                    </div>

                    <div class="hero-quick-links" aria-label="Quick access links">
                        <a class="hero-quick-link" href="about.php">About Us</a>
                        <a class="hero-quick-link" href="amenities.php">Amenities</a>
                        <a class="hero-quick-link" href="reviews.php">Guest Reviews</a>
                        <a class="hero-quick-link" href="contact.php">Contact Us</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 reveal-up">
                    <span class="section-eyebrow">Welcome to Azure Horizon</span>
                    <h2 class="section-title">A sunset-crafted stay designed to feel calm, warm and unforgettable.</h2>
                    <p class="section-text">
                        Nestled along the shoreline, Azure Horizon Resort & Spa offers a refined escape shaped by
                        ocean views, elegant interiors and moments of complete relaxation.
                    </p>
                    <p class="section-text">
                        From luxurious Suites & Villas to curated dining experiences and tranquil wellness spaces,
                        every detail is designed to turn each stay into a memorable coastal retreat.
                    </p>

                    <div class="stats-wrap">
                        <div class="stat-box">
                            <h3>3</h3>
                            <p>Signature suite styles</p>
                        </div>
                        <div class="stat-box">
                            <h3>2</h3>
                            <p>Distinct dining venues</p>
                        </div>
                        <div class="stat-box">
                            <h3>1</h3>
                            <p>Luxury coastal destination</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 reveal-up">
                    <img
                        src="assets/images/home/SpaRoom.png"
                        alt="Luxury spa room with a calm, elegant interior and resort-style ambience"
                        class="intro-image img-fluid"
                    >
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding section-soft">
        <div class="container">
            <div class="text-center mb-5 reveal-up">
                <span class="section-eyebrow">Stay beautifully</span>
                <h2 class="section-title">Featured Suites & Villas</h2>
                <p class="section-text mx-auto">
                    Discover elegant stays crafted for comfort, privacy and unforgettable views.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 reveal-up">
                    <article class="feature-card">
                        <img src="assets/images/home/Suite1.png" alt="Refined suite with king bed, warm wood finishes and sea-facing terrace">
                        <h3>Horizon Suite</h3>
                        <p>
                            A polished, ocean-facing suite with soft neutral tones, a private terrace and
                            golden-hour views designed for restful luxury.
                        </p>
                        <a href="rooms_and_suites.php" class="btn btn-outline-dark rounded-pill px-4">View Stay Options</a>
                    </article>
                </div>

                <div class="col-lg-4 reveal-up">
                    <article class="feature-card">
                        <img src="assets/images/home/Suite2.png" alt="Expansive resort exterior with pool, palm trees and sunset coastal backdrop">
                        <h3>Lagoon Villa</h3>
                        <p>
                            A villa-inspired stay that feels open, breezy and serene, perfect for guests drawn
                            to privacy, water views and long, unhurried evenings.
                        </p>
                        <a href="rooms_and_suites.php" class="btn btn-outline-dark rounded-pill px-4">View Stay Options</a>
                    </article>
                </div>

                <div class="col-lg-4 reveal-up">
                    <article class="feature-card">
                        <img src="assets/images/home/Suite3.png" alt="Grand duplex-style luxury suite with staircase, chandelier and dramatic interior design">
                        <h3>Celeste Duplex Villa</h3>
                        <p>
                            A dramatic two-level villa concept with statement architecture, generous space and
                            a distinctly elevated resort experience.
                        </p>
                        <a href="rooms_and_suites.php" class="btn btn-outline-dark rounded-pill px-4">View Stay Options</a>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="row align-items-end mb-5">
                <div class="col-lg-7 reveal-up">
                    <span class="section-eyebrow">Dining at Azure Horizon</span>
                    <h2 class="section-title">Two signature dining moods, one unforgettable coastline.</h2>
                </div>
                <div class="col-lg-5 reveal-up">
                    <p class="section-text">
                        Discover two distinctive dining experiences, from bold open-fire flavours at Fiery Blaze
                        to breezy beachfront evenings at Sunset Lagoon.
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6 reveal-up">
                    <article class="dining-card">
                        <img src="assets/images/home/RestaurantFieryBlaze.png" alt="Warm upscale grill restaurant interior with live fire cooking and elegant evening ambience">
                        <div class="dining-card-body">
                            <span class="badge-soft">Grill & Fire Dining</span>
                            <h3>Fiery Blaze</h3>
                            <p>
                                A refined grill house centred around open-fire cooking, rich evening ambience
                                and bold signature plates.
                            </p>
                            <a href="Dining.php" class="btn btn-outline-dark rounded-pill px-4">See Dining Page</a>
                        </div>
                    </article>
                </div>

                <div class="col-lg-6 reveal-up">
                    <article class="dining-card">
                        <img src="assets/images/home/RestaurantSunsetLagoon.png" alt="Beachfront restaurant with ocean view, open-air seating and sunset dining atmosphere">
                        <div class="dining-card-body">
                            <span class="badge-soft">Beach Club & Bar</span>
                            <h3>Sunset Lagoon</h3>
                            <p>
                                A breezy beachfront dining space where guests can enjoy coastal dishes,
                                ocean views and relaxed sunset-side evenings.
                            </p>
                            <a href="Dining.php" class="btn btn-outline-dark rounded-pill px-4">See Dining Page</a>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="cta-section text-center reveal-up">
                <span class="section-eyebrow text-white">Discover more</span>
                <h2 class="section-title">Unwind in elegance, embrace the horizon.</h2>
                <p class="section-text mx-auto mb-4">
                    Explore the story behind Azure Horizon Resort & Spa and discover the inspiration
                    behind its coastal charm, luxurious stays and signature experiences.
                </p>
                <a href="about.php" class="cta-button">Explore More</a>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
