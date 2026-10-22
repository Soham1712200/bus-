<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$payment = $_SESSION["payment"] ?? null;

if (!$payment) {
    header("Location: index.php");
    exit;
}

function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

$success = $payment["status"] === "success";
$pnr = $_SESSION['payment']['pnr'] ?? null;

// If payment succeeded and there was a pending bus booking, finalize it in the database
if ($success && isset($_SESSION['pending_booking'])) {
    $bookingData = $_SESSION['pending_booking'];
    $busId = (int)$bookingData['bus_id'];
    $journeyDate = sanitize($bookingData['journey_date'] ?? date('Y-m-d'));
    $seatNumbersStr = sanitize($bookingData['seat_numbers']);
    $seatNumbers = array_filter(explode(',', $seatNumbersStr));
    $seatCount = count($seatNumbers);

    $customer = sanitize($payment['customer']);
    $contactPhone = sanitize($bookingData['contact_phone'] ?? '');
    $contactEmail = sanitize($bookingData['contact_email'] ?? '');
    $passengersJson = $bookingData['passengers_json'] ?? json_encode([['name' => $customer, 'age' => 30, 'gender' => 'Male', 'seat' => $seatNumbersStr]]);
    $baseFare = (float)($bookingData['base_fare'] ?? ((float)$payment['amount'] / 1.05));
    $gstAmount = (float)($bookingData['gst_amount'] ?? ((float)$payment['amount'] - $baseFare));
    $totalAmount = (float)$payment['amount'];
    $method = sanitize($payment['method']);
    $txnId = sanitize($payment['transaction_id']);

    // Check for seat conflict
    $alreadyBooked = get_booked_seats($busId, $journeyDate);
    $hasConflict = false;
    foreach ($seatNumbers as $st) {
        if (in_array($st, $alreadyBooked)) {
            $hasConflict = true;
            break;
        }
    }

    if (!$hasConflict) {
        $pnr = generate_pnr();
        $currentUser = current_user();
        $userId = $currentUser ? (int)$currentUser['id'] : null;

        $pdo = get_db_connection();
        if ($pdo) {
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
                'Dummy Gateway (' . $method . ')',
                $txnId,
                $customer,
                $contactPhone,
                $contactEmail,
                $passengersJson
            ]);

            $_SESSION['payment']['pnr'] = $pnr;
            set_flash('success', "Booking confirmed successfully! Your PNR is {$pnr}.");
        }
    }

    unset($_SESSION['pending_booking']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Result - Dummy Payment Gateway</title>
    <link rel="stylesheet" href="assets/css/dummy_gateway.css">
    <style>
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-lbl {
            color: var(--muted);
        }
        .detail-val {
            font-weight: 700;
            color: var(--text);
        }
    </style>
</head>
<body>

<div class="box">
    <?php if ($success): ?>
        <div class="result success">
            &#10004; Demo Payment Successful
        </div>
    <?php else: ?>
        <div class="result failed">
            &#10008; Demo Payment Failed
        </div>
    <?php endif; ?>

    <div style="margin: 24px 0 16px;">
        <div class="detail-row">
            <span class="detail-lbl">Customer:</span>
            <span class="detail-val"><?= e($payment["customer"]) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-lbl">Amount:</span>
            <span class="detail-val">₹<?= e($payment["amount"]) ?> INR</span>
        </div>
        <div class="detail-row">
            <span class="detail-lbl">Method:</span>
            <span class="detail-val"><?= e($payment["method"]) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-lbl">Transaction ID:</span>
            <span class="detail-val" style="font-family:monospace;"><?= e($payment["transaction_id"]) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-lbl">Status:</span>
            <span class="detail-val" style="color:<?= $success ? 'var(--success)' : 'var(--danger)' ?>"><?= e(strtoupper($payment["status"])) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-lbl">Time:</span>
            <span class="detail-val"><?= e($payment["time"]) ?></span>
        </div>
    </div>

    <div class="notice">
        This is a simulated transaction, not a real payment.
    </div>

    <?php if ($pnr): ?>
        <div style="margin: 20px 0; padding: 16px; background: #f0fdf4; border: 2px dashed #bbf7d0; border-radius: 16px;">
            <div style="font-size: 12px; color: #15803d; font-weight: 700;">CONFIRMED BUS TICKET PNR</div>
            <div style="font-size: 24px; font-weight: 800; color: #166534; margin: 4px 0;"><?= e($pnr) ?></div>
            <a href="ticket.php?pnr=<?= urlencode($pnr) ?>" style="display:block; text-decoration:none;">
                <button type="button" style="margin-top:10px;">View &amp; Print E-Ticket &rarr;</button>
            </a>
        </div>
    <?php endif; ?>

    <a href="index.php" style="display:block; text-decoration:none; margin-top:12px;">
        <button type="button" style="background:#e2e8f0; color:#334155; box-shadow:none; margin-top:6px;">Back to Checkout</button>
    </a>
</div>

</body>
</html>
