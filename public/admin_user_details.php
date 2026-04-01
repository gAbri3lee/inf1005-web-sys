<?php
require_once __DIR__ . '/../app/includes/auth.php';

auth_require_login(
    'admin_user_details.php',
    'Please sign in to access this page.'
);

if (!auth_is_admin()) {
    auth_flash_set('dashboard_error', 'Admin access required to view user details.');
    auth_redirect('dashboard.php');
}

$pageStylesheets = ['assets/css/dashboard.css'];
$databaseNotice = '';
$userRecord = null;
$userBookings = [];
$userSpaBookings = [];
$userReviews = [];

function admin_user_details_render_stars(int $rating): string
{
    $rating = max(1, min(5, $rating));
    return str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating);
}

function admin_user_details_status_badge_class(string $status): string
{
    return match (strtolower(trim($status))) {
        'cancelled' => 'text-bg-secondary',
        'pending' => 'text-bg-warning',
        default => 'text-bg-success',
    };
}

$targetUserId = (int)($_GET['user_id'] ?? 0);
if ($targetUserId <= 0) {
    auth_flash_set('dashboard_error', 'Invalid user selected.');
    auth_redirect('dashboard.php#admin-users');
}

try {
    require_once __DIR__ . '/../app/includes/db.php';
} catch (Throwable $exception) {
    $databaseNotice = 'The database is unavailable right now. Please check the schema and your MySQL connection.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $redirectTarget = 'admin_user_details.php?user_id=' . $targetUserId . '#room-bookings';

    if ($databaseNotice !== '' || !isset($pdo)) {
        auth_flash_set('dashboard_error', 'Unable to update the booking right now because the database is unavailable.');
        auth_redirect($redirectTarget);
    }

    if (!csrf_validate('admin_user_details_form', $_POST['csrf_token'] ?? '')) {
        auth_flash_set('dashboard_error', 'Your admin session expired. Please try again.');
        auth_redirect($redirectTarget);
    }

    try {
        switch ($action) {
            case 'admin_delete_booking':
                $bookingId = (int)($_POST['booking_id'] ?? 0);
                if ($bookingId <= 0) {
                    auth_flash_set('dashboard_error', 'Invalid booking selected.');
                    break;
                }

                $checkStmt = $pdo->prepare('SELECT id FROM bookings WHERE id = ? AND user_id = ? LIMIT 1');
                $checkStmt->execute([$bookingId, $targetUserId]);
                if (!$checkStmt->fetch()) {
                    auth_flash_set('dashboard_error', 'That booking could not be found for this user.');
                    break;
                }

                $deleteStmt = $pdo->prepare('DELETE FROM bookings WHERE id = ? AND user_id = ?');
                $deleteStmt->execute([$bookingId, $targetUserId]);
                auth_flash_set('dashboard_notice', 'Booking deleted.');
                break;

            case 'admin_update_booking':
                $bookingId = (int)($_POST['booking_id'] ?? 0);
                $guestName = trim((string)($_POST['guest_name'] ?? ''));
                $guestEmail = strtolower(trim((string)($_POST['guest_email'] ?? '')));
                $guestPhone = trim((string)($_POST['guest_phone'] ?? ''));
                $checkIn = trim((string)($_POST['check_in'] ?? ''));
                $checkOut = trim((string)($_POST['check_out'] ?? ''));
                $status = trim((string)($_POST['status'] ?? ''));

                if ($bookingId <= 0) {
                    auth_flash_set('dashboard_error', 'Invalid booking selected.');
                    break;
                }

                $allowedStatuses = ['Confirmed', 'Pending', 'Cancelled'];
                if (!in_array($status, $allowedStatuses, true)) {
                    $status = 'Confirmed';
                }

                if ($guestEmail !== '' && !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                    auth_flash_set('dashboard_error', 'Please enter a valid guest email address.');
                    break;
                }

                $stmt = $pdo->prepare('SELECT id, nights FROM bookings WHERE id = ? AND user_id = ? LIMIT 1');
                $stmt->execute([$bookingId, $targetUserId]);
                $existing = $stmt->fetch();
                if (!$existing) {
                    auth_flash_set('dashboard_error', 'That booking could not be found for this user.');
                    break;
                }

                $nights = (int)($existing['nights'] ?? 1);
                $checkInDate = DateTimeImmutable::createFromFormat('Y-m-d', $checkIn);
                $checkOutDate = DateTimeImmutable::createFromFormat('Y-m-d', $checkOut);

                if ($checkInDate && $checkOutDate && $checkInDate->format('Y-m-d') === $checkIn && $checkOutDate->format('Y-m-d') === $checkOut) {
                    if ($checkOutDate > $checkInDate) {
                        $diffDays = (int)$checkInDate->diff($checkOutDate)->days;
                        $nights = max(1, $diffDays);
                    }
                }

                $updateStmt = $pdo->prepare(
                    'UPDATE bookings
                     SET guest_name = ?, guest_email = ?, guest_phone = ?, check_in = ?, check_out = ?, status = ?, nights = ?
                     WHERE id = ? AND user_id = ?'
                );
                $updateStmt->execute([
                    $guestName === '' ? null : $guestName,
                    $guestEmail === '' ? null : $guestEmail,
                    $guestPhone === '' ? null : $guestPhone,
                    $checkIn === '' ? null : $checkIn,
                    $checkOut === '' ? null : $checkOut,
                    $status,
                    $nights,
                    $bookingId,
                    $targetUserId,
                ]);

                auth_flash_set('dashboard_notice', 'Booking updated.');
                break;

            default:
                auth_flash_set('dashboard_error', 'That admin action is not supported.');
                break;
        }
    } catch (Throwable $exception) {
        auth_flash_set('dashboard_error', 'Unable to update the booking right now. Please try again.');
    }

    auth_redirect($redirectTarget);
}

if (isset($pdo)) {
    try {
        $userStmt = $pdo->prepare(
            'SELECT id, full_name, email, phone, COALESCE(is_admin, 0) AS is_admin, created_at
             FROM users
             WHERE id = ?
             LIMIT 1'
        );
        $userStmt->execute([$targetUserId]);
        $userRecord = $userStmt->fetch();

        if (!$userRecord) {
            auth_flash_set('dashboard_error', 'That user could not be found.');
            auth_redirect('dashboard.php#admin-users');
        }

        if ((int)($userRecord['is_admin'] ?? 0) === 1) {
            auth_flash_set('dashboard_error', 'Admin accounts are not shown on this details page.');
            auth_redirect('dashboard.php#admin-users');
        }

        $bookingsStmt = $pdo->prepare(
            'SELECT id, room_name, guest_name, guest_email, guest_phone, check_in, check_out, nights, room_rate, total_price, status, created_at
             FROM bookings
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC'
        );
        $bookingsStmt->execute([$targetUserId]);
        $userBookings = $bookingsStmt->fetchAll();

        $spaStmt = $pdo->prepare(
            'SELECT id, treatment_name, treatment_date, treatment_time, guests, status, notes, created_at
             FROM spa_bookings
             WHERE user_id = ?
             ORDER BY treatment_date DESC, treatment_time DESC, id DESC'
        );
        $spaStmt->execute([$targetUserId]);
        $userSpaBookings = $spaStmt->fetchAll();

        $reviewStmt = $pdo->prepare(
            'SELECT id, rating, title, body, created_at
             FROM reviews
             WHERE user_id = ?
             ORDER BY created_at DESC, id DESC'
        );
        $reviewStmt->execute([$targetUserId]);
        $userReviews = $reviewStmt->fetchAll();
    } catch (Throwable $exception) {
        $databaseNotice = 'The database is unavailable right now. Please check the schema and your MySQL connection.';
    }
}

$dashboardNotice = auth_flash_get('dashboard_notice');
$dashboardError = auth_flash_get('dashboard_error');

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="dashboard-page">
    <section class="dashboard-hero">
        <div class="container">
            <div class="dashboard-hero-card reveal-up">
                <div class="row g-4 align-items-end">
                    <div class="col-lg-8">
                        <span class="section-eyebrow text-white">Admin</span>
                        <h1 class="dashboard-title">User details</h1>
                        <p class="dashboard-subtitle mb-0">Full activity overview: room bookings, spa bookings, and reviews.</p>
                    </div>
                    <div class="col-lg-4">
                        <div class="dashboard-entry-actions justify-content-end" style="gap: 10px;">
                            <a class="btn btn-outline-light btn-sm" href="dashboard.php#admin-users">Back to dashboard</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-stats reveal-up" aria-label="User summary">
                <article class="dashboard-stat-card">
                    <span class="dashboard-stat-label">Room bookings</span>
                    <strong class="dashboard-stat-value"><?php echo count($userBookings); ?></strong>
                </article>
                <article class="dashboard-stat-card">
                    <span class="dashboard-stat-label">Spa bookings</span>
                    <strong class="dashboard-stat-value"><?php echo count($userSpaBookings); ?></strong>
                </article>
                <article class="dashboard-stat-card">
                    <span class="dashboard-stat-label">Reviews posted</span>
                    <strong class="dashboard-stat-value"><?php echo count($userReviews); ?></strong>
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

            <?php if ($userRecord): ?>
                <section class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Account</p>
                            <h2 class="dashboard-panel-title"><?php echo htmlspecialchars((string)($userRecord['full_name'] ?? $userRecord['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
                        </div>
                    </div>

                    <div class="dashboard-entry-grid">
                        <div><span class="dashboard-entry-label">User ID</span><strong><?php echo (int)($userRecord['id'] ?? 0); ?></strong></div>
                        <div><span class="dashboard-entry-label">Email</span><strong><?php echo htmlspecialchars((string)($userRecord['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <div><span class="dashboard-entry-label">Phone</span><strong><?php echo htmlspecialchars((string)(($userRecord['phone'] ?? '') !== '' ? $userRecord['phone'] : '—'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        <div><span class="dashboard-entry-label">Created</span><strong><?php echo htmlspecialchars((string)($userRecord['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    </div>
                </section>

                <section id="room-bookings" class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Room bookings</p>
                            <h2 class="dashboard-panel-title">Room booking history</h2>
                        </div>
                    </div>

                    <?php if (!$userBookings): ?>
                        <div class="dashboard-empty">No room bookings found for this user.</div>
                    <?php else: ?>
                        <div class="dashboard-list">
                            <?php foreach ($userBookings as $booking): ?>
                                <?php $status = (string)($booking['status'] ?? 'Confirmed'); ?>
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
                                        <span class="badge rounded-pill <?php echo admin_user_details_status_badge_class($status); ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>

                                    <form action="admin_user_details.php?user_id=<?php echo $targetUserId; ?>#room-bookings" method="POST" class="dashboard-adjust-form" novalidate>
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('admin_user_details_form'), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="admin_update_booking">
                                        <input type="hidden" name="booking_id" value="<?php echo (int)($booking['id'] ?? 0); ?>">

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Guest name</label>
                                                <input class="form-control" type="text" name="guest_name" value="<?php echo htmlspecialchars((string)($booking['guest_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Guest email</label>
                                                <input class="form-control" type="email" name="guest_email" value="<?php echo htmlspecialchars((string)($booking['guest_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Guest phone</label>
                                                <input class="form-control" type="text" name="guest_phone" value="<?php echo htmlspecialchars((string)($booking['guest_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Check-in</label>
                                                <input class="form-control" type="date" name="check_in" value="<?php echo htmlspecialchars((string)($booking['check_in'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Check-out</label>
                                                <input class="form-control" type="date" name="check_out" value="<?php echo htmlspecialchars((string)($booking['check_out'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status">
                                                    <?php foreach (['Confirmed', 'Pending', 'Cancelled'] as $statusOption): ?>
                                                        <option value="<?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $status === $statusOption ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($statusOption, ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-8 d-flex align-items-end" style="gap: 10px; flex-wrap: wrap;">
                                                <button type="submit" class="btn btn-gold btn-sm">Save booking</button>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="dashboard-entry-actions">
                                        <form action="admin_user_details.php?user_id=<?php echo $targetUserId; ?>#room-bookings" method="POST" class="dashboard-inline-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('admin_user_details_form'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="admin_delete_booking">
                                            <input type="hidden" name="booking_id" value="<?php echo (int)($booking['id'] ?? 0); ?>">
                                            <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Delete this booking?');">Delete booking</button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Spa bookings</p>
                            <h2 class="dashboard-panel-title">Spa booking history</h2>
                        </div>
                    </div>

                    <?php if (!$userSpaBookings): ?>
                        <div class="dashboard-empty">No spa bookings found for this user.</div>
                    <?php else: ?>
                        <div class="dashboard-list">
                            <?php foreach ($userSpaBookings as $spaBooking): ?>
                                <?php $spaStatus = (string)($spaBooking['status'] ?? 'Pending'); ?>
                                <article class="dashboard-entry">
                                    <div class="dashboard-entry-top">
                                        <div>
                                            <h3><?php echo htmlspecialchars((string)($spaBooking['treatment_name'] ?? 'Spa booking'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <p class="dashboard-entry-meta mb-0">
                                                <?php echo htmlspecialchars((string)($spaBooking['treatment_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                at
                                                <?php echo htmlspecialchars(substr((string)($spaBooking['treatment_time'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8'); ?>
                                            </p>
                                        </div>
                                        <span class="badge rounded-pill <?php echo admin_user_details_status_badge_class($spaStatus); ?>"><?php echo htmlspecialchars($spaStatus, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>

                                    <div class="dashboard-entry-grid">
                                        <div><span class="dashboard-entry-label">Guests</span><strong><?php echo (int)($spaBooking['guests'] ?? 1); ?></strong></div>
                                        <div><span class="dashboard-entry-label">Requested on</span><strong><?php echo htmlspecialchars((string)($spaBooking['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                        <div class="dashboard-entry-notes"><span class="dashboard-entry-label">Notes</span><strong><?php echo htmlspecialchars((string)(($spaBooking['notes'] ?? '') !== '' ? $spaBooking['notes'] : 'No notes provided.'), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Reviews</p>
                            <h2 class="dashboard-panel-title">Reviews posted</h2>
                        </div>
                    </div>

                    <?php if (!$userReviews): ?>
                        <div class="dashboard-empty">No reviews found for this user.</div>
                    <?php else: ?>
                        <div class="dashboard-review-list">
                            <?php foreach ($userReviews as $review): ?>
                                <?php
                                    $rating = (int)($review['rating'] ?? 0);
                                    $title = (string)($review['title'] ?? '');
                                    $body = (string)($review['body'] ?? '');
                                ?>
                                <article class="dashboard-review-card">
                                    <div class="dashboard-review-head">
                                        <div>
                                            <h3><?php echo htmlspecialchars($title !== '' ? $title : 'Guest review', ENT_QUOTES, 'UTF-8'); ?></h3>
                                            <p class="dashboard-entry-meta mb-0"><?php echo htmlspecialchars((string)($review['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                        <div class="dashboard-review-stars" aria-label="<?php echo $rating; ?> out of 5 stars">
                                            <?php echo admin_user_details_render_stars($rating); ?>
                                        </div>
                                    </div>
                                    <?php if ($body !== ''): ?>
                                        <p class="dashboard-review-body mb-0"><?php echo htmlspecialchars($body, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
