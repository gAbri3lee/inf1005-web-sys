<?php
require_once __DIR__ . '/../app/includes/auth.php';

$errors = [];
$databaseNotice = '';
$formData = [
    'email' => '',
];

$nextPath = auth_normalize_next_path((string)($_GET['next'] ?? $_POST['next'] ?? 'dashboard.php'), 'dashboard.php');
$authNotice = auth_flash_get('auth_notice');

if (auth_is_logged_in()) {
    auth_redirect($nextPath);
}

try {
    require_once __DIR__ . '/../app/includes/db.php';
} catch (Throwable $exception) {
    $databaseNotice = 'Unable to connect to the account database right now. Please check your database settings and try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $honeypot = trim((string)($_POST['website'] ?? ''));
    $formData['email'] = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    if (!csrf_validate('login_form', $postedToken)) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    }

    if ($honeypot !== '') {
        $errors[] = 'Unable to sign in. Please try again.';
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if ($databaseNotice !== '') {
        $errors[] = $databaseNotice;
    }

    if (!$errors && isset($pdo)) {
        try {
            $stmt = $pdo->prepare('SELECT id, email, password, full_name, phone, is_admin FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$formData['email']]);
            $user = $stmt->fetch();
        } catch (Throwable $exception) {
            $stmt = $pdo->prepare('SELECT id, email, password, full_name FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$formData['email']]);
            $user = $stmt->fetch();
        }

        $storedPassword = (string)($user['password'] ?? '');
        $passwordOk = $user && $storedPassword !== '' && password_verify($password, $storedPassword);

        if (!$passwordOk && $user && $storedPassword !== '') {
            $info = password_get_info($storedPassword);
            if ((int)($info['algo'] ?? 0) === 0) {
                // Legacy/plaintext passwords: allow once, then upgrade to a hash.
                $passwordOk = hash_equals($storedPassword, $password);

                if ($passwordOk) {
                    try {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $upgradeStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ? AND password = ?');
                        $upgradeStmt->execute([$newHash, (int)($user['id'] ?? 0), $storedPassword]);
                        $user['password'] = $newHash;
                    } catch (Throwable $exception) {
                        // Best-effort upgrade; login still succeeds.
                    }
                }
            }
        }

        if (!$user || !$passwordOk) {
            $errors[] = 'Invalid email or password.';
        } else {
            auth_login_user($user);
            csrf_refresh('login_form');
            auth_flash_set('dashboard_notice', 'Welcome back. Your account dashboard is ready.');
            auth_redirect($nextPath);
        }
    }
}

$pageStylesheets = ['assets/css/login.css'];

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="auth-page">
    <section class="auth-hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-xl-7 reveal-up">
                    <span class="section-eyebrow">Guest login</span>
                    <h1 class="auth-title">Welcome back to Horizon Sands Bali</h1>
                    <p class="auth-subtitle mb-0">Sign in to access your dashboard, manage bookings, and continue with reviews or spa reservations.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="auth-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5 reveal-up">
                    <div class="content-card auth-card">
                        <h2 class="auth-card-title">Sign in</h2>

                        <?php if ($authNotice): ?>
                            <div class="alert alert-warning" role="alert">
                                <?php echo htmlspecialchars($authNotice, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($databaseNotice && !$errors): ?>
                            <div class="alert alert-warning" role="alert">
                                <?php echo htmlspecialchars($databaseNotice, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($errors): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $message): ?>
                                        <li><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('login_form'), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="next" value="<?php echo htmlspecialchars($nextPath, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="auth-honeypot" aria-hidden="true">
                                <label for="website" class="form-label">Website</label>
                                <input type="text" class="form-control" id="website" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                    autocomplete="email"
                                    required
                                >
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
                            </div>
                            <button type="submit" class="btn btn-gold w-100">Login</button>
                        </form>

                        <p class="auth-footer-text mb-0">Need an account? <a href="register.php?next=<?php echo rawurlencode($nextPath); ?>">Create one here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
