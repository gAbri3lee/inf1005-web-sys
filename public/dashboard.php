<?php
require_once __DIR__ . '/../app/includes/auth.php';

auth_require_login(
    'dashboard.php',
    'Please sign in or create an account to access your dashboard.'
);

$pageStylesheets = ['assets/css/dashboard.css'];
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

$dashboardNotice = auth_flash_get('dashboard_notice');
$dashboardError = auth_flash_get('dashboard_error');

try {
    require_once __DIR__ . '/../app/includes/db.php';
} catch (Throwable $exception) {
    $databaseNotice = 'Your account is active, but the dashboard database tables are not available right now. Please check the schema and your MySQL connection.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $redirectTarget = match ($action) {
        'cancel_spa_booking' => 'dashboard.php#spa-bookings',
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

$recentRoomBookings = array_slice($roomBookings, 0, 3);
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
                            <h3>Manage stays</h3>
                            <p>Open your room-bookings page to edit guest details, adjust dates, or cancel a reservation.</p>
                            <a class="btn btn-gold" href="room_bookings.php">Open room bookings</a>
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

                <section class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Room bookings</p>
                            <h2 class="dashboard-panel-title">Recent stay activity</h2>
                        </div>
                        <a class="btn btn-outline-secondary btn-sm" href="room_bookings.php">Manage room bookings</a>
                    </div>

                    <?php if (!$recentRoomBookings): ?>
                        <div class="dashboard-empty">
                            No room bookings yet. Once you confirm a stay, it will appear here and on your room-bookings page.
                        </div>
                    <?php else: ?>
                        <div class="dashboard-list">
                            <?php foreach ($recentRoomBookings as $booking): ?>
                                <?php $bookingStatus = (string)($booking['status'] ?? 'Confirmed'); ?>
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
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($roomBookings) > count($recentRoomBookings)): ?>
                            <div class="dashboard-entry-actions">
                                <a class="btn btn-outline-secondary btn-sm" href="room_bookings.php">View all bookings</a>
                            </div>
                        <?php endif; ?>
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
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
