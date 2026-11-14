<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/app_config.php';

$legacy = app_legacy_config();
$settings = [];
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
    $settings[$key] = app_environment($key) ?? (string) ($legacy[$key] ?? '');
}

$missing = array_filter($settings, static fn (string $value): bool => $value === '');
if ($missing !== []) {
    throw new RuntimeException('Database configuration is incomplete. Set DB_HOST, DB_PORT, DB_NAME, DB_USER, and DB_PASS.');
}
if (!ctype_digit($settings['DB_PORT']) || (int) $settings['DB_PORT'] < 1 || (int) $settings['DB_PORT'] > 65535
    || preg_match('/[;\x00\r\n]/', $settings['DB_HOST'] . $settings['DB_NAME'] . $settings['DB_USER'])) {
    throw new RuntimeException('Database configuration contains invalid connection settings.');
}

$dsn = sprintf(
    'pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
    $settings['DB_HOST'],
    (int) $settings['DB_PORT'],
    $settings['DB_NAME']
);

try {
    return new PDO(
        $dsn,
        $settings['DB_USER'],
        $settings['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log('PostgreSQL connection failed (SQLSTATE ' . (string) $exception->getCode() . ').');
    throw new RuntimeException('Database connection failed. Check the configured DB_* values and network access.');
}
