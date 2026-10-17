<?php

declare(strict_types=1);

function validateBookingDate(string $date): ?string
{
    $timezone = new DateTimeZone('Europe/Belgrade');
    $dt = DateTime::createFromFormat('Y-m-d', $date, $timezone);

    if (!$dt || $dt->format('Y-m-d') !== $date) {
        return 'Neispravan format datuma.';
    }

    $today = new DateTime('today', $timezone);
    $maxDate = (clone $today)->modify('+6 days');

    if ($dt < $today) {
        return 'Datum ne može biti u prošlosti.';
    }

    if ($dt > $maxDate) {
        return 'Možete zakazati termin samo u narednih 7 dana.';
    }

    if ((int) $dt->format('N') === 1) {
        return 'Ponedeljak je neradan dan.';
    }

    return null;
}

function validateClientName(string $name): ?string
{
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);

    if (!preg_match('/^[A-ZŠĐČĆŽ][^\s]+ [A-ZŠĐČĆŽ][^\s]+$/u', $name)) {
        return 'Unesite tačno ime i prezime (2 reči, obe počinju velikim slovom).';
    }

    return null;
}

function validateClientPhone(string $phone): ?string
{
    $phone = trim($phone);

    if (!preg_match('/^\d{10}$/', $phone)) {
        return 'Broj telefona mora imati tačno 10 cifara.';
    }

    return null;
}

function validateBookingTime(string $time): ?string
{
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        return 'Neispravan format vremena.';
    }

    return null;
}

function validateServiceId(int $serviceId, PDO $pdo): ?string
{
    $stmt = $pdo->prepare('SELECT id FROM services WHERE id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$serviceId]);

    if (!$stmt->fetch()) {
        return 'Izabrana usluga nije dostupna.';
    }

    return null;
}

function validateNote(?string $note): ?string
{
    if ($note === null || trim($note) === '') {
        return null;
    }

    if (mb_strlen(trim($note)) > 500) {
        return 'Napomena može imati najviše 500 karaktera.';
    }

    return null;
}
