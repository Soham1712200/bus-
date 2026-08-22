<?php
/**
 * Gopi Travels - Unified Real-Time Seat Engine API (Online + Offline Counter Sync)
 * 4-Color Status Matrix & Temporary Seat Locking Engine
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db_connection();
if (!$pdo) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Clean up expired seat locks (older than 5 minutes or past locked_until)
try {
    $pdo->exec("DELETE FROM seat_locks WHERE locked_until < NOW()");
} catch (Exception $e) {}

$action = isset($_REQUEST['action']) ? sanitize($_REQUEST['action']) : 'get_matrix';
$busId = isset($_REQUEST['bus_id']) ? (int)$_REQUEST['bus_id'] : 0;
$journeyDate = isset($_REQUEST['journey_date']) ? sanitize($_REQUEST['journey_date']) : date('Y-m-d');
$sessionToken = isset($_REQUEST['session_token']) ? sanitize($_REQUEST['session_token']) : session_id();

if ($busId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Valid Bus ID is required']);
    exit;
}

// 1. GET FULL SEAT MATRIX WITH 4-COLOR STATUS
if ($action === 'get_matrix') {
    try {
        // Fetch bus details
        $bStmt = $pdo->prepare("SELECT b.*, r.source_city, r.destination_city FROM buses b JOIN routes r ON b.route_id = r.id WHERE b.id = ?");
        $bStmt->execute([$busId]);
        $bus = $bStmt->fetch();

        if (!$bus) {
            echo json_encode(['status' => 'error', 'message' => 'Bus not found']);
            exit;
        }

        // Fetch Confirmed Bookings (Differentiating Online vs Counter)
        $bkStmt = $pdo->prepare("SELECT seat_numbers, booking_channel, passenger_name, booking_reference 
                                FROM bookings 
                                WHERE bus_id = ? AND journey_date = ? AND booking_status = 'Confirmed'");
        $bkStmt->execute([$busId, $journeyDate]);
        $confirmedRows = $bkStmt->fetchAll();

        $bookedOnline = [];
        $bookedOffline = [];
        $seatPassengerInfo = [];

        foreach ($confirmedRows as $row) {
            $seats = array_filter(explode(',', $row['seat_numbers']));
            foreach ($seats as $s) {
                $s = trim($s);
                if ($row['booking_channel'] === 'counter') {
                    $bookedOffline[] = $s;
                } else {
                    $bookedOnline[] = $s;
                }
                $seatPassengerInfo[$s] = [
                    'passenger' => $row['passenger_name'],
                    'pnr' => $row['booking_reference'],
                    'channel' => $row['booking_channel']
                ];
            }
        }

        // Fetch Active Seat Locks
        $lockStmt = $pdo->prepare("SELECT seat_number, session_token, locked_by_channel, locked_by_name, TIMESTAMPDIFF(SECOND, NOW(), locked_until) as seconds_left 
                                   FROM seat_locks 
                                   WHERE bus_id = ? AND journey_date = ? AND locked_until > NOW()");
        $lockStmt->execute([$busId, $journeyDate]);
        $activeLocks = $lockStmt->fetchAll();

        $lockedSeats = [];
        foreach ($activeLocks as $lock) {
            $seatNo = $lock['seat_number'];
            $isMine = ($lock['session_token'] === $sessionToken);
            $lockedSeats[$seatNo] = [
                'is_my_lock' => $isMine,
                'locked_by' => $lock['locked_by_name'],
                'channel' => $lock['locked_by_channel'],
                'seconds_left' => max(1, (int)$lock['seconds_left'])
            ];
        }

        // Count totals
        $totalBooked = count($bookedOnline) + count($bookedOffline);
        $availableCount = max(0, $bus['total_seats'] - $totalBooked - count($lockedSeats));

        echo json_encode([
            'status' => 'success',
            'bus' => $bus,
            'journey_date' => $journeyDate,
            'summary' => [
                'total_seats' => (int)$bus['total_seats'],
                'available' => $availableCount,
                'booked_online' => count($bookedOnline),
                'booked_offline' => count($bookedOffline),
                'locked' => count($lockedSeats)
            ],
            'seats' => [
                'booked_online' => $bookedOnline,
                'booked_offline' => $bookedOffline,
                'locked' => $lockedSeats,
                'passenger_meta' => $seatPassengerInfo
            ]
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// 2. TEMPORARILY LOCK SEAT (5-Minute Expiry)
if ($action === 'lock_seat') {
    $seatNumber = isset($_REQUEST['seat_number']) ? sanitize($_REQUEST['seat_number']) : '';
    $channel = isset($_REQUEST['channel']) ? sanitize($_REQUEST['channel']) : 'counter';
    $agentName = isset($_REQUEST['agent_name']) ? sanitize($_REQUEST['agent_name']) : 'Staff';

    if (empty($seatNumber)) {
        echo json_encode(['status' => 'error', 'message' => 'Seat number required']);
        exit;
    }

    try {
        // 1. Check if already confirmed booked
        $chkStmt = $pdo->prepare("SELECT id FROM bookings WHERE bus_id = ? AND journey_date = ? AND booking_status = 'Confirmed' AND FIND_IN_SET(?, seat_numbers)");
        $chkStmt->execute([$busId, $journeyDate, $seatNumber]);
        if ($chkStmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "Seat {$seatNumber} has already been booked by another passenger."]);
            exit;
        }

        // 2. Check if locked by someone else
        $lockChk = $pdo->prepare("SELECT session_token, locked_by_name FROM seat_locks WHERE bus_id = ? AND journey_date = ? AND seat_number = ? AND locked_until > NOW()");
        $lockChk->execute([$busId, $journeyDate, $seatNumber]);
        $existing = $lockChk->fetch();

        if ($existing && $existing['session_token'] !== $sessionToken) {
            echo json_encode(['status' => 'error', 'message' => "Seat {$seatNumber} is currently in progress by " . htmlspecialchars($existing['locked_by_name'])]);
            exit;
        }

        // 3. Upsert 5-minute lock
        $insStmt = $pdo->prepare("INSERT INTO seat_locks (bus_id, journey_date, seat_number, session_token, locked_by_channel, locked_by_name, locked_until)
                                  VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
                                  ON DUPLICATE KEY UPDATE 
                                    session_token = VALUES(session_token), 
                                    locked_by_channel = VALUES(locked_by_channel), 
                                    locked_by_name = VALUES(locked_by_name), 
                                    locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE)");
        $insStmt->execute([$busId, $journeyDate, $seatNumber, $sessionToken, $channel, $agentName]);

        echo json_encode([
            'status' => 'success',
            'message' => "Seat {$seatNumber} locked for 5 minutes.",
            'seat_number' => $seatNumber,
            'expires_in_seconds' => 300
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// 3. UNLOCK SEAT
if ($action === 'unlock_seat') {
    $seatNumber = isset($_REQUEST['seat_number']) ? sanitize($_REQUEST['seat_number']) : '';

    if (!empty($seatNumber)) {
        try {
            $delStmt = $pdo->prepare("DELETE FROM seat_locks WHERE bus_id = ? AND journey_date = ? AND seat_number = ? AND session_token = ?");
            $delStmt->execute([$busId, $journeyDate, $seatNumber, $sessionToken]);
            echo json_encode(['status' => 'success', 'message' => "Seat {$seatNumber} released."]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
