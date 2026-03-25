<?php
session_start();
include __DIR__ . '/../app/includes/navbar.php';
?>

<main>
    <section class="page-hero page-hero-dining">
        <div class="container page-hero-content">
            <div class="row">
                <div class="col-lg-8 col-xl-7 reveal-up">
                    <span class="hero-tag">Dining at Azure Horizon</span>
                    <h1 class="page-hero-title">Three distinct venues, each shaped by the coast.</h1>
                    <p class="page-hero-text">
                        From open-fire grilling indoors under dramatic vaulted ceilings, to beachfront dining
                        with the tide at your feet and slow mornings in our all-day café, every meal at
                        Azure Horizon is designed to feel immersive, relaxed and memorable.
                    </p>
                    <div class="hero-actions">
                        <a href="#venues" class="btn btn-gold">Explore Our Venues</a>
                        <a href="contact.php" class="btn btn-outline-light hero-dining-btn">Plan a Reservation Enquiry</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 reveal-up">
                    <span class="section-eyebrow">Inspired by grand destination dining</span>
                    <h2 class="section-title">A dining collection that moves from sunrise coffee to moonlit celebrations.</h2>
                    <p class="section-text">
                        Each concept has its own rhythm. Ember Shore brings guests together over live-fire grilling
                        and theatrical open-kitchen energy, while Sunset Lagoon Beach Dining &amp; Bar offers breezy
                        al fresco evenings right at the shoreline.
                    </p>
                    <p class="section-text">
                        Between meals, The Cove Café is your unhurried retreat for artisan coffee, fresh pastries
                        and light bites throughout the day.
                    </p>
                    <div class="stats-wrap dining-stats">
                        <div class="stat-box">
                            <h3>3</h3>
                            <p>Dining concepts</p>
                        </div>
                        <div class="stat-box">
                            <h3>2</h3>
                            <p>Signature dinner venues</p>
                        </div>
                        <div class="stat-box">
                            <h3>1</h3>
                            <p>Beachfront bar experience</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 reveal-up">
                    <div class="content-card dining-intro-card">
                        <div class="dining-intro-grid">
                            <article>
                                <span class="badge-soft">Hours</span>
                                <h3>Breakfast to late evening</h3>
                                <p>Guests can enjoy all-day dining choices, sunset drinks and special occasion dinners across the resort.</p>
                            </article>
                            <article>
                                <span class="badge-soft">Dress code</span>
                                <h3>Relaxed elegance</h3>
                                <p>Smart casual is welcome throughout. Elevated evening attire is suggested for dinner at Ember Shore and Sunset Lagoon.</p>
                            </article>
                            <article>
                                <span class="badge-soft">Reservations</span>
                                <h3>Recommended for dinner</h3>
                                <p>Advance reservations are encouraged for the indoor grill, beachfront dining terrace and sunset bar seating.</p>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="venues" class="section-padding section-soft">
        <div class="container">
            <div class="text-center mb-5 reveal-up">
                <span class="section-eyebrow">Our venues</span>
                <h2 class="section-title">Choose your dining mood</h2>
                <p class="section-text mx-auto">
                    Explore our signature venues, each designed with its own atmosphere, menu direction and view.
                </p>
            </div>

            <div class="row g-4 justify-content-center">

                <div class="col-lg-4 col-md-6 reveal-up">
                    <article class="dining-card venue-card h-100">
                        <img
                            src="assets/images/home/RestaurantFieryBlaze.png"
                            alt="Indoor open-fire grill restaurant with dramatic flames, copper hood, chefs at work and intimate evening dining atmosphere"
                        >
                        <div class="dining-card-body">
                            <span class="badge-soft">Indoor Open-Fire Grill</span>
                            <h3>Ember Shore</h3>
                            <p>
                                An atmospheric indoor grill restaurant where a live open fire takes centre stage beneath a striking copper hood. 
                                Guests settle into warm, firelit surroundings as chefs grill premium meats and fresh seafood in full view, 
                                creating a bold and memorable dining experience after sunset.  
                            </p>
                            <ul class="venue-meta list-unstyled mb-0">
                                <li><strong>Signature mood:</strong> Dramatic firelit ambience with open-kitchen energy</li>
                                <li><strong>Recommended for:</strong> Special occasions, intimate dinners and group celebrations</li>
                                <li><strong>Suggested hours:</strong> 5:30 PM to 10:30 PM</li>
                            </ul>
                        </div>
                    </article>
                </div>

                <div class="col-lg-4 col-md-6 reveal-up">
                    <article class="dining-card venue-card h-100">
                        <img
                            src="assets/images/home/RestaurantSunsetLagoon.png"
                            alt="Beachfront restaurant and bar with open-air terrace, ocean views, palm trees and guests dining at sunset"
                        >
                        <div class="dining-card-body">
                            <span class="badge-soft">Beach Dining &amp; Bar</span>
                            <h3>Sunset Lagoon</h3>
                            <p>
                                A breezy open-air restaurant and bar set right by the shoreline,
                                where guests can enjoy fresh coastal dishes, tropical cocktails
                                and relaxed seaside dining as the sky turns to gold. With the sound of the waves nearby, 
                                it is an easy, elegant setting for long evenings in Bali. 
                            </p>
                            <ul class="venue-meta list-unstyled mb-0">
                                <li><strong>Signature mood:</strong> Relaxed beachfront dining and sunset cocktails</li>
                                <li><strong>Recommended for:</strong> Romantic evenings, casual dinners and after-beach drinks</li>
                                <li><strong>Suggested hours:</strong> 12:00 PM to 11:00 PM</li>
                            </ul>
                        </div>
                    </article>
                </div>

                <div class="col-lg-4 col-md-6 reveal-up">
                    <article class="dining-card venue-card h-100">
                       <img src="assets/images/home/Cafe.png" alt="Bright café interior with large windows, warm timber furniture, guests dining at wooden tables and a pastry display counter along the wall">
                        <div class="dining-card-body">
                            <span class="badge-soft">All-Day Café</span>
                            <h3>The Cove Café</h3>
                            <p>
                                A bright and inviting café serving artisan coffee, fresh pastries, brunch favourites and light meals 
                                for guests seeking a slower start to the day or a quiet midday pause. With a relaxed atmosphere and easy charm, 
                                it is the perfect spot for morning coffee, casual meetups or a laid-back break between beachside plans.                       
                            </p>
                            <ul class="venue-meta list-unstyled mb-0">
                                <li><strong>Signature mood:</strong> Relaxed coffee, pastries and light daytime dining</li>
                                <li><strong>Recommended for:</strong> Morning coffee, remote work and casual meetups</li>
                                <li><strong>Suggested hours:</strong> 7:00 AM to 6:00 PM</li>
                            </ul>
                        </div>
                    </article>
                </div>

            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 reveal-up">
                    <article class="moment-card h-100">
                        <div class="moment-icon" aria-hidden="true">01</div>
                        <h3>Open-fire evenings</h3>
                        <p>
                            Watch chefs work at the live grill, settle into the warm firelit interior and enjoy an
                            indoor barbecue experience that feels both dramatic and deeply inviting.
                        </p>
                    </article>
                </div>
                <div class="col-lg-4 reveal-up">
                    <article class="moment-card h-100">
                        <div class="moment-icon" aria-hidden="true">02</div>
                        <h3>Beachfront evenings</h3>
                        <p>
                            Sunset Lagoon is designed for long, unhurried evenings — fresh coastal plates, cocktails
                            in hand and the sound of the ocean just steps away.
                        </p>
                    </article>
                </div>
                <div class="col-lg-4 reveal-up">
                    <article class="moment-card h-100">
                        <div class="moment-icon" aria-hidden="true">03</div>
                        <h3>Slow mornings</h3>
                        <p>
                            The Cove Café is your corner for unhurried starts — artisan coffee, warm pastries and a
                            bright, welcoming space that makes every morning feel like a treat.
                        </p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="cta-section text-center reveal-up">
                <span class="section-eyebrow text-white">Reserve your table</span>
                <h2 class="section-title">Planning a celebration, dinner or beachfront drinks?</h2>
                <p class="section-text mx-auto mb-4">
                    Contact our team for reservation enquiries, private dining requests or special arrangements for your stay.
                </p>
                <a href="contact.php" class="cta-button">Contact Dining Team</a>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>