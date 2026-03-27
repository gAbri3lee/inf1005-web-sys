<?php
session_start();

$pageSize = 12;
$errors = [];
$successMessage = '';
$databaseNotice = '';
$pdo = null;

try {
	require_once __DIR__ . '/../app/includes/db.php';
} catch (Throwable $exception) {
	$databaseNotice = 'Unable to connect to the reviews database right now. Check app/config/database.php and then run database/schema.sql in MySQL Workbench.';
}

const REVIEW_CATEGORY_OPTIONS = [
	'food' => 'Food',
	'room' => 'Room',
	'views' => 'Views',
	'service' => 'Service',
	'amenities' => 'Amenities',
	'cleanliness' => 'Cleanliness',
];

const REVIEW_CATEGORY_COLUMNS = [
	'food' => 'food',
	'room' => 'room',
	'views' => 'views',
	'service' => 'service',
	'amenities' => 'amenities',
	'cleanliness' => 'cleanliness',
];

$categoryOptions = REVIEW_CATEGORY_OPTIONS;

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

function normalize_category_flag(mixed $value): int
{
	return (int)((int)$value === 1);
}

function review_categories_from_row(array $review): array
{
	$categories = [];

	foreach (REVIEW_CATEGORY_COLUMNS as $categoryKey => $columnName) {
		if (normalize_category_flag($review[$columnName] ?? 0) === 1) {
			$categories[] = $categoryKey;
		}
	}

	return sanitize_categories($categories, REVIEW_CATEGORY_OPTIONS);
}

function review_category_flags(array $categories): array
{
	$normalizedCategories = sanitize_categories($categories, REVIEW_CATEGORY_OPTIONS);
	$flags = [];

	foreach (REVIEW_CATEGORY_COLUMNS as $categoryKey => $columnName) {
		$flags[$columnName] = in_array($categoryKey, $normalizedCategories, true) ? 1 : 0;
	}

	return $flags;
}

function normalize_review_record(array $review): array
{
	$userId = $review['user_id'] ?? null;
	if ($userId !== null && $userId !== '') {
		$userId = (int)$userId;
	} else {
		$userId = null;
	}

	$userName = trim((string)($review['user_name'] ?? ''));
	if ($userName === '') {
		$userName = 'Guest';
	}

	$title = trim((string)($review['title'] ?? ''));
	if ($title === '') {
		$title = 'Guest review';
	}

	$body = trim((string)($review['body'] ?? ''));
	$imagePath = trim((string)($review['image_path'] ?? ''));

	$createdAt = (int)($review['created_at'] ?? 0);
	if ($createdAt <= 0) {
		$createdAt = time();
	}

	$categories = array_key_exists('categories', $review)
		? sanitize_categories((array)$review['categories'], REVIEW_CATEGORY_OPTIONS)
		: review_categories_from_row($review);
	$categoryFlags = review_category_flags($categories);

	return [
		'id' => (int)($review['id'] ?? 0),
		'user_id' => $userId,
		'user_name' => $userName,
		'rating' => normalize_rating($review['rating'] ?? 5),
		'title' => $title,
		'body' => $body,
		'categories' => $categories,
		'food' => $categoryFlags['food'],
		'room' => $categoryFlags['room'],
		'views' => $categoryFlags['views'],
		'service' => $categoryFlags['service'],
		'amenities' => $categoryFlags['amenities'],
		'cleanliness' => $categoryFlags['cleanliness'],
		'image_path' => $imagePath,
		'created_at' => $createdAt,
	];
}

function reviews_db_list(PDO $pdo, array $options = []): array
{
	$pageSize = (int)($options['pageSize'] ?? 12);
	if ($pageSize < 1) {
		$pageSize = 12;
	}
	if ($pageSize > 50) {
		$pageSize = 50;
	}

	$page = (int)($options['page'] ?? 1);
	if ($page < 1) {
		$page = 1;
	}

	$stats = $pdo->query('SELECT COUNT(*) AS total, AVG(rating) AS average_rating FROM reviews WHERE is_published = 1')->fetch();
	$total = (int)($stats['total'] ?? 0);
	$average = isset($stats['average_rating']) ? (float)$stats['average_rating'] : null;

	$totalPages = max(1, (int)ceil($total / $pageSize));
	if ($page > $totalPages) {
		$page = $totalPages;
	}

	$items = [];
	if ($total > 0) {
		$offset = ($page - 1) * $pageSize;
		$stmt = $pdo->prepare(
			'SELECT id, user_id, user_name, rating, title, body, image_path, food, room, views, service, amenities, cleanliness, UNIX_TIMESTAMP(created_at) AS created_at
			FROM reviews
			WHERE is_published = 1
			ORDER BY created_at DESC, id DESC
			LIMIT :limit OFFSET :offset'
		);
		$stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();

		$rows = $stmt->fetchAll();
		foreach ($rows as $row) {
			$items[] = normalize_review_record($row);
		}
	}

	return [
		'items' => $items,
		'total' => $total,
		'average' => $average,
		'page' => $page,
		'totalPages' => $totalPages,
	];
}

function resolve_review_user_id(PDO $pdo, mixed $sessionUserId, string $sessionFullName): ?int
{
	$userId = (int)$sessionUserId;
	if ($userId <= 0) {
		return null;
	}

	$fullName = trim($sessionFullName);
	if ($fullName === '') {
		return null;
	}

	$stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND full_name = ? LIMIT 1');
	$stmt->execute([$userId, $fullName]);
	$row = $stmt->fetch();

	return $row ? $userId : null;
}

function reviews_db_add(PDO $pdo, array $review): int
{
	$normalized = normalize_review_record($review);

	try {
		$stmt = $pdo->prepare(
			'INSERT INTO reviews (user_id, user_name, rating, title, body, image_path, food, room, views, service, amenities, cleanliness, is_published, created_at)
			VALUES (:user_id, :user_name, :rating, :title, :body, :image_path, :food, :room, :views, :service, :amenities, :cleanliness, 1, FROM_UNIXTIME(:created_at))'
		);
		$stmt->bindValue(':user_id', $normalized['user_id'], $normalized['user_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
		$stmt->bindValue(':user_name', $normalized['user_name'], PDO::PARAM_STR);
		$stmt->bindValue(':rating', $normalized['rating'], PDO::PARAM_INT);
		$stmt->bindValue(':title', $normalized['title'], PDO::PARAM_STR);
		$stmt->bindValue(':body', $normalized['body'], PDO::PARAM_STR);
		$stmt->bindValue(':image_path', $normalized['image_path'] === '' ? null : $normalized['image_path'], $normalized['image_path'] === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
		$stmt->bindValue(':food', $normalized['food'], PDO::PARAM_INT);
		$stmt->bindValue(':room', $normalized['room'], PDO::PARAM_INT);
		$stmt->bindValue(':views', $normalized['views'], PDO::PARAM_INT);
		$stmt->bindValue(':service', $normalized['service'], PDO::PARAM_INT);
		$stmt->bindValue(':amenities', $normalized['amenities'], PDO::PARAM_INT);
		$stmt->bindValue(':cleanliness', $normalized['cleanliness'], PDO::PARAM_INT);
		$stmt->bindValue(':created_at', $normalized['created_at'], PDO::PARAM_INT);
		$stmt->execute();

		return (int)$pdo->lastInsertId();
	} catch (Throwable $exception) {
		throw $exception;
	}
}

function cleanup_uploaded_review_image(?string $imagePath): void
{
	if ($imagePath === null) {
		return;
	}

	$relativePath = trim($imagePath);
	if ($relativePath === '') {
		return;
	}

	$relativePath = ltrim($relativePath, './\\');
	$absolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

	if (is_file($absolutePath)) {
		@unlink($absolutePath);
	}
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

	return './uploads/reviews/' . $filename;
}

$isLoggedIn = isset($_SESSION['user_id']);

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

		try {
			reviews_db_add($pdo, [
				'user_id' => resolve_review_user_id($pdo, $_SESSION['user_id'] ?? null, $name),
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
		} catch (Throwable $exception) {
			cleanup_uploaded_review_image($imagePath);
			if ($pdo instanceof PDO) {
				$errors[] = 'Unable to save your review right now. Please make sure the reviews tables exist in MySQL and try again.';
			} else {
				$errors[] = 'Unable to save your review right now because the MySQL connection is not available.';
			}
		}
	}
}

$requestedPage = (int)($_GET['page'] ?? 1);
try {
	$list = reviews_db_list($pdo, [
		'page' => $requestedPage,
		'pageSize' => $pageSize,
	]);
	$pagedReviews = $list['items'];
	$totalReviews = (int)$list['total'];
	$averageRating = $list['average'];
	$page = (int)$list['page'];
	$totalPages = (int)$list['totalPages'];
} catch (Throwable $exception) {
	$pagedReviews = [];
	$totalReviews = 0;
	$averageRating = null;
	$page = 1;
	$totalPages = 1;
	if ($databaseNotice === '') {
		$databaseNotice = 'Reviews are now database-backed, but the reviews tables are not ready yet. Run the SQL in database/schema.sql from MySQL Workbench.';
	}
}

if (isset($_GET['submitted']) && $_GET['submitted'] === '1') {
	$successMessage = 'Thanks! Your review has been saved to the database.';
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
									<span class="reviews-stat-value js-reviews-total"><?php echo $totalReviews; ?></span>
								</div>
								<div class="reviews-stat">
									<span class="reviews-stat-label">Average rating</span>
									<span class="reviews-stat-value js-reviews-average">
										<?php if ($averageRating === null): ?>
											—
										<?php else: ?>
											<?php echo number_format($averageRating, 1); ?> / 5
										<?php endif; ?>
									</span>
									<?php if ($averageRating !== null): ?>
										<div class="reviews-stat-stars js-reviews-average-stars">
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

						<?php if ($databaseNotice): ?>
							<div class="alert alert-warning reveal-up" role="alert">
								<?php echo htmlspecialchars($databaseNotice, ENT_QUOTES, 'UTF-8'); ?>
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
														if ($img !== '' && strpos($img, 'uploads/reviews/') === 0) {
															$img = './' . ltrim($img, './');
														}
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

