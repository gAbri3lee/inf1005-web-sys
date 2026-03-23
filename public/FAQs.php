<?php
session_start();
include __DIR__ . '/../app/includes/navbar.php';
?>

<link rel="stylesheet" href="assets/css/faq.css">

<main class="faq-page">
    <section class="faq-hero-simple">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <span class="faq-eyebrow">Guest Support</span>
                    <h1 class="faq-title-main">Frequently Asked Questions</h1>
                    <p class="faq-subtitle-main mb-0">
                        Find quick answers about bookings, rooms, parking, dining, and hotel policies.
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="faq-hero-box">
                        <h3 class="mb-2">Need help fast?</h3>
                        <p class="mb-0">Use the search bar or tap a category to filter the questions instantly.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="faq-main-section">
        <div class="container">
            <div class="faq-search-card">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-5">
                        <h2 class="faq-search-title">Find an answer fast</h2>
                        <p class="faq-search-text mb-0">
                            Try keywords like check-in, breakfast, parking, cancellation, Wi-Fi, or pets.
                        </p>
                    </div>

                    <div class="col-lg-7">
                        <input
                            type="text"
                            id="faqSearch"
                            class="form-control faq-search-input"
                            placeholder="Search by keyword..."
                        >
                    </div>
                </div>

                <div class="faq-category-tabs">
                    <button type="button" class="faq-category-btn active" data-category="booking">Booking &amp; Check-in</button>
                    <button type="button" class="faq-category-btn" data-category="rooms">Rooms &amp; Amenities</button>
                    <button type="button" class="faq-category-btn" data-category="parking">Parking &amp; Arrival</button>
                    <button type="button" class="faq-category-btn" data-category="dining">Dining</button>
                    <button type="button" class="faq-category-btn" data-category="policies">Policies</button>
                </div>

                <div class="faq-meta">
                    <p class="mb-0">Category: <strong id="faqCurrentCategory">Booking &amp; Check-in</strong></p>
                    <p class="mb-0" id="faqResultCount">0 questions</p>
                </div>

                <div id="faqEmptyState" class="faq-empty-state" style="display:none;">
                    No matching questions found.
                </div>
            </div>

            <div class="faq-list-wrap">

                <div class="faq-item" data-category="booking">
                    <div class="accordion" id="faqAccordion1">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="false" aria-controls="collapse1">
                                    What time is check-in and check-out?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse" aria-labelledby="heading1" data-bs-parent="#faqAccordion1">
                                <div class="accordion-body">
                                    Check-in begins from 3:00 PM, while check-out is by 11:00 AM. Early check-in and late check-out are subject to availability.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="booking">
                    <div class="accordion" id="faqAccordion2">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                    Can I request early check-in or late check-out?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="heading2" data-bs-parent="#faqAccordion2">
                                <div class="accordion-body">
                                    Yes. Requests can be made before arrival or at reception, but approval depends on room availability.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="booking">
                    <div class="accordion" id="faqAccordion3">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                    What do I need during check-in?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="heading3" data-bs-parent="#faqAccordion3">
                                <div class="accordion-body">
                                    Please present a valid photo ID or passport, your booking confirmation, and a payment card if required for incidentals.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="rooms">
                    <div class="accordion" id="faqAccordion4">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                    Do all rooms include complimentary Wi-Fi?
                                </button>
                            </h2>
                            <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="heading4" data-bs-parent="#faqAccordion4">
                                <div class="accordion-body">
                                    Yes. Complimentary high-speed Wi-Fi is available in all rooms, suites, and most public areas of the hotel.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="rooms">
                    <div class="accordion" id="faqAccordion5">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                                    Are extra beds or baby cots available?
                                </button>
                            </h2>
                            <div id="collapse5" class="accordion-collapse collapse" aria-labelledby="heading5" data-bs-parent="#faqAccordion5">
                                <div class="accordion-body">
                                    Baby cots are available on request, while extra beds may be arranged for selected room types and may incur additional charges.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="rooms">
                    <div class="accordion" id="faqAccordion6">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading6">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                    What amenities are provided in the room?
                                </button>
                            </h2>
                            <div id="collapse6" class="accordion-collapse collapse" aria-labelledby="heading6" data-bs-parent="#faqAccordion6">
                                <div class="accordion-body">
                                    Standard amenities include towels, bath essentials, a hairdryer, refreshments, and selected premium comforts depending on room category.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="parking">
                    <div class="accordion" id="faqAccordion7">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading7">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse7" aria-expanded="false" aria-controls="collapse7">
                                    Is parking available at the hotel?
                                </button>
                            </h2>
                            <div id="collapse7" class="accordion-collapse collapse" aria-labelledby="heading7" data-bs-parent="#faqAccordion7">
                                <div class="accordion-body">
                                    Yes. Secure on-site parking is available for hotel guests, subject to space availability during peak periods.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="parking">
                    <div class="accordion" id="faqAccordion8">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading8">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse8" aria-expanded="false" aria-controls="collapse8">
                                    Do you offer airport transfer services?
                                </button>
                            </h2>
                            <div id="collapse8" class="accordion-collapse collapse" aria-labelledby="heading8" data-bs-parent="#faqAccordion8">
                                <div class="accordion-body">
                                    Airport transfer arrangements can be made through the concierge. Advance booking is recommended.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="parking">
                    <div class="accordion" id="faqAccordion9">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading9">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse9" aria-expanded="false" aria-controls="collapse9">
                                    Is the hotel accessible by public transport?
                                </button>
                            </h2>
                            <div id="collapse9" class="accordion-collapse collapse" aria-labelledby="heading9" data-bs-parent="#faqAccordion9">
                                <div class="accordion-body">
                                    Yes. The hotel is well-connected by nearby MRT stations, buses, and point-to-point transport options.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="dining">
                    <div class="accordion" id="faqAccordion10">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading10">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse10" aria-expanded="false" aria-controls="collapse10">
                                    Is breakfast included in the stay?
                                </button>
                            </h2>
                            <div id="collapse10" class="accordion-collapse collapse" aria-labelledby="heading10" data-bs-parent="#faqAccordion10">
                                <div class="accordion-body">
                                    Breakfast inclusion depends on your selected room package. Please refer to your booking confirmation for details.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="dining">
                    <div class="accordion" id="faqAccordion11">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading11">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse11" aria-expanded="false" aria-controls="collapse11">
                                    Are there dining options available on-site?
                                </button>
                            </h2>
                            <div id="collapse11" class="accordion-collapse collapse" aria-labelledby="heading11" data-bs-parent="#faqAccordion11">
                                <div class="accordion-body">
                                    Yes. Azure Horizon offers on-site dining venues, lounge experiences, and selected in-room dining options during operating hours.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="policies">
                    <div class="accordion" id="faqAccordion12">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading12">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse12" aria-expanded="false" aria-controls="collapse12">
                                    What is your cancellation policy?
                                </button>
                            </h2>
                            <div id="collapse12" class="accordion-collapse collapse" aria-labelledby="heading12" data-bs-parent="#faqAccordion12">
                                <div class="accordion-body">
                                    Cancellation terms vary by room type, package, and promotional rate. Please review your booking terms carefully before confirming.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="policies">
                    <div class="accordion" id="faqAccordion13">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading13">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse13" aria-expanded="false" aria-controls="collapse13">
                                    Is smoking allowed in the hotel?
                                </button>
                            </h2>
                            <div id="collapse13" class="accordion-collapse collapse" aria-labelledby="heading13" data-bs-parent="#faqAccordion13">
                                <div class="accordion-body">
                                    Azure Horizon maintains a non-smoking environment in guest rooms and most public areas. Designated smoking zones may be available.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faq-item" data-category="policies">
                    <div class="accordion" id="faqAccordion14">
                        <div class="accordion-item faq-accordion-item">
                            <h2 class="accordion-header" id="heading14">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse14" aria-expanded="false" aria-controls="collapse14">
                                    Are pets allowed on the property?
                                </button>
                            </h2>
                            <div id="collapse14" class="accordion-collapse collapse" aria-labelledby="heading14" data-bs-parent="#faqAccordion14">
                                <div class="accordion-body">
                                    Pet policies depend on room type and hotel arrangements. Guests should contact the hotel directly before arrival for confirmation.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="faq-help-card mt-4">
                <div class="row align-items-center g-3">
                    <div class="col-lg-8">
                        <h3 class="mb-2">Still need help?</h3>
                        <p class="mb-0">
                            Contact our team directly if you need help with arrival details, room preferences, or special requests.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="contact.php" class="btn btn-dark rounded-pill px-4 me-2 mb-2 mb-lg-0">Contact Us</a>
                        <a href="parking_and_transport.php" class="btn btn-outline-dark rounded-pill px-4">Parking &amp; Transport</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="assets/js/faq.js"></script>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>