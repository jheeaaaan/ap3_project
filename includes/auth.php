<?php
declare(strict_types=1);

const AUTH_SESSION_TIMEOUT = 1800;

require_once __DIR__ . '/app_config.php';
require_once __DIR__ . '/database_error.php';

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    $sessionPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . 'ap3_project_sessions';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0700, true);
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
    $https = app_environment('APP_ENV') === 'production'
        || (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0])) === 'https';
    $cookiePath = app_base_path();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath !== '' ? $cookiePath : '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (!empty($_SESSION['auth_user'])) {
        $now = time();
        $last = (int) ($_SESSION['last_activity'] ?? 0);
        if ($last === 0 || $now - $last > AUTH_SESSION_TIMEOUT) {
            auth_destroy_session();
            $_SESSION['auth_expired'] = true;
            return;
        }
        $_SESSION['last_activity'] = $now;
    }
}

function auth_destroy_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function auth_accounts(): array
{
    $legacy = app_legacy_config();
    $accounts = [];
    foreach ([
        'administrator' => ['AUTH_ADMIN_USERNAME', 'AUTH_ADMIN_ROLE', 'AUTH_ADMIN_PASSWORD_HASH'],
        'staff' => ['AUTH_STAFF_USERNAME', 'AUTH_STAFF_ROLE', 'AUTH_STAFF_PASSWORD_HASH'],
    ] as $key => [$usernameKey, $roleKey, $hashKey]) {
        $username = app_environment($usernameKey) ?? (string) ($legacy[$usernameKey] ?? '');
        $role = app_environment($roleKey) ?? (string) ($legacy[$roleKey] ?? '');
        $storedHash = app_environment($hashKey) ?? (string) ($legacy[$hashKey] ?? '');
        $decodedHash = base64_decode($storedHash, true);
        $hash = $decodedHash === false ? $storedHash : $decodedHash;
        if (is_string($username) && $username !== '' && is_string($hash)
            && password_get_info($hash)['algo'] !== null
            && $role === ucfirst($key)) {
            if (isset($accounts[strtolower($username)])) {
                return [];
            }
            $accounts[strtolower($username)] = [
                'username' => $username,
                'role' => $role,
                'hash' => $hash,
            ];
        }
    }
    return $accounts;
}

function auth_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function auth_valid_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function auth_require_valid_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !auth_valid_csrf()) {
        http_response_code(400);
        exit('This form could not be verified. Refresh the page and try again.');
    }
}

function auth_login(string $username, string $password): bool
{
    $submittedUsername = trim($username);
    $account = null;
    foreach (auth_accounts() as $configuredAccount) {
        if (strcasecmp($configuredAccount['username'], $submittedUsername) === 0) {
            $account = $configuredAccount;
            break;
        }
    }
    if (!$account || !password_verify($password, $account['hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION = [
        'auth_user' => $account['username'],
        'auth_role' => $account['role'],
        'last_activity' => time(),
        'csrf_token' => bin2hex(random_bytes(32)),
    ];
    return true;
}

function auth_is_logged_in(): bool
{
    return isset($_SESSION['auth_user'], $_SESSION['auth_role']);
}

function require_login(): void
{
    if (!auth_is_logged_in()) {
        $reason = !empty($_SESSION['auth_expired']) ? '?message=expired' : '';
        header('Location: ' . app_base_path() . '/login.php' . $reason);
        exit;
    }
}

function require_administrator(): void
{
    require_login();
    if ($_SESSION['auth_role'] !== 'Administrator') {
        http_response_code(403);
        exit('You do not have permission to perform this action.');
    }
}

auth_start_session();
