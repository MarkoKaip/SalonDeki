<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/booking_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Metod nije dozvoljen.'], 405);
}

$date = trim((string) ($_GET['date'] ?? ''));

if ($date === '') {
    jsonResponse(['success' => false, 'message' => 'Datum je obavezan.'], 400);
}

try {
    $service = new BookingService();
    $slots = $service->getAvailableSlots($date);

    jsonResponse([
        'success' => true,
        'date' => $date,
        'slots' => $slots,
    ]);
} catch (InvalidArgumentException $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Došlo je do greške. Pokušajte ponovo.'], 500);
}
