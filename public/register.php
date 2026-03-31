<?php
require_once __DIR__ . '/../app/includes/auth.php';

$errors = [];
$databaseNotice = '';
$formData = [
    'email' => '',
];

function validate_password_strength(string $password, array &$errors): bool
{
    $minLen = 8;

    if ($password === '' || app_string_length($password) < $minLen) {
        $errors[] = 'Password must be at least 8 characters.';
        return false;
    }

    $hasLower = (bool)preg_match('/[a-z]/', $password);
    $hasUpper = (bool)preg_match('/[A-Z]/', $password);
    $hasDigit = (bool)preg_match('/\d/', $password);
    $hasSymbol = (bool)preg_match('/[^A-Za-z\d]/', $password);

    if (!$hasLower || !$hasUpper || !$hasDigit || !$hasSymbol) {
        $errors[] = 'Password must include uppercase, lowercase, a number, and a symbol.';
        return false;
    }

    return true;
}

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
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (!csrf_validate('register_form', $postedToken)) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    }

    if ($honeypot !== '') {
        $errors[] = 'Unable to submit your registration request. Please try again.';
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    validate_password_strength($password, $errors);

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if ($databaseNotice !== '') {
        $errors[] = $databaseNotice;
    }

    if (!$errors && isset($pdo)) {
        $existingStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $existingStmt->execute([$formData['email']]);

        if ($existingStmt->fetch()) {
            $errors[] = 'An account with that email already exists. Please sign in instead.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $displayName = auth_build_display_name($formData['email']);

            $insertStmt = $pdo->prepare('INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)');
            $insertStmt->execute([$displayName, $formData['email'], $hashedPassword]);

            auth_login_user([
                'id' => (int)$pdo->lastInsertId(),
                'email' => $formData['email'],
                'full_name' => $displayName,
            ]);

            csrf_refresh('register_form');
            auth_flash_set('dashboard_notice', 'Your account is ready. You can now manage room bookings, spa reservations, and reviews.');
            auth_redirect($nextPath);
        }
    }
}

$pageStylesheets = ['assets/css/register.css'];
$pageScripts = ['assets/js/register.js'];

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="register-page">
    <section class="register-shell">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="register-card content-card reveal-up">
                        <header class="register-header text-center">
                            <h1 class="register-title">Create your guest account</h1>
                            <p class="register-subtitle mb-0">
                                Register with your email to unlock your dashboard, room bookings, spa reservations, and reviews.
                            </p>
                        </header>

                        <div class="register-benefits">
                            <div class="row g-3 justify-content-center">
                                <div class="col-6 col-md-4">
                                    <div class="benefit-item">
                                        <span class="benefit-badge" aria-hidden="true">
                                            <img class="benefit-icon" src="assets/images/bedlogo.webp" alt="" role="presentation" loading="lazy">
                                        </span>
                                        <span class="benefit-text">Manage stays</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="benefit-item">
                                        <span class="benefit-badge" aria-hidden="true">
                                            <img class="benefit-icon" src="assets/images/member.webp" alt="" role="presentation" loading="lazy">
                                        </span>
                                        <span class="benefit-text">Track bookings</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="benefit-item">
                                        <span class="benefit-badge" aria-hidden="true">
                                            <img class="benefit-icon" src="assets/images/wifi.webp" alt="" role="presentation" loading="lazy">
                                        </span>
                                        <span class="benefit-text">Save preferences</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="register-divider">

                        <p class="register-helper mb-4">
                            Already have an account? <a href="login.php?next=<?php echo rawurlencode($nextPath); ?>">Sign in here</a>
                        </p>

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

                        <form action="register.php" method="POST" class="register-form" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token('register_form'), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="next" value="<?php echo htmlspecialchars($nextPath, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="register-honeypot" aria-hidden="true">
                                <label for="website" class="form-label">Website</label>
                                <input type="text" class="form-control" id="website" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="email" class="form-label">Email</label>
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

                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" minlength="8" autocomplete="new-password" aria-describedby="password_help" required>
                                        <button class="btn btn-outline-secondary register-toggle" type="button" data-target="password" aria-controls="password" aria-pressed="false">Show</button>
                                    </div>
                                    <div id="password_help" class="register-password-help" aria-live="polite">
                                        <div class="register-password-title">Password must include:</div>
                                        <ul class="register-password-list mb-0">
                                            <li data-rule="length">At least 8 characters</li>
                                            <li data-rule="upper">An uppercase letter (A-Z)</li>
                                            <li data-rule="lower">A lowercase letter (a-z)</li>
                                            <li data-rule="digit">A number (0-9)</li>
                                            <li data-rule="symbol">A symbol (e.g. !@#$)</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                                        <button class="btn btn-outline-secondary register-toggle" type="button" data-target="confirm_password" aria-controls="confirm_password" aria-pressed="false">Show</button>
                                    </div>
                                </div>
                            </div>

                            <p class="register-terms">
                                By signing up, you agree to our <a href="policies.php">Policies</a>.
                            </p>

                            <div class="register-actions">
                                <button type="submit" class="btn btn-gold">Create account</button>
                                <a class="register-cancel" href="index.php">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
