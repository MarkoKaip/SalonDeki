<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Europe/Belgrade');

function basePath(string $path = ''): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    if (basename($base) === 'admin' || basename($base) === 'api') {
        $base = dirname($base);
    }

    $base = $base === '/' || $base === '\\' ? '' : $base;

    if ($path === '') {
        return $base === '' ? '' : $base;
    }

    return ($base === '' ? '' : $base) . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return basePath('assets/' . ltrim($path, '/'));
}

function assetCss(string $file): string
{
    return asset('css/' . ltrim($file, '/')) . '?v=6';
}

function salonLogoUrl(): string
{
    return asset('logo.png') . '?v=1';
}

function salonAppIconUrl(): string
{
    return asset('apple-touch-icon.jpg') . '?v=2';
}

function salonAppIconLargeUrl(): string
{
    return asset('icon-512.jpg') . '?v=2';
}

function salonAppIconMaskableUrl(): string
{
    return asset('icon-512-maskable.png') . '?v=2';
}

function salonAppName(): string
{
    return 'Deki Frizerski Salon';
}

function url(string $path = ''): string
{
    return basePath($path);
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getSetting(string $key, string $default = ''): string
{
    static $settings = null;

    if ($settings === null) {
        $pdo = getDbConnection();
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
        $settings = [];

        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $settings[$key] ?? $default;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $value;
}
