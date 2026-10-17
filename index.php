<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$salonName = getSetting('salon_name', 'Salon Deki');
$csrfToken = csrfToken();

renderLandingStart('Početna');
?>

<div class="landing-page">
    <img
        src="<?= e(salonLogoUrl()) ?>"
        alt="<?= e($salonName) ?>"
        class="landing-logo-image"
        width="168"
        height="168"
        loading="eager"
        decoding="async"
    >

    <h1 class="landing-title">Zakaži svoje šišanje 🪮</h1>

    <a href="<?= e(url('booking.php')) ?>" class="btn btn-primary landing-cta" id="landingCta">✂️ Zakaži termin</a>

    <button type="button" class="btn btn-secondary landing-install" id="pwaInstallBtn">⬇ Preuzmi aplikaciju</button>
    <p class="landing-install-hint" id="pwaInstallHint">Ako nema dugmeta za instalaciju u Chrome-u: meni ⋮ pa Instaliraj aplikaciju.</p>

    <a href="<?= e(url('admin/login.php')) ?>" class="landing-admin">Admin prijava</a>
</div>

<script>
    window.SALON_CLIENT_CONFIG = {
        csrfToken: <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>,
        apiMyBooking: <?= json_encode(url('api/my-booking.php'), JSON_UNESCAPED_UNICODE) ?>,
        apiClientSession: <?= json_encode(url('api/client-session.php'), JSON_UNESCAPED_UNICODE) ?>,
        myBookingUrl: <?= json_encode(url('booking.php?moj=1'), JSON_UNESCAPED_UNICODE) ?>,
        bookingUrl: <?= json_encode(url('booking.php'), JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="<?= e(asset('js/client-booking.js')) ?>?v=4"></script>
<script>
    SalonClientBooking.initLandingPage(window.SALON_CLIENT_CONFIG);
</script>

<?php renderLandingEnd(); ?>
