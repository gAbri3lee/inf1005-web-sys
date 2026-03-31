<?php
require_once __DIR__ . '/../app/includes/auth.php';

$faqCategories = [
	'booking' => 'Booking & Check-in',
	'rooms' => 'Rooms & Amenities',
	'parking' => 'Parking & Arrival',
	'dining' => 'Dining',
	'policies' => 'Policies',
];

$faqItems = [
	[
		'category' => 'booking',
		'question' => 'What time is check-in and check-out?',
		'answer' => 'Check-in begins from 3:00 PM, while check-out is by 11:00 AM. Early check-in and late check-out are subject to availability.',
	],
	[
		'category' => 'booking',
		'question' => 'Can I request early check-in or late check-out?',
		'answer' => 'Yes. Requests can be made before arrival or at reception, but approval depends on room availability.',
	],
	[
		'category' => 'booking',
		'question' => 'What do I need during check-in?',
		'answer' => 'Please present a valid photo ID or passport, your booking confirmation, and a payment card if required for incidentals.',
	],
	[
		'category' => 'rooms',
		'question' => 'Do all rooms include complimentary Wi-Fi?',
		'answer' => 'Yes. Complimentary high-speed Wi-Fi is available in all rooms, suites, and most public areas of the hotel.',
	],
	[
		'category' => 'rooms',
		'question' => 'Are extra beds or baby cots available?',
		'answer' => 'Baby cots are available on request, while extra beds may be arranged for selected room types and may incur additional charges.',
	],
	[
		'category' => 'rooms',
		'question' => 'What amenities are provided in the room?',
		'answer' => 'Standard amenities include towels, bath essentials, a hairdryer, refreshments, and selected premium comforts depending on room category.',
	],
	[
		'category' => 'parking',
		'question' => 'Is parking available at the hotel?',
		'answer' => 'Yes. Secure on-site parking is available for hotel guests, subject to space availability during peak periods.',
	],
	[
		'category' => 'parking',
		'question' => 'Do you offer airport transfer services?',
		'answer' => 'Airport transfer arrangements can be made through the concierge. Advance booking is recommended.',
	],
	[
		'category' => 'parking',
		'question' => 'Is the hotel accessible by public transport?',
		'answer' => 'Yes. The hotel is well-connected by nearby MRT stations, buses, and point-to-point transport options.',
	],
	[
		'category' => 'dining',
		'question' => 'Is breakfast included in the stay?',
		'answer' => 'Breakfast inclusion depends on your selected room package. Please refer to your booking confirmation for details.',
	],
	[
		'category' => 'dining',
		'question' => 'Are there dining options available on-site?',
		'answer' => 'Yes. Horizon Sands Bali offers on-site dining venues, lounge experiences, and selected in-room dining options during operating hours.',
	],
	[
		'category' => 'policies',
		'question' => 'What is your cancellation policy?',
		'answer' => 'Cancellation terms vary by room type, package, and promotional rate. Please review your booking terms carefully before confirming.',
	],
	[
		'category' => 'policies',
		'question' => 'Is smoking allowed in the hotel?',
		'answer' => 'Horizon Sands Bali maintains a non-smoking environment in guest rooms and most public areas. Designated smoking zones may be available.',
	],
	[
		'category' => 'policies',
		'question' => 'Are pets allowed on the property?',
		'answer' => 'Pet policies depend on room type and hotel arrangements. Guests should contact the hotel directly before arrival for confirmation.',
	],
];

$faqCounts = array_fill_keys(array_keys($faqCategories), 0);
foreach ($faqItems as $item) {
	$key = $item['category'];
	if (isset($faqCounts[$key])) {
		$faqCounts[$key]++;
	}
}

$pageStylesheets = ['assets/css/FAQ.css'];
$pageScripts = ['assets/js/FAQ.js'];

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="faq-page">
	<section class="faq-hero">
		<div class="container">
			<div class="row g-4 align-items-end">
				<div class="col-lg-7">
					<div class="faq-hero-copy reveal-up">
						<span class="section-eyebrow">Guest Support</span>
						<h1 class="faq-title">Frequently Asked Questions</h1>
						<p class="faq-subtitle">
							Quick answers for arrivals, rooms, dining, hotel policies, and the details guests usually ask before they book.
						</p>
					</div>
				</div>
				<div class="col-lg-5">
					<div class="faq-hero-panel reveal-up">
						<div class="faq-panel-stat">
							<span class="faq-panel-number"><?php echo count($faqItems); ?></span>
							<span class="faq-panel-label">Answers ready</span>
						</div>
						<p class="faq-panel-text mb-0">
							Search by keyword or switch categories to narrow the list instantly. If you still need help, our team is one click away.
						</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section class="faq-content">
		<div class="container">
			<div class="faq-toolbar content-card reveal-up">
				<div class="row g-4 align-items-center">
					<div class="col-lg-5">
						<h2 class="faq-toolbar-title">Find an answer fast</h2>
						<p class="faq-toolbar-text mb-0">
							Try keywords like check-in, breakfast, parking, cancellation, Wi-Fi, pets, or transfer.
						</p>
					</div>
					<div class="col-lg-7">
						<div class="faq-search-wrap">
							<label class="visually-hidden" for="faqSearch">Search FAQs</label>
							<input
								type="search"
								id="faqSearch"
								class="form-control faq-search-input"
								placeholder="Search questions or answers"
								autocomplete="off"
							>
						</div>
					</div>
				</div>

				<div class="faq-toolbar-bottom">
					<div class="faq-category-tabs" role="tablist" aria-label="FAQ categories">
						<button
							type="button"
							class="faq-category-btn active"
							data-category="all"
							aria-pressed="true"
						>
							<span>All topics</span>
							<small><?php echo count($faqItems); ?></small>
						</button>

						<?php foreach ($faqCategories as $key => $label): ?>
							<button
								type="button"
								class="faq-category-btn"
								data-category="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
								aria-pressed="false"
							>
								<span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
								<small><?php echo (int)$faqCounts[$key]; ?></small>
							</button>
						<?php endforeach; ?>
					</div>

					<div class="faq-meta">
						<div class="faq-meta-chip">
							<span class="faq-meta-label">Showing</span>
							<strong id="faqCurrentCategory">All topics</strong>
						</div>
						<div class="faq-meta-chip">
							<span class="faq-meta-label">Results</span>
							<strong id="faqResultCount"><?php echo count($faqItems); ?> questions</strong>
						</div>
					</div>
				</div>

				<div id="faqEmptyState" class="faq-empty-state" hidden>
					<h3>No matching questions found</h3>
					<p class="mb-0">Try a simpler keyword or switch to another category to explore more answers.</p>
				</div>
			</div>

			<div class="row g-4 mt-1">
				<div class="col-xl-8">
					<div class="accordion faq-accordion reveal-up" id="faqAccordion">
						<?php foreach ($faqItems as $index => $item): ?>
							<?php
								$itemNumber = $index + 1;
								$categoryKey = $item['category'];
								$categoryLabel = $faqCategories[$categoryKey] ?? ucfirst($categoryKey);
							?>
							<div
								class="accordion-item faq-item"
								data-category="<?php echo htmlspecialchars($categoryKey, ENT_QUOTES, 'UTF-8'); ?>"
							>
								<h2 class="accordion-header" id="faqHeading<?php echo $itemNumber; ?>">
									<button
										class="accordion-button collapsed"
										type="button"
										data-bs-toggle="collapse"
										data-bs-target="#faqCollapse<?php echo $itemNumber; ?>"
										aria-expanded="false"
										aria-controls="faqCollapse<?php echo $itemNumber; ?>"
									>
										<span class="faq-question-meta"><?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?></span>
										<span class="faq-question-text"><?php echo htmlspecialchars($item['question'], ENT_QUOTES, 'UTF-8'); ?></span>
									</button>
								</h2>
								<div
									id="faqCollapse<?php echo $itemNumber; ?>"
									class="accordion-collapse collapse"
									role="region"
									aria-labelledby="faqHeading<?php echo $itemNumber; ?>"
									data-bs-parent="#faqAccordion"
								>
									<div class="accordion-body">
										<p class="mb-0"><?php echo htmlspecialchars($item['answer'], ENT_QUOTES, 'UTF-8'); ?></p>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="faq-load-more-wrap reveal-up">
						<button type="button" id="faqLoadMore" class="btn btn-outline-dark faq-load-more-btn">
							Show more questions
						</button>
					</div>
				</div>

				<div class="col-xl-4">
					<div class="faq-aside reveal-up">
						<div class="content-card faq-aside-card">
							<div class="faq-aside-header">
								<span class="faq-aside-kicker">Quick Guide</span>
								<h2 class="faq-aside-title">Popular help topics</h2>
								<p class="faq-aside-intro mb-0">
									A fast overview of the things guests usually want to confirm before arrival.
								</p>
							</div>

							<div class="faq-highlight-list">
								<div class="faq-highlight-item">
									<div class="faq-highlight-icon">01</div>
									<div>
										<div class="faq-highlight-label">Arrival & Check-in</div>
										<p class="mb-0">Check-in starts at 3:00 PM, with early arrival requests handled based on availability.</p>
									</div>
								</div>

								<div class="faq-highlight-item">
									<div class="faq-highlight-icon">02</div>
									<div>
										<div class="faq-highlight-label">Room Comfort</div>
										<p class="mb-0">Complimentary Wi-Fi, bath essentials, and selected premium amenities are included in every stay.</p>
									</div>
								</div>

								<div class="faq-highlight-item">
									<div class="faq-highlight-icon">03</div>
									<div>
										<div class="faq-highlight-label">Dining & Breakfast</div>
										<p class="mb-0">Breakfast depends on your package, and on-site dining options are available throughout the day.</p>
									</div>
								</div>
							</div>
						</div>

						<div class="content-card faq-help-card">
							<span class="faq-help-kicker">Still need help?</span>
							<h2 class="faq-help-title">Talk to the Horizon Sands team</h2>
							<p class="faq-help-text">
								Reach out for room preferences, arrival details, special requests, or anything you would like us to prepare before your stay.
							</p>
							<div class="faq-help-actions">
								<a href="contact.php" class="btn btn-gold">Contact Us</a>
								<a href="parking_and_transport.php" class="btn btn-outline-dark">Parking & Transport</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
