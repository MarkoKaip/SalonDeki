<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/validators.php';

function setClientSession(string $phone, string $name): void
{
    $_SESSION['client_phone'] = $phone;
    $_SESSION['client_name'] = $name;
}

function getClientPhone(): string
{
    return (string) ($_SESSION['client_phone'] ?? '');
}

function getClientName(): string
{
    return (string) ($_SESSION['client_name'] ?? '');
}

function clearClientSession(): void
{
    unset($_SESSION['client_phone'], $_SESSION['client_name']);
}

function normalizeClientPhone(string $phone): string
{
    return preg_replace('/\D+/', '', trim($phone)) ?? '';
}
