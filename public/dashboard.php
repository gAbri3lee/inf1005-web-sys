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
$loyaltySnapshot = null;
$isAdmin = auth_is_admin();
$adminUsers = [];
$adminBookingsByUser = [];
$adminSpaBookingsByUser = [];
$adminReviewsByUser = [];
$accountRecord = null;
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

function dashboard_validate_password_strength(string $password, array &$errors): bool
{
    $minLen = 8;

    if ($password === '' || app_string_length($password) < $minLen) {
        $errors[] = 'Password must be at least 8 characters.';
        return false;
    }

    $hasLower = (bool)preg_match('/[a-z]/', $password);
    $hasUpper = (bool)preg_match('/[A-Z]/', $password);
    $hasDigit = (bool)preg_match('/\d/', $password);
    $hasSymbol = (bool)preg_match('/[^A-Za-z\d]/', $password);

    if (!$hasLower || !$hasUpper || !$hasDigit || !$hasSymbol) {
        $errors[] = 'Password must include uppercase, lowercase, a number, and a symbol.';
        return false;
    }

    return true;
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
        'update_account_details' => 'dashboard.php#account-details',
        'change_password' => 'dashboard.php#account-details',
        'admin_update_user_phone' => 'dashboard.php#admin-users',
        'admin_delete_user' => 'dashboard.php#admin-users',
        'admin_update_booking' => 'dashboard.php#admin-users',
        'admin_delete_booking' => 'dashboard.php#admin-users',
        default => 'dashboard.php',
    };

    // Allow admin booking actions to return to a specific user section.
    if (in_array($action, ['admin_update_booking', 'admin_delete_booking'], true)) {
        $returnTo = trim((string)($_POST['return_to'] ?? ''));
        $openUserId = (int)($_POST['open_user'] ?? 0);

        if ($returnTo !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $returnTo)) {
            $redirectTarget = 'dashboard.php'
                . ($openUserId > 0 ? ('?open_user=' . $openUserId) : '')
                . '#' . $returnTo;
        }
    }

    if ($databaseNotice !== '' || !isset($pdo)) {
        $fallbackMessage = match ($action) {
            'change_password' => 'Unable to update your password right now because the database is unavailable.',
            'update_account_details' => 'Unable to update your account right now because the database is unavailable.',
            default => 'Unable to update your dashboard right now because the database is unavailable.',
        };
        auth_flash_set('dashboard_error', $fallbackMessage);
        auth_redirect($redirectTarget);
    }

    if (!csrf_validate('dashboard_action_form', $_POST['csrf_token'] ?? '')) {
        auth_flash_set('dashboard_error', 'Your dashboard session expired. Please try again.');
        auth_redirect($redirectTarget);
    }

    $userId = auth_user_id() ?? 0;

    try {
        switch ($action) {
            case 'update_account_details':
                if (auth_is_admin()) {
                    auth_flash_set('dashboard_error', 'Account updates are unavailable for admin accounts.');
                    break;
                }

                $fullName = trim((string)($_POST['full_name'] ?? ''));
                $phone = trim((string)($_POST['phone'] ?? ''));
                $phone = $phone === '' ? null : $phone;

                if ($userId <= 0) {
                    auth_flash_set('dashboard_error', 'Unable to update your account right now.');
                    break;
                }

                if ($fullName === '') {
                    auth_flash_set('dashboard_error', 'Please enter your name.');
                    break;
                }

                if (app_string_length($fullName) > 100) {
                    auth_flash_set('dashboard_error', 'Name must be 100 characters or fewer.');
                    break;
                }

                if ($phone !== null && app_string_length($phone) > 50) {
                    auth_flash_set('dashboard_error', 'Phone number must be 50 characters or fewer.');
                    break;
                }

                if ($phone !== null && $phone !== '' && !preg_match('/^\d+$/', $phone)) {
                    auth_flash_set('dashboard_error', 'Numbers only');
                    break;
                }

                $updateStmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
                $updateStmt->execute([$fullName, $phone, $userId]);

                $_SESSION['auth_user']['display_name'] = $fullName;
                $_SESSION['full_name'] = $fullName;

                auth_flash_set('dashboard_notice', 'Account details updated.');
                break;

            case 'change_password':
                if (auth_is_admin()) {
                    auth_flash_set('dashboard_error', 'Password updates are unavailable for admin accounts.');
                    break;
                }

                $currentPassword = (string)($_POST['current_password'] ?? '');
                $newPassword = (string)($_POST['new_password'] ?? '');
                $confirmPassword = (string)($_POST['confirm_password'] ?? '');

                if ($userId <= 0) {
                    auth_flash_set('dashboard_error', 'Unable to update your password right now.');
                    break;
                }

                $pwErrors = [];

                if ($currentPassword === '') {
                    $pwErrors[] = 'Please enter your current password.';
                }

                if ($newPassword !== $confirmPassword) {
                    $pwErrors[] = 'New password and confirmation do not match.';
                }

                dashboard_validate_password_strength($newPassword, $pwErrors);

                if ($pwErrors) {
                    auth_flash_set('dashboard_error', implode(' ', $pwErrors));
                    break;
                }

                $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $record = $stmt->fetch();
                $storedPassword = (string)($record['password'] ?? '');

                if ($storedPassword === '') {
                    auth_flash_set('dashboard_error', 'Unable to verify your current password. Please contact support.');
                    break;
                }

                $info = password_get_info($storedPassword);
                $isHashed = (int)($info['algo'] ?? 0) !== 0;

                $currentOk = $isHashed
                    ? password_verify($currentPassword, $storedPassword)
                    : hash_equals($storedPassword, $currentPassword);

                if (!$currentOk) {
                    auth_flash_set('dashboard_error', 'Current password is incorrect.');
                    break;
                }

                if ($newPassword === $currentPassword || ($isHashed && password_verify($newPassword, $storedPassword))) {
                    auth_flash_set('dashboard_error', 'New password must be different from your current password.');
                    break;
                }

                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ? AND password = ?');
                $updateStmt->execute([$newHash, $userId, $storedPassword]);

                if ($updateStmt->rowCount() !== 1) {
                    auth_flash_set('dashboard_error', 'Password was not updated. Please try again.');
                    break;
                }

                auth_flash_set('dashboard_notice', 'Password updated.');
                break;

            case 'admin_update_user_phone':
                if (!auth_is_admin()) {
                    auth_flash_set('dashboard_error', 'Admin access required to update user details.');
                    break;
                }

                $targetUserId = (int)($_POST['user_id'] ?? 0);
                $phone = trim((string)($_POST['phone'] ?? ''));
                $phone = $phone === '' ? null : $phone;

                if ($targetUserId <= 0) {
                    auth_flash_set('dashboard_error', 'Invalid user selected.');
                    break;
                }

                if ($phone !== null && app_string_length($phone) > 50) {
                    auth_flash_set('dashboard_error', 'Phone number must be 50 characters or fewer.');
                    break;
                }

                if ($phone !== null && $phone !== '' && !preg_match('/^\d+$/', $phone)) {
                    auth_flash_set('dashboard_error', 'Numbers only');
                    break;
                }

                $stmt = $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?');
                $stmt->execute([$phone, $targetUserId]);
                auth_flash_set('dashboard_notice', 'User phone number updated.');
                break;

            case 'admin_delete_user':
                if (!auth_is_admin()) {
                    auth_flash_set('dashboard_error', 'Admin access required to delete users.');
                    break;
                }

                $targetUserId = (int)($_POST['user_id'] ?? 0);
                if ($targetUserId <= 0) {
                    auth_flash_set('dashboard_error', 'Invalid user selected.');
                    break;
                }

                if ($targetUserId === $userId) {
                    auth_flash_set('dashboard_error', 'You cannot delete your own admin account.');
                    break;
                }

                $checkStmt = $pdo->prepare('SELECT id, COALESCE(is_admin, 0) AS is_admin FROM users WHERE id = ? LIMIT 1');
                $checkStmt->execute([$targetUserId]);
                $target = $checkStmt->fetch();

                if (!$target) {
                    auth_flash_set('dashboard_error', 'That user could not be found.');
                    break;
                }

                if ((int)($target['is_admin'] ?? 0) === 1) {
                    auth_flash_set('dashboard_error', 'Admin accounts cannot be deleted from this dashboard.');
                    break;
                }

                $pdo->beginTransaction();
                try {
                    $deleteBookingsStmt = $pdo->prepare('DELETE FROM bookings WHERE user_id = ?');
                    $deleteBookingsStmt->execute([$targetUserId]);

                    $deleteStmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                    $deleteStmt->execute([$targetUserId]);

                    $pdo->commit();
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $exception;
                }
                auth_flash_set('dashboard_notice', 'User account deleted.');
                break;

            case 'admin_delete_booking':
                if (!auth_is_admin()) {
                    auth_flash_set('dashboard_error', 'Admin access required to delete bookings.');
                    break;
                }

                $bookingId = (int)($_POST['booking_id'] ?? 0);
                if ($bookingId <= 0) {
                    auth_flash_set('dashboard_error', 'Invalid booking selected.');
                    break;
                }

                $deleteStmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
                $deleteStmt->execute([$bookingId]);
                auth_flash_set('dashboard_notice', 'Booking deleted.');
                break;

            case 'admin_update_booking':
                if (!auth_is_admin()) {
                    auth_flash_set('dashboard_error', 'Admin access required to update bookings.');
                    break;
                }

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

                $stmt = $pdo->prepare('SELECT id, nights FROM bookings WHERE id = ? LIMIT 1');
                $stmt->execute([$bookingId]);
                $existing = $stmt->fetch();
                if (!$existing) {
                    auth_flash_set('dashboard_error', 'That booking could not be found.');
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
                     WHERE id = ?'
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
                ]);

                auth_flash_set('dashboard_notice', 'Booking updated.');
                break;

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
        $fallbackMessage = match ($action) {
            'change_password' => 'Unable to update your password right now. Please try again.',
            'update_account_details' => 'Unable to update your account details right now. Please try again.',
            default => 'Unable to update your dashboard right now. Please try again.',
        };
        auth_flash_set('dashboard_error', $fallbackMessage);
    }

    auth_redirect($redirectTarget);
}

if (isset($pdo)) {
    try {
        $userId = auth_user_id();

        if (auth_is_admin()) {
            try {
                $usersStmt = $pdo->query(
                    'SELECT id, full_name, email, phone, COALESCE(is_admin, 0) AS is_admin, created_at
                     FROM users
                     WHERE COALESCE(is_admin, 0) = 0
                     ORDER BY created_at DESC, id DESC'
                );
                $adminUsers = $usersStmt->fetchAll();
            } catch (Throwable $exception) {
                // Fallback for older schemas: still hide the seeded admin account by email.
                try {
                    $usersStmt = $pdo->prepare(
                        'SELECT id, full_name, email, phone, created_at
                         FROM users
                         WHERE email <> ?
                         ORDER BY created_at DESC, id DESC'
                    );
                    $usersStmt->execute(['admin@horizonsands.test']);
                    $adminUsers = $usersStmt->fetchAll();
                } catch (Throwable $exception) {
                    $adminUsers = [];
                }
            }

            try {
                $bookingsStmt = $pdo->query(
                    'SELECT b.id, b.user_id, b.room_name, b.guest_name, b.guest_email, b.guest_phone, b.check_in, b.check_out, b.nights, b.room_rate, b.total_price, b.status, b.created_at
                     FROM bookings b
                     INNER JOIN users u ON u.id = b.user_id
                     WHERE COALESCE(u.is_admin, 0) = 0
                     ORDER BY b.created_at DESC, b.id DESC'
                );
                $allBookings = $bookingsStmt->fetchAll();
                foreach ($allBookings as $booking) {
                    $bookingUserId = (int)($booking['user_id'] ?? 0);
                    if (!isset($adminBookingsByUser[$bookingUserId])) {
                        $adminBookingsByUser[$bookingUserId] = [];
                    }
                    $adminBookingsByUser[$bookingUserId][] = $booking;
                }
            } catch (Throwable $exception) {
                $adminBookingsByUser = [];
            }

            try {
                $spaBookingsStmt = $pdo->query(
                    'SELECT id, user_id, treatment_name, treatment_date, treatment_time, guests, status, notes, created_at
                     FROM spa_bookings
                     ORDER BY treatment_date DESC, treatment_time DESC, id DESC'
                );
                $allSpaBookings = $spaBookingsStmt->fetchAll();
                foreach ($allSpaBookings as $spaBooking) {
                    $spaBookingUserId = (int)($spaBooking['user_id'] ?? 0);
                    if (!isset($adminSpaBookingsByUser[$spaBookingUserId])) {
                        $adminSpaBookingsByUser[$spaBookingUserId] = [];
                    }
                    $adminSpaBookingsByUser[$spaBookingUserId][] = $spaBooking;
                }
            } catch (Throwable $exception) {
                $adminSpaBookingsByUser = [];
            }

            try {
                $reviewsStmt = $pdo->query(
                    'SELECT id, user_id, user_name, rating, title, body, created_at
                     FROM reviews
                     ORDER BY created_at DESC, id DESC'
                );
                $allReviews = $reviewsStmt->fetchAll();
                foreach ($allReviews as $review) {
                    $reviewUserId = (int)($review['user_id'] ?? 0);
                    if (!isset($adminReviewsByUser[$reviewUserId])) {
                        $adminReviewsByUser[$reviewUserId] = [];
                    }
                    $adminReviewsByUser[$reviewUserId][] = $review;
                }
            } catch (Throwable $exception) {
                $adminReviewsByUser = [];
            }

            $summary = [
                'room_bookings' => array_sum(array_map('count', $adminBookingsByUser)),
                'spa_bookings' => array_sum(array_map('count', $adminSpaBookingsByUser)),
                'reviews' => array_sum(array_map('count', $adminReviewsByUser)),
            ];
        } else {
            try {
                $accountStmt = $pdo->prepare('SELECT id, full_name, email, phone, created_at FROM users WHERE id = ? LIMIT 1');
                $accountStmt->execute([$userId]);
                $accountRecord = $accountStmt->fetch() ?: null;
            } catch (Throwable $exception) {
                $accountRecord = null;
            }

            try {
                require_once __DIR__ . '/../app/includes/loyalty.php';
                if ($userId !== null) {
                    $loyaltySnapshot = loyalty_get_user_snapshot($pdo, (int)$userId);
                }
            } catch (Throwable $exception) {
                $loyaltySnapshot = null;
            }

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
        }
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
                        <span class="section-eyebrow text-white"><?php echo $isAdmin ? 'Admin dashboard' : 'User dashboard'; ?></span>
                        <h1 class="dashboard-title">Welcome back, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="dashboard-subtitle mb-0">
                            <?php echo $isAdmin
                                ? 'Manage guest accounts and room bookings from one place.'
                                : 'Manage your room stays, spa reservations, and review activity from one place.'; ?>
                        </p>
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
                <?php if ($isAdmin): ?>
                    <section id="admin-users" class="content-card dashboard-panel reveal-up">
                        <div class="dashboard-panel-head">
                            <div>
                                <p class="dashboard-panel-label">Administration</p>
                                <h2 class="dashboard-panel-title">User accounts & bookings</h2>
                            </div>
                        </div>

                        <?php if (!$adminUsers): ?>
                            <div class="dashboard-empty">
                                No users found, or admin tables are unavailable.
                            </div>
                        <?php else: ?>
                            <div class="dashboard-list">
                                <?php foreach ($adminUsers as $user): ?>
                                    <?php
                                        $uId = (int)($user['id'] ?? 0);
                                        $uName = (string)($user['full_name'] ?? '');
                                        $uEmail = (string)($user['email'] ?? '');
                                        $uPhone = (string)($user['phone'] ?? '');
                                        $uIsAdmin = (int)($user['is_admin'] ?? 0) === 1;
                                        $userBookings = $adminBookingsByUser[$uId] ?? [];
                                        $userSpaBookings = $adminSpaBookingsByUser[$uId] ?? [];
                                        $userReviews = $adminReviewsByUser[$uId] ?? [];
                                        $totalBookings = count($userBookings) + count($userSpaBookings);
                                    ?>

                                    <article id="admin-user-<?php echo $uId; ?>" class="dashboard-entry">
                                        <div class="dashboard-entry-top">
                                            <div>
                                                <h3><?php echo htmlspecialchars($uName !== '' ? $uName : $uEmail, ENT_QUOTES, 'UTF-8'); ?></h3>
                                                <p class="dashboard-entry-meta mb-0"><?php echo htmlspecialchars($uEmail, ENT_QUOTES, 'UTF-8'); ?></p>
                                            </div>
                                            <?php if ($uIsAdmin): ?>
                                                <span class="badge rounded-pill text-bg-dark">Admin</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill text-bg-secondary">User</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="dashboard-entry-grid">
                                            <div><span class="dashboard-entry-label">User ID</span><strong><?php echo $uId; ?></strong></div>
                                            <div><span class="dashboard-entry-label">Phone</span><strong><?php echo htmlspecialchars($uPhone !== '' ? $uPhone : '—', ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                            <div><span class="dashboard-entry-label">Bookings</span><strong><?php echo $totalBookings; ?></strong></div>
                                            <div><span class="dashboard-entry-label">Created</span><strong><?php echo htmlspecialchars((string)($user['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                        </div>

                                        <div class="dashboard-entry-actions">
                                            <form action="dashboard.php#admin-users" method="POST" class="dashboard-inline-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('dashboard_action_form'), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="admin_update_user_phone">
                                                <input type="hidden" name="user_id" value="<?php echo $uId; ?>">
                                                <div class="input-group" style="max-width: 420px;">
                                                    <input class="form-control" type="text" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($uPhone, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <button type="submit" class="btn btn-gold btn-sm">Update phone</button>
                                                </div>
                                            </form>

                                            <a class="btn btn-gold btn-sm" href="admin_user_details.php?user_id=<?php echo $uId; ?>">View more details</a>

                                            <?php if (!$uIsAdmin): ?>
                                                <form action="dashboard.php#admin-users" method="POST" class="dashboard-inline-form">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('dashboard_action_form'), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="admin_delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $uId; ?>">
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Delete this user account? This will also remove spa bookings and may detach room bookings.');">Delete user</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>

                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                <section id="account-details" class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Account</p>
                            <h2 class="dashboard-panel-title">Your details</h2>
                        </div>
                    </div>

                    <?php if (!$accountRecord): ?>
                        <div class="dashboard-empty">
                            Account details are unavailable right now. Please ensure the users table exists in your schema.
                        </div>
                    <?php else: ?>
                        <?php
                            $accountName = (string)($accountRecord['full_name'] ?? '');
                            $accountEmail = (string)($accountRecord['email'] ?? '');
                            $accountPhone = (string)($accountRecord['phone'] ?? '');
                            $accountCreated = (string)($accountRecord['created_at'] ?? '');
                        ?>

                        <div class="dashboard-entry-grid mb-3">
                            <div><span class="dashboard-entry-label">Email</span><strong><?php echo htmlspecialchars($accountEmail, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div><span class="dashboard-entry-label">Phone</span><strong><?php echo htmlspecialchars($accountPhone !== '' ? $accountPhone : '—', ENT_QUOTES, 'UTF-8'); ?></strong></div>
                            <div><span class="dashboard-entry-label">Created</span><strong><?php echo htmlspecialchars($accountCreated, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                        </div>

                        <form action="dashboard.php#account-details" method="POST" class="mb-4" style="max-width: 560px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('dashboard_action_form'), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="update_account_details">

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="account_full_name">Full name</label>
                                    <input
                                        class="form-control"
                                        id="account_full_name"
                                        type="text"
                                        name="full_name"
                                        value="<?php echo htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8'); ?>"
                                        maxlength="100"
                                        required
                                    >
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="account_phone">Phone (optional)</label>
                                    <input
                                        class="form-control"
                                        id="account_phone"
                                        type="text"
                                        name="phone"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        value="<?php echo htmlspecialchars($accountPhone, ENT_QUOTES, 'UTF-8'); ?>"
                                        maxlength="50"
                                    >
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-gold btn-sm">Save details</button>
                            </div>
                        </form>

                        <form action="dashboard.php#account-details" method="POST" style="max-width: 560px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('dashboard_action_form'), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="change_password">

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="current_password">Current password</label>
                                    <input class="form-control" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="new_password">New password</label>
                                    <input class="form-control" id="new_password" type="password" name="new_password" minlength="8" autocomplete="new-password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="confirm_password">Confirm new password</label>
                                    <input class="form-control" id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-gold btn-sm">Update password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </section>

                <section class="content-card dashboard-panel reveal-up">
                    <div class="dashboard-panel-head">
                        <div>
                            <p class="dashboard-panel-label">Loyalty program</p>
                            <h2 class="dashboard-panel-title">Your loyalty status</h2>
                        </div>
                        <a class="btn btn-gold btn-sm" href="loyalty.php">View details</a>
                    </div>

                    <?php if (!$loyaltySnapshot): ?>
                        <div class="dashboard-empty">
                            Loyalty details are unavailable right now. Please ensure the loyalty tables exist in your schema.
                        </div>
                    <?php else: ?>
                        <?php
                        $totalSpent = (float)($loyaltySnapshot['total_spent'] ?? 0);
                        $remainingToNext = (float)($loyaltySnapshot['remaining_to_next'] ?? 0);
                        $tierName = (string)($loyaltySnapshot['tier_name'] ?? '');
                        $discountLabel = (string)($loyaltySnapshot['discount_label'] ?? '');
                        $nextTier = is_array($loyaltySnapshot['next_tier'] ?? null) ? $loyaltySnapshot['next_tier'] : null;
                        $nextTierName = $nextTier ? (string)($nextTier['tier_name'] ?? '') : '';
                        $nextTierMin = $nextTier ? (float)($nextTier['min_spending'] ?? 0) : 0.0;
                        $progressPercent = $nextTierMin > 0 ? min(100.0, max(0.0, ($totalSpent / $nextTierMin) * 100.0)) : 100.0;
                        ?>

                        <div class="dashboard-loyalty">
                            <div class="dashboard-loyalty-top">
                                <div class="dashboard-loyalty-tier">
                                    <span class="dashboard-entry-label">Current tier</span>
                                    <div class="dashboard-loyalty-tier-row">
                                        <strong class="dashboard-loyalty-tier-name"><?php echo htmlspecialchars($tierName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php if ($discountLabel !== ''): ?>
                                            <span class="dashboard-loyalty-pill" aria-label="Current discount">
                                                <?php echo htmlspecialchars($discountLabel, ENT_QUOTES, 'UTF-8'); ?> discount
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="dashboard-loyalty-highlights" aria-label="Loyalty summary">
                                    <div class="dashboard-loyalty-highlight">
                                        <span class="dashboard-entry-label">Total spent</span>
                                        <strong>$<?php echo number_format($totalSpent, 2); ?></strong>
                                    </div>
                                    <div class="dashboard-loyalty-highlight">
                                        <span class="dashboard-entry-label">Left for next tier</span>
                                        <strong><?php echo $nextTier ? ('$' . number_format($remainingToNext, 2)) : '—'; ?></strong>
                                    </div>
                                </div>
                            </div>

                            <?php if ($nextTier): ?>
                                <div class="dashboard-loyalty-progress" aria-label="Progress to next tier">
                                    <div class="dashboard-loyalty-progress-head">
                                        <span class="dashboard-loyalty-progress-label">
                                            Progress to <?php echo htmlspecialchars($nextTierName, ENT_QUOTES, 'UTF-8'); ?> tier
                                        </span>
                                        <span class="dashboard-loyalty-progress-meta">
                                            $<?php echo number_format($totalSpent, 2); ?> / $<?php echo number_format($nextTierMin, 2); ?>
                                        </span>
                                    </div>
                                    <div class="dashboard-loyalty-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int)round($progressPercent); ?>">
                                        <span class="dashboard-loyalty-progress-fill" style="width: <?php echo (float)$progressPercent; ?>%;"></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <p class="dashboard-loyalty-message"><?php echo htmlspecialchars((string)$loyaltySnapshot['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    <?php endif; ?>
                </section>

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
                        <a class="btn btn-gold btn-sm" href="room_bookings.php">Manage room bookings</a>
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
                                <a class="btn btn-gold btn-sm" href="room_bookings.php">View all bookings</a>
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
                        <a class="btn btn-gold btn-sm" href="spa_booking.php">Book a treatment</a>
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
                                                <button type="submit" class="btn btn-gold btn-sm" onclick="return confirm('Cancel this spa treatment?');">Cancel treatment</button>
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
                        <a class="btn btn-gold btn-sm" href="reviews.php">Open reviews page</a>
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
                <?php endif; ?>
            </div>
        </div>
    </section>

</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
