<?php
session_start();

require_once __DIR__ . '/../app/includes/reviews_repository.php';

$pageSize = 12;
$errors = [];
$successMessage = '';

$categoryOptions = [
	'food' => 'Food',
	'room' => 'Room',
	'views' => 'Views',
	'service' => 'Service',
	'amenities' => 'Amenities',
	'cleanliness' => 'Cleanliness',
];

if (!isset($_SESSION['reviews_form_token'])) {
	$_SESSION['reviews_form_token'] = bin2hex(random_bytes(32));
}

function normalize_rating(mixed $value): int
{
	$rating = (int)$value;
	if ($rating < 1) return 1;
	if ($rating > 5) return 5;
	return $rating;
}

function format_review_date(int $timestamp): string
{
	return date('M j, Y', $timestamp);
}

function render_stars(int $rating): string
{
	$rating = normalize_rating($rating);
	$out = '<span class="review-stars" aria-label="' . $rating . ' out of 5 stars">';
	for ($i = 1; $i <= 5; $i++) {
		$class = $i <= $rating ? 'star filled' : 'star';
		$out .= '<span class="' . $class . '" aria-hidden="true">★</span>';
	}
	$out .= '</span>';
	return $out;
}

function render_average_stars(?float $averageRating): string
{
	if ($averageRating === null) {
		return '';
	}
	if ($averageRating >= 5.0) {
		$display = 5.0;
	} elseif ($averageRating >= 4.0) {
		$display = 4.5;
	} else {
		$display = round($averageRating * 2) / 2;
		if ($display < 0) $display = 0;
		if ($display > 5) $display = 5;
	}

	$out = '<span class="review-stars" aria-label="' . number_format($display, 1) . ' out of 5 stars">';
	for ($i = 1; $i <= 5; $i++) {
		$state = 'star';
		if ($display >= $i) {
			$state = 'star filled';
		} elseif ($display >= ($i - 0.5)) {
			$state = 'star half';
		}
		$out .= '<span class="' . $state . '" aria-hidden="true">★</span>';
	}
	$out .= '</span>';
	return $out;
}

function sanitize_categories(array $submitted, array $allowed): array
{
	$categories = [];
	foreach ($submitted as $value) {
		$key = strtolower(trim((string)$value));
		if ($key !== '' && array_key_exists($key, $allowed)) {
			$categories[] = $key;
		}
	}
	$categories = array_values(array_unique($categories));
	sort($categories);
	return $categories;
}

function safe_uploaded_image(string $inputName, string $targetDir, array &$errors): ?string
{
	if (!isset($_FILES[$inputName]) || !is_array($_FILES[$inputName])) {
		return null;
	}

	$file = $_FILES[$inputName];
	if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
		return null;
	}

	if ($file['error'] !== UPLOAD_ERR_OK) {
		$errors[] = 'Image upload failed. Please try again.';
		return null;
	}

	$maxBytes = 2 * 1024 * 1024;
	if (!isset($file['size']) || (int)$file['size'] > $maxBytes) {
		$errors[] = 'Please upload an image smaller than 2MB.';
		return null;
	}

	$tmpPath = (string)($file['tmp_name'] ?? '');
	if ($tmpPath === '' || !is_file($tmpPath)) {
		$errors[] = 'Image upload failed. Please try again.';
		return null;
	}

	$imgInfo = @getimagesize($tmpPath);
	if ($imgInfo === false) {
		$errors[] = 'Please upload a valid image file.';
		return null;
	}

	$mime = (string)($imgInfo['mime'] ?? '');
	$allowedMimes = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/webp' => 'webp',
	];
	if (!array_key_exists($mime, $allowedMimes)) {
		$errors[] = 'Only JPG, PNG, or WEBP images are allowed.';
		return null;
	}

	if (!is_dir($targetDir)) {
		@mkdir($targetDir, 0775, true);
	}
	if (!is_dir($targetDir)) {
		$errors[] = 'Server is unable to store uploaded images right now.';
		return null;
	}

	$ext = $allowedMimes[$mime];
	$filename = 'review_' . bin2hex(random_bytes(10)) . '.' . $ext;
	$destination = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

	if (!@move_uploaded_file($tmpPath, $destination)) {
		$errors[] = 'Unable to save the uploaded image. Please try again.';
		return null;
	}

	return 'uploads/reviews/' . $filename;
}

$isLoggedIn = isset($_SESSION['user_id']);

reviews_repo_init();

$activeCategory = strtolower(trim((string)($_GET['category'] ?? '')));
if ($activeCategory !== '' && !array_key_exists($activeCategory, $categoryOptions)) {
	$activeCategory = '';
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!$isLoggedIn) {
		header('Location: login.php?next=reviews.php');
		exit();
	}

	$postedToken = $_POST['csrf_token'] ?? '';
	$honeypot = trim($_POST['website'] ?? '');

	if (!hash_equals($_SESSION['reviews_form_token'], $postedToken)) {
		$errors[] = 'Your session has expired. Please refresh the page and try again.';
	}

	if ($honeypot !== '') {
		$errors[] = 'Unable to submit your review. Please try again.';
	}

	$rating = normalize_rating($_POST['rating'] ?? 5);
	$title = trim((string)($_POST['title'] ?? ''));
	$body = trim((string)($_POST['body'] ?? ''));
	$categories = sanitize_categories((array)($_POST['categories'] ?? []), $categoryOptions);
	$imagePath = safe_uploaded_image('review_image', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reviews', $errors);

	if ($body === '' || mb_strlen($body) < 20) {
		$errors[] = 'Please write a review of at least 20 characters.';
	}

	if (!$categories) {
		$errors[] = 'Please choose at least one category.';
	}

	if (!$errors) {
		$name = trim((string)($_SESSION['full_name'] ?? ''));
		if ($name === '') {
			$name = 'Guest';
		}

		reviews_repo_add([
			'user_id' => (int)($_SESSION['user_id'] ?? 0),
			'user_name' => $name,
			'rating' => $rating,
			'title' => $title === '' ? 'Guest review' : $title,
			'body' => $body,
			'categories' => $categories,
			'image_path' => $imagePath,
			'created_at' => time(),
		]);

		$_SESSION['reviews_form_token'] = bin2hex(random_bytes(32));
		header('Location: reviews.php?submitted=1');
		exit();
	}
}

$requestedPage = (int)($_GET['page'] ?? 1);
$list = reviews_repo_list([
	'category' => '',
	'page' => $requestedPage,
	'pageSize' => $pageSize,
]);

$pagedReviews = $list['items'];
$totalReviews = (int)$list['total'];
$averageRating = $list['average'];
$page = (int)$list['page'];
$totalPages = (int)$list['totalPages'];

if (isset($_GET['submitted']) && $_GET['submitted'] === '1') {
	$successMessage = 'Thanks! Your review is saved for this session only. Publishing to the database will be available once it is set up.';
}

$pageStylesheets = ['assets/css/reviews.css'];
$pageScripts = ['assets/js/reviews.js'];

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="reviews-page">
	<section class="reviews-shell">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-12 col-xl-10">
					<div class="content-card reviews-card reveal-up">
						<header class="reviews-header text-center">
							<span class="section-eyebrow">Guest reviews</span>
							<h1 class="reviews-title">What guests are saying</h1>
							<div class="reviews-stats">
								<div class="reviews-stat">
									<span class="reviews-stat-label">Total reviews</span>
									<span class="reviews-stat-value"><?php echo $totalReviews; ?></span>
								</div>
								<div class="reviews-stat">
									<span class="reviews-stat-label">Average rating</span>
									<span class="reviews-stat-value">
										<?php if ($averageRating === null): ?>
											—
										<?php else: ?>
											<?php echo number_format($averageRating, 1); ?> / 5
										<?php endif; ?>
									</span>
									<?php if ($averageRating !== null): ?>
										<div class="reviews-stat-stars">
											<?php echo render_average_stars($averageRating); ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</header>

						<?php if ($successMessage): ?>
							<div class="alert alert-success reveal-up" role="alert">
								<?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
							</div>
						<?php endif; ?>

							<div class="row g-4 align-items-start" id="reviews-browse" data-initial-category="<?php echo htmlspecialchars($activeCategory, ENT_QUOTES, 'UTF-8'); ?>">
							<div class="col-lg-7 col-xl-8">
								<div class="reviews-filter reveal-up">
									<div class="reviews-filter-label">Filter by category</div>
									<div class="reviews-filter-chips">
												<button type="button" class="btn btn-sm js-review-filter <?php echo $activeCategory === '' ? 'btn-gold' : 'btn-outline-secondary'; ?>" data-category="" aria-pressed="<?php echo $activeCategory === '' ? 'true' : 'false'; ?>">All</button>
										<?php foreach ($categoryOptions as $key => $label): ?>
													<button type="button" class="btn btn-sm js-review-filter <?php echo $activeCategory === $key ? 'btn-gold' : 'btn-outline-secondary'; ?>" data-category="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" aria-pressed="<?php echo $activeCategory === $key ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></button>
										<?php endforeach; ?>
									</div>
								</div>

								<div class="row g-4">
									<?php if (!$pagedReviews): ?>
										<div class="col-12">
											<div class="alert alert-info mb-0 reveal-up" role="alert">
												No reviews yet. Be the first to leave one.
											</div>
										</div>
									<?php else: ?>
										<?php foreach ($pagedReviews as $review): ?>
											<?php
												$cats = $review['categories'] ?? [];
												$cats = is_array($cats) ? array_values($cats) : [];
												$cats = sanitize_categories($cats, $categoryOptions);
												$dataCats = htmlspecialchars(implode(',', $cats), ENT_QUOTES, 'UTF-8');
											?>
											<div class="col-12 col-md-6 js-review-item" data-categories="<?php echo $dataCats; ?>">
												<article class="content-card review-card reveal-up">
													<div class="review-head">
														<div>
															<p class="review-name"><?php echo htmlspecialchars((string)($review['user_name'] ?? $review['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
															<div class="review-meta"><?php echo htmlspecialchars(format_review_date((int)($review['created_at'] ?? time())), ENT_QUOTES, 'UTF-8'); ?></div>
														</div>
														<?php echo render_stars((int)($review['rating'] ?? 0)); ?>
													</div>

													<div class="review-title"><?php echo htmlspecialchars((string)($review['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
													<p class="review-body"><?php echo htmlspecialchars((string)($review['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>

													<?php
														$cats = $review['categories'] ?? [];
														$img = (string)($review['image_path'] ?? $review['image'] ?? '');
													?>

													<?php if (is_array($cats) && $cats): ?>
														<div class="review-categories">
															<?php foreach ($cats as $ck): ?>
																<?php if (array_key_exists($ck, $categoryOptions)): ?>
																	<span class="review-category"><?php echo htmlspecialchars($categoryOptions[$ck], ENT_QUOTES, 'UTF-8'); ?></span>
																<?php endif; ?>
															<?php endforeach; ?>
														</div>
													<?php endif; ?>

													<?php if ($img !== ''): ?>
														<div class="review-image-wrap">
															<img class="review-image" src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="Review image" loading="lazy">
														</div>
													<?php endif; ?>
										</article>
											</div>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>

								<?php if ($totalPages > 1): ?>
									<nav class="mt-5 reveal-up" aria-label="Reviews pagination">
										<ul class="pagination justify-content-center mb-0">
											<li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
												<a class="page-link" href="reviews.php?page=<?php echo max(1, $page - 1); ?>" aria-label="Previous">
													<span aria-hidden="true">&laquo;</span>
												</a>
											</li>

											<?php for ($p = 1; $p <= $totalPages; $p++): ?>
												<li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
													<a class="page-link" href="reviews.php?page=<?php echo $p; ?>"><?php echo $p; ?></a>
												</li>
											<?php endfor; ?>

											<li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
												<a class="page-link" href="reviews.php?page=<?php echo min($totalPages, $page + 1); ?>" aria-label="Next">
													<span aria-hidden="true">&raquo;</span>
												</a>
											</li>
										</ul>
									</nav>
								<?php endif; ?>
							</div>

							<div class="col-lg-5 col-xl-4">
								<div class="content-card reviews-form-card reveal-up">
									<h2 class="h3 mb-2">Write a review</h2>
									<p class="text-muted mb-4">Share your experience to help other guests plan their stay.</p>

									<?php if (!$isLoggedIn): ?>
										<div class="alert alert-warning" role="alert">
											You must be logged in to write a review.
										</div>
										<div class="d-grid gap-2">
											<a class="btn btn-gold" href="login.php?next=reviews.php">Login</a>
											<a class="btn btn-outline-light btn-nav-action" href="register.php">Sign up</a>
										</div>
									<?php else: ?>
										<?php if ($errors): ?>
											<div class="alert alert-danger" role="alert">
												<ul class="mb-0">
													<?php foreach ($errors as $message): ?>
														<li><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></li>
													<?php endforeach; ?>
												</ul>
											</div>
										<?php endif; ?>

										<form action="reviews.php" method="POST" enctype="multipart/form-data" novalidate>
											<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['reviews_form_token'], ENT_QUOTES, 'UTF-8'); ?>">
											<div class="reviews-honeypot" aria-hidden="true">
												<label for="website" class="form-label">Website</label>
												<input type="text" class="form-control" id="website" name="website" tabindex="-1" autocomplete="off">
											</div>

											<div class="mb-3">
												<label class="form-label">Categories</label>
												<div class="reviews-category-grid">
													<?php foreach ($categoryOptions as $key => $label): ?>
														<div class="form-check">
															<input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" id="cat_<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" name="categories[]">
															<label class="form-check-label" for="cat_<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></label>
														</div>
													<?php endforeach; ?>
												</div>
											</div>

											<div class="mb-3">
												<label for="rating" class="form-label">Rating</label>
												<select class="form-select" id="rating" name="rating" required>
													<option value="5">5 - Excellent</option>
													<option value="4">4 - Very good</option>
													<option value="3">3 - Good</option>
													<option value="2">2 - Fair</option>
													<option value="1">1 - Poor</option>
												</select>
											</div>

											<div class="mb-3">
												<label for="title" class="form-label">Title (optional)</label>
												<input type="text" class="form-control" id="title" name="title" placeholder="A short summary">
											</div>

											<div class="mb-3">
												<label for="body" class="form-label">Your review</label>
												<textarea class="form-control" id="body" name="body" rows="5" placeholder="Tell us what you enjoyed and what we can improve" required></textarea>
											</div>

											<div class="mb-3">
												<label for="review_image" class="form-label">Add an image (optional)</label>
												<input class="form-control" type="file" id="review_image" name="review_image" accept="image/png,image/jpeg,image/webp">
												<div class="form-text">JPG, PNG, or WEBP. Max 2MB.</div>
											</div>

											<button type="submit" class="btn btn-gold w-100">Publish review</button>
										</form>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>

