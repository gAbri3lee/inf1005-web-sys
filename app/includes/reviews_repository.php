<?php

declare(strict_types=1);

require_once __DIR__ . '/reviews_data.php';

function reviews_repo_init(): void
{
	if (!isset($_SESSION['submitted_reviews']) || !is_array($_SESSION['submitted_reviews'])) {
		$_SESSION['submitted_reviews'] = [];
	}
}

function reviews_repo_normalize_rating(mixed $value): int
{
	$rating = (int)$value;
	if ($rating < 1) return 1;
	if ($rating > 5) return 5;
	return $rating;
}

function reviews_repo_normalize_categories(mixed $value): array
{
	if (!is_array($value)) {
		return [];
	}
	$categories = [];
	foreach ($value as $v) {
		$key = strtolower(trim((string)$v));
		if ($key !== '') {
			$categories[] = $key;
		}
	}
	$categories = array_values(array_unique($categories));
	sort($categories);
	return $categories;
}

function reviews_repo_normalize_review(array $review): array
{
	$id = (string)($review['id'] ?? '');
	if ($id === '') {
		$id = 'r_' . bin2hex(random_bytes(8));
	}

	$userId = $review['user_id'] ?? null;
	if ($userId !== null && $userId !== '') {
		$userId = (int)$userId;
	} else {
		$userId = null;
	}

	$userName = (string)($review['user_name'] ?? $review['name'] ?? '');
	$userName = trim($userName);
	if ($userName === '') {
		$userName = 'Guest';
	}

	$title = (string)($review['title'] ?? '');
	$title = trim($title);
	if ($title === '') {
		$title = 'Guest review';
	}

	$body = (string)($review['body'] ?? '');
	$body = trim($body);

	$categories = reviews_repo_normalize_categories($review['categories'] ?? []);
	$imagePath = (string)($review['image_path'] ?? $review['image'] ?? '');
	$imagePath = trim($imagePath);

	$createdAt = (int)($review['created_at'] ?? 0);
	if ($createdAt <= 0) {
		$createdAt = time();
	}

	return [
		'id' => $id,
		'user_id' => $userId,
		'user_name' => $userName,
		'rating' => reviews_repo_normalize_rating($review['rating'] ?? 5),
		'title' => $title,
		'body' => $body,
		'categories' => $categories,
		'categories_csv' => implode(',', $categories),
		'image_path' => $imagePath,
		'created_at' => $createdAt,
	];
}

function reviews_repo_add(array $review): string
{
	reviews_repo_init();
	$normalized = reviews_repo_normalize_review($review);
	$_SESSION['submitted_reviews'][] = $normalized;
	return (string)$normalized['id'];
}

function reviews_repo_all(): array
{
	reviews_repo_init();

	$sessionReviews = array_reverse($_SESSION['submitted_reviews']);
	$seedReviews = reviews_seed_reviews();

	$all = array_merge($sessionReviews, $seedReviews);
	$out = [];
	foreach ($all as $review) {
		if (is_array($review)) {
			$out[] = reviews_repo_normalize_review($review);
		}
	}
	return $out;
}

function reviews_repo_list(array $options = []): array
{
	$pageSize = (int)($options['pageSize'] ?? 12);
	if ($pageSize < 1) $pageSize = 12;
	if ($pageSize > 50) $pageSize = 50;

	$page = (int)($options['page'] ?? 1);
	if ($page < 1) $page = 1;

	$category = strtolower(trim((string)($options['category'] ?? '')));

	$all = reviews_repo_all();
	if ($category !== '') {
		$all = array_values(array_filter($all, function ($review) use ($category) {
			$cats = $review['categories'] ?? [];
			return is_array($cats) && in_array($category, $cats, true);
		}));
	}

	$total = count($all);
	$average = null;
	if ($total > 0) {
		$sum = 0;
		foreach ($all as $r) {
			$sum += reviews_repo_normalize_rating($r['rating'] ?? 0);
		}
		$average = $sum / $total;
	}

	$totalPages = max(1, (int)ceil($total / $pageSize));
	if ($page > $totalPages) $page = $totalPages;

	$offset = ($page - 1) * $pageSize;
	$items = array_slice($all, $offset, $pageSize);

	return [
		'items' => $items,
		'total' => $total,
		'average' => $average,
		'page' => $page,
		'totalPages' => $totalPages,
	];
}
