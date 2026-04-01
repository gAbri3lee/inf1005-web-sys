<?php
$config = require __DIR__ . '/../config/database.php';

$host = $config['host'];
$db   = $config['database'];
$user = $config['username'];
$pass = $config['password'];
$charset = $config['charset'];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    try {
        $pdo->exec('SET SESSION auto_increment_increment = 1');
        $pdo->exec('SET SESSION auto_increment_offset = 1');
    } catch (Throwable $exception) {
        // Best-effort: some hosts restrict session variables.
    }
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
