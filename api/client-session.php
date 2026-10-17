<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/client_session.php';
require_once __DIR__ . '/../includes/booking_service.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $phone = getClientPhone();
    $name = getClientName();
    $booking = null;

    if ($phone !== '') {
        $service = new BookingService();
        $active = $service->getActiveBookingByPhone($phone);

        if ($active) {
            $booking = $service->formatBookingForClient($active);
        }
    }

    jsonResponse([
        'success' => true,
        'phone' => $phone,
        'name' => $name,
        'booking' => $booking,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Metod nije dozvoljen.'], 405);
}

$payload = json_decode(file_get_contents('php://input'), true);

if (!is_array($payload)) {
    $payload = $_POST;
}

if (!verifyCsrf($payload['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'message' => 'Neispravan CSRF token.'], 403);
}

$action = trim((string) ($payload['action'] ?? ''));

if ($action === 'clear') {
    clearClientSession();
    jsonResponse(['success' => true]);
}

$phone = normalizeClientPhone((string) ($payload['phone'] ?? ''));
$name = trim(preg_replace('/\s+/u', ' ', (string) ($payload['name'] ?? '')) ?? '');

$errors = array_filter([
    validateClientPhone($phone),
    validateClientName($name),
]);

if ($errors !== []) {
    jsonResponse(['success' => false, 'message' => (string) reset($errors)], 400);
}

setClientSession($phone, $name);

try {
    $service = new BookingService();
    $active = $service->getActiveBookingByPhone($phone);

    if ($active) {
        jsonResponse([
            'success' => true,
            'has_active_booking' => true,
            'booking' => $service->formatBookingForClient($active),
            'redirect' => url('booking.php?moj=1'),
        ]);
    }

    jsonResponse([
        'success' => true,
        'has_active_booking' => false,
        'phone' => $phone,
        'name' => $name,
    ]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'Došlo je do greške.'], 500);
}
