<?php
require_once __DIR__ . '/../app/includes/auth.php';

auth_require_login(
    'loyalty.php',
    'Please sign in or create an account to view your loyalty details.'
);

$pageStylesheets = ['assets/css/dashboard.css'];
$databaseNotice = '';
$tiers = [];
$loyaltySnapshot = null;

try {
    require_once __DIR__ . '/../app/includes/db.php';
    require_once __DIR__ . '/../app/includes/loyalty.php';
} catch (Throwable $exception) {
    $databaseNotice = 'Loyalty details are unavailable right now. Please check the schema and your MySQL connection.';
}

if (isset($pdo)) {
    try {
        $userId = auth_user_id() ?? 0;
        $tiers = loyalty_get_all_tiers($pdo);
        $loyaltySnapshot = loyalty_get_user_snapshot($pdo, $userId);
    } catch (Throwable $exception) {
        $databaseNotice = 'Loyalty details are unavailable right now. Please check the schema and your MySQL connection.';
    }
}

$displayName = auth_user_display_name();

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="dashboard-page">
    <section class="dashboard-hero">
        <div class="container">
            <div class="dashboard-hero-card reveal-up">
                <div class="row g-4 align-items-end">
                    <div class="col-lg-8">
                        <span class="section-eyebrow text-white">Loyalty program</span>
                        <h1 class="dashboard-title">Loyalty details for <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p class="dashboard-subtitle mb-0">Track your spending progress and unlock booking discounts as you stay more.</p>
                    </div>
                    <div class="col-lg-4 d-flex justify-content-lg-end">
                        <a class="btn btn-gold" href="dashboard.php">Back to dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-section">
        <div class="container">
            <?php if ($databaseNotice): ?>
                <div class="alert alert-warning reveal-up" role="alert">
                    <?php echo htmlspecialchars($databaseNotice, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($loyaltySnapshot): ?>
                <div class="dashboard-grid">
                    <section class="content-card dashboard-panel reveal-up">
                        <div class="dashboard-panel-head">
                            <div>
                                <p class="dashboard-panel-label">Your status</p>
                                <h2 class="dashboard-panel-title">Current tier</h2>
                            </div>
                        </div>

                        <div class="dashboard-entry-grid">
                            <div>
                                <span class="dashboard-entry-label">Tier</span>
                                <strong><?php echo htmlspecialchars((string)$loyaltySnapshot['tier_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <span class="dashboard-entry-label">Discount</span>
                                <strong><?php echo htmlspecialchars((string)$loyaltySnapshot['discount_label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div>
                                <span class="dashboard-entry-label">Total spent</span>
                                <strong>$<?php echo number_format((float)$loyaltySnapshot['total_spent'], 2); ?></strong>
                            </div>
                            <?php if (!empty($loyaltySnapshot['next_tier'])): ?>
                                <div>
                                    <span class="dashboard-entry-label">Next tier</span>
                                    <strong><?php echo htmlspecialchars((string)($loyaltySnapshot['next_tier']['tier_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <div>
                                    <span class="dashboard-entry-label">Amount left</span>
                                    <strong>$<?php echo number_format((float)$loyaltySnapshot['remaining_to_next'], 2); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <p class="mt-3 mb-0"><?php echo htmlspecialchars((string)$loyaltySnapshot['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </section>

                    <section class="content-card dashboard-panel reveal-up">
                        <div class="dashboard-panel-head">
                            <div>
                                <p class="dashboard-panel-label">Program tiers</p>
                                <h2 class="dashboard-panel-title">How loyalty works</h2>
                            </div>
                        </div>

                        <?php if (!$tiers): ?>
                            <div class="dashboard-empty">
                                No tiers configured yet.
                            </div>
                        <?php else: ?>
                            <div class="dashboard-list">
                                <?php foreach ($tiers as $tier): ?>
                                    <?php
                                        $tierName = (string)($tier['tier_name'] ?? '');
                                        $min = (float)($tier['min_spending'] ?? 0);
                                        $rate = (float)($tier['discount_rate'] ?? 0);
                                        $isCurrent = strtolower($tierName) === strtolower((string)$loyaltySnapshot['tier_name']);
                                    ?>
                                    <article class="dashboard-entry" style="border: <?php echo $isCurrent ? '2px solid currentColor' : '1px solid rgba(255,255,255,0.08)'; ?>;">
                                        <div class="dashboard-entry-top">
                                            <div>
                                                <h3><?php echo htmlspecialchars($tierName, ENT_QUOTES, 'UTF-8'); ?><?php echo $isCurrent ? ' (Current)' : ''; ?></h3>
                                                <p class="dashboard-entry-meta mb-0">Spend at least $<?php echo number_format($min, 2); ?> total to reach this tier.</p>
                                            </div>
                                            <span class="badge rounded-pill text-bg-success"><?php echo htmlspecialchars(loyalty_discount_label($rate), ENT_QUOTES, 'UTF-8'); ?> off</span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
