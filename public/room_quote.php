<?php
session_start();

require_once __DIR__ . '/rooms_catalog.php';

$pageStylesheets = ['assets/css/rooms_and_suites.css'];
$errors = [];

$roomId = (int)($_GET['room_id'] ?? 0);
$checkIn = trim((string)($_GET['check_in'] ?? ''));
$checkOut = trim((string)($_GET['check_out'] ?? ''));
$room = $roomId > 0 ? rooms_catalog_find($roomId) : null;

if (!$room) {
	$errors[] = 'Please select a valid room.';
}

function room_quote_parse_date(string $value): ?DateTimeImmutable
{
	if ($value === '') {
		return null;
	}

	$date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
	if (!$date) {
		return null;
	}

	return $date->format('Y-m-d') === $value ? $date : null;
}

$dtIn = room_quote_parse_date($checkIn);
$dtOut = room_quote_parse_date($checkOut);

if (!$dtIn || !$dtOut) {
	$errors[] = 'Please select a valid check-in and check-out date.';
}

$nights = 0;
$total = 0.0;

if (!$errors) {
	if ($dtOut <= $dtIn) {
		$errors[] = 'Check-out date must be after check-in date.';
	} else {
		$nights = max(0, (int)$dtIn->diff($dtOut)->days);
		$rate = (float)($room['price_per_night'] ?? 0);
		$total = $rate * $nights;

		$_SESSION['pending_booking'] = [
			'room_id' => (int)($room['id'] ?? 0),
			'check_in' => $dtIn->format('Y-m-d'),
			'check_out' => $dtOut->format('Y-m-d'),
			'nights' => $nights,
			'rate' => $rate,
			'total' => $total,
		];
	}
}

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="rooms-page">
	<section class="rooms-shell">
		<div class="container">
			<div class="content-card rooms-card reveal-up">
				<header class="rooms-header text-center">
					<h1 class="rooms-title">Your Stay Total</h1>
					<p class="rooms-subtitle mb-0">Review your selection before checkout.</p>
				</header>

				<?php if ($errors): ?>
					<div class="alert alert-danger" role="alert">
						<ul class="mb-0">
							<?php foreach ($errors as $message): ?>
								<li><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<div class="d-grid d-md-flex justify-content-md-center gap-2 mt-3">
						<a class="btn btn-gold" href="rooms_and_suites.php">Back to Rooms</a>
					</div>
				<?php else: ?>
					<div class="row g-4 align-items-stretch">
						<div class="col-lg-7">
							<article class="content-card room-card h-100">
								<div class="room-media">
									<?php $cover = rooms_catalog_primary_image($room); ?>
									<img class="room-image" src="<?php echo htmlspecialchars($cover, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string)($room['name'] ?? 'Room'), ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
								</div>
								<div class="room-body">
									<h2 class="room-name mb-0"><?php echo htmlspecialchars((string)($room['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>
									<p class="room-desc mb-0"><?php echo htmlspecialchars((string)($room['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
									<div class="room-badges">
										<span class="badge rounded-pill text-bg-light"><?php echo (int)($room['occupancy'] ?? 0); ?> pax</span>
										<span class="badge rounded-pill text-bg-light"><?php echo htmlspecialchars((string)($room['view'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> view</span>
										<?php if (!empty($room['accessible'])): ?>
											<span class="badge rounded-pill text-bg-light">Accessible</span>
										<?php endif; ?>
									</div>
								</div>
							</article>
						</div>
						<div class="col-lg-5">
							<div class="content-card h-100 p-4 p-md-5">
								<h2 class="h4 mb-3">Price breakdown</h2>
								<div class="d-grid gap-2">
									<div class="d-flex justify-content-between">
										<span class="text-muted">Check-in</span>
										<span><?php echo htmlspecialchars($dtIn->format('M j, Y'), ENT_QUOTES, 'UTF-8'); ?></span>
									</div>
									<div class="d-flex justify-content-between">
										<span class="text-muted">Check-out</span>
										<span><?php echo htmlspecialchars($dtOut->format('M j, Y'), ENT_QUOTES, 'UTF-8'); ?></span>
									</div>
									<div class="d-flex justify-content-between">
										<span class="text-muted">Nights</span>
										<span><?php echo (int)$nights; ?></span>
									</div>
									<hr class="my-2">
									<div class="d-flex justify-content-between">
										<span class="text-muted">Rate / night</span>
										<span>$<?php echo number_format((float)($room['price_per_night'] ?? 0), 2); ?></span>
									</div>
									<div class="d-flex justify-content-between">
										<span class="fw-bold">Total</span>
										<span class="fw-bold">$<?php echo number_format($total, 2); ?></span>
									</div>
								</div>

								<div class="d-grid gap-2 mt-4">
									<a class="btn btn-gold" href="checkout.php">Proceed to checkout</a>
									<a class="btn btn-outline-secondary" href="rooms_and_suites.php">Change selection</a>
								</div>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
