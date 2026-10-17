<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking_service.php';
require_once __DIR__ . '/../includes/layout.php';

requireAdmin();

$filterType = trim((string) ($_GET['filter_type'] ?? ''));
$filterDate = trim((string) ($_GET['filter_date'] ?? date('Y-m-d')));
$service = new BookingService();
$bookings = $service->getBookings(
    in_array($filterType, ['day', 'week', 'month'], true) ? $filterType : null,
    $filterDate !== '' ? $filterDate : null
);
$csrfToken = csrfToken();
$success = flash('success');
$error = flash('error');

renderAdminHeader('Rezervacije');
?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<section class="filters-bar">
    <form method="get" class="filters-row" id="filtersForm">
        <label>
            Prikaz
            <select name="filter_type" id="filterType">
                <option value="">Sve rezervacije</option>
                <option value="day" <?= $filterType === 'day' ? 'selected' : '' ?>>Po danu</option>
                <option value="week" <?= $filterType === 'week' ? 'selected' : '' ?>>Po nedelji</option>
                <option value="month" <?= $filterType === 'month' ? 'selected' : '' ?>>Po mesecu</option>
            </select>
        </label>
        <label id="filterDateWrap" <?= $filterType === '' ? 'hidden' : '' ?>>
            Datum
            <input type="date" name="filter_date" id="filterDate" value="<?= e($filterDate) ?>">
        </label>
        <button type="submit" class="btn btn-primary btn-small">Primeni filter</button>
        <a href="<?= e(url('admin/index.php')) ?>" class="btn btn-secondary btn-small">Reset</a>
    </form>
</section>

<div class="bookings-table-wrap">
    <?php if ($bookings === []): ?>
        <div class="empty-state">Nema rezervacija za izabrani prikaz.</div>
    <?php else: ?>
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Vreme</th>
                    <th>Usluga</th>
                    <th>Klijent</th>
                    <th>Telefon</th>
                    <th>Status</th>
                    <th>Akcije</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= e($service->formatDate($booking['booking_date'])) ?></td>
                        <td><?= e($service->formatTime($booking['booking_time'])) ?></td>
                        <td><?= e($booking['service_name']) ?></td>
                        <td><?= e($booking['client_name']) ?></td>
                        <td><a href="tel:<?= e(preg_replace('/\s+/', '', $booking['client_phone'])) ?>"><?= e($booking['client_phone']) ?></a></td>
                        <td>
                            <span class="status-badge status-<?= e($booking['status']) ?>">
                                <?= e($service->formatStatusLabel($booking['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($booking['status'] === 'pending'): ?>
                                <div class="table-actions">
                                    <form method="post" action="<?= e(url('admin/actions.php')) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                        <input type="hidden" name="action" value="confirm">
                                        <input type="hidden" name="filter_type" value="<?= e($filterType) ?>">
                                        <input type="hidden" name="filter_date" value="<?= e($filterDate) ?>">
                                        <button type="submit" class="btn btn-success btn-small">Potvrdi</button>
                                    </form>
                                    <form method="post" action="<?= e(url('admin/actions.php')) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="filter_type" value="<?= e($filterType) ?>">
                                        <input type="hidden" name="filter_date" value="<?= e($filterDate) ?>">
                                        <button type="submit" class="btn btn-danger btn-small">Otkaži</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span style="color: var(--color-text-muted); font-size: 0.9rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (!empty($booking['note'])): ?>
                        <tr>
                            <td colspan="7" style="background:var(--color-surface-elevated); color: var(--color-text-muted); font-size:0.92rem;">
                                Napomena: <?= e($booking['note']) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php renderAdminFooter(); ?>
