<?php
// This file contains authentication-related functions and session management for the Horizon Sands web application. It provides utilities for handling user login, logout, session security, and access control, as well as CSRF token generation and validation to protect against cross-site request forgery attacks.
// The functions in this file are designed to be reusable across different parts of the application, ensuring consistent authentication behavior and security practices throughout the site. By centralizing authentication logic here, we can easily manage user sessions and access control in a secure and efficient manner.
declare(strict_types=1);

// Check if the request is made over HTTPS, considering various server configurations and proxy settings to ensure accurate detection of secure connections.
function app_is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;

    // If the host is localhost or a loopback address, we should not trust proxy headers for HTTPS detection, as they can be easily spoofed in a local development environment.
    $isLocalHost = $host === 'localhost' || $host === '127.0.0.1' || $host === '::1';
    if ($isLocalHost) {
        return false;
    }

    // Check if the application is configured to trust proxy headers for HTTPS detection. This is important when the application is behind a reverse proxy or load balancer that terminates SSL/TLS connections and forwards requests to the application server over HTTP.
    $trustProxy = strtolower(trim((string)(getenv('APP_TRUST_PROXY') ?: ($_SERVER['APP_TRUST_PROXY'] ?? ''))));
    $trustProxy = in_array($trustProxy, ['1', 'true', 'yes', 'on'], true);

    // If trusting proxy headers, check the X-Forwarded-Proto header to determine if the original request was made over HTTPS. This header is commonly set by proxies to indicate the protocol used by the client when connecting to the proxy.
    if ($trustProxy) {
        $forwardedProto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        return $forwardedProto === 'https';
    }

    return false;
}

// Generate a unique session name based on the project directory name to avoid conflicts with other applications that may be running on the same server. This helps ensure that session data is properly isolated and prevents issues with session collisions.
function app_session_name(): string
{
    $projectRoot = dirname(__DIR__, 2);
    $projectSlug = preg_replace('/[^A-Za-z0-9_]+/', '_', (string)basename($projectRoot)) ?? 'app';
    $projectSlug = trim($projectSlug, '_');

    //  If the resulting slug is empty after sanitization, default to 'app' to ensure we have a valid session name. This can happen if the project directory name contains only invalid characters.
    if ($projectSlug === '') {
        $projectSlug = 'app';
    }

    return 'azure_horizon_session_' . $projectSlug;
}

// Start the session with secure settings and implement session rotation to enhance security. This function ensures that sessions are properly initialized and protected against common session-related vulnerabilities, such as session fixation and hijacking.
function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Always set the session name before starting the session.
    // If this is skipped, PHP may fall back to PHPSESSID and create a separate session,
    // which can look like "login needs two tries".
    $desiredSessionName = app_session_name();
    if (session_name() !== $desiredSessionName) {
        session_name($desiredSessionName);
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');

    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => app_is_https_request(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    session_start();

    $now = time();
    if (!isset($_SESSION['_session_started_at'])) {
        session_regenerate_id(false);
        $_SESSION['_session_started_at'] = $now;
        $_SESSION['_session_rotated_at'] = $now;
        return;
    }

    $rotatedAt = (int)($_SESSION['_session_rotated_at'] ?? 0);
    if ($rotatedAt <= 0 || ($now - $rotatedAt) >= 900) {
        session_regenerate_id(false);
        $_SESSION['_session_rotated_at'] = $now;
    }
}

// Normalize the "next" path for redirection after login or access control checks. This function ensures that the provided path is valid, does not contain potentially dangerous characters, and falls back to a safe default if the input is invalid. This helps prevent open redirect vulnerabilities and ensures that users are redirected to appropriate locations within the application.
function auth_normalize_next_path(?string $next, string $fallback = 'index.php'): string
{
    $next = trim((string)$next);
    if ($next === '') {
        return $fallback;
    }

    if (preg_match('#^(?:[a-z]+:)?//#i', $next)) {
        return $fallback;
    }

    $next = ltrim($next, '/');
    if ($next === '') {
        return $fallback;
    }

    if (!preg_match('/^[A-Za-z0-9_\/-]+\.php(?:\?[A-Za-z0-9_.~%\-=&\[\]]*)?$/', $next)) {
        return $fallback;
    }

    return $next;
}

// Get the current relative URL for redirection purposes. This function retrieves the request URI from the server variables, normalizes it using the same logic as the "next" path normalization, and ensures that it falls back to a safe default if the request URI is empty or invalid. This allows the application to redirect users back to their original destination after login or access control checks.
function auth_current_relative_url(): string
{
    $requestUri = trim((string)($_SERVER['REQUEST_URI'] ?? ''));
    if ($requestUri === '') {
        return 'index.php';
    }

    return auth_normalize_next_path($requestUri, 'index.php');
}

// Redirect to a specified path and exit the script. This function is used for redirecting users after login, logout, or access control checks. It ensures that the redirection is performed correctly and that the script execution is halted immediately after sending the redirect header.
function auth_redirect(string $path): void
{
    header('Location: ' . $path);
    exit();
}

// Flash message handling for authentication-related notices. These functions allow setting and retrieving temporary messages that can be displayed to users after certain actions, such as login failures or access denials. The messages are stored in the session and are designed to be shown once before being cleared, providing a user-friendly way to communicate important information.
function auth_flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

// Retrieve and clear a flash message from the session. This function checks for the existence of a flash message with the specified key, returns it if found, and then removes it from the session to ensure that it is only displayed once. If no message is found for the given key, it returns null.
function auth_flash_get(string $key): ?string
{
    $messages = $_SESSION['_flash'] ?? [];
    if (!is_array($messages) || !array_key_exists($key, $messages)) {
        return null;
    }

    $message = (string)$messages[$key];
    unset($_SESSION['_flash'][$key]);

    if (!$_SESSION['_flash']) {
        unset($_SESSION['_flash']);
    }

    return $message;
}

// Build a display name from an email address by extracting the local part, removing special characters, and formatting it in a user-friendly way. This function is used to generate a default display name for users who do not have a full name or display name set in their profile, providing a more personalized experience based on their email address.
function auth_build_display_name(string $email): string
{
    $localPart = trim((string)strtok(strtolower($email), '@'));
    $localPart = preg_replace('/[^a-z0-9._-]+/i', '', $localPart) ?? '';
    $localPart = str_replace(['.', '_', '-'], ' ', $localPart);
    $localPart = trim(preg_replace('/\s+/', ' ', $localPart) ?? '');

    if ($localPart === '') {
        return 'Guest';
    }

    $displayName = ucwords($localPart);
    return app_string_substr($displayName, 0, 100);
}

// Helper functions for multibyte string handling to ensure proper support for UTF-8 encoded strings. These functions check for the availability of the mbstring extension and use it if available, falling back to standard string functions if not. This allows the application to handle international characters and emojis correctly in user display names and other string manipulations.
function app_string_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return (int)mb_strlen($value, 'UTF-8');
    }

    return strlen($value);
}

// Helper function for substring extraction that supports multibyte characters. This function checks for the availability of the mbstring extension and uses it if available, ensuring that substrings are extracted correctly without breaking multibyte characters. If mbstring is not available, it falls back to the standard substr function, which may not handle multibyte characters properly but provides a basic fallback.
function app_string_substr(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null
            ? (string)mb_substr($value, $start, null, 'UTF-8')
            : (string)mb_substr($value, $start, $length, 'UTF-8');
    }

    return $length === null
        ? (string)substr($value, $start)
        : (string)substr($value, $start, $length);
}

// Log in a user by storing their information in the session. This function takes a user record as input, validates the necessary fields, and then sets the appropriate session variables to indicate that the user is logged in. It also regenerates the session ID to prevent session fixation attacks and ensures that the user's display name is properly set based on their email if a full name or display name is not provided.
function auth_login_user(array $user): void
{
    $userId = (int)($user['id'] ?? 0);
    $email = strtolower(trim((string)($user['email'] ?? '')));
    $displayName = trim((string)($user['full_name'] ?? $user['display_name'] ?? ''));
    $isAdmin = (int)($user['is_admin'] ?? 0) === 1;

    if ($userId <= 0 || $email === '') {
        throw new InvalidArgumentException('Invalid user record supplied for login.');
    }

    if ($displayName === '') {
        $displayName = auth_build_display_name($email);
    }

    session_regenerate_id(true);

    $_SESSION['auth_user'] = [
        'id' => $userId,
        'email' => $email,
        'display_name' => $displayName,
        'is_admin' => $isAdmin,
        'logged_in_at' => time(),
    ];
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['full_name'] = $displayName;
    $_SESSION['is_admin'] = $isAdmin;
}

// Get the currently logged-in user's information from the session. This function checks if the user is logged in by verifying the presence of the 'auth_user' key in the session and validating its contents. It returns an associative array with the user's information if they are logged in, or null if no user is currently authenticated. This allows other parts of the application to easily access the current user's details and determine their authentication status.
function auth_current_user(): ?array
{
    $user = $_SESSION['auth_user'] ?? null;
    if (!is_array($user)) {
        return null;
    }

    $userId = (int)($user['id'] ?? 0);
    $email = trim((string)($user['email'] ?? ''));
    if ($userId <= 0 || $email === '') {
        return null;
    }

    $user['display_name'] = trim((string)($user['display_name'] ?? auth_build_display_name($email)));
    return $user;
}

// Check if a user is currently logged in by verifying the presence of a valid user record in the session. This function relies on the auth_current_user function to determine if there is an authenticated user and returns a boolean value indicating the login status. It provides a simple way for other parts of the application to check if a user is logged in without needing to access the session directly.
function auth_is_logged_in(): bool
{
    return auth_current_user() !== null;
}

// Get the currently logged-in user's ID from the session. This function retrieves the current user's information using auth_current_user and returns their ID as an integer. If no user is logged in, it returns null. This allows other parts of the application to easily access the current user's ID for various purposes, such as database queries or access control checks.
function auth_user_id(): ?int
{
    $user = auth_current_user();
    return $user ? (int)$user['id'] : null;
}

// Get the currently logged-in user's email from the session. This function retrieves the current user's information using auth_current_user and returns their email as a string. If no user is logged in, it returns an empty string. This allows other parts of the application to easily access the current user's email for display purposes or other logic that may require the user's email address.
function auth_user_email(): string
{
    $user = auth_current_user();
    return $user ? (string)$user['email'] : '';
}

// Get the currently logged-in user's display name from the session. This function retrieves the current user's information using auth_current_user and returns their display name as a string. If no user is logged in, it returns an empty string. The display name is generated based on the user's full name, display name, or email address, providing a user-friendly way to refer to the user throughout the application.
function auth_user_display_name(): string
{
    $user = auth_current_user();
    return $user ? (string)$user['display_name'] : '';
}

// Check if the currently logged-in user has administrative privileges. This function retrieves the current user's information using auth_current_user and checks the 'is_admin' flag to determine if the user is an administrator. It returns a boolean value indicating whether the user has admin rights, allowing other parts of the application to enforce access control based on user roles.
function auth_is_admin(): bool
{
    $user = auth_current_user();
    return $user ? (bool)($user['is_admin'] ?? false) : false;
}

// Require that a user is logged in to access a certain page or perform a specific action. If the user is not logged in, they will be redirected to the login page with an optional message and a "next" parameter that indicates where they should be redirected after successful login. This function helps enforce access control for authenticated areas of the application and provides a seamless user experience by redirecting users back to their intended destination after logging in.
function auth_require_admin(?string $next = null, ?string $message = null): void
{
    auth_require_login($next, $message);

    if (auth_is_admin()) {
        return;
    }

    auth_flash_set('auth_notice', $message ?: 'You do not have permission to access that area.');
    auth_redirect('dashboard.php');
}

// Log out the current user by clearing their session data and destroying the session. This function ensures that all session variables related to authentication are removed, and it also handles the proper deletion of the session cookie to prevent any residual session data from being accessible after logout. This helps ensure that users are securely logged out and that their session cannot be hijacked or reused by malicious actors.
function auth_logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool)($params['secure'] ?? false),
                'httponly' => (bool)($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

// Require that a user is logged in to access a certain page or perform a specific action. If the user is not logged in, they will be redirected to the login page with an optional message and a "next" parameter that indicates where they should be redirected after successful login. This function helps enforce access control for authenticated areas of the application and provides a seamless user experience by redirecting users back to their intended destination after logging in.
function auth_require_login(?string $next = null, ?string $message = null): void
{
    if (auth_is_logged_in()) {
        return;
    }

    if ($message !== null && $message !== '') {
        auth_flash_set('auth_notice', $message);
    }

    $destination = auth_normalize_next_path($next ?? auth_current_relative_url(), 'dashboard.php');
    auth_redirect('login.php?next=' . rawurlencode($destination));
}

// CSRF token management functions to protect against cross-site request forgery attacks. These functions allow for the generation, refreshing, and validation of CSRF tokens that can be used in forms and AJAX requests to ensure that actions are being performed by authenticated users and not by malicious actors. By implementing CSRF protection, we can enhance the security of the application and prevent unauthorized actions from being executed on behalf of users.
function csrf_token(string $key): string
{
    if (!isset($_SESSION['_csrf_tokens']) || !is_array($_SESSION['_csrf_tokens'])) {
        $_SESSION['_csrf_tokens'] = [];
    }

    if (!isset($_SESSION['_csrf_tokens'][$key]) || !is_string($_SESSION['_csrf_tokens'][$key])) {
        $_SESSION['_csrf_tokens'][$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_tokens'][$key];
}

// Refresh the CSRF token for a given key by generating a new random token and storing it in the session. This function can be used to invalidate old tokens and ensure that only the most recent token is valid for a particular action or form. By refreshing tokens periodically, we can further enhance the security of the application and reduce the risk of token reuse in CSRF attacks.
function csrf_refresh(string $key): string
{
    $_SESSION['_csrf_tokens'][$key] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf_tokens'][$key];
}

// Validate a submitted CSRF token against the token stored in the session for a given key. This function checks if both the submitted token and the session token are present and non-empty, and then uses hash_equals to compare them in a timing-attack resistant manner. If the tokens match, it returns true, indicating that the request is valid; otherwise, it returns false, indicating a potential CSRF attack.
function csrf_validate(string $key, ?string $submittedToken): bool
{
    $submittedToken = (string)$submittedToken;
    $sessionToken = (string)($_SESSION['_csrf_tokens'][$key] ?? '');

    return $sessionToken !== ''
        && $submittedToken !== ''
        && hash_equals($sessionToken, $submittedToken);
}

app_start_session();
