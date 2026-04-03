<?php

declare(strict_types=1);

function booking_adjustments_ensure_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS booking_adjustments (
            id INT NOT NULL AUTO_INCREMENT,
            booking_id INT NOT NULL,
            user_id INT NOT NULL,
            kind VARCHAR(10) NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            old_nights INT DEFAULT NULL,
            new_nights INT DEFAULT NULL,
            old_total DECIMAL(10, 2) NOT NULL,
            new_total DECIMAL(10, 2) NOT NULL,
            status VARCHAR(15) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY booking_adjustments_booking_uk (booking_id),
            KEY booking_adjustments_user_status_idx (user_id, status),
            KEY booking_adjustments_updated_idx (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function booking_adjustments_upsert_pending(
    PDO $pdo,
    int $bookingId,
    int $userId,
    string $kind,
    float $amount,
    ?int $oldNights,
    ?int $newNights,
    float $oldTotal,
    float $newTotal
): void {
    $kind = strtolower(trim($kind));
    if (!in_array($kind, ['due', 'refund'], true)) {
        throw new InvalidArgumentException('Invalid adjustment kind.');
    }

    $amount = round(max(0.0, $amount), 2);
    $oldTotal = round($oldTotal, 2);
    $newTotal = round($newTotal, 2);

    $stmt = $pdo->prepare(
        "INSERT INTO booking_adjustments (booking_id, user_id, kind, amount, old_nights, new_nights, old_total, new_total, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
         ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            kind = VALUES(kind),
            amount = VALUES(amount),
            old_nights = VALUES(old_nights),
            new_nights = VALUES(new_nights),
            old_total = VALUES(old_total),
            new_total = VALUES(new_total),
            status = 'pending'"
    );

    $stmt->execute([
        $bookingId,
        $userId,
        $kind,
        $amount,
        $oldNights,
        $newNights,
        $oldTotal,
        $newTotal,
    ]);
}

function booking_adjustments_clear_pending(PDO $pdo, int $bookingId, int $userId): void
{
    $stmt = $pdo->prepare("UPDATE booking_adjustments SET status = 'acknowledged' WHERE booking_id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$bookingId, $userId]);
}

function booking_adjustments_get_latest_pending_for_user(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT a.id,
                a.booking_id,
                a.user_id,
                a.kind,
                a.amount,
                a.old_nights,
                a.new_nights,
                a.old_total,
                a.new_total,
                a.status,
                a.updated_at,
                b.room_name,
                b.check_in,
                b.check_out
         FROM booking_adjustments a
         LEFT JOIN bookings b ON b.id = a.booking_id
         WHERE a.user_id = ? AND a.status = 'pending'
         ORDER BY a.updated_at DESC, a.id DESC
         LIMIT 1"
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function booking_adjustments_get_pending_due(PDO $pdo, int $adjustmentId, int $userId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT a.id,
                a.booking_id,
                a.user_id,
                a.kind,
                a.amount,
                a.old_nights,
                a.new_nights,
                a.old_total,
                a.new_total,
                a.status,
                b.room_name,
                b.check_in,
                b.check_out,
                b.nights,
                b.room_rate
         FROM booking_adjustments a
         LEFT JOIN bookings b ON b.id = a.booking_id
         WHERE a.id = ? AND a.user_id = ? AND a.status = 'pending' AND a.kind = 'due'
         LIMIT 1"
    );
    $stmt->execute([$adjustmentId, $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function booking_adjustments_mark_paid(PDO $pdo, int $adjustmentId, int $userId): void
{
    $stmt = $pdo->prepare("UPDATE booking_adjustments SET status = 'paid' WHERE id = ? AND user_id = ? AND status = 'pending' AND kind = 'due'");
    $stmt->execute([$adjustmentId, $userId]);
}

function booking_adjustments_acknowledge_refund(PDO $pdo, int $adjustmentId, int $userId): void
{
    $stmt = $pdo->prepare("UPDATE booking_adjustments SET status = 'acknowledged' WHERE id = ? AND user_id = ? AND status = 'pending' AND kind = 'refund'");
    $stmt->execute([$adjustmentId, $userId]);
}
