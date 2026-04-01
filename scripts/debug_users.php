<?php
// Temporary debugging helper for local development.
// Usage:
//   php scripts/debug_users.php
//   php scripts/debug_users.php someone@example.com "PlaintextPassword"

$config = require __DIR__ . '/../app/config/database.php';

$dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';charset=' . $config['charset'];
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$rows = $pdo->query('SELECT id, email, LENGTH(password) AS pw_len, LEFT(password, 10) AS pw_prefix FROM users ORDER BY id DESC LIMIT 10')
    ->fetchAll();

echo "DB: {$config['database']} @ {$config['host']}" . PHP_EOL;
echo "Recent users:" . PHP_EOL;
foreach ($rows as $row) {
    $id = (int)($row['id'] ?? 0);
    $email = (string)($row['email'] ?? '');
    $len = (int)($row['pw_len'] ?? 0);
    $prefix = (string)($row['pw_prefix'] ?? '');
    echo "- {$id} {$email} pw_len={$len} pw_prefix=" . json_encode($prefix) . PHP_EOL;
}

echo PHP_EOL;

if ($argc >= 3) {
    $email = (string)$argv[1];
    $password = (string)$argv[2];

    $stmt = $pdo->prepare('SELECT id, email, password FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "No user found for {$email}" . PHP_EOL;
        exit(0);
    }

    $stored = (string)($user['password'] ?? '');
    $info = password_get_info($stored);

    echo "User id=" . (int)($user['id'] ?? 0) . " email=" . (string)($user['email'] ?? '') . PHP_EOL;
    echo "Stored pw len=" . strlen($stored) . " algo=" . (int)($info['algo'] ?? 0) . "" . PHP_EOL;
    echo "password_verify=" . (password_verify($password, $stored) ? 'true' : 'false') . PHP_EOL;
    echo "plaintext_equals=" . (hash_equals($stored, $password) ? 'true' : 'false') . PHP_EOL;
}
