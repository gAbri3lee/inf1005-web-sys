<?php
require_once __DIR__ . '/../app/includes/auth.php';

auth_require_login(
    'room_bookings.php',
    'Please sign in or create an account to manage your room bookings.'
);

$pageStylesheets = ['assets/css/dashboard.css'];
$pageScripts = ['assets/js/dashboard.js'];
$databaseNotice = '';
$roomBookings = [];
$summary = [
    'room_bookings' => 0,
    'active_bookings' => 0,
    'cancelled_bookings' => 0,
];

function room_bookings_is_cancelled(string $status): bool
{
    return strtolower(trim($status)) === 'cancelled';
}

function room_bookings_status_badge_class(string $status): string
{
    return match (strtolower(trim($status))) {
        'cancelled' => 'text-bg-secondary',
        'pending' => 'text-bg-warning',
        default => 'text-bg-success',
    };
}

function room_bookings_parse_date(string $value): ?DateTimeImmutable
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if (!$date) {
        return null;
    }

    return $date->format('Y-m-d') === $value ? $date : null;
}

function room_bookings_digits_only(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function room_bookings_parse_expiry_end(string $value): ?DateTimeImmutable
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2}|\d{4})$/', $value, $matches)) {
        return null;
    }

    $month = (int)$matches[1];
    $year = (int)$matches[2];
    if (strlen($matches[2]) === 2) {
        $year += 2000;
    }

    $date = DateTimeImmutable::createFromFormat('Y-n-j', $year . '-' . $month . '-1');
    if (!$date) {
        return null;
    }

    return $date->modify('last day of this month')->setTime(23, 59, 59);
}

function room_bookings_billing_summary(array $booking): string
{
    $parts = array_filter([
        trim((string)($booking['billing_address'] ?? '')),
        trim((string)($booking['billing_city'] ?? '')),
        trim((string)($booking['billing_postal'] ?? '')),
    ], static fn (string $part): bool => $part !== '');

    if (!$parts) {
        return 'No billing address on file.';
    }

    return implode(', ', $parts);
}

$dashboardNotice = auth_flash_get('dashboard_notice');
$dashboardError = auth_flash_get('dashboard_error');
$today = new DateTimeImmutable('today');

try {
    require_once __DIR__ . '/../app/includes/db.php';
} catch (Throwable $exception) {
    $databaseNotice = 'Your room bookings are unavailable right now.';
}

if (isset($pdo)) {
    try {
        require_once __DIR__ . '/../app/includes/loyalty_helper.php';
    } catch (Throwable $exception) {
        // Loyalty is optional.
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $redirectTarget = 'room_bookings.php';

    if ($databaseNotice !== '' || !isset($pdo)) {
        auth_flash_set('dashboard_error', 'Unable to update your booking right now.');
        auth_redirect($redirectTarget);
    }

    if (!csrf_validate('room_bookings_form', $_POST['csrf_token'] ?? '')) {
        auth_flash_set('dashboard_error', 'Your room booking session expired. Please try again.');
        auth_redirect($redirectTarget);
    }

    $userId = auth_user_id() ?? 0;

    try {
        switch ($action) {
            case 'cancel_room_booking':
                $bookingId = (int)($_POST['booking_id'] ?? 0);
                $stmt = $pdo->prepare('SELECT id, status FROM bookings WHERE id = ? AND user_id = ? LIMIT 1');
                $stmt->execute([$bookingId, $userId]);
                $booking = $stmt->fetch();

                if (!$booking) {
                    auth_flash_set('dashboard_error', 'That room booking could not be found.');
                } elseif (room_bookings_is_cancelled((string)($booking['status'] ?? ''))) {
                    auth_flash_set('dashboard_error', 'That room booking is already cancelled.');
                } else {
                    $updateStmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ? AND user_id = ?');
                    $updateStmt->execute(['Cancelled', $bookingId, $userId]);

                    if (function_exists('loyalty_refresh_user')) {
                        try {
                            loyalty_refresh_user($pdo, $userId);
                        } catch (Throwable $exception) {
                        }
                    }

                    auth_flash_set('dashboard_notice', 'Your room booking has been cancelled.');
                }
                break;

            case 'adjust_room_booking':
                $bookingId = (int)($_POST['booking_id'] ?? 0);
                $checkInValue = trim((string)($_POST['check_in'] ?? ''));
                $checkOutValue = trim((string)($_POST['check_out'] ?? ''));
                $guestName = trim((string)($_POST['guest_name'] ?? ''));
                $guestEmail = strtolower(trim((string)($_POST['guest_email'] ?? '')));
                $guestPhone = trim((string)($_POST['guest_phone'] ?? ''));
                $billingAddress = trim((string)($_POST['billing_address'] ?? ''));
                $billingCity = trim((string)($_POST['billing_city'] ?? ''));
                $billingPostal = trim((string)($_POST['billing_postal'] ?? ''));

                $stmt = $pdo->prepare(
                    'SELECT id, status, room_rate, total_price, room_name
                     FROM bookings
                     WHERE id = ? AND user_id = ? LIMIT 1'
                );
                $stmt->execute([$bookingId, $userId]);
                $booking = $stmt->fetch();

                if (!$booking) {
                    auth_flash_set('dashboard_error', 'That room booking could not be found.');
                    break;
                }

                if (room_bookings_is_cancelled((string)($booking['status'] ?? ''))) {
                    auth_flash_set('dashboard_error', 'Cancelled room bookings cannot be adjusted.');
                    break;
                }

                $checkInDate = room_bookings_parse_date($checkInValue);
                $checkOutDate = room_bookings_parse_date($checkOutValue);

                if (!$checkInDate || !$checkOutDate) {
                    auth_flash_set('dashboard_error', 'Please enter valid new check-in and check-out dates.');
                    break;
                }

                if ($checkInDate < $today) {
                    auth_flash_set('dashboard_error', 'Adjusted check-in date must be today or later.');
                    break;
                }

                if ($checkOutDate <= $checkInDate) {
                    auth_flash_set('dashboard_error', 'Adjusted check-out date must be after the check-in date.');
                    break;
                }

                if ($guestName === '') {
                    auth_flash_set('dashboard_error', 'Please enter the guest name for this booking.');
                    break;
                }

                if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                    auth_flash_set('dashboard_error', 'Please enter a valid guest email address.');
                    break;
                }

                if ($guestPhone === '') {
                    auth_flash_set('dashboard_error', 'Please enter a contact phone number.');
                    break;
                }

                if ($billingAddress === '') {
                    auth_flash_set('dashboard_error', 'Please enter the billing address for this booking.');
                    break;
                }

                if ($billingCity === '') {
                    auth_flash_set('dashboard_error', 'Please enter the billing city for this booking.');
                    break;
                }

                if ($billingPostal === '') {
                    auth_flash_set('dashboard_error', 'Please enter the billing postal code for this booking.');
                    break;
                }

                $nights = max(1, (int)$checkInDate->diff($checkOutDate)->days);
                $roomRate = (float)($booking['room_rate'] ?? 0);
                $previousTotal = (float)($booking['total_price'] ?? 0);
                $totalPrice = $roomRate * $nights;
                $priceDifference = $totalPrice - $previousTotal;

                if ($priceDifference > 0.009) {
                    $cardName = trim((string)($_POST['card_name'] ?? ''));
                    $cardDigits = room_bookings_digits_only((string)($_POST['card_number'] ?? ''));
                    $expiryValue = trim((string)($_POST['expiry'] ?? ''));
                    $cvvDigits = room_bookings_digits_only((string)($_POST['cvv'] ?? ''));
                    $expiryEnd = room_bookings_parse_expiry_end($expiryValue);

                    if ($cardName === '') {
                        auth_flash_set('dashboard_error', 'Please enter the name on card to pay the additional stay balance.');
                        break;
                    }

                    if (strlen($cardDigits) !== 16) {
                        auth_flash_set('dashboard_error', 'Card number must be 16 digits to confirm a longer stay.');
                        break;
                    }

                    if (!$expiryEnd || $expiryEnd < new DateTimeImmutable('now')) {
                        auth_flash_set('dashboard_error', 'Please enter a valid future card expiry to confirm a longer stay.');
                        break;
                    }

                    if (strlen($cvvDigits) !== 3) {
                        auth_flash_set('dashboard_error', 'CVV must be 3 digits to confirm a longer stay.');
                        break;
                    }
                }

                $updateStmt = $pdo->prepare(
                    'UPDATE bookings
                     SET guest_name = :guest_name,
                         guest_email = :guest_email,
                         guest_phone = :guest_phone,
                         billing_address = :billing_address,
                         billing_city = :billing_city,
                         billing_postal = :billing_postal,
                         check_in = :check_in,
                         check_out = :check_out,
                         nights = :nights,
                         total_price = :total_price
                     WHERE id = :booking_id AND user_id = :user_id'
                );
                $updateStmt->execute([
                    ':guest_name' => $guestName,
                    ':guest_email' => $guestEmail,
                    ':guest_phone' => $guestPhone,
                    ':billing_address' => $billingAddress,
                    ':billing_city' => $billingCity,
                    ':billing_postal' => $billingPostal,
                    ':check_in' => $checkInDate->format('Y-m-d'),
                    ':check_out' => $checkOutDate->format('Y-m-d'),
                    ':nights' => $nights,
                    ':total_price' => $totalPrice,
                    ':booking_id' => $bookingId,
                    ':user_id' => $userId,
                ]);

                if (function_exists('loyalty_refresh_user')) {
                    try {
                        loyalty_refresh_user($pdo, $userId);
                    } catch (Throwable $exception) {
                    }
                }

                if ($priceDifference > 0.009) {
                    auth_flash_set(
                        'dashboard_notice',
                        'Your stay at ' . (string)($booking['room_name'] ?? 'this room') . ' has been extended. An additional $' . number_format($priceDifference, 2) . ' has been charged to the card you provided.'
                    );
                } elseif ($priceDifference < -0.009) {
                    auth_flash_set(
                        'dashboard_notice',
                        'Your stay at ' . (string)($booking['room_name'] ?? 'this room') . ' has been shortened. A refund of $' . number_format(abs($priceDifference), 2) . ' will be returned to your card within 7 business days.'
                    );
                } else {
                    auth_flash_set('dashboard_notice', 'Your room booking details have been updated with no change in total price.');
                }
                break;

            default:
                auth_flash_set('dashboard_error', 'That room booking action is not supported.');
                break;
        }
    } catch (Throwable $exception) {
        auth_flash_set('dashboard_error', 'Unable to update your booking right now. Please try again.');
    }

    auth_redirect($redirectTarget);
}

if (isset($pdo)) {
    try {
        $userId = auth_user_id();
        $bookingStmt = $pdo->prepare(
            'SELECT id, room_id, room_name, guest_name, guest_email, guest_phone,
                    billing_address, billing_city, billing_postal,
                    check_in, check_out, nights, room_rate, total_price, status, created_at
             FROM bookings
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC'
        );
        $bookingStmt->execute([$userId]);
        $roomBookings = $bookingStmt->fetchAll();

        $cancelledCount = 0;
        foreach ($roomBookings as $booking) {
            if (room_bookings_is_cancelled((string)($booking['status'] ?? ''))) {
                $cancelledCount++;
            }
        }

        $summary = [
            'room_bookings' => count($roomBookings),
            'active_bookings' => count($roomBookings) - $cancelledCount,
            'cancelled_bookings' => $cancelledCount,
        ];
    } catch (Throwable $exception) {
        $databaseNotice = 'Your room bookings are unavailable right now. Please check the schema and your MySQL connection.';
    }
}

$displayName = auth_user_display_name();
$email = auth_user_email();

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="dashboard-page">
    <section class="dashboard-hero">
        <div class="container">
            <div class="dashboard-hero-card reveal-up">
                <div class="row g-4 align-items-end">
                    <div class="col-lg-8">
                        <span class="section-eyebrow text-white">Room bookings</span>
                        <h1 class="dashboard-title">Manage your stays</h1>
                        <p class="dashboard-subtitle mb-0">Review upcoming reservations, update guest details, and keep your booking history in one place.</p>
                    </div>
                    <div class="col-lg-4">
                        <div class="dashboard-user-meta">
                            <div class="dashboard-meta-label">Signed in as</div>
                            <div class="dashboard-meta-value"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-stats reveal-up" aria-label="Room booking summary">
                <article class="dashboard-stat-card">
                    <span class="dashboard-stat-label">Total bookings</span>
                    <strong class="dashboard-stat-value"><?php echo $summary['room_bookings']; ?></strong>
                </article>
                <article class="dashboard-stat-card">
                    <span class="dashboard-stat-label">Active stays</span>
                    <strong class="dashboard-stat-value"><?php echo $summary['active_bookings']; ?></strong>
                </article>
                <article class="dashboard-stat-card">
                    <span class="dashboard-stat-label">Cancelled</span>
                    <strong class="dashboard-stat-value"><?php echo $summary['cancelled_bookings']; ?></strong>
                </article>
            </div>
        </div>
    </section>

    <section class="dashboard-section">
        <div class="container">
            <?php if ($dashboardNotice): ?>
                <div class="alert alert-success reveal-up" role="alert">
                    <?php echo htmlspecialchars($dashboardNotice, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($dashboardError): ?>
                <div class="alert alert-danger reveal-up" role="alert">
                    <?php echo htmlspecialchars($dashboardError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($databaseNotice): ?>
                <div class="alert alert-warning reveal-up" role="alert">
                    <?php echo htmlspecialchars($databaseNotice, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <section class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Booking history</p>
                            <h2 class="dashboard-panel-title">Your room reservations</h2>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-back-dashboard btn-sm" href="dashboard.php">Back to dashboard</a>
                            <a class="btn btn-gold btn-sm" href="rooms_and_suites.php">Book a room</a>
                        </div>
                    </div>

                    <?php if (!$roomBookings): ?>
                        <div class="dashboard-empty">
                            No room bookings yet. Once you confirm a stay, it will appear here with edit and cancellation controls.
                        </div>
                    <?php else: ?>
                        <div class="dashboard-list">
                            <?php foreach ($roomBookings as $booking): ?>
                                <?php
                                    $bookingStatus = (string)($booking['status'] ?? 'Confirmed');
                                    $isBookingCancelled = room_bookings_is_cancelled($bookingStatus);
                                ?>
                                <article class="dashboard-entry">
                                    <div class="dashboard-entry-top">
                                        <div>
                                            <h3><?php echo htmlspecialchars((string)($booking['room_name'] ?? 'Room booking'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <p class="dashboard-entry-meta mb-0">
                                                Reserved for <?php echo htmlspecialchars((string)(($booking['guest_name'] ?? '') !== '' ? $booking['guest_name'] : $displayName), ENT_QUOTES, 'UTF-8'); ?>
                                            </p>
                                        </div>
                                        <span class="badge rounded-pill <?php echo room_bookings_status_badge_class($bookingStatus); ?>"><?php echo htmlspecialchars($bookingStatus, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>

                                    <div class="dashboard-entry-grid">
                                        <div><span class="dashboard-entry-label">Check-in</span><strong><?php echo htmlspecialchars((string)($booking['check_in'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Check-out</span><strong><?php echo htmlspecialchars((string)($booking['check_out'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Nights</span><strong><?php echo (int)($booking['nights'] ?? 0); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Rate</span><strong>$<?php echo number_format((float)($booking['room_rate'] ?? 0), 2); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Total</span><strong>$<?php echo number_format((float)($booking['total_price'] ?? 0), 2); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Guest email</span><strong><?php echo htmlspecialchars((string)($booking['guest_email'] ?? 'Not provided'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Phone</span><strong><?php echo htmlspecialchars((string)($booking['guest_phone'] ?? 'Not provided'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Booked on</span><strong><?php echo htmlspecialchars((string)($booking['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                        <div class="dashboard-entry-notes"><span class="dashboard-entry-label">Billing address</span><strong><?php echo htmlspecialchars(room_bookings_billing_summary($booking), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                    </div>

                                    <div class="dashboard-entry-actions">
                                        <?php if (!$isBookingCancelled): ?>
                                            <details class="dashboard-adjust-panel">
                                                <summary class="dashboard-summary-button">Edit booking</summary>
                                                <form
                                                    action="room_bookings.php"
                                                    method="POST"
                                                    class="dashboard-adjust-form js-dashboard-adjust-form"
                                                    data-room-name="<?php echo htmlspecialchars((string)($booking['room_name'] ?? 'Room booking'), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-original-check-in="<?php echo htmlspecialchars((string)($booking['check_in'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-original-check-out="<?php echo htmlspecialchars((string)($booking['check_out'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-original-nights="<?php echo (int)($booking['nights'] ?? 0); ?>"
                                                    data-room-rate="<?php echo htmlspecialchars(number_format((float)($booking['room_rate'] ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-original-total="<?php echo htmlspecialchars(number_format((float)($booking['total_price'] ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    novalidate
                                                >
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('room_bookings_form'), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="adjust_room_booking">
                                                    <input type="hidden" name="booking_id" value="<?php echo (int)($booking['id'] ?? 0); ?>">
                                                    <input type="hidden" name="card_name" value="">
                                                    <input type="hidden" name="card_number" value="">
                                                    <input type="hidden" name="expiry" value="">
                                                    <input type="hidden" name="cvv" value="">

                                                    <div class="row g-3">
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="booking_check_in_<?php echo (int)($booking['id'] ?? 0); ?>">Check-in</label>
                                                            <input
                                                                class="form-control"
                                                                id="booking_check_in_<?php echo (int)($booking['id'] ?? 0); ?>"
                                                                name="check_in"
                                                                type="date"
                                                                min="<?php echo htmlspecialchars($today->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                value="<?php echo htmlspecialchars((string)($booking['check_in'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                required
                                                            >
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="booking_check_out_<?php echo (int)($booking['id'] ?? 0); ?>">Check-out</label>
                                                            <input
                                                                class="form-control"
                                                                id="booking_check_out_<?php echo (int)($booking['id'] ?? 0); ?>"
                                                                name="check_out"
                                                                type="date"
                                                                min="<?php echo htmlspecialchars($today->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                value="<?php echo htmlspecialchars((string)($booking['check_out'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                required
                                                            >
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="booking_guest_name_<?php echo (int)($booking['id'] ?? 0); ?>">Guest name</label>
                                                            <input
                                                                class="form-control"
                                                                id="booking_guest_name_<?php echo (int)($booking['id'] ?? 0); ?>"
                                                                name="guest_name"
                                                                type="text"
                                                                value="<?php echo htmlspecialchars((string)($booking['guest_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                required
                                                            >
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="booking_guest_email_<?php echo (int)($booking['id'] ?? 0); ?>">Guest email</label>
                                                            <input
                                                                class="form-control"
                                                                id="booking_guest_email_<?php echo (int)($booking['id'] ?? 0); ?>"
                                                                name="guest_email"
                                                                type="email"
                                                                value="<?php echo htmlspecialchars((string)($booking['guest_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                required
                                                            >
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="booking_guest_phone_<?php echo (int)($booking['id'] ?? 0); ?>">Phone</label>
                                                            <input
                                                                class="form-control"
                                                                id="booking_guest_phone_<?php echo (int)($booking['id'] ?? 0); ?>"
                                                                name="guest_phone"
                                                                type="text"
                                                                value="<?php echo htmlspecialchars((string)($booking['guest_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                required
                                                            >
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="booking_billing_city_<?php echo (int)($booking['id'] ?? 0); ?>">Billing city</label>
                                                            <input
                                                                class="form-control"
                                                                id="booking_billing_city_<?php echo (int)($booking['id'] ?? 0); ?>"
                                                                name="billing_city"
                                                                type="text"
                                                                value="<?php echo htmlspecialchars((string)($booking['billing_city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                required
                                                            >
                                                        </div>
                                                        <div class="col-md-8">
                                                            <label class="form-label" for="booking_billing_address_<?php echo (int)($booking['id'] ?? 0); ?>">Billing address</label>
                                                            <input
                                                                class="form-control"
                                                                id="booking_billing_address_<?php echo (int)($booking['id'] ?? 0); ?>"
                                                                name="billing_address"
                                                                type="text"
                                                                value="<?php echo htmlspecialchars((string)($booking['billing_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                required
                                                            >
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="booking_billing_postal_<?php echo (int)($booking['id'] ?? 0); ?>">Billing postal code</label>
                                                            <input
                                                                class="form-control"
                                                                id="booking_billing_postal_<?php echo (int)($booking['id'] ?? 0); ?>"
                                                                name="billing_postal"
                                                                type="text"
                                                                value="<?php echo htmlspecialchars((string)($booking['billing_postal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                required
                                                            >
                                                        </div>
                                                        <div class="col-12 d-flex justify-content-end">
                                                            <button type="submit" class="btn btn-gold">Save changes</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </details>

                                            <form action="room_bookings.php" method="POST" class="dashboard-inline-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('room_bookings_form'), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="cancel_room_booking">
                                                <input type="hidden" name="booking_id" value="<?php echo (int)($booking['id'] ?? 0); ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel this room booking?');">Cancel booking</button>
                                            </form>
                                        <?php else: ?>
                                            <p class="dashboard-status-note mb-0">Cancelled bookings remain in your history and can no longer be edited.</p>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </section>

    <div class="modal fade" id="roomAdjustConfirmModal" tabindex="-1" aria-labelledby="roomAdjustConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content dashboard-modal">
                <div class="modal-header border-0">
                    <h2 class="modal-title h4 mb-0" id="roomAdjustConfirmTitle">Confirm stay update</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" data-adjust-modal-close aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <p class="dashboard-modal-copy js-adjust-modal-copy mb-3">Review the updated booking details before continuing.</p>
                    <div class="dashboard-modal-summary js-adjust-modal-summary"></div>
                    <div class="dashboard-modal-payment js-adjust-modal-payment d-none">
                        <div class="dashboard-modal-payment-note">This update increases your total. Enter card details to pay the additional balance now. Card details are used only for this confirmation and are not stored.</div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="adjust_modal_card_name">Name on card</label>
                                <input class="form-control js-adjust-card-name" id="adjust_modal_card_name" type="text" autocomplete="cc-name" placeholder="Name on card">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="adjust_modal_card_number">Card number</label>
                                <input class="form-control js-adjust-card-number" id="adjust_modal_card_number" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="adjust_modal_expiry">Expiry</label>
                                <input class="form-control js-adjust-expiry" id="adjust_modal_expiry" type="text" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="adjust_modal_cvv">CVV</label>
                                <input class="form-control js-adjust-cvv" id="adjust_modal_cvv" type="password" inputmode="numeric" autocomplete="cc-csc" placeholder="123" maxlength="3">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-adjust-modal-close>Go back</button>
                    <button type="button" class="btn btn-gold js-adjust-modal-confirm">Confirm update</button>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
