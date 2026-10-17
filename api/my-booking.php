<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/client_session.php';
require_once __DIR__ . '/../includes/booking_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Metod nije dozvoljen.'], 405);
}

try {
    $service = new BookingService();
    $phone = normalizeClientPhone((string) ($_GET['phone'] ?? getClientPhone()));
    $id = (int) ($_GET['id'] ?? 0);
    $booking = null;

    if ($phone !== '') {
        $booking = $service->getActiveBookingByPhone($phone);
    } elseif ($id > 0) {
        $found = $service->getBookingById($id);

        if ($found && $service->isBookingActive($found)) {
            $booking = $found;
        }
    }

    if (!$booking) {
        jsonResponse(['success' => false, 'message' => 'Rezervacija nije pronađena.'], 404);
    }

    jsonResponse([
        'success' => true,
        'booking' => $service->formatBookingForClient($booking),
    ]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Došlo je do greške.'], 500);
}
