<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect('admin/login.php');
    }
}

function attemptAdminPinLogin(string $pin): bool
{
    $pin = trim($pin);

    if (!preg_match('/^\d{4}$/', $pin)) {
        return false;
    }

    $fallbackHash = '$2y$10$5oABMm7T1duKEp9cZaqXV.DIdhlh9yXd8aNml8kSNeijJ6Gx2fMN.';
    $storedHash = getSetting('admin_pin_hash');
    $valid = false;

    if ($storedHash !== '' && password_verify($pin, $storedHash)) {
        $valid = true;
    } elseif (password_verify($pin, $fallbackHash)) {
        $valid = true;
        saveAdminPinHash($fallbackHash);
    }

    if (!$valid) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_username'] = 'Admin';

    return true;
}

function saveAdminPinHash(string $hash): void
{
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute(['admin_pin_hash', $hash]);
}

function logoutAdmin(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_username']);
}

function currentAdminUsername(): string
{
    return $_SESSION['admin_username'] ?? '';
}
