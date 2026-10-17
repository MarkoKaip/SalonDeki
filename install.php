<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

$messages = [];
$errors = [];

try {
    if (!file_exists(__DIR__ . '/config/database.local.php')) {
        throw new RuntimeException('Nedostaje config/database.local.php — kopiraj ga sa servera i unesi MySQL podatke.');
    }

    if (DB_HOST === 'sqlXXX.infinityfree.com' || DB_NAME === 'if0_XXXXXXX_salon') {
        throw new RuntimeException('U database.local.php još uvek stoje placeholder vrednosti (XXX). Unesi prave podatke iz InfinityFree MySQL panela.');
    }

    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $schema = file_get_contents(__DIR__ . '/database/schema.sql');

    if ($schema === false) {
        throw new RuntimeException('Ne mogu da učitam schema.sql fajl.');
    }

    $statements = array_filter(array_map('trim', explode(';', $schema)));

    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }

        $upper = strtoupper(ltrim($statement));

        if (strpos($upper, 'CREATE DATABASE') === 0 || strpos($upper, 'USE ') === 0) {
            continue;
        }

        $pdo->exec($statement);
    }

    $pinHash = '$2y$10$5oABMm7T1duKEp9cZaqXV.DIdhlh9yXd8aNml8kSNeijJ6Gx2fMN.';
    $pinStmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $pinStmt->execute(['admin_pin_hash', $pinHash]);

    $messages[] = 'Baza podataka je uspešno instalirana.';
    $messages[] = 'Admin PIN: 2109';
    $messages[] = 'Obrišite install.php nakon instalacije.';
} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1045) {
        $errors[] = 'Pristup bazi odbijen (pogrešan user ili lozinka). Proveri database.local.php — lozinka mora biti ista kao vPanel lozinka hosting naloga za salondeki.rf.gd.';
    } elseif ((int) ($e->errorInfo[1] ?? 0) === 1049) {
        $errors[] = 'Baza ne postoji. U Control Panel → MySQL Databases kreiraj bazu "salon", pa u database.local.php stavi puno ime (npr. if0_42750910_salon).';
    } else {
        $errors[] = $e->getMessage();
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalacija | Salon Deki</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 640px; margin: 3rem auto; padding: 0 1rem; }
        .ok { background: #eaf6ef; color: #2f7a4f; padding: 1rem; border-radius: 8px; margin-bottom: 0.75rem; }
        .err { background: #fdecea; color: #a94442; padding: 1rem; border-radius: 8px; margin-bottom: 0.75rem; }
        a { color: #9a7852; }
    </style>
</head>
<body>
    <h1>Salon Deki — instalacija</h1>

    <?php foreach ($errors as $error): ?>
        <div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <?php foreach ($messages as $message): ?>
        <div class="ok"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endforeach; ?>

    <?php if ($errors === []): ?>
        <p><a href="index.php">Idi na sajt</a> | <a href="admin/login.php">Admin panel</a></p>
    <?php endif; ?>
</body>
</html>
