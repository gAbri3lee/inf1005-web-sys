<?php
session_start();

if (!isset($_SESSION['contact_form_token'])) {
    $_SESSION['contact_form_token'] = bin2hex(random_bytes(32));
}

$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => ''
];

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $honeypot = trim($_POST['website'] ?? '');

    if (!hash_equals($_SESSION['contact_form_token'], $postedToken)) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    }

    if ($honeypot !== '') {
        $errors[] = 'Unable to submit your enquiry. Please try again.';
    }

    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['subject'] = trim($_POST['subject'] ?? '');
    $formData['message'] = trim($_POST['message'] ?? '');

    if ($formData['name'] === '' || mb_strlen($formData['name']) < 2) {
        $errors[] = 'Please enter your full name.';
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($formData['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $formData['phone'])) {
        $errors[] = 'Please enter a valid phone number.';
    }

    $allowedSubjects = [
        'Room Reservation',
        'Dining Reservation',
        'Events & Celebrations',
        'Transport & Parking',
        'General Enquiry'
    ];

    if (!in_array($formData['subject'], $allowedSubjects, true)) {
        $errors[] = 'Please select a valid enquiry type.';
    }

    if ($formData['message'] === '' || mb_strlen($formData['message']) < 10) {
        $errors[] = 'Please enter a message with at least 10 characters.';
    }

    if (!$errors) {
        $successMessage = 'Thank you for contacting Horizon Sands Bali. Our team will get back to you shortly.';
        $formData = [
            'name' => '',
            'email' => '',
            'phone' => '',
            'subject' => '',
            'message' => ''
        ];
        $_SESSION['contact_form_token'] = bin2hex(random_bytes(32));
    }
}

include __DIR__ . '/../app/includes/navbar.php';
?>
<link rel="stylesheet" href="assets/css/contact.css">

<main>
    <section class="page-hero page-hero-contact">
        <div class="container page-hero-content">
            <div class="row">
                <div class="col-lg-8 col-xl-7 reveal-up">
                    <span class="hero-tag">Contact Horizon Sands Bali</span>
                    <h1 class="page-hero-title">We are here to help before, during and after your stay.</h1>
                    <p class="page-hero-text">
                        Whether you are planning a reservation, arranging a special celebration or simply have a
                        question about your visit, our team is ready to assist.
                    </p>
                    <div class="hero-actions">
                        <a href="#contact-form" class="btn btn-gold">Send an Enquiry</a>
                        <a href="Dining.php" class="btn btn-outline-light hero-dining-btn">Explore Dining</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding page-main-offset">
        <div class="container">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-5 reveal-up">
                    <div class="content-card contact-info-card h-100">
                        <span class="section-eyebrow">Contact details</span>
                        <h2 class="section-title">Get in touch with our team</h2>
                        <p class="section-text">
                            Reach out for room reservations, dining enquiries, events, transport questions or any
                            assistance related to your stay.
                        </p>

                        <div class="contact-detail-group">
                            <div class="contact-detail-item">
                                <h3>Address</h3>
                                <p>Sunset Bay Drive<br>Azure Coast, Bali 80361</p>
                            </div>
                            <div class="contact-detail-item">
                                <h3>Phone</h3>
                                <p><a href="tel:+6561234567">+65 6123 4567</a></p>
                            </div>
                            <div class="contact-detail-item">
                                <h3>Email</h3>
                                <p><a href="mailto:hello@horizonsandsbali.test">hello@horizonsandsbali.test</a></p>
                            </div>
                            <div class="contact-detail-item">
                                <h3>Guest services hours</h3>
                                <p>Available daily, 24 hours</p>
                            </div>
                        </div>

                        <div class="contact-quick-notes">
                            <article>
                                <span class="badge-soft">Dining reservations</span>
                                <p class="mb-0">For barbecue, fine dining or private celebrations, include your preferred date, time and group size.</p>
                            </article>
                            <article>
                                <span class="badge-soft">Transport support</span>
                                <p class="mb-0">Airport transfer and parking enquiries can be submitted through the contact form below.</p>
                            </article>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 reveal-up" id="contact-form">
                    <div class="content-card contact-form-card h-100">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4 mb-4">
                            <div>
                                <span class="section-eyebrow">Enquiry form</span>
                                <h2 class="section-title mb-2">Send us a message</h2>
                                <p class="section-text mb-0">Complete the form below and our team will get back to you.</p>
                            </div>
                            <img src="assets/images/AboutUsSpa.webp" alt="Hotel guest services and concierge themed visual" class="contact-illustration">
                        </div>

                        <?php if ($successMessage !== ''): ?>
                            <div class="alert alert-success" role="status">
                                <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($errors): ?>
                            <div class="alert alert-danger" role="alert">
                                <p class="mb-2"><strong>Please correct the following:</strong></p>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>#contact-form" method="post" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['contact_form_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="visually-hidden" aria-hidden="true">
                                <label for="website">Leave this field empty</label>
                                <input type="text" id="website" name="website" autocomplete="off" tabindex="-1">
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full name</label>
                                    <input type="text" class="form-control" id="name" name="name" maxlength="100" required value="<?php echo htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="email" name="email" maxlength="120" required value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" maxlength="20" value="<?php echo htmlspecialchars($formData['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="subject" class="form-label">Subject</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">Select an enquiry type</option>
                                        <option value="Room Reservation" <?php echo $formData['subject'] === 'Room Reservation' ? 'selected' : ''; ?>>Room Reservation</option>
                                        <option value="Dining Reservation" <?php echo $formData['subject'] === 'Dining Reservation' ? 'selected' : ''; ?>>Dining Reservation</option>
                                        <option value="Events & Celebrations" <?php echo $formData['subject'] === 'Events & Celebrations' ? 'selected' : ''; ?>>Events & Celebrations</option>
                                        <option value="Transport & Parking" <?php echo $formData['subject'] === 'Transport & Parking' ? 'selected' : ''; ?>>Transport & Parking</option>
                                        <option value="General Enquiry" <?php echo $formData['subject'] === 'General Enquiry' ? 'selected' : ''; ?>>General Enquiry</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="6" maxlength="1200" required><?php echo htmlspecialchars($formData['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="col-12 d-flex flex-column flex-sm-row gap-3 align-items-sm-center justify-content-between">
                                    <p class="form-note mb-0">Please do not include payment card details or other sensitive information in this form.</p>
                                    <button type="submit" class="btn btn-gold">Submit Enquiry</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding section-soft">
        <div class="container">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-4 reveal-up">
                    <article class="moment-card h-100">
                        <div class="moment-icon" aria-hidden="true">01</div>
                        <h3>Before your stay</h3>
                        <p>Ask about room types, dining options, transport arrangements and special requests before arrival.</p>
                    </article>
                </div>
                <div class="col-lg-4 reveal-up">
                    <article class="moment-card h-100">
                        <div class="moment-icon" aria-hidden="true">02</div>
                        <h3>During your stay</h3>
                        <p>Reach our guest services team at any time for assistance with dining, amenities and resort recommendations.</p>
                    </article>
                </div>
                <div class="col-lg-4 reveal-up">
                    <article class="moment-card h-100">
                        <div class="moment-icon" aria-hidden="true">03</div>
                        <h3>Group or event enquiries</h3>
                        <p>Use the contact form for celebration dinners, private events, corporate stays and tailored arrangements.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
