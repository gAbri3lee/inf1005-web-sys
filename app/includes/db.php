<?php
$config = require __DIR__ . '/../config/database.php';

$host = $config['host'];
$port = (int)($config['port'] ?? 3306);
$db   = $config['database'];
$user = $config['username'];
$pass = $config['password'];
$charset = $config['charset'];
$socket = trim((string)($config['socket'] ?? ''));

$dsnParts = ['mysql:'];

if ($socket !== '') {
    $dsnParts[] = "unix_socket=$socket";
} else {
    $dsnParts[] = "host=$host";
    if ($port > 0) {
        $dsnParts[] = "port=$port";
    }
}

$dsnParts[] = "dbname=$db";
$dsnParts[] = "charset=$charset";
$dsn = implode(';', $dsnParts);
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
