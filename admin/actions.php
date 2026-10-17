<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking_service.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/index.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
    flash('error', 'Neispravan CSRF token.');
    redirect('admin/index.php');
}

$bookingId = (int) ($_POST['booking_id'] ?? 0);
$action = trim((string) ($_POST['action'] ?? ''));
$filterType = trim((string) ($_POST['filter_type'] ?? ''));
$filterDate = trim((string) ($_POST['filter_date'] ?? ''));

$redirectQuery = http_build_query(array_filter([
    'filter_type' => $filterType !== '' ? $filterType : null,
    'filter_date' => $filterDate !== '' ? $filterDate : null,
]));

try {
    $service = new BookingService();

    if ($bookingId <= 0) {
        throw new InvalidArgumentException('Rezervacija nije pronađena.');
    }

    if ($action === 'confirm') {
        $service->updateBookingStatus($bookingId, 'confirmed');
        flash('success', 'Rezervacija je potvrđena.');
    } elseif ($action === 'cancel') {
        $service->updateBookingStatus($bookingId, 'cancelled');
        flash('success', 'Rezervacija je otkazana.');
    } else {
        throw new InvalidArgumentException('Nepoznata akcija.');
    }
} catch (Throwable $e) {
    flash('error', $e->getMessage());
}

redirect('admin/index.php' . ($redirectQuery ? '?' . $redirectQuery : ''));
