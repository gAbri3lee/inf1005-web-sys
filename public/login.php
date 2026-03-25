<?php
session_start();
$errors = [];
$successMessage = '';

// Temporary hardcoded login (remove when database auth is ready)
const TEMP_LOGIN_EMAIL = 'demo@azurehorizon.test';
const TEMP_LOGIN_PASSWORD = 'demo1234';
const TEMP_LOGIN_NAME = 'Demo User';

function normalize_next_path(string $next): string
{
    $next = trim($next);
    if ($next === '') {
        return 'index.php';
    }

    // Only allow simple local php filenames (prevents open redirects)
    if (!preg_match('/^[A-Za-z0-9_-]+\.php$/', $next)) {
        return 'index.php';
    }

    return $next;
}

$formData = [
    'email' => ''
];

$nextPath = normalize_next_path((string)($_GET['next'] ?? 'index.php'));

if (!isset($_SESSION['login_form_token'])) {
    $_SESSION['login_form_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $honeypot = trim($_POST['website'] ?? '');

    if (!hash_equals($_SESSION['login_form_token'], $postedToken)) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    }

    if ($honeypot !== '') {
        $errors[] = 'Unable to submit your login request. Please try again.';
    }

    $formData['email'] = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (!$errors) {
        $postedNext = normalize_next_path((string)($_POST['next'] ?? $nextPath));

        if (
            strcasecmp($formData['email'], TEMP_LOGIN_EMAIL) === 0
            && hash_equals(TEMP_LOGIN_PASSWORD, $password)
        ) {
            $_SESSION['user_id'] = 1;
            $_SESSION['full_name'] = TEMP_LOGIN_NAME;
            $_SESSION['login_form_token'] = bin2hex(random_bytes(32));
            header('Location: ' . $postedNext);
            exit();
        }

        $errors[] = 'Invalid email or password.';
    }
}

// Inject page CSS into <head> (W3C-valid)
$pageStylesheets = ['assets/css/login.css'];

include __DIR__ . '/../app/includes/navbar.php';
?>

<main class="auth-page">
    <section class="auth-hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-xl-7 reveal-up">
                    <span class="section-eyebrow">Guest login</span>
                    <h1 class="auth-title">Welcome back to Azure Horizon</h1>
                    <p class="auth-subtitle mb-0">Sign in to manage your bookings, profile, and preferences.</p>
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

                        <?php if ($errors): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $message): ?>
                                        <li><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($successMessage): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="POST" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['login_form_token'], ENT_QUOTES, 'UTF-8'); ?>">
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

                        <p class="auth-footer-text small mb-0">
                            Temporary demo login:
                            <strong><?php echo htmlspecialchars(TEMP_LOGIN_EMAIL, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </p>
                        <p class="auth-footer-text mb-0">Don't have an account? <a href="register.php">Create one here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
