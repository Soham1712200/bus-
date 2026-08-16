<?php
/**
 * Jayambe Travels - JSON API: Get Bus Seats & Availability
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$busId = isset($_GET['bus_id']) ? (int)$_GET['bus_id'] : 0;
$journeyDate = isset($_GET['journey_date']) ? sanitize($_GET['journey_date']) : date('Y-m-d');

if ($busId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid bus ID']);
    exit;
}

$pdo = get_db_connection();
if (!$pdo) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, bus_number, bus_class, total_seats, fare, is_sleeper, is_ac FROM buses WHERE id = ?");
    $stmt->execute([$busId]);
    $bus = $stmt->fetch();

    if (!$bus) {
        echo json_encode(['status' => 'error', 'message' => 'Bus not found']);
        exit;
    }

    $bookedSeats = get_booked_seats($busId, $journeyDate);

    echo json_encode([
        'status' => 'success',
        'bus' => $bus,
        'journey_date' => $journeyDate,
        'booked_seats' => $bookedSeats,
        'available_count' => max(0, $bus['total_seats'] - count($bookedSeats))
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
