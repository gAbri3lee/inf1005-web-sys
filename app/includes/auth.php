<?php
declare(strict_types=1);

function app_is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    $forwardedProto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    return $forwardedProto === 'https';
}

function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (!headers_sent()) {
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');

        session_name('azure_horizon_session');
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
        session_regenerate_id(true);
        $_SESSION['_session_started_at'] = $now;
        $_SESSION['_session_rotated_at'] = $now;
        return;
    }

    $rotatedAt = (int)($_SESSION['_session_rotated_at'] ?? 0);
    if ($rotatedAt <= 0 || ($now - $rotatedAt) >= 900) {
        session_regenerate_id(true);
        $_SESSION['_session_rotated_at'] = $now;
    }
}

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

function auth_current_relative_url(): string
{
    $requestUri = trim((string)($_SERVER['REQUEST_URI'] ?? ''));
    if ($requestUri === '') {
        return 'index.php';
    }

    return auth_normalize_next_path($requestUri, 'index.php');
}

function auth_redirect(string $path): void
{
    header('Location: ' . $path);
    exit();
}

function auth_flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

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

function app_string_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return (int)mb_strlen($value, 'UTF-8');
    }

    return strlen($value);
}

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

function auth_is_logged_in(): bool
{
    return auth_current_user() !== null;
}

function auth_user_id(): ?int
{
    $user = auth_current_user();
    return $user ? (int)$user['id'] : null;
}

function auth_user_email(): string
{
    $user = auth_current_user();
    return $user ? (string)$user['email'] : '';
}

function auth_user_display_name(): string
{
    $user = auth_current_user();
    return $user ? (string)$user['display_name'] : '';
}

function auth_is_admin(): bool
{
    $user = auth_current_user();
    return $user ? (bool)($user['is_admin'] ?? false) : false;
}

function auth_require_admin(?string $next = null, ?string $message = null): void
{
    auth_require_login($next, $message);

    if (auth_is_admin()) {
        return;
    }

    auth_flash_set('auth_notice', $message ?: 'You do not have permission to access that area.');
    auth_redirect('dashboard.php');
}

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

function csrf_refresh(string $key): string
{
    $_SESSION['_csrf_tokens'][$key] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf_tokens'][$key];
}

function csrf_validate(string $key, ?string $submittedToken): bool
{
    $submittedToken = (string)$submittedToken;
    $sessionToken = (string)($_SESSION['_csrf_tokens'][$key] ?? '');

    return $sessionToken !== ''
        && $submittedToken !== ''
        && hash_equals($sessionToken, $submittedToken);
}

app_start_session();
