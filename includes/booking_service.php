<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/validators.php';

class BookingService
{
    private PDO $pdo;

    private static $pastBookingsPurged = false;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? getDbConnection();
    }

    public function purgePastBookings(): int
    {
        $timezone = new DateTimeZone('Europe/Belgrade');
        $now = new DateTime('now', $timezone);

        $stmt = $this->pdo->prepare(
            'DELETE FROM bookings
             WHERE booking_date < ?
                OR (booking_date = ? AND booking_time < ?)'
        );
        $stmt->execute([
            $now->format('Y-m-d'),
            $now->format('Y-m-d'),
            $now->format('H:i:s'),
        ]);

        return $stmt->rowCount();
    }

    private function purgePastBookingsIfNeeded(): void
    {
        if (self::$pastBookingsPurged) {
            return;
        }

        self::$pastBookingsPurged = true;

        try {
            $this->purgePastBookings();
        } catch (Throwable $e) {
            // Ne ruši sajt ako brisanje na hostingu ne uspe.
        }
    }

    public function getActiveServices(): array
    {
        $this->purgePastBookingsIfNeeded();

        $stmt = $this->pdo->query(
            'SELECT id, name, duration_minutes FROM services WHERE is_active = 1 ORDER BY sort_order ASC'
        );

        return $stmt->fetchAll();
    }

    public function getAvailableDays(): array
    {
        $timezone = new DateTimeZone('Europe/Belgrade');
        $days = [];
        $today = new DateTime('today', $timezone);

        for ($i = 0; $i < 7; $i++) {
            $date = (clone $today)->modify("+{$i} days");
            $dayOfWeek = (int) $date->format('N');
            $weekType = $this->resolveWeekType($date);

            $days[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $this->formatDayLabel($date),
                'day_name' => $this->dayName($dayOfWeek),
                'is_available' => $dayOfWeek !== 1,
                'week_type' => $weekType,
            ];
        }

        return $days;
    }

    public function getAvailableSlots(string $date): array
    {
        $error = validateBookingDate($date);

        if ($error !== null) {
            throw new InvalidArgumentException($error);
        }

        $dateObj = new DateTime($date);
        $dayOfWeek = (int) $dateObj->format('N');
        $weekType = $this->resolveWeekType($dateObj);
        $rule = $this->getScheduleRule($weekType, $dayOfWeek);

        if ($rule === null) {
            return [];
        }

        $allSlots = $this->generateSlots($rule['start_time'], $rule['end_time'], (int) $rule['slot_interval']);
        $bookedSlots = $this->getBookedSlots($date);

        $available = array_values(array_diff($allSlots, $bookedSlots));
        $available = $this->filterPastSlots($date, $available);

        return $available;
    }

    private function filterPastSlots(string $date, array $slots): array
    {
        $timezone = new DateTimeZone('Europe/Belgrade');
        $today = (new DateTime('today', $timezone))->format('Y-m-d');

        if ($date !== $today) {
            return $slots;
        }

        $now = new DateTime('now', $timezone);

        return array_values(array_filter(
            $slots,
            function (string $slot) use ($date, $now, $timezone): bool {
                $slotDateTime = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $slot, $timezone);

                if (!$slotDateTime) {
                    return false;
                }

                return $slotDateTime > $now;
            }
        ));
    }

    public function createBooking(array $data): int
    {
        $pdo = $this->pdo;

        $serviceId = (int) ($data['service_id'] ?? 0);
        $date = trim((string) ($data['date'] ?? ''));
        $time = trim((string) ($data['time'] ?? ''));
        $name = trim(preg_replace('/\s+/u', ' ', (string) ($data['name'] ?? '')) ?? '');
        $phone = trim((string) ($data['phone'] ?? ''));
        $note = trim((string) ($data['note'] ?? ''));

        $errors = array_filter([
            validateServiceId($serviceId, $pdo),
            validateBookingDate($date),
            validateBookingTime($time),
            validateClientName($name),
            validateClientPhone($phone),
            validateNote($note),
        ]);

        if ($errors !== []) {
            throw new InvalidArgumentException((string) reset($errors));
        }

        $slots = $this->getAvailableSlots($date);

        if (!in_array($time, $slots, true)) {
            throw new InvalidArgumentException('Izabrani termin više nije dostupan.');
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO bookings (service_id, booking_date, booking_time, client_name, client_phone, note, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $serviceId,
                $date,
                $time . ':00',
                $name,
                $phone,
                $note !== '' ? $note : null,
                'pending',
            ]);

            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                throw new InvalidArgumentException('Termin je upravo rezervisan. Izaberite drugi termin.');
            }

            throw $e;
        }
    }

    public function getBookingById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT b.*, s.name AS service_name
             FROM bookings b
             INNER JOIN services s ON s.id = b.service_id
             WHERE b.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $booking = $stmt->fetch();

        return $booking ?: null;
    }

    public function getActiveBookingByPhone(string $phone): ?array
    {
        $this->purgePastBookingsIfNeeded();

        $phone = preg_replace('/\D+/', '', trim($phone));

        if ($phone === '') {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT b.*, s.name AS service_name
             FROM bookings b
             INNER JOIN services s ON s.id = b.service_id
             WHERE b.client_phone = ? AND b.status != ?
             ORDER BY b.booking_date DESC, b.booking_time DESC, b.created_at DESC'
        );
        $stmt->execute([$phone, 'cancelled']);

        while ($row = $stmt->fetch()) {
            if ($this->isBookingActive($row)) {
                return $row;
            }
        }

        return null;
    }

    public function isBookingActive(array $booking): bool
    {
        if ($booking['status'] === 'cancelled') {
            return false;
        }

        $timezone = new DateTimeZone('Europe/Belgrade');
        $slot = DateTime::createFromFormat(
            'Y-m-d H:i',
            $booking['booking_date'] . ' ' . $this->formatTime($booking['booking_time']),
            $timezone
        );

        if (!$slot) {
            return false;
        }

        return $slot >= new DateTime('now', $timezone);
    }

    public function formatClientStatusMessage(string $status): string
    {
        switch ($status) {
            case 'confirmed':
                return 'Termin je potvrđen. Vidimo se u salonu!';
            case 'cancelled':
                return 'Rezervacija je otkazana. Možete zakazati novi termin.';
            default:
                return 'Rezervacija je na čekanju. Salon će uskoro potvrditi ili odbiti.';
        }
    }

    public function formatBookingForClient(array $booking): array
    {
        return [
            'id' => (int) $booking['id'],
            'service_name' => $booking['service_name'],
            'booking_date' => $booking['booking_date'],
            'booking_date_label' => $this->formatDate($booking['booking_date']),
            'booking_time' => $this->formatTime($booking['booking_time']),
            'client_name' => $booking['client_name'],
            'client_phone' => $booking['client_phone'],
            'note' => $booking['note'],
            'status' => $booking['status'],
            'status_label' => $this->formatStatusLabel($booking['status']),
            'status_message' => $this->formatClientStatusMessage($booking['status']),
            'is_active' => $this->isBookingActive($booking),
        ];
    }

    public function getBookings(?string $filterType = null, ?string $filterDate = null): array
    {
        $this->purgePastBookingsIfNeeded();

        $sql = 'SELECT b.*, s.name AS service_name
                FROM bookings b
                INNER JOIN services s ON s.id = b.service_id
                WHERE 1=1';
        $params = [];

        if ($filterType && $filterDate) {
            $date = DateTime::createFromFormat('Y-m-d', $filterDate);

            if ($date) {
                if ($filterType === 'day') {
                    $sql .= ' AND b.booking_date = ?';
                    $params[] = $filterDate;
                } elseif ($filterType === 'week') {
                    $weekStart = (clone $date)->modify('monday this week')->format('Y-m-d');
                    $weekEnd = (clone $date)->modify('sunday this week')->format('Y-m-d');
                    $sql .= ' AND b.booking_date BETWEEN ? AND ?';
                    $params[] = $weekStart;
                    $params[] = $weekEnd;
                } elseif ($filterType === 'month') {
                    $monthStart = $date->format('Y-m-01');
                    $monthEnd = $date->format('Y-m-t');
                    $sql .= ' AND b.booking_date BETWEEN ? AND ?';
                    $params[] = $monthStart;
                    $params[] = $monthEnd;
                }

                $sql .= " AND b.status IN ('pending', 'confirmed')";
            }
        }

        $sql .= ' ORDER BY b.booking_date ASC, b.booking_time ASC, b.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function updateBookingStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['confirmed', 'cancelled'], true)) {
            throw new InvalidArgumentException('Neispravan status.');
        }

        $stmt = $this->pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);

        return $stmt->rowCount() > 0;
    }

    public function formatStatusLabel(string $status): string
    {
        switch ($status) {
            case 'confirmed':
                return 'Potvrđeno';
            case 'cancelled':
                return 'Otkazano';
            default:
                return 'Na čekanju';
        }
    }

    public function formatDate(string $date): string
    {
        $dt = new DateTime($date);
        $days = ['', 'Ponedeljak', 'Utorak', 'Sreda', 'Četvrtak', 'Petak', 'Subota', 'Nedelja'];
        $months = [
            1 => 'januar', 2 => 'februar', 3 => 'mart', 4 => 'april',
            5 => 'maj', 6 => 'jun', 7 => 'jul', 8 => 'avgust',
            9 => 'septembar', 10 => 'oktobar', 11 => 'novembar', 12 => 'decembar',
        ];

        $dayName = $days[(int) $dt->format('N')];
        $monthName = $months[(int) $dt->format('n')];

        return sprintf('%s, %d. %s %s.', $dayName, (int) $dt->format('j'), $monthName, $dt->format('Y'));
    }

    public function formatTime(string $time): string
    {
        return substr($time, 0, 5);
    }

    private function resolveWeekType(DateTime $date): string
    {
        $today = new DateTime('today');
        $currentWeekStart = (clone $today)->modify('monday this week');
        $nextWeekStart = (clone $currentWeekStart)->modify('+7 days');

        return $date >= $nextWeekStart ? 'week_next' : 'week_current';
    }

    private function getScheduleRule(string $weekType, int $dayOfWeek): ?array
    {
        if ($dayOfWeek === 1) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT start_time, end_time, slot_interval
             FROM schedule_rules
             WHERE week_type = ? AND day_of_week = ?
             LIMIT 1'
        );
        $stmt->execute([$weekType, $dayOfWeek]);

        $rule = $stmt->fetch();

        return $rule ?: null;
    }

    private function generateSlots(string $startTime, string $endTime, int $intervalMinutes): array
    {
        $slots = [];
        $start = DateTime::createFromFormat('H:i:s', $startTime) ?: DateTime::createFromFormat('H:i', substr($startTime, 0, 5));
        $end = DateTime::createFromFormat('H:i:s', $endTime) ?: DateTime::createFromFormat('H:i', substr($endTime, 0, 5));

        if (!$start || !$end) {
            return [];
        }

        while ($start <= $end) {
            $slots[] = $start->format('H:i');
            $start->modify("+{$intervalMinutes} minutes");
        }

        return $slots;
    }

    private function getBookedSlots(string $date): array
    {
        $this->purgePastBookingsIfNeeded();

        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(booking_time, '%H:%i') AS slot
             FROM bookings
             WHERE booking_date = ? AND status != 'cancelled'"
        );
        $stmt->execute([$date]);

        return array_column($stmt->fetchAll(), 'slot');
    }

    private function formatDayLabel(DateTime $date): string
    {
        $months = ['', 'jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'avg', 'sep', 'okt', 'nov', 'dec'];

        return $date->format('j') . '. ' . $months[(int) $date->format('n')];
    }

    private function dayName(int $dayOfWeek): string
    {
        $names = ['', 'Pon', 'Uto', 'Sre', 'Čet', 'Pet', 'Sub', 'Ned'];

        return $names[$dayOfWeek] ?? '';
    }
}
