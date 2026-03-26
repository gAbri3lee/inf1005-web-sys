<?php
session_start();
$errors = [];
$successMessage = '';

function validate_password_strength(string $password, array &$errors): bool
{
    $password = (string)$password;
    $minLen = 8;

    if ($password === '' || mb_strlen($password) < $minLen) {
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

$formData = [
    'first_name' => '',
    'last_name' => '',
    'country' => '',
    'postal_code' => '',
    'email' => '',
    'marketing_opt_in' => false,
];

if (!isset($_SESSION['register_form_token'])) {
    $_SESSION['register_form_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $honeypot = trim($_POST['website'] ?? '');

    if (!hash_equals($_SESSION['register_form_token'], $postedToken)) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    }

    if ($honeypot !== '') {
        $errors[] = 'Unable to submit your registration request. Please try again.';
    }

    $formData['first_name'] = trim($_POST['first_name'] ?? '');
    $formData['last_name'] = trim($_POST['last_name'] ?? '');
    $formData['country'] = trim($_POST['country'] ?? '');
    $formData['postal_code'] = trim($_POST['postal_code'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['marketing_opt_in'] = isset($_POST['marketing_opt_in']);
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($formData['first_name'] === '' || mb_strlen($formData['first_name']) < 2) {
        $errors[] = 'Please enter your first name.';
    }

    if ($formData['last_name'] === '' || mb_strlen($formData['last_name']) < 2) {
        $errors[] = 'Please enter your last name.';
    }

    if ($formData['country'] === '') {
        $errors[] = 'Please select your country/region.';
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    validate_password_strength($password, $errors);

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $successMessage = 'Registration successful! You can now sign in.';
        $formData = [
            'first_name' => '',
            'last_name' => '',
            'country' => '',
            'postal_code' => '',
            'email' => '',
            'marketing_opt_in' => false,
        ];
        $_SESSION['register_form_token'] = bin2hex(random_bytes(32));
    }
}

// Inject page CSS/JS in valid locations
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
                            <h1 class="register-title">Join Azure Horizon for Free</h1>
                            <p class="register-subtitle mb-0">
                                Access member rates and tailored stay inspiration.
                            </p>
                        </header>

                        <div class="register-benefits">
                            <div class="row g-3 justify-content-center">
                                <div class="col-6 col-md-4">
                                    <div class="benefit-item">
                                        <span class="benefit-badge" aria-hidden="true">
                                            <img class="benefit-icon" src="assets/images/bedlogo.webp" alt="" role="presentation" loading="lazy">
                                        </span>
                                        <span class="benefit-text">Earn Free Nights</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="benefit-item">
                                        <span class="benefit-badge" aria-hidden="true">
                                            <img class="benefit-icon" src="assets/images/member.webp" alt="" role="presentation" loading="lazy">
                                        </span>
                                        <span class="benefit-text">Member Rates</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <div class="benefit-item">
                                        <span class="benefit-badge" aria-hidden="true">
                                            <img class="benefit-icon" src="assets/images/wifi.webp" alt="" role="presentation" loading="lazy">
                                        </span>
                                        <span class="benefit-text">Free Wi‑Fi</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="register-divider">

                        <p class="register-helper mb-4">
                            Already a member? <a href="login.php">Activate online account</a>
                        </p>

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

                        <form action="register.php" method="POST" class="register-form" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['register_form_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="register-honeypot" aria-hidden="true">
                                <label for="website" class="form-label">Website</label>
                                <input type="text" class="form-control" id="website" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="first_name"
                                        name="first_name"
                                        value="<?php echo htmlspecialchars($formData['first_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        autocomplete="given-name"
                                        required
                                    >
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="last_name"
                                        name="last_name"
                                        value="<?php echo htmlspecialchars($formData['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        autocomplete="family-name"
                                        required
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label for="country" class="form-label">Country/Region</label>
                                    <select class="form-select" id="country" name="country" required>
                                        <option value="" <?php echo $formData['country'] === '' ? 'selected' : ''; ?> disabled>Select</option>
                                        <option value="Singapore" <?php echo $formData['country'] === 'Singapore' ? 'selected' : ''; ?>>Singapore</option>
                                        <option value="USA" <?php echo $formData['country'] === 'USA' ? 'selected' : ''; ?>>USA</option>
                                        <option value="Malaysia" <?php echo $formData['country'] === 'Malaysia' ? 'selected' : ''; ?>>Malaysia</option>
                                        <option value="Indonesia" <?php echo $formData['country'] === 'Indonesia' ? 'selected' : ''; ?>>Indonesia</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="postal_code" class="form-label">Zip/Postal Code</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="postal_code"
                                        name="postal_code"
                                        value="<?php echo htmlspecialchars($formData['postal_code'], ENT_QUOTES, 'UTF-8'); ?>"
                                        autocomplete="postal-code"
                                    >
                                </div>

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
                                            <li data-rule="upper">An uppercase letter (A–Z)</li>
                                            <li data-rule="lower">A lowercase letter (a–z)</li>
                                            <li data-rule="digit">A number (0–9)</li>
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

                            <div class="register-checks">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="marketing_opt_in" name="marketing_opt_in" <?php echo $formData['marketing_opt_in'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="marketing_opt_in">
                                        I would like to receive personalised communications, including offers and promotions, via email.
                                    </label>
                                </div>
                            </div>

                            <p class="register-terms">
                                By signing up, you agree to our <a href="policies.php">Policies</a>.
                            </p>

                            <div class="register-actions">
                                <button type="submit" class="btn btn-gold">Join Today</button>
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
