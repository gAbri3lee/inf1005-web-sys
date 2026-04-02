<?php

declare(strict_types=1);

require __DIR__ . '/../app/includes/db.php';
require __DIR__ . '/../app/includes/loyalty_helper.php';

$userIds = $pdo->query('SELECT id FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);

$updated = 0;
foreach ($userIds as $userId) {
    $userId = (int)$userId;
    if ($userId <= 0) {
        continue;
    }

    loyalty_refresh_user($pdo, $userId);
    $updated++;
}

echo "Recalculated loyalty for {$updated} user(s)." . PHP_EOL;
