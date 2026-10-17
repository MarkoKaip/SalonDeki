<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/client_session.php';
require_once __DIR__ . '/includes/booking_service.php';
require_once __DIR__ . '/includes/client_dashboard.php';
require_once __DIR__ . '/includes/layout.php';

$service = new BookingService();

if (isset($_GET['novi'])) {
    clearClientSession();
    redirect('booking.php');
}

if (isset($_GET['moj'])) {
    $phone = normalizeClientPhone(getClientPhone());

    if ($phone !== '') {
        $activeBooking = $service->getActiveBookingByPhone($phone);

        if ($activeBooking) {
            renderClientDashboard($activeBooking, $service, $phone);
            exit;
        }
    }

    redirect('booking.php');
}

$services = $service->getActiveServices();
$days = $service->getAvailableDays();
$csrfToken = csrfToken();

renderHeader('Zakaži termin', 'page-booking');
?>

<section class="booking-page">
    <div class="container">
        <div class="booking-header">
            <h1>Zakaži termin</h1>
            <p>Unesite podatke, izaberite uslugu i termin.</p>
        </div>

        <div class="progress-bar progress-bar-4">
            <div class="progress-step active" data-step-indicator="1">1. Podaci</div>
            <div class="progress-step" data-step-indicator="2">2. Usluga</div>
            <div class="progress-step" data-step-indicator="3">3. Termin</div>
            <div class="progress-step" data-step-indicator="4">4. Potvrda</div>
        </div>

        <div id="bookingAlert" class="alert alert-error" hidden></div>

        <div class="booking-step active" data-step="1">
            <h2 class="step-title">Vaši podaci</h2>
            <form class="booking-form" id="clientIdentifyForm" novalidate>
                <div class="form-group">
                    <label for="clientPhone">Broj telefona *</label>
                    <input type="tel" id="clientPhone" name="phone" required maxlength="10" inputmode="numeric" pattern="[0-9]{10}" autocomplete="tel" placeholder="0612345678">
                    <div class="field-error" data-error-for="phone"></div>
                </div>
                <div class="form-group">
                    <label for="clientName">Ime i prezime *</label>
                    <input type="text" id="clientName" name="name" required maxlength="80" autocomplete="name" placeholder="Marko Petrović">
                    <div class="field-error" data-error-for="name"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="toStep2">Nastavi</button>
            </form>
        </div>

        <div class="booking-step" data-step="2">
            <h2 class="step-title">Izaberite uslugu</h2>
            <div class="service-options">
                <?php foreach ($services as $item): ?>
                    <button type="button" class="option-card" data-service-id="<?= (int) $item['id'] ?>" data-service-name="<?= e($item['name']) ?>">
                        <h3><?= e($item['name']) ?></h3>
                        <p>Trajanje <?= (int) $item['duration_minutes'] ?> min</p>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="step-actions">
                <button type="button" class="btn btn-secondary" id="backToStep1">Nazad</button>
                <button type="button" class="btn btn-primary" id="toStep3" disabled>Nastavi</button>
            </div>
        </div>

        <div class="booking-step" data-step="3">
            <h2 class="step-title">Izaberite datum</h2>
            <div class="day-options">
                <?php foreach ($days as $day): ?>
                    <button
                        type="button"
                        class="day-card<?= $day['is_available'] ? '' : ' disabled' ?>"
                        data-date="<?= e($day['date']) ?>"
                        data-date-label="<?= e($service->formatDate($day['date'])) ?>"
                        <?= $day['is_available'] ? '' : 'disabled' ?>
                    >
                        <span class="day-name"><?= e($day['day_name']) ?></span>
                        <span class="day-date"><?= e($day['label']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <h2 class="step-title slot-step-title">Izaberite termin</h2>
            <div id="slotsContainer" class="slots-empty">Prvo izaberite datum.</div>

            <div class="step-actions">
                <button type="button" class="btn btn-secondary" id="backToStep2">Nazad</button>
                <button type="button" class="btn btn-primary" id="toStep4" disabled>Nastavi</button>
            </div>
        </div>

        <div class="booking-step" data-step="4">
            <h2 class="step-title">Potvrda rezervacije</h2>
            <div class="booking-summary" id="bookingSummary"></div>

            <form class="booking-form" id="bookingForm" novalidate>
                <div class="form-group">
                    <label for="clientNote">Napomena (opciono)</label>
                    <textarea id="clientNote" name="note" maxlength="500" placeholder="Dodatne informacije..."></textarea>
                    <div class="field-error" data-error-for="note"></div>
                </div>
                <button type="submit" class="btn btn-primary btn-block" id="submitBooking">Rezerviši termin</button>
            </form>

            <div class="step-actions">
                <button type="button" class="btn btn-secondary" id="backToStep3">Nazad</button>
            </div>
        </div>
    </div>
</section>

<script>
    window.BOOKING_CONFIG = {
        csrfToken: <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>,
        slotsUrl: <?= json_encode(url('api/slots.php'), JSON_UNESCAPED_UNICODE) ?>,
        bookUrl: <?= json_encode(url('api/book.php'), JSON_UNESCAPED_UNICODE) ?>,
        apiMyBooking: <?= json_encode(url('api/my-booking.php'), JSON_UNESCAPED_UNICODE) ?>,
        apiClientSession: <?= json_encode(url('api/client-session.php'), JSON_UNESCAPED_UNICODE) ?>,
        myBookingUrl: <?= json_encode(url('booking.php?moj=1'), JSON_UNESCAPED_UNICODE) ?>,
        bookingUrl: <?= json_encode(url('booking.php'), JSON_UNESCAPED_UNICODE) ?>
    };
</script>
<script src="<?= e(asset('js/client-booking.js')) ?>?v=6"></script>
<script src="<?= e(asset('js/booking.js')) ?>?v=7"></script>

<?php renderFooter(); ?>
