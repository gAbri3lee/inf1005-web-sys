<?php
require_once __DIR__ . '/../app/includes/auth.php';

auth_require_login(
    'spa_booking.php',
    'Please sign in or create an account to reserve a spa treatment.'
);

$pageStylesheets = ['assets/css/spa_booking.css'];
$pageScripts = ['assets/js/spa_booking.js'];
$errors = [];
$databaseNotice = '';

$spaTreatments = [
    'Balinese Renewal Massage' => '60 minutes of pressure-point massage and calming aromatherapy.',
    'Ocean Stone Therapy' => 'Warm stone treatment designed to release tension and restore circulation.',
    'Sunrise Facial Ritual' => 'A brightening facial with hydration-focused finishing layers.',
    'Couples Sanctuary Journey' => 'A side-by-side wellness experience for two in a private suite.',
];

$timeOptions = [
    '10:00:00' => '10:00 AM',
    '11:30:00' => '11:30 AM',
    '13:00:00' => '1:00 PM',
    '14:30:00' => '2:30 PM',
    '16:00:00' => '4:00 PM',
    '17:30:00' => '5:30 PM',
    '19:00:00' => '7:00 PM',
];

$today = new DateTimeImmutable('today');
$formData = [
    'guest_name' => auth_user_display_name(),
    'guest_email' => auth_user_email(),
    'treatment_name' => array_key_first($spaTreatments),
    'treatment_date' => $today->modify('+1 day')->format('Y-m-d'),
    'treatment_time' => array_key_first($timeOptions),
    'guests' => '1',
    'notes' => '',
];

try {
    require_once __DIR__ . '/../app/includes/db.php';
} catch (Throwable $exception) {
    $databaseNotice = 'Unable to connect to the spa booking database right now. Please try again shortly.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $honeypot = trim((string)($_POST['website'] ?? ''));

    foreach ($formData as $key => $defaultValue) {
        $formData[$key] = trim((string)($_POST[$key] ?? $defaultValue));
    }

    if (!csrf_validate('spa_booking_form', $postedToken)) {
        $errors[] = 'Your booking session has expired. Please refresh the page and try again.';
    }

    if ($honeypot !== '') {
        $errors[] = 'Unable to submit your spa reservation right now. Please try again.';
    }

    if ($formData['guest_name'] === '') {
        $errors[] = 'Please enter the guest name for the reservation.';
    }

    if (!filter_var($formData['guest_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!array_key_exists($formData['treatment_name'], $spaTreatments)) {
        $errors[] = 'Please choose a valid treatment.';
    }

    if (!array_key_exists($formData['treatment_time'], $timeOptions)) {
        $errors[] = 'Please choose a valid treatment time.';
    }

    $guestCount = (int)$formData['guests'];
    if ($guestCount < 1 || $guestCount > 4) {
        $errors[] = 'Guest count must be between 1 and 4.';
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $formData['treatment_date']);
    if (!$date || $date->format('Y-m-d') !== $formData['treatment_date']) {
        $errors[] = 'Please choose a valid treatment date.';
    } elseif ($date < $today) {
        $errors[] = 'Treatment date must be today or later.';
    }

    if ($databaseNotice !== '') {
        $errors[] = $databaseNotice;
    }

    if (!$errors && isset($pdo)) {
        $stmt = $pdo->prepare(
            'INSERT INTO spa_bookings (
                user_id,
                guest_name,
                guest_email,
                treatment_name,
                treatment_date,
                treatment_time,
                guests,
                notes,
                status
            ) VALUES (
                :user_id,
                :guest_name,
                :guest_email,
                :treatment_name,
                :treatment_date,
                :treatment_time,
                :guests,
                :notes,
                :status
            )'
        );

        $stmt->execute([
            ':user_id' => auth_user_id(),
            ':guest_name' => $formData['guest_name'],
            ':guest_email' => strtolower($formData['guest_email']),
            ':treatment_name' => $formData['treatment_name'],
            ':treatment_date' => $formData['treatment_date'],
            ':treatment_time' => $formData['treatment_time'],
            ':guests' => $guestCount,
            ':notes' => $formData['notes'] === '' ? null : $formData['notes'],
            ':status' => 'Pending',
        ]);

        csrf_refresh('spa_booking_form');
        auth_flash_set('dashboard_notice', 'Your spa reservation request has been submitted.');
        auth_redirect('dashboard.php#spa-bookings');
    }
}

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="spa-booking-page">
    <section class="spa-booking-shell">
        <div class="container">
            <div class="content-card spa-booking-card reveal-up">
                <header class="spa-booking-header text-center">
                    <span class="section-eyebrow">Spa reservation</span>
                    <h1 class="spa-booking-title">Reserve your treatment</h1>
                    <p class="spa-booking-subtitle mb-0">Choose a treatment, select your preferred slot, and we will add it to your dashboard instantly.</p>
                </header>

                <?php if ($databaseNotice && !$errors): ?>
                    <div class="alert alert-warning" role="alert">
                        <?php echo htmlspecialchars($databaseNotice, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $message): ?>
                                <li><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="row g-4 align-items-start">
                    <div class="col-lg-5">
                        <div class="spa-summary">
                            <h2 class="h4 mb-3">Treatment menu</h2>
                            <div class="spa-treatment-list">
                                <?php foreach ($spaTreatments as $treatmentName => $description): ?>
                                    <button
                                        type="button"
                                        class="spa-treatment-item js-spa-treatment-item <?php echo $formData['treatment_name'] === $treatmentName ? 'is-active' : ''; ?>"
                                        data-treatment-name="<?php echo htmlspecialchars($treatmentName, ENT_QUOTES, 'UTF-8'); ?>"
                                        aria-pressed="<?php echo $formData['treatment_name'] === $treatmentName ? 'true' : 'false'; ?>"
                                    >
                                        <h3><?php echo htmlspecialchars($treatmentName, ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <p class="mb-0"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <form action="spa_booking.php" method="POST" class="spa-booking-form" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('spa_booking_form'), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="spa-form-spotlight">
                                <div class="spa-form-spotlight-header">
                                    <span class="spa-form-spotlight-tag">Booking form</span>
                                    <span class="spa-form-spotlight-note">Takes about 1 minute</span>
                                </div>
                                <h2 class="spa-form-spotlight-title">Complete this form to lock in your preferred spa slot.</h2>
                                <p class="spa-form-spotlight-text mb-0">Choose your treatment, add your ideal date and time, and send the reservation request straight to your dashboard.</p>
                            </div>
                            <div class="form-honeypot" aria-hidden="true">
                                <label for="website" class="form-label">Website</label>
                                <input type="text" class="form-control" id="website" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="guest_name" class="form-label">Guest name</label>
                                    <input class="form-control" id="guest_name" name="guest_name" type="text" value="<?php echo htmlspecialchars($formData['guest_name'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="guest_email" class="form-label">Guest email</label>
                                    <input class="form-control" id="guest_email" name="guest_email" type="email" value="<?php echo htmlspecialchars($formData['guest_email'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email" required>
                                </div>
                                <div class="col-12">
                                    <label for="treatment_name" class="form-label">Treatment</label>
                                    <select class="form-select js-spa-treatment-select" id="treatment_name" name="treatment_name" required>
                                        <?php foreach ($spaTreatments as $treatmentName => $description): ?>
                                            <option value="<?php echo htmlspecialchars($treatmentName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formData['treatment_name'] === $treatmentName ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($treatmentName, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="treatment_date" class="form-label">Preferred date</label>
                                    <input class="form-control" id="treatment_date" name="treatment_date" type="date" min="<?php echo htmlspecialchars($today->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($formData['treatment_date'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="treatment_time" class="form-label">Preferred time</label>
                                    <select class="form-select" id="treatment_time" name="treatment_time" required>
                                        <?php foreach ($timeOptions as $timeValue => $timeLabel): ?>
                                            <option value="<?php echo htmlspecialchars($timeValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formData['treatment_time'] === $timeValue ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="guests" class="form-label">Guests</label>
                                    <select class="form-select" id="guests" name="guests" required>
                                        <?php for ($guestOption = 1; $guestOption <= 4; $guestOption++): ?>
                                            <option value="<?php echo $guestOption; ?>" <?php echo (int)$formData['guests'] === $guestOption ? 'selected' : ''; ?>>
                                                <?php echo $guestOption; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="notes" class="form-label">Notes (optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="5" placeholder="Accessibility support, celebration notes, therapist preference, or anything else our team should know"><?php echo htmlspecialchars($formData['notes'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                            </div>

                            <div class="spa-booking-actions">
                                <div class="spa-booking-actions-copy">
                                    <strong>Ready to reserve?</strong>
                                    <p class="mb-0">Submit your request and review it anytime from your dashboard.</p>
                                </div>
                                <button type="submit" class="btn btn-gold spa-booking-submit">Reserve spa treatment</button>
                                <a class="btn btn-back-dashboard" href="dashboard.php#spa-bookings">Back to dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
