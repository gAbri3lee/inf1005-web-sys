<?php
session_start();

if (!isset($_SESSION['user_id'])) {
	header('Location: login.php?next=' . rawurlencode('checkout.php'));
	exit();
}

require_once __DIR__ . '/../app/includes/rooms_repository.php';

$pageStylesheets = ['assets/css/rooms_and_suites.css'];
$pageScripts = ['assets/js/checkout.js'];

$errors = [];
$successMessage = '';

$pending = $_SESSION['pending_booking'] ?? null;
if (!is_array($pending)) {
	$errors[] = 'Your booking selection is missing. Please choose a room and dates first.';
}

// Keep any previously entered checkout form data if the user is bounced around.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && empty($_SESSION['checkout_form_data']) && isset($_POST) && is_array($_POST)) {
	// No-op: placeholder to make intent clear (form data is saved only on POST validation errors).
}

$room = null;
if (!$errors) {
	$roomId = (int)($pending['room_id'] ?? 0);
	$room = $roomId > 0 ? rooms_repo_find($roomId) : null;
	if (!$room) {
		$errors[] = 'Selected room could not be found. Please try again.';
	}
}

$formData = [
	'full_name' => '',
	'email' => '',
	'phone' => '',
	'address1' => '',
	'city' => '',
	'postal' => '',
	'card_name' => '',
	'card_number' => '',
	'expiry' => '',
	'cvv' => '',
];

if (isset($_SESSION['checkout_form_data']) && is_array($_SESSION['checkout_form_data'])) {
	$stored = $_SESSION['checkout_form_data'];
	foreach ($formData as $k => $_) {
		if (array_key_exists($k, $stored)) {
			$formData[$k] = trim((string)$stored[$k]);
		}
	}
}

function digits_only(string $value): string
{
	return preg_replace('/\D+/', '', $value) ?? '';
}

function parse_expiry_end(string $value): ?DateTimeImmutable
{
	$value = trim($value);
	if ($value === '') return null;

	if (!preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2}|\d{4})$/', $value, $m)) {
		return null;
	}

	$month = (int)$m[1];
	$yearRaw = $m[2];
	$year = (int)$yearRaw;
	if (strlen($yearRaw) === 2) {
		$year += 2000;
	}

	$start = DateTimeImmutable::createFromFormat('Y-n-j', $year . '-' . $month . '-1');
	if (!$start) return null;

	return $start->modify('last day of this month')->setTime(23, 59, 59);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
	foreach ($formData as $k => $_) {
		$formData[$k] = trim((string)($_POST[$k] ?? ''));
	}

	if ($formData['full_name'] === '') $errors[] = 'Please enter your full name.';
	if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
	if ($formData['phone'] === '') $errors[] = 'Please enter your phone number.';
	if ($formData['address1'] === '') $errors[] = 'Please enter your billing address.';
	if ($formData['city'] === '') $errors[] = 'Please enter your city.';
	if ($formData['postal'] === '') $errors[] = 'Please enter your postal code.';
	if ($formData['card_name'] === '') $errors[] = 'Please enter the name on card.';

	$cardDigits = digits_only($formData['card_number']);
	if ($cardDigits === '') {
		$errors[] = 'Please enter a card number.';
	} elseif (strlen($cardDigits) !== 16) {
		$errors[] = 'Card number must be 16 digits.';
	}

	$expiryEnd = parse_expiry_end($formData['expiry']);
	if (!$expiryEnd) {
		$errors[] = 'Expiry must be in MM/YY format.';
	} else {
		$now = new DateTimeImmutable('now');
		if ($expiryEnd < $now) {
			$errors[] = 'Expiry date must be in the future.';
		}
	}

	$cvvDigits = digits_only($formData['cvv']);
	if ($cvvDigits === '') {
		$errors[] = 'Please enter card CVV.';
	} elseif (strlen($cvvDigits) !== 3) {
		$errors[] = 'CVV must be 3 digits.';
	}

	if (!$errors) {
		$successMessage = 'Checkout complete. Your booking has been recorded.';
		unset($_SESSION['pending_booking']);
		unset($_SESSION['checkout_form_data']);
		$formData = array_map(static fn() => '', $formData);
	} else {
		$_SESSION['checkout_form_data'] = $formData;
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

				<?php if ($errors): ?>
					<div class="alert alert-danger" role="alert">
						<ul class="mb-0">
							<?php foreach ($errors as $msg): ?>
								<li><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="d-grid d-md-flex justify-content-md-center gap-2 mt-3">
						<a class="btn btn-gold" href="checkout.php">Back to Checkout</a>
					</div>
				<?php else: ?>
					<?php if ($successMessage): ?>
						<div class="alert alert-success" role="alert">
							<?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
						</div>
						<div class="d-grid d-md-flex justify-content-md-center gap-2 mt-3">
							<a class="btn btn-gold" href="rooms_and_suites.php">Browse more rooms</a>
						</div>
					<?php else: ?>
						<?php
							$checkIn = (string)($pending['check_in'] ?? '');
							$checkOut = (string)($pending['check_out'] ?? '');
							$nights = (int)($pending['nights'] ?? 0);
							$rate = (float)($pending['rate'] ?? 0);
							$total = (float)($pending['total'] ?? 0);
						?>

						<div class="row g-4">
							<div class="col-lg-5">
								<div class="content-card p-4 p-md-5 h-100">
									<h2 class="h4 mb-3">Order summary</h2>
									<p class="mb-1"><strong><?php echo htmlspecialchars((string)($room['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></p>
									<p class="text-muted mb-3"><?php echo htmlspecialchars((string)($room['view'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> view • <?php echo (int)($room['occupancy'] ?? 0); ?> pax</p>
									<div class="d-grid gap-2">
										<div class="d-flex justify-content-between"><span class="text-muted">Check-in</span><span><?php echo htmlspecialchars($checkIn, ENT_QUOTES, 'UTF-8'); ?></span></div>
										<div class="d-flex justify-content-between"><span class="text-muted">Check-out</span><span><?php echo htmlspecialchars($checkOut, ENT_QUOTES, 'UTF-8'); ?></span></div>
										<div class="d-flex justify-content-between"><span class="text-muted">Nights</span><span><?php echo (int)$nights; ?></span></div>
										<hr class="my-2">
										<div class="d-flex justify-content-between"><span class="text-muted">Rate / night</span><span>$<?php echo number_format($rate, 2); ?></span></div>
										<div class="d-flex justify-content-between"><span class="fw-bold">Total</span><span class="fw-bold">$<?php echo number_format($total, 2); ?></span></div>
									</div>

									<div class="d-grid gap-2 mt-4">
										<a class="btn btn-outline-secondary" href="rooms_and_suites.php">Change selection</a>
									</div>
								</div>
							</div>

							<div class="col-lg-7">
								<div class="content-card p-4 p-md-5">
									<h2 class="h4 mb-3">Billing information</h2>

									<form action="checkout.php" method="POST" novalidate>
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
												<input class="form-control" id="card_number" name="card_number" type="text" inputmode="numeric" autocomplete="cc-number" value="<?php echo htmlspecialchars($formData['card_number'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="1234 5678 9012 3456" maxlength="19" required>
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
											<button type="submit" class="btn btn-gold">Pay &amp; confirm booking</button>
										</div>
									</form>
								</div>
							</div>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
	</section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
