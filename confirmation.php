<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/booking_service.php';
require_once __DIR__ . '/includes/layout.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    redirect('booking.php');
}

$service = new BookingService();
$booking = $service->getBookingById($id);

if (!$booking) {
    redirect('booking.php');
}

renderHeader('Potvrda rezervacije', 'page-confirmation');
?>

<section class="container">
    <article class="confirmation-card fade-in">
        <h1>Rezervacija je poslata</h1>
        <p>Hvala! Vaša rezervacija je uspešno sačuvana i trenutno je na čekanju.</p>

        <div class="confirmation-details">
            <div class="detail-row">
                <span>Status</span>
                <span class="status-badge status-<?= e($booking['status']) ?>">
                    <?= e($service->formatStatusLabel($booking['status'])) ?>
                </span>
            </div>
            <div class="detail-row">
                <span>Usluga</span>
                <strong><?= e($booking['service_name']) ?></strong>
            </div>
            <div class="detail-row">
                <span>Datum</span>
                <strong><?= e($service->formatDate($booking['booking_date'])) ?></strong>
            </div>
            <div class="detail-row">
                <span>Vreme</span>
                <strong><?= e($service->formatTime($booking['booking_time'])) ?></strong>
            </div>
            <div class="detail-row">
                <span>Ime i prezime</span>
                <strong><?= e($booking['client_name']) ?></strong>
            </div>
            <div class="detail-row">
                <span>Telefon</span>
                <strong><?= e($booking['client_phone']) ?></strong>
            </div>
            <?php if (!empty($booking['note'])): ?>
                <div class="detail-row">
                    <span>Napomena</span>
                    <strong><?= e($booking['note']) ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <div class="confirmation-note">
            Salon će pregledati vašu rezervaciju i uskoro je potvrditi ili odbiti. Hvala na strpljenju.
        </div>

        <a href="<?= e(url('booking.php?moj=1')) ?>" class="btn btn-primary">Moj termin</a>
    </article>
</section>

<script>
    window.SALON_CLIENT_CONFIG = {
        bookingId: <?= (int) $booking['id'] ?>,
        phone: <?= json_encode($booking['client_phone'], JSON_UNESCAPED_UNICODE) ?>,
        name: <?= json_encode($booking['client_name'], JSON_UNESCAPED_UNICODE) ?>,
        apiMyBooking: <?= json_encode(url('api/my-booking.php'), JSON_UNESCAPED_UNICODE) ?>,
        myBookingUrl: <?= json_encode(url('booking.php?moj=1'), JSON_UNESCAPED_UNICODE) ?>,
        bookingUrl: <?= json_encode(url('booking.php'), JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="<?= e(asset('js/client-booking.js')) ?>?v=4"></script>
<script>
    SalonClientBooking.saveClient(
        window.SALON_CLIENT_CONFIG.phone,
        window.SALON_CLIENT_CONFIG.name
    );
</script>

<?php renderFooter(); ?>
