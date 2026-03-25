<?php
session_start();

// Inject page CSS in valid location
$pageStylesheets = ['assets/css/policies.css'];

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="policies-page">
	<section class="policies-shell">
		<div class="container">
			<div class="content-card policies-card reveal-up">
				<header class="policies-header text-center">
					<h1 class="policies-title">Policies</h1>
					<p class="policies-subtitle mb-0">A quick guide to booking, stays, and guest expectations at Azure Horizon Resort &amp; Spa.</p>
					<p class="policies-meta mb-0">Last updated: March 25, 2026</p>
				</header>

				<section class="policies-section" aria-label="Check-in and check-out">
					<h2>Check-in &amp; Check-out</h2>
					<ul>
						<li>Check-in begins at 3:00 PM; early check-in is subject to availability.</li>
						<li>Check-out is by 12:00 PM; late check-out may incur additional fees.</li>
						<li>A valid photo ID may be required at check-in.</li>
					</ul>
				</section>

				<section class="policies-section" aria-label="Payment and deposits">
					<h2>Payment &amp; Deposits</h2>
					<ul>
						<li>Rates are quoted per room, per night (unless stated otherwise).</li>
						<li>A deposit or pre-authorisation may be taken to cover incidentals.</li>
						<li>Taxes and service charges (if applicable) will be displayed at checkout.</li>
					</ul>
				</section>

				<section class="policies-section" aria-label="Cancellations and changes">
					<h2>Cancellations &amp; Changes</h2>
					<ul>
						<li>Cancellation and amendment rules vary by rate type and will be shown before you confirm.</li>
						<li>No-shows may be charged for the first night (or the full stay depending on rate rules).</li>
						<li>For date changes, the new stay is subject to availability and updated pricing.</li>
					</ul>
				</section>

				<section class="policies-section" aria-label="Guest conduct">
					<h2>Guest Conduct</h2>
					<ul>
						<li>Please respect quiet hours and keep noise to a minimum late at night.</li>
						<li>Parties and disruptive behaviour are not permitted.</li>
						<li>Damage to rooms or property may be charged to the guest.</li>
					</ul>
				</section>

				<section class="policies-section" aria-label="Smoking and vaping">
					<h2>Smoking &amp; Vaping</h2>
					<ul>
						<li>All rooms are non-smoking unless clearly designated otherwise.</li>
						<li>Cleaning fees may apply if smoking/vaping occurs in non-smoking areas.</li>
					</ul>
				</section>

				<section class="policies-section" aria-label="Pets">
					<h2>Pets</h2>
					<ul>
						<li>Pet policies vary by room type; please contact us before arrival for approval.</li>
						<li>Service animals are welcome.</li>
					</ul>
				</section>

				<section class="policies-section" aria-label="Accessibility">
					<h2>Accessibility</h2>
					<ul>
						<li>Wheelchair-friendly rooms are available and can be filtered in Rooms &amp; Suites.</li>
						<li>If you have specific needs, contact us ahead of time so we can prepare your stay.</li>
					</ul>
				</section>

				<section class="policies-section" aria-label="Privacy">
					<h2>Privacy</h2>
					<ul>
						<li>We only use submitted details to support your booking and your stay experience (demo project).</li>
						<li>For this assignment build, payment details are not processed by a real payment provider.</li>
					</ul>
				</section>

				<div class="policies-note" role="note">
					If you have questions about these policies, reach out via <a href="contact.php">Contact</a>.
				</div>
			</div>
		</div>
	</section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
