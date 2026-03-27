<?php
require_once __DIR__ . '/../app/includes/auth.php';

auth_require_login(
    'dashboard.php',
    'Please sign in or create an account to access your dashboard.'
);

$pageStylesheets = ['assets/css/dashboard.css'];
$pageScripts = ['assets/js/dashboard.js'];
$databaseNotice = '';
$roomBookings = [];
$spaBookings = [];
$reviews = [];
$summary = [
    'room_bookings' => 0,
    'spa_bookings' => 0,
    'reviews' => 0,
];

function dashboard_render_stars(int $rating): string
{
    $rating = max(1, min(5, $rating));
    return str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating);
}

function dashboard_is_cancelled(string $status): bool
{
    return strtolower(trim($status)) === 'cancelled';
}

function dashboard_status_badge_class(string $status): string
{
    return match (strtolower(trim($status))) {
        'cancelled' => 'text-bg-secondary',
        'pending' => 'text-bg-warning',
        default => 'text-bg-success',
    };
}

function dashboard_parse_date(string $value): ?DateTimeImmutable
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

function dashboard_digits_only(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function dashboard_parse_expiry_end(string $value): ?DateTimeImmutable
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

$dashboardNotice = auth_flash_get('dashboard_notice');
$dashboardError = auth_flash_get('dashboard_error');
$today = new DateTimeImmutable('today');

try {
    require_once __DIR__ . '/../app/includes/db.php';
} catch (Throwable $exception) {
    $databaseNotice = 'Your account is active, but the dashboard database tables are not available right now. Please check the schema and your MySQL connection.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $redirectTarget = match ($action) {
        'cancel_spa_booking' => 'dashboard.php#spa-bookings',
        'cancel_room_booking', 'adjust_room_booking' => 'dashboard.php#room-bookings',
        default => 'dashboard.php',
    };

    if ($databaseNotice !== '' || !isset($pdo)) {
        auth_flash_set('dashboard_error', 'Unable to update your booking right now because the database is unavailable.');
        auth_redirect($redirectTarget);
    }

    if (!csrf_validate('dashboard_action_form', $_POST['csrf_token'] ?? '')) {
        auth_flash_set('dashboard_error', 'Your dashboard session expired. Please try again.');
        auth_redirect($redirectTarget);
    }

    $userId = auth_user_id() ?? 0;

    try {
        switch ($action) {
            case 'cancel_spa_booking':
                $spaBookingId = (int)($_POST['spa_booking_id'] ?? 0);
                $stmt = $pdo->prepare('SELECT id, status FROM spa_bookings WHERE id = ? AND user_id = ? LIMIT 1');
                $stmt->execute([$spaBookingId, $userId]);
                $spaBooking = $stmt->fetch();

                if (!$spaBooking) {
                    auth_flash_set('dashboard_error', 'That spa booking could not be found.');
                } elseif (dashboard_is_cancelled((string)($spaBooking['status'] ?? ''))) {
                    auth_flash_set('dashboard_error', 'That spa booking is already cancelled.');
                } else {
                    $updateStmt = $pdo->prepare('UPDATE spa_bookings SET status = ? WHERE id = ? AND user_id = ?');
                    $updateStmt->execute(['Cancelled', $spaBookingId, $userId]);
                    auth_flash_set('dashboard_notice', 'Your spa treatment has been cancelled.');
                }
                break;

            case 'cancel_room_booking':
                $bookingId = (int)($_POST['booking_id'] ?? 0);
                $stmt = $pdo->prepare('SELECT id, status FROM bookings WHERE id = ? AND user_id = ? LIMIT 1');
                $stmt->execute([$bookingId, $userId]);
                $booking = $stmt->fetch();

                if (!$booking) {
                    auth_flash_set('dashboard_error', 'That room booking could not be found.');
                } elseif (dashboard_is_cancelled((string)($booking['status'] ?? ''))) {
                    auth_flash_set('dashboard_error', 'That room booking is already cancelled.');
                } else {
                    $updateStmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ? AND user_id = ?');
                    $updateStmt->execute(['Cancelled', $bookingId, $userId]);
                    auth_flash_set('dashboard_notice', 'Your room booking has been cancelled.');
                }
                break;

            case 'adjust_room_booking':
                $bookingId = (int)($_POST['booking_id'] ?? 0);
                $checkInValue = trim((string)($_POST['check_in'] ?? ''));
                $checkOutValue = trim((string)($_POST['check_out'] ?? ''));

                $stmt = $pdo->prepare('SELECT id, status, room_rate, total_price, room_name FROM bookings WHERE id = ? AND user_id = ? LIMIT 1');
                $stmt->execute([$bookingId, $userId]);
                $booking = $stmt->fetch();

                if (!$booking) {
                    auth_flash_set('dashboard_error', 'That room booking could not be found.');
                    break;
                }

                if (dashboard_is_cancelled((string)($booking['status'] ?? ''))) {
                    auth_flash_set('dashboard_error', 'Cancelled room bookings cannot be adjusted.');
                    break;
                }

                $checkInDate = dashboard_parse_date($checkInValue);
                $checkOutDate = dashboard_parse_date($checkOutValue);

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

                $nights = max(1, (int)$checkInDate->diff($checkOutDate)->days);
                $roomRate = (float)($booking['room_rate'] ?? 0);
                $previousTotal = (float)($booking['total_price'] ?? 0);
                $totalPrice = $roomRate * $nights;
                $priceDifference = $totalPrice - $previousTotal;

                if ($priceDifference > 0.009) {
                    $cardName = trim((string)($_POST['card_name'] ?? ''));
                    $cardDigits = dashboard_digits_only((string)($_POST['card_number'] ?? ''));
                    $expiryValue = trim((string)($_POST['expiry'] ?? ''));
                    $cvvDigits = dashboard_digits_only((string)($_POST['cvv'] ?? ''));
                    $expiryEnd = dashboard_parse_expiry_end($expiryValue);

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
                     SET check_in = :check_in,
                         check_out = :check_out,
                         nights = :nights,
                         total_price = :total_price
                     WHERE id = :booking_id AND user_id = :user_id'
                );
                $updateStmt->execute([
                    ':check_in' => $checkInDate->format('Y-m-d'),
                    ':check_out' => $checkOutDate->format('Y-m-d'),
                    ':nights' => $nights,
                    ':total_price' => $totalPrice,
                    ':booking_id' => $bookingId,
                    ':user_id' => $userId,
                ]);

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
                    auth_flash_set('dashboard_notice', 'Your room booking dates have been updated with no change in total price.');
                }
                break;

            default:
                auth_flash_set('dashboard_error', 'That dashboard action is not supported.');
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
            'SELECT id, room_name, check_in, check_out, nights, room_rate, total_price, status, created_at
             FROM bookings
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC'
        );
        $bookingStmt->execute([$userId]);
        $roomBookings = $bookingStmt->fetchAll();

        $spaStmt = $pdo->prepare(
            'SELECT id, treatment_name, treatment_date, treatment_time, guests, status, notes, created_at
             FROM spa_bookings
             WHERE user_id = ?
             ORDER BY treatment_date DESC, treatment_time DESC, id DESC'
        );
        $spaStmt->execute([$userId]);
        $spaBookings = $spaStmt->fetchAll();

        $reviewStmt = $pdo->prepare(
            'SELECT id, rating, title, body, created_at
             FROM reviews
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC'
        );
        $reviewStmt->execute([$userId]);
        $reviews = $reviewStmt->fetchAll();

        $summary = [
            'room_bookings' => count($roomBookings),
            'spa_bookings' => count($spaBookings),
            'reviews' => count($reviews),
        ];
    } catch (Throwable $exception) {
        $databaseNotice = 'Your account is active, but the dashboard database tables are not available right now. Please check the schema and your MySQL connection.';
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
                        <span class="section-eyebrow text-white">User dashboard</span>
                        <h1 class="dashboard-title">Welcome back, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="dashboard-subtitle mb-0">Manage your room stays, spa reservations, and review activity from one place.</p>
                    </div>
                    <div class="col-lg-4">
                        <div class="dashboard-user-meta">
                            <div class="dashboard-meta-label">Signed in as</div>
                            <div class="dashboard-meta-value"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-stats reveal-up" aria-label="Dashboard summary">
                <article class="dashboard-stat-card">
                    <span class="dashboard-stat-label">Room bookings</span>
                    <strong class="dashboard-stat-value"><?php echo $summary['room_bookings']; ?></strong>
                </article>
                <article class="dashboard-stat-card">
                    <span class="dashboard-stat-label">Spa reservations</span>
                    <strong class="dashboard-stat-value"><?php echo $summary['spa_bookings']; ?></strong>
                </article>
                <article class="dashboard-stat-card">
                    <span class="dashboard-stat-label">Reviews posted</span>
                    <strong class="dashboard-stat-value"><?php echo $summary['reviews']; ?></strong>
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
                            <p class="dashboard-panel-label">Quick actions</p>
                            <h2 class="dashboard-panel-title">Plan your next experience</h2>
                        </div>
                    </div>

                    <div class="dashboard-actions-grid">
                        <article class="dashboard-action-card">
                            <h3>Book a room</h3>
                            <p>Browse the suites and villas collection, choose your dates, and confirm your next stay.</p>
                            <a class="btn btn-gold" href="rooms_and_suites.php">Explore rooms</a>
                        </article>
                        <article class="dashboard-action-card">
                            <h3>Reserve the spa</h3>
                            <p>Schedule a treatment and keep track of every wellness booking in your account.</p>
                            <a class="btn btn-gold" href="spa_booking.php">Book the spa</a>
                        </article>
                        <article class="dashboard-action-card">
                            <h3>Leave a review</h3>
                            <p>Share your experience with future guests and keep your published feedback in one place.</p>
                            <a class="btn btn-gold" href="reviews.php">Write a review</a>
                        </article>
                    </div>
                </section>

                <section id="room-bookings" class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Room bookings</p>
                            <h2 class="dashboard-panel-title">Your stay history</h2>
                        </div>
                        <a class="btn btn-outline-secondary btn-sm" href="rooms_and_suites.php">Book another stay</a>
                    </div>

                    <?php if (!$roomBookings): ?>
                        <div class="dashboard-empty">
                            No room bookings yet. Your confirmed stays will appear here once you check out.
                        </div>
                    <?php else: ?>
                        <div class="dashboard-list">
                            <?php foreach ($roomBookings as $booking): ?>
                                <?php
                                    $bookingStatus = (string)($booking['status'] ?? 'Confirmed');
                                    $isBookingCancelled = dashboard_is_cancelled($bookingStatus);
                                ?>
                                <article class="dashboard-entry">
                                    <div class="dashboard-entry-top">
                                        <div>
                                            <h3><?php echo htmlspecialchars((string)($booking['room_name'] ?? 'Room booking'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <p class="dashboard-entry-meta mb-0">
                                                <?php echo htmlspecialchars((string)($booking['check_in'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                to
                                                <?php echo htmlspecialchars((string)($booking['check_out'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                            </p>
                                        </div>
                                        <span class="badge rounded-pill <?php echo dashboard_status_badge_class($bookingStatus); ?>"><?php echo htmlspecialchars($bookingStatus, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="dashboard-entry-grid">
                                        <div><span class="dashboard-entry-label">Nights</span><strong><?php echo (int)($booking['nights'] ?? 0); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Rate</span><strong>$<?php echo number_format((float)($booking['room_rate'] ?? 0), 2); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Total</span><strong>$<?php echo number_format((float)($booking['total_price'] ?? 0), 2); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Booked on</span><strong><?php echo htmlspecialchars((string)($booking['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                    </div>

                                    <div class="dashboard-entry-actions">
                                        <?php if (!$isBookingCancelled): ?>
                                            <details class="dashboard-adjust-panel">
                                                <summary class="dashboard-summary-button">Adjust stay</summary>
                                                <form
                                                    action="dashboard.php#room-bookings"
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
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('dashboard_action_form'), ENT_QUOTES, 'UTF-8'); ?>">
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
                                                        <div class="col-md-4 d-flex align-items-end">
                                                            <button type="submit" class="btn btn-gold w-100">Save changes</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </details>

                                            <form action="dashboard.php#room-bookings" method="POST" class="dashboard-inline-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('dashboard_action_form'), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="cancel_room_booking">
                                                <input type="hidden" name="booking_id" value="<?php echo (int)($booking['id'] ?? 0); ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel this room booking?');">Cancel booking</button>
                                            </form>
                                        <?php else: ?>
                                            <p class="dashboard-status-note mb-0">Cancelled bookings can no longer be adjusted.</p>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section id="spa-bookings" class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Spa reservations</p>
                            <h2 class="dashboard-panel-title">Upcoming wellness sessions</h2>
                        </div>
                        <a class="btn btn-outline-secondary btn-sm" href="spa_booking.php">Book a treatment</a>
                    </div>

                    <?php if (!$spaBookings): ?>
                        <div class="dashboard-empty">
                            No spa reservations yet. Once you reserve a treatment, it will appear here with its status.
                        </div>
                    <?php else: ?>
                        <div class="dashboard-list">
                            <?php foreach ($spaBookings as $spaBooking): ?>
                                <?php
                                    $spaStatus = (string)($spaBooking['status'] ?? 'Pending');
                                    $isSpaCancelled = dashboard_is_cancelled($spaStatus);
                                ?>
                                <article class="dashboard-entry">
                                    <div class="dashboard-entry-top">
                                        <div>
                                            <h3><?php echo htmlspecialchars((string)($spaBooking['treatment_name'] ?? 'Spa treatment'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <p class="dashboard-entry-meta mb-0">
                                                <?php echo htmlspecialchars((string)($spaBooking['treatment_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                at
                                                <?php echo htmlspecialchars(substr((string)($spaBooking['treatment_time'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8'); ?>
                                            </p>
                                        </div>
                                        <span class="badge rounded-pill <?php echo dashboard_status_badge_class($spaStatus); ?>"><?php echo htmlspecialchars($spaStatus, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="dashboard-entry-grid">
                                        <div><span class="dashboard-entry-label">Guests</span><strong><?php echo (int)($spaBooking['guests'] ?? 1); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Requested on</span><strong><?php echo htmlspecialchars((string)($spaBooking['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                        <div class="dashboard-entry-notes"><span class="dashboard-entry-label">Notes</span><strong><?php echo htmlspecialchars((string)(($spaBooking['notes'] ?? '') !== '' ? $spaBooking['notes'] : 'No notes provided.'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                    </div>

                                    <div class="dashboard-entry-actions">
                                        <?php if (!$isSpaCancelled): ?>
                                            <form action="dashboard.php#spa-bookings" method="POST" class="dashboard-inline-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('dashboard_action_form'), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="cancel_spa_booking">
                                                <input type="hidden" name="spa_booking_id" value="<?php echo (int)($spaBooking['id'] ?? 0); ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel this spa treatment?');">Cancel treatment</button>
                                            </form>
                                        <?php else: ?>
                                            <p class="dashboard-status-note mb-0">Cancelled spa treatments remain here for your records.</p>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section id="my-reviews" class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Review activity</p>
                            <h2 class="dashboard-panel-title">Your published reviews</h2>
                        </div>
                        <a class="btn btn-outline-secondary btn-sm" href="reviews.php">Open reviews page</a>
                    </div>

                    <?php if (!$reviews): ?>
                        <div class="dashboard-empty">
                            You have not published any reviews yet. Once you submit one, it will appear here.
                        </div>
                    <?php else: ?>
                        <div class="dashboard-review-list">
                            <?php foreach ($reviews as $review): ?>
                                <article class="dashboard-review-card">
                                    <div class="dashboard-review-head">
                                        <div>
                                            <h3><?php echo htmlspecialchars((string)(($review['title'] ?? '') !== '' ? $review['title'] : 'Guest review'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <p class="dashboard-entry-meta mb-0"><?php echo htmlspecialchars((string)($review['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <div class="dashboard-review-stars" aria-label="<?php echo (int)($review['rating'] ?? 0); ?> out of 5 stars">
                                            <?php echo dashboard_render_stars((int)($review['rating'] ?? 0)); ?>
                                        </div>
                                    </div>
                                    <p class="dashboard-review-body mb-0"><?php echo htmlspecialchars((string)($review['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
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
                    <p class="dashboard-modal-copy js-adjust-modal-copy mb-3">Review the updated stay details before continuing.</p>
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
