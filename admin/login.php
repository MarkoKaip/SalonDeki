<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('admin/index.php');
}

$error = flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim((string) ($_POST['pin'] ?? ''));

    if ($pin === '') {
        flash('error', 'Unesite PIN kod.');
        redirect('admin/login.php');
    }

    if (attemptAdminPinLogin($pin)) {
        redirect('admin/index.php');
    }

    flash('error', 'Pogrešan PIN kod.');
    redirect('admin/login.php');
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin prijava | Salon Deki</title>
    <meta name="theme-color" content="#0a0a0a">
    <?php renderAppMeta(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(assetCss('variables.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetCss('base.css')) ?>">
    <link rel="stylesheet" href="<?= e(assetCss('admin.css')) ?>">
    <style>
        html, body.page-admin {
            background: #0a0a0a !important;
            color: #f5f0e6 !important;
        }
    </style>
</head>
<body class="page-admin">
    <div class="login-page">
        <div class="login-card">
            <h1>Admin prijava</h1>
            <p>Unesite PIN kod za pristup rezervacijama.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="login-form" id="adminPinForm">
                <div class="form-group">
                    <label for="pin">PIN kod</label>
                    <input type="password" id="pin" name="pin" required maxlength="4" inputmode="numeric" pattern="[0-9]{4}" autocomplete="off" placeholder="••••">
                </div>
                <div class="login-form-actions">
                    <button type="submit" class="btn btn-primary login-submit">Prijavi se</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('pin').addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });
    </script>
</body>
</html>
