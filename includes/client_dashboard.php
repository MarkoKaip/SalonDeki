<?php

declare(strict_types=1);

function renderClientDashboard(array $booking, BookingService $service, string $phone): void
{
    $clientBooking = $service->formatBookingForClient($booking);
    setClientSession($phone, $booking['client_name']);
    $csrfToken = csrfToken();

    renderHeader('Moj termin', 'page-confirmation');
    ?>
<section class="container">
    <article class="confirmation-card fade-in">
        <div class="client-dashboard-head">
            <div>
                <h1>Moj termin</h1>
                <p class="client-greeting">Zdravo, <strong><?= e($booking['client_name']) ?></strong></p>
            </div>
            <a href="<?= e(url('booking.php?novi=1')) ?>" class="client-switch-link">Nisi ti? Promeni ime</a>
        </div>

        <p id="clientStatusMessage"><?= e($clientBooking['status_message']) ?></p>

        <div class="confirmation-details">
            <div class="detail-row">
                <span>Status</span>
                <span class="status-badge status-<?= e($clientBooking['status']) ?>" id="clientStatusBadge">
                    <?= e($clientBooking['status_label']) ?>
                </span>
            </div>
            <div class="detail-row">
                <span>Usluga</span>
                <strong><?= e($clientBooking['service_name']) ?></strong>
            </div>
            <div class="detail-row">
                <span>Datum</span>
                <strong><?= e($clientBooking['booking_date_label']) ?></strong>
            </div>
            <div class="detail-row">
                <span>Vreme</span>
                <strong><?= e($clientBooking['booking_time']) ?></strong>
            </div>
            <div class="detail-row">
                <span>Telefon</span>
                <strong><?= e($clientBooking['client_phone']) ?></strong>
            </div>
            <?php if (!empty($clientBooking['note'])): ?>
                <div class="detail-row">
                    <span>Napomena</span>
                    <strong><?= e($clientBooking['note']) ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <div class="confirmation-note" id="clientStatusNote">
            <?php if ($clientBooking['status'] === 'confirmed'): ?>
                Termin je odobren. Ako ne možete doći, javite se salonu na vreme.
            <?php elseif ($clientBooking['status'] === 'cancelled'): ?>
                Rezervacija je otkazana. Možete zakazati novi termin.
            <?php else: ?>
                Salon pregleda rezervaciju. Status će se ažurirati ovde kada admin potvrdi ili otkaže.
            <?php endif; ?>
        </div>

        <div class="step-actions">
            <a href="<?= e(url('index.php')) ?>" class="btn btn-secondary">Početna</a>
            <?php if (!$clientBooking['is_active'] || $clientBooking['status'] === 'cancelled'): ?>
                <a href="<?= e(url('booking.php')) ?>" class="btn btn-primary">Zakaži novi termin</a>
            <?php else: ?>
                <button type="button" class="btn btn-primary" id="refreshBookingBtn">Osveži status</button>
            <?php endif; ?>
        </div>
    </article>
</section>

<script>
    window.SALON_CLIENT_CONFIG = {
        csrfToken: <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>,
        phone: <?= json_encode($phone, JSON_UNESCAPED_UNICODE) ?>,
        apiMyBooking: <?= json_encode(url('api/my-booking.php'), JSON_UNESCAPED_UNICODE) ?>,
        bookingUrl: <?= json_encode(url('booking.php'), JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="<?= e(asset('js/client-booking.js')) ?>?v=4"></script>
<script>
    SalonClientBooking.saveClient(
        window.SALON_CLIENT_CONFIG.phone,
        <?= json_encode($booking['client_name'], JSON_UNESCAPED_UNICODE) ?>
    );

    const refreshBtn = document.getElementById('refreshBookingBtn');

    if (refreshBtn) {
        refreshBtn.addEventListener('click', async () => {
            refreshBtn.disabled = true;
            refreshBtn.textContent = 'Osvežavanje...';

            const response = await fetch(
                `${window.SALON_CLIENT_CONFIG.apiMyBooking}?phone=${encodeURIComponent(window.SALON_CLIENT_CONFIG.phone)}`
            );
            const data = await response.json();

            if (!response.ok || !data.success) {
                window.location.href = window.SALON_CLIENT_CONFIG.bookingUrl;
                return;
            }

            const booking = data.booking;
            document.getElementById('clientStatusBadge').textContent = booking.status_label;
            document.getElementById('clientStatusBadge').className = `status-badge status-${booking.status}`;
            document.getElementById('clientStatusMessage').textContent = booking.status_message;

            if (!booking.is_active || booking.status === 'cancelled') {
                window.location.href = window.SALON_CLIENT_CONFIG.bookingUrl;
                return;
            }

            refreshBtn.disabled = false;
            refreshBtn.textContent = 'Osveži status';
        });
    }
</script>
    <?php
    renderFooter();
}
