<?php
require_once __DIR__ . '/../app/includes/auth.php';

auth_require_login(
    'checkout.php',
    'Please sign in or create an account to confirm your room booking.'
);

require_once __DIR__ . '/rooms_catalog.php';

$pageStylesheets = ['assets/css/rooms_and_suites.css'];
$pageScripts = ['assets/js/checkout.js'];
$errors = [];
$databaseNotice = '';

$adjustmentId = (int)($_GET['adjustment_id'] ?? 0);
$adjustBookingId = (int)($_GET['adjust_booking_id'] ?? 0);
$adjustmentMode = $adjustmentId > 0 || $adjustBookingId > 0;
$adjustmentBooking = null;
$amountDue = 0.0;

try {
    require_once __DIR__ . '/../app/includes/db.php';
} catch (Throwable $exception) {
    $databaseNotice = 'Unable to connect to the booking database right now. Please try again shortly.';
}

$pending = $_SESSION['pending_booking'] ?? null;
if (!$adjustmentMode && !is_array($pending)) {
    $errors[] = 'Your booking selection is missing. Please choose a room and dates first.';
}

$room = null;
if (!$errors && !$adjustmentMode) {
    $roomId = (int)($pending['room_id'] ?? 0);
    $room = $roomId > 0 ? rooms_catalog_find($roomId) : null;
    if (!$room) {
        $errors[] = 'Selected room could not be found. Please try again.';
    }
}

if (!$errors && $adjustmentMode) {
    if ($databaseNotice !== '' || !isset($pdo)) {
        $errors[] = $databaseNotice !== '' ? $databaseNotice : 'Unable to connect to the booking database right now. Please try again shortly.';
    } else {
        require_once __DIR__ . '/../app/includes/booking_adjustments.php';
        booking_adjustments_ensure_table($pdo);

        if ($adjustmentId > 0) {
            $pendingAdjustment = booking_adjustments_get_pending_due($pdo, $adjustmentId, (int)(auth_user_id() ?? 0));
            if (!$pendingAdjustment) {
                $errors[] = 'That additional payment request is no longer available.';
            } else {
                $amountDue = (float)($pendingAdjustment['amount'] ?? 0);
                $adjustmentBooking = $pendingAdjustment;
            }
        } else {
            // Back-compat path (legacy link): booking id + amount in querystring.
            $stmt = $pdo->prepare('SELECT id, user_id, room_id, room_name, check_in, check_out, nights, room_rate, total_price, status FROM bookings WHERE id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$adjustBookingId, auth_user_id()]);
            $adjustmentBooking = $stmt->fetch() ?: null;
            if (!$adjustmentBooking) {
                $errors[] = 'That booking could not be found for your account.';
            }
        }
    }

    if ($adjustmentId <= 0) {
        $amountDueRaw = trim((string)($_GET['amount_due'] ?? ''));
        $amountDue = is_numeric($amountDueRaw) ? (float)$amountDueRaw : 0.0;
        if ($amountDue <= 0.009) {
            $errors[] = 'The additional payment amount is missing or invalid.';
        }
    }

    // Provide a minimal room object for the existing template.
    if (!$errors) {
        $room = [
            'name' => (string)($adjustmentBooking['room_name'] ?? 'Room booking'),
            'view' => '',
            'occupancy' => 0,
        ];
    }
}

$checkoutErrorReturnPath = $adjustmentMode
    ? 'dashboard.php'
    : ((!$pending || !$room) ? 'rooms_and_suites.php' : 'checkout.php');

$formData = [
    'full_name' => auth_user_display_name(),
    'email' => auth_user_email(),
    'phone' => '',
    'address1' => '',
    'city' => '',
    'postal' => '',
    'card_name' => auth_user_display_name(),
    'expiry' => '',
];

function checkout_digits_only(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function checkout_parse_expiry_end(string $value): ?DateTimeImmutable
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    $postedToken = $_POST['csrf_token'] ?? '';
    $honeypot = trim((string)($_POST['website'] ?? ''));

    foreach ($formData as $key => $defaultValue) {
        $formData[$key] = trim((string)($_POST[$key] ?? $defaultValue));
    }

    if (!csrf_validate('checkout_form', $postedToken)) {
        $errors[] = 'Your checkout session has expired. Please refresh the page and try again.';
    }

    if ($honeypot !== '') {
        $errors[] = 'Unable to submit your booking right now. Please try again.';
    }

    if ($formData['full_name'] === '') {
        $errors[] = 'Please enter your full name.';
    }
    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($formData['phone'] === '') {
        $errors[] = 'Please enter your phone number.';
    }
    if ($formData['address1'] === '') {
        $errors[] = 'Please enter your billing address.';
    }
    if ($formData['city'] === '') {
        $errors[] = 'Please enter your city.';
    }
    if ($formData['postal'] === '') {
        $errors[] = 'Please enter your postal code.';
    }
    if ($formData['card_name'] === '') {
        $errors[] = 'Please enter the name on card.';
    }

    $cardDigits = checkout_digits_only((string)($_POST['card_number'] ?? ''));
    if ($cardDigits === '') {
        $errors[] = 'Please enter a card number.';
    } elseif (strlen($cardDigits) !== 16) {
        $errors[] = 'Card number must be 16 digits.';
    }

    $expiryEnd = checkout_parse_expiry_end($formData['expiry']);
    if (!$expiryEnd) {
        $errors[] = 'Expiry must be in MM/YY format.';
    } elseif ($expiryEnd < new DateTimeImmutable('now')) {
        $errors[] = 'Expiry date must be in the future.';
    }

    $cvvDigits = checkout_digits_only((string)($_POST['cvv'] ?? ''));
    if ($cvvDigits === '') {
        $errors[] = 'Please enter card CVV.';
    } elseif (strlen($cvvDigits) !== 3) {
        $errors[] = 'CVV must be 3 digits.';
    }

    if ($databaseNotice !== '') {
        $errors[] = $databaseNotice;
    }

    if (!$errors && $adjustmentMode && isset($pdo) && $adjustmentBooking) {
        // Only mark the banner as resolved after the user submits checkout successfully.
        if ($adjustmentId > 0) {
            require_once __DIR__ . '/../app/includes/booking_adjustments.php';
            booking_adjustments_ensure_table($pdo);
            booking_adjustments_mark_paid($pdo, $adjustmentId, (int)(auth_user_id() ?? 0));
        }

        csrf_refresh('checkout_form');
        auth_flash_set('dashboard_notice', 'Payment of $' . number_format($amountDue, 2) . ' received. Thank you for confirming your updated room booking.');
        auth_redirect('dashboard.php#room-bookings');
    }

    if (!$errors && !$adjustmentMode && isset($pdo) && $pending && $room) {
        $stmt = $pdo->prepare(
            'INSERT INTO bookings (
                user_id,
                room_id,
                room_name,
                guest_name,
                guest_email,
                guest_phone,
                billing_address,
                billing_city,
                billing_postal,
                check_in,
                check_out,
                nights,
                room_rate,
                total_price,
                status
            ) VALUES (
                :user_id,
                :room_id,
                :room_name,
                :guest_name,
                :guest_email,
                :guest_phone,
                :billing_address,
                :billing_city,
                :billing_postal,
                :check_in,
                :check_out,
                :nights,
                :room_rate,
                :total_price,
                :status
            )'
        );

        $stmt->execute([
            ':user_id' => auth_user_id(),
            ':room_id' => (int)($pending['room_id'] ?? 0),
            ':room_name' => (string)($room['name'] ?? 'Room booking'),
            ':guest_name' => $formData['full_name'],
            ':guest_email' => strtolower($formData['email']),
            ':guest_phone' => $formData['phone'],
            ':billing_address' => $formData['address1'],
            ':billing_city' => $formData['city'],
            ':billing_postal' => $formData['postal'],
            ':check_in' => (string)($pending['check_in'] ?? ''),
            ':check_out' => (string)($pending['check_out'] ?? ''),
            ':nights' => (int)($pending['nights'] ?? 1),
            ':room_rate' => (float)($pending['rate'] ?? 0),
            ':total_price' => (float)($pending['discounted_total'] ?? ($pending['total'] ?? 0)),
            ':status' => 'Confirmed',
        ]);

        try {
            $userId = auth_user_id() ?? 0;
            $phone = trim((string)($formData['phone'] ?? ''));

            if ($userId > 0 && $phone !== '') {
                $updatePhoneStmt = $pdo->prepare(
                    "UPDATE users
                     SET phone = CASE WHEN phone IS NULL OR phone = '' THEN ? ELSE phone END
                     WHERE id = ?"
                );
                $updatePhoneStmt->execute([$phone, $userId]);
            }
        } catch (Throwable $exception) {
            // Best-effort: user phone may not exist in older schemas.
        }

        try {
            require_once __DIR__ . '/../app/includes/loyalty_helper.php';
            $userId = auth_user_id() ?? 0;
            if ($userId > 0) {
                loyalty_refresh_user($pdo, $userId);
            }
        } catch (Throwable $exception) {
            // Loyalty refresh is best-effort.
        }

        unset($_SESSION['pending_booking']);
        csrf_refresh('checkout_form');
        auth_flash_set('dashboard_notice', 'Your room booking has been confirmed.');
        auth_redirect('room_bookings.php');
    }
}

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="rooms-page">
    <section class="rooms-shell">
        <div class="container">
            <div class="content-card rooms-card reveal-up">
                <header class="rooms-header text-center">
                    <h1 class="rooms-title">Checkout</h1>
                    <p class="rooms-subtitle mb-0">Enter billing information to confirm your booking.</p>
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
                    <?php if ($checkoutErrorReturnPath !== 'checkout.php'): ?>
                        <div class="d-grid d-md-flex justify-content-md-center gap-2 mt-3">
                            <a class="btn btn-gold" href="<?php echo htmlspecialchars($checkoutErrorReturnPath, ENT_QUOTES, 'UTF-8'); ?>">Back to Rooms</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php
                        if ($adjustmentMode && is_array($adjustmentBooking)) {
                            $checkIn = (string)($adjustmentBooking['check_in'] ?? '');
                            $checkOut = (string)($adjustmentBooking['check_out'] ?? '');
                            $nights = (int)($adjustmentBooking['nights'] ?? 0);
                            $rate = (float)($adjustmentBooking['room_rate'] ?? 0);
                            $total = (float)$amountDue;
                            $discountRate = 0.0;
                            $discountAmount = 0.0;
                            $discountedTotal = (float)$amountDue;
                            $tierName = '';
                        } else {
                            $checkIn = (string)($pending['check_in'] ?? '');
                            $checkOut = (string)($pending['check_out'] ?? '');
                            $nights = (int)($pending['nights'] ?? 0);
                            $rate = (float)($pending['rate'] ?? 0);
                            $total = (float)($pending['total'] ?? 0);
                            $discountRate = (float)($pending['discount_rate'] ?? 0);
                            $discountAmount = (float)($pending['discount_amount'] ?? 0);
                            $discountedTotal = (float)($pending['discounted_total'] ?? $total);
                            $tierName = trim((string)($pending['tier_name'] ?? ''));
                        }
                    ?>

                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="content-card p-4 p-md-5 h-100">
                                <h2 class="h4 mb-3"><?php echo $adjustmentMode ? 'Additional payment' : 'Order summary'; ?></h2>
                                <p class="mb-1"><strong><?php echo htmlspecialchars((string)($room['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
                                <?php if (!$adjustmentMode): ?>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars((string)($room['view'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> view &bull; <?php echo (int)($room['occupancy'] ?? 0); ?> pax</p>
                                <?php else: ?>
                                    <p class="text-muted mb-3">Payment required to confirm an admin-updated booking.</p>
                                <?php endif; ?>
                                <div class="d-grid gap-2">
                                    <div class="d-flex justify-content-between"><span class="text-muted">Check-in</span><span><?php echo htmlspecialchars($checkIn, ENT_QUOTES, 'UTF-8'); ?></span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Check-out</span><span><?php echo htmlspecialchars($checkOut, ENT_QUOTES, 'UTF-8'); ?></span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Nights</span><span><?php echo $nights; ?></span></div>
                                    <hr class="my-2">
                                    <?php if (!$adjustmentMode): ?>
                                        <div class="d-flex justify-content-between"><span class="text-muted">Rate / night</span><span>$<?php echo number_format($rate, 2); ?></span></div>
                                    <?php endif; ?>
                                    <?php if (!$adjustmentMode && $discountRate > 0.000001 && $discountAmount > 0.009): ?>
                                        <div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><span>$<?php echo number_format($total, 2); ?></span></div>
                                        <div class="d-flex justify-content-between"><span class="text-muted">Loyalty discount<?php echo $tierName !== '' ? ' (' . htmlspecialchars($tierName, ENT_QUOTES, 'UTF-8') . ')' : ''; ?></span><span>-$<?php echo number_format($discountAmount, 2); ?></span></div>
                                        <div class="d-flex justify-content-between"><span class="fw-bold">Total</span><span class="fw-bold">$<?php echo number_format($discountedTotal, 2); ?></span></div>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between"><span class="fw-bold"><?php echo $adjustmentMode ? 'Amount due' : 'Total'; ?></span><span class="fw-bold">$<?php echo number_format($adjustmentMode ? $discountedTotal : $total, 2); ?></span></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <?php if (!$adjustmentMode): ?>
                                        <a class="btn btn-outline-secondary" href="rooms_and_suites.php">Change selection</a>
                                    <?php else: ?>
                                        <a class="btn btn-outline-secondary" href="dashboard.php#room-bookings">Back to dashboard</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="content-card p-4 p-md-5">
                                <h2 class="h4 mb-3">Billing information</h2>

                                <form action="<?php
                                    if ($adjustmentMode) {
                                        if ($adjustmentId > 0) {
                                            echo 'checkout.php?adjustment_id=' . (int)$adjustmentId;
                                        } else {
                                            echo 'checkout.php?adjust_booking_id=' . (int)$adjustBookingId . '&amount_due=' . rawurlencode(number_format((float)$amountDue, 2, '.', ''));
                                        }
                                    } else {
                                        echo 'checkout.php';
                                    }
                                ?>" method="POST" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('checkout_form'), ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="form-honeypot" aria-hidden="true">
                                        <label for="website" class="form-label">Website</label>
                                        <input type="text" class="form-control" id="website" name="website" tabindex="-1" autocomplete="off">
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="full_name" class="form-label">Full name</label>
                                            <input class="form-control" id="full_name" name="full_name" type="text" autocomplete="name" value="<?php echo htmlspecialchars($formData['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email</label>
                                            <input class="form-control" id="email" name="email" type="email" autocomplete="email" value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input class="form-control" id="phone" name="phone" type="tel" autocomplete="tel" value="<?php echo htmlspecialchars($formData['phone'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="address1" class="form-label">Billing address</label>
                                            <input class="form-control" id="address1" name="address1" type="text" autocomplete="address-line1" value="<?php echo htmlspecialchars($formData['address1'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="city" class="form-label">City</label>
                                            <input class="form-control" id="city" name="city" type="text" autocomplete="address-level2" value="<?php echo htmlspecialchars($formData['city'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="postal" class="form-label">Postal code</label>
                                            <input class="form-control" id="postal" name="postal" type="text" autocomplete="postal-code" value="<?php echo htmlspecialchars($formData['postal'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>

                                        <div class="col-12"><hr class="my-2"></div>

                                        <div class="col-12">
                                            <label for="card_name" class="form-label">Name on card</label>
                                            <input class="form-control" id="card_name" name="card_name" type="text" autocomplete="cc-name" value="<?php echo htmlspecialchars($formData['card_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="card_number" class="form-label">Card number</label>
                                            <input class="form-control" id="card_number" name="card_number" type="text" inputmode="numeric" autocomplete="cc-number" value="" placeholder="1234 5678 9012 3456" maxlength="19" required>
                                            <div class="invalid-feedback" id="card_number_feedback">Card number must be 16 digits.</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="expiry" class="form-label">Expiry</label>
                                            <input class="form-control" id="expiry" name="expiry" type="text" inputmode="numeric" autocomplete="cc-exp" value="<?php echo htmlspecialchars($formData['expiry'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="MM/YY" maxlength="5" required>
                                            <div class="invalid-feedback" id="expiry_feedback">Enter a valid future expiry (MM/YY).</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cvv" class="form-label">CVV</label>
                                            <input class="form-control" id="cvv" name="cvv" type="password" inputmode="numeric" autocomplete="cc-csc" value="" placeholder="123" maxlength="3" required>
                                            <div class="invalid-feedback" id="cvv_feedback">CVV must be 3 digits.</div>
                                        </div>
                                    </div>

                                    <div class="d-grid mt-4">
                                        <button type="submit" class="btn btn-gold"><?php echo $adjustmentMode ? ('Pay $' . number_format((float)$amountDue, 2)) : 'Pay &amp; confirm booking'; ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
