<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/booking_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Metod nije dozvoljen.'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true);

if (!is_array($payload)) {
    $payload = $_POST;
}

if (!verifyCsrf($payload['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'message' => 'Neispravan CSRF token. Osvežite stranicu.'], 403);
}

try {
    $service = new BookingService();
    $bookingId = $service->createBooking([
        'service_id' => (int) ($payload['service_id'] ?? 0),
        'date' => (string) ($payload['date'] ?? ''),
        'time' => (string) ($payload['time'] ?? ''),
        'name' => (string) ($payload['name'] ?? ''),
        'phone' => (string) ($payload['phone'] ?? ''),
        'note' => (string) ($payload['note'] ?? ''),
    ]);

    jsonResponse([
        'success' => true,
        'booking_id' => $bookingId,
        'redirect' => url('confirmation.php?id=' . $bookingId),
    ]);
} catch (InvalidArgumentException $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Došlo je do greške. Pokušajte ponovo.'], 500);
}
