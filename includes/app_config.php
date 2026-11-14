<?php
declare(strict_types=1);

function app_environment(string $name): ?string
{
    $value = getenv($name);
    if ($value === false) {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? false;
    }
    if (!is_string($value) || trim($value) === '') {
        return null;
    }
    return trim($value);
}

function app_legacy_config(): array
{
    $path = app_environment('AP3_LEGACY_CONFIG_FILE');
    if ($path === null
        && PHP_OS_FAMILY === 'Windows'
        && app_environment('APP_ENV') !== 'production') {
        $documentRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
        if ($documentRoot !== '') {
            $path = rtrim($documentRoot, '/\\')
                . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . 'secure'
                . DIRECTORY_SEPARATOR . 'ap3_project.ini';
        }
    }

    if ($path === null || !is_readable($path)) {
        return [];
    }

    $values = parse_ini_file($path, false, INI_SCANNER_RAW);
    return is_array($values) ? $values : [];
}

function app_base_path(): string
{
    $configured = app_environment('APP_BASE_PATH');
    if ($configured !== null) {
        $path = '/' . trim($configured, '/');
        if ($path === '/') {
            return '';
        }
        if (!preg_match('#^/(?:[A-Za-z0-9._~-]+/)*[A-Za-z0-9._~-]+$#', $path)) {
            throw new RuntimeException('APP_BASE_PATH must be a URL path without a trailing slash.');
        }
        return $path;
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $directory = str_replace('\\', '/', dirname($script));
    $module = basename($directory);
    if (in_array($module, ['farmers', 'farms', 'crops', 'planting', 'harvest', 'reports'], true)) {
        $directory = str_replace('\\', '/', dirname($directory));
    }

    return $directory === '/' || $directory === '.' ? '' : rtrim($directory, '/');
}
