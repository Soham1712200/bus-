<?php
/**
 * Gopi Travels - Backend Booking & Transaction Processor
 * Saves booking in database, assigns unique PNR, and forwards to E-Ticket view
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/stripe.php';
require_once __DIR__ . '/vendor/autoload.php';

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
$paymentMethod = sanitize($_POST['payment_method'] ?? 'UPI');

// Re-verify that seats were not booked in the meantime
$alreadyBooked = get_booked_seats($busId, $journeyDate);
foreach ($seatNumbers as $st) {
    if (in_array($st, $alreadyBooked)) {
        set_flash('danger', "Seat {$st} is already booked for this date. Please select another seat.");
        header("Location: search.php?journey_date={$journeyDate}");
        exit;
    }
}

// Generate unique PNR and default Transaction ID
$pnr = generate_pnr();
$txnId = generate_txn_id();
$currentUser = current_user();
$userId = $currentUser ? (int)$currentUser['id'] : null;

// Handle Stripe Payment
if (stripos($paymentMethod, 'Stripe') !== false && !empty($_POST['stripeToken'])) {
    try {
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        $charge = \Stripe\Charge::create([
            'amount' => round($totalAmount * 100), // amount in paise
            'currency' => 'inr',
            'description' => "Booking PNR: {$pnr}",
            'source' => sanitize($_POST['stripeToken']),
        ]);
        $txnId = 'STRIPE-' . $charge->id;
    } catch (\Stripe\Exception\CardException $e) {
        set_flash('danger', 'Card was declined: ' . $e->getError()->message);
        header("Location: search.php?journey_date={$journeyDate}");
        exit;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        set_flash('danger', 'Payment Gateway error. Please try again later.');
        header("Location: search.php?journey_date={$journeyDate}");
        exit;
    }
}

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
