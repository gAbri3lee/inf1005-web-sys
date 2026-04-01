<?php
declare(strict_types=1);

/**
 * Loyalty program helpers.
 *
 * Spending is calculated from paid room bookings (simple school-project rule):
 * - booking is NOT cancelled
 * - total_price is included regardless of check-out date
 *
 * This makes tiers update immediately after a user confirms a booking.
 */

function loyalty_money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function loyalty_discount_label(float $discountRate): string
{
    $percent = max(0.0, $discountRate) * 100;
    return number_format($percent, 0) . '%';
}

/**
 * Returns all tiers ordered by min spending ascending.
 */
function loyalty_get_all_tiers(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, tier_name, min_spending, discount_rate FROM loyalty_tiers ORDER BY min_spending ASC, id ASC');
    return $stmt->fetchAll();
}

/**
 * Calculates total spending from completed bookings.
 */
function loyalty_calculate_total_spent(PDO $pdo, int $userId): float
{
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(COALESCE(total_price, 0)), 0) AS total_spent\n" .
        "FROM bookings\n" .
        "WHERE user_id = ?\n" .
        "  AND LOWER(TRIM(COALESCE(status, ''))) <> 'cancelled'"
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    return (float)($row['total_spent'] ?? 0);
}

/**
 * Finds the best tier for a given total spent.
 */
function loyalty_find_current_tier(PDO $pdo, float $totalSpent): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, tier_name, min_spending, discount_rate
         FROM loyalty_tiers
         WHERE min_spending <= ?
         ORDER BY min_spending DESC, id DESC
         LIMIT 1'
    );
    $stmt->execute([$totalSpent]);
    $tier = $stmt->fetch();

    return $tier ?: null;
}

/**
 * Finds the next tier above a given total spent.
 */
function loyalty_find_next_tier(PDO $pdo, float $totalSpent): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, tier_name, min_spending, discount_rate
         FROM loyalty_tiers
         WHERE min_spending > ?
         ORDER BY min_spending ASC, id ASC
         LIMIT 1'
    );
    $stmt->execute([$totalSpent]);
    $tier = $stmt->fetch();

    return $tier ?: null;
}

/**
 * Refreshes the cached row in user_loyalty and writes to loyalty_history when tier changes.
 */
function loyalty_refresh_user(PDO $pdo, int $userId): array
{
    $totalSpent = loyalty_calculate_total_spent($pdo, $userId);
    $currentTier = loyalty_find_current_tier($pdo, $totalSpent);
    $currentTierId = (int)($currentTier['id'] ?? 0);

    // Optional: mirror into users table (if columns exist in this deployment).
    // This keeps the project easy to query for dashboards/reports.
    try {
        $mirror = $pdo->prepare('UPDATE users SET total_spent = ?, loyalty_tier_id = ? WHERE id = ?');
        $mirror->execute([
            $totalSpent,
            $currentTierId > 0 ? $currentTierId : null,
            $userId,
        ]);
    } catch (Throwable $exception) {
        // Ignore if columns/constraints are not present.
    }

    return [
        'total_spent' => $totalSpent,
        'current_tier' => $currentTier,
        'next_tier' => loyalty_find_next_tier($pdo, $totalSpent),
    ];
}

/**
 * Returns a dashboard-friendly snapshot.
 */
function loyalty_get_user_snapshot(PDO $pdo, int $userId): array
{
    $data = loyalty_refresh_user($pdo, $userId);

    $totalSpent = (float)($data['total_spent'] ?? 0);
    $currentTier = is_array($data['current_tier'] ?? null) ? $data['current_tier'] : null;
    $nextTier = is_array($data['next_tier'] ?? null) ? $data['next_tier'] : null;

    $tierName = (string)($currentTier['tier_name'] ?? '');
    $discountRate = (float)($currentTier['discount_rate'] ?? 0);

    $remaining = 0.0;
    $message = '';

    if ($nextTier) {
        $nextName = (string)($nextTier['tier_name'] ?? '');
        $nextMin = (float)($nextTier['min_spending'] ?? 0);
        $remaining = max(0.0, $nextMin - $totalSpent);
        $message = 'You are ' . loyalty_money($remaining) . ' away from ' . $nextName . ' tier.';
    } else {
        if ($tierName !== '') {
            $message = 'You are at our highest tier (' . $tierName . '). Congratulations!';
        } else {
            $message = 'Your loyalty tier will appear once tiers are configured.';
        }
    }

    return [
        'total_spent' => $totalSpent,
        'tier_name' => $tierName !== '' ? $tierName : 'Bronze',
        'discount_rate' => $discountRate,
        'discount_label' => loyalty_discount_label($discountRate),
        'next_tier' => $nextTier,
        'remaining_to_next' => $remaining,
        'message' => $message,
    ];
}
