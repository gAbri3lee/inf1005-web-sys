<?php

declare(strict_types=1);

require __DIR__ . '/../app/includes/db.php';

$results = [
    'tables' => [],
    'users_columns' => [],
];

$tableStmt = $pdo->prepare(
    'SELECT COUNT(*) AS c
     FROM information_schema.tables
     WHERE table_schema = DATABASE()
       AND table_name = ?'
);

foreach (['loyalty_tiers'] as $tableName) {
    $tableStmt->execute([$tableName]);
    $row = $tableStmt->fetch();
    $results['tables'][$tableName] = (int)($row['c'] ?? 0);
}

$columnStmt = $pdo->prepare(
    'SELECT COUNT(*) AS c
     FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = "users"
       AND column_name = ?'
);

foreach (['total_spent', 'loyalty_tier_id'] as $columnName) {
    $columnStmt->execute([$columnName]);
    $row = $columnStmt->fetch();
    $results['users_columns'][$columnName] = (int)($row['c'] ?? 0);
}

echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
