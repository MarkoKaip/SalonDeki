<?php

declare(strict_types=1);

function renderAppMeta(): void
{
    $appName = salonAppName();
    ?>
    <meta name="application-name" content="<?= e($appName) ?>">
    <meta name="apple-mobile-web-app-title" content="<?= e($appName) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="icon" type="image/jpeg" href="<?= e(salonAppIconUrl()) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(url('apple-touch-icon.png?v=2')) ?>">
    <link rel="apple-touch-icon-precomposed" href="<?= e(url('apple-touch-icon-precomposed.png?v=2')) ?>">
    <link rel="manifest" href="<?= e(url('manifest.php')) ?>">
    <?php
}

function renderPwaScripts(): void
{
    ?>
<script>
    window.SALON_PWA = {
        swUrl: <?= json_encode(url('sw.js'), JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="<?= e(asset('js/pwa-install.js')) ?>?v=1"></script>
    <?php
}

function renderLandingStart(string $title): void
{
    $salonName = getSetting('salon_name', 'Salon Deki');
    ?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | <?= e($salonName) ?></title>
    <meta name="theme-color" content="#0a0a0a">
    <?php renderAppMeta(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(assetCss('variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetCss('base.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetCss('home.css')) ?>">
    <style>
        html, body.page-landing {
            background: #0a0a0a !important;
            color: #f5f0e6 !important;
        }
    </style>
</head>
<body class="page-landing">
    <?php
}

function renderLandingEnd(): void
{
    renderPwaScripts();
    ?>
</body>
</html>
    <?php
}

function renderHeader(string $title, string $bodyClass = ''): void
{
    $salonName = getSetting('salon_name', 'Salon Deki');
    ?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | <?= e($salonName) ?></title>
    <meta name="theme-color" content="#0a0a0a">
    <?php renderAppMeta(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(assetCss('variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetCss('base.css')) ?>">
    <?php if ($bodyClass === 'page-home'): ?>
    <link rel="stylesheet" href="<?= e(assetCss('home.css')) ?>">
    <?php elseif ($bodyClass === 'page-booking'): ?>
    <link rel="stylesheet" href="<?= e(assetCss('booking.css')) ?>">
    <?php elseif ($bodyClass === 'page-confirmation'): ?>
    <link rel="stylesheet" href="<?= e(assetCss('booking.css')) ?>">
    <?php endif; ?>
    <style>
        html, body {
            background: #0a0a0a !important;
            color: #f5f0e6 !important;
        }
    </style>
</head>
<body class="<?= e($bodyClass) ?>">
    <header class="site-header">
        <div class="container header-inner">
            <a href="<?= e(url('index.php')) ?>" class="logo">
                <img src="<?= e(salonLogoUrl()) ?>" alt="<?= e($salonName) ?>" class="logo-image" width="56" height="56">
            </a>
            <nav class="site-nav">
                <a href="<?= e(url('index.php')) ?>">Početna</a>
                <a href="<?= e(url('booking.php')) ?>" class="nav-cta">Zakaži termin</a>
                <a href="<?= e(url('admin/login.php')) ?>" class="nav-admin">Admin</a>
            </nav>
        </div>
    </header>
    <main class="site-main">
    <?php
}

function renderFooter(): void
{
    ?>
    </main>
    <?php renderPwaScripts(); ?>
</body>
</html>
    <?php
}

function renderAdminHeader(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | Admin</title>
    <meta name="theme-color" content="#0a0a0a">
    <?php renderAppMeta(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(assetCss('variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetCss('base.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetCss('admin.css')) ?>">
</head>
<body class="page-admin">
    <header class="admin-header">
        <div class="container admin-header-inner">
            <div>
                <h1><?= e($title) ?></h1>
                <p>Prijavljen: <?= e(currentAdminUsername()) ?></p>
            </div>
            <div class="admin-header-actions">
                <a href="<?= e(url('index.php')) ?>" class="btn btn-secondary">Sajt</a>
                <a href="<?= e(url('admin/logout.php')) ?>" class="btn btn-secondary">Odjava</a>
            </div>
        </div>
    </header>
    <main class="admin-main container">
    <?php
}

function renderAdminFooter(): void
{
    ?>
    </main>
    <script src="<?= e(asset('js/admin.js')) ?>?v=3"></script>
    <?php renderPwaScripts(); ?>
</body>
</html>
    <?php
}
