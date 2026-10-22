<?php
/**
 * Gopi Travels - Backend Booking & Transaction Processor
 * Saves booking in database, assigns unique PNR, and forwards to E-Ticket view
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/gateway.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bus_id']) || empty($_POST['seat_numbers'])) {
    set_flash('danger', 'Invalid booking request.');
    header('Location: search.php');
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid security token. Please try again.');
    header('Location: search.php');
    exit;
}

$pdo = get_db_connection();
if (!$pdo) {
    die("Database connection error.");
}

$busId = (int)$_POST['bus_id'];
$journeyDate = sanitize($_POST['journey_date']);
$seatNumbersStr = sanitize($_POST['seat_numbers']);
$seatNumbers = array_filter(explode(',', $seatNumbersStr));
$seatCount = count($seatNumbers);

$passengerName = sanitize($_POST['passenger_name'] ?? 'Passenger');
$contactPhone = sanitize($_POST['contact_phone'] ?? '');
$contactEmail = sanitize($_POST['contact_email'] ?? '');
$passengersJson = $_POST['passengers_json'] ?? '[]';
$baseFare = (float)$_POST['base_fare'];
$gstAmount = (float)$_POST['gst_amount'];
$totalAmount = (float)$_POST['total_amount'];
$paymentMethod = sanitize($_POST['payment_method'] ?? 'Pay on Boarding');

// Re-verify that seats were not booked in the meantime
$alreadyBooked = get_booked_seats($busId, $journeyDate);
foreach ($seatNumbers as $st) {
    if (in_array($st, $alreadyBooked)) {
        set_flash('danger', "Seat {$st} is already booked for this date. Please select another seat.");
        header("Location: search.php?journey_date={$journeyDate}");
        exit;
    }
}

// Handle GopiPay Sandbox Simulation Outcomes
$simulationStatus = sanitize($_POST['gopipay_simulation_status'] ?? $_POST['simulation_status'] ?? 'success');
if ($simulationStatus === 'failed') {
    $declineReason = sanitize($_POST['gopipay_decline_reason'] ?? 'Payment was declined or cancelled by bank.');
    set_flash('danger', "GopiPay Transaction Declined: {$declineReason}. Please select another payment method or retry.");
    header('Location: search.php?journey_date=' . urlencode($journeyDate));
    exit;
}

// Generate unique PNR and Transaction Reference
$pnr = generate_pnr();
$txnId = !empty($_POST['transaction_id']) ? sanitize($_POST['transaction_id']) : (!empty($_POST['gopipay_txnid']) ? sanitize($_POST['gopipay_txnid']) : ('GP-' . date('ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8))));
$currentUser = current_user();
$userId = $currentUser ? (int)$currentUser['id'] : null;





try {
    $stmt = $pdo->prepare("INSERT INTO bookings (
        booking_reference, user_id, bus_id, journey_date, seat_numbers, total_passengers,
        base_fare, gst_amount, total_amount, payment_method, payment_status, transaction_id,
        passenger_name, passenger_phone, passenger_email, passenger_details, booking_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid', ?, ?, ?, ?, ?, 'Confirmed')");

    $stmt->execute([
        $pnr,
        $userId,
        $busId,
        $journeyDate,
        $seatNumbersStr,
        $seatCount,
        $baseFare,
        $gstAmount,
        $totalAmount,
        $paymentMethod,
        $txnId,
        $passengerName,
        $contactPhone,
        $contactEmail,
        $passengersJson
    ]);

    set_flash('success', "Booking confirmed successfully! Your PNR is {$pnr}.");
    header("Location: ticket.php?pnr=" . urlencode($pnr));
    exit;

} catch (Exception $e) {
    error_log("Booking Error: " . $e->getMessage());
    set_flash('danger', 'Unable to complete reservation due to a database error. Please try again.');
    header('Location: search.php');
    exit;
}
