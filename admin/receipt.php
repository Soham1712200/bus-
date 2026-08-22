<?php
/**
 * Gopi Travels - Dual-Mode Thermal (80mm) & A4 Counter Receipt
 */

require_once __DIR__ . '/includes/auth_guard.php';

$pdo = get_db_connection();
$pnr = isset($_GET['pnr']) ? sanitize($_GET['pnr']) : '';
$format = isset($_GET['format']) ? sanitize($_GET['format']) : 'thermal'; // 'thermal' or 'a4'

$booking = null;
if (!empty($pnr) && $pdo) {
    $stmt = $pdo->prepare("SELECT bk.*, b.bus_number, b.bus_class, b.departure_time, b.arrival_time, 
                                  r.source_city, r.destination_city, r.distance_km, r.estimated_time,
                                  u.name as cashier_name, u.branch_office
                           FROM bookings bk
                           JOIN buses b ON bk.bus_id = b.id
                           JOIN routes r ON b.route_id = r.id
                           LEFT JOIN users u ON bk.booked_by_user_id = u.id
                           WHERE bk.booking_reference = ?");
    $stmt->execute([$pnr]);
    $booking = $stmt->fetch();
}

if (!$booking) {
    set_flash('danger', 'Booking not found for PNR: ' . htmlspecialchars($pnr));
    header('Location: counter-booking.php');
    exit;
}

$passengers = !empty($booking['passenger_details']) ? json_decode($booking['passenger_details'], true) : [];
$qrSvg = generate_qr_svg("JAYAMBE-TICKET:PNR={$booking['booking_reference']}:DATE={$booking['journey_date']}:BUS={$booking['bus_number']}:SEATS={$booking['seat_numbers']}");
$barcodeSvg = generate_barcode_svg($booking['booking_reference']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt: <?= htmlspecialchars($booking['booking_reference']); ?> | Gopi Travels</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        .thermal-slip {
            width: 320px;
            margin: 20px auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 18px;
            font-family: 'Space Mono', monospace, 'Courier New';
            font-size: 11px;
            color: #000000;
            line-height: 1.4;
            box-shadow: var(--shadow-lg);
        }
        .thermal-slip table {
            width: 100%;
            font-size: 11px;
        }
        .thermal-divider {
            border-top: 1px dashed #000000;
            margin: 8px 0;
        }
        .thermal-double-divider {
            border-top: 2px solid #000000;
            margin: 8px 0;
        }

        @media print {
            .btn-receipt-actions, .top-bar, .admin-navbar { display: none !important; }
            body { background: #fff !important; }
            .thermal-slip { border: none !important; box-shadow: none !important; margin: 0 auto !important; width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body style="background-color: #f1f5f9; min-height: 100vh; padding: 20px 0;">

    <!-- Action Toolbar (Hidden in Print) -->
    <div class="btn-receipt-actions" style="max-width: 480px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 14px 20px; border-radius: 12px; box-shadow: var(--shadow-sm); border: 1px solid #cbd5e1;">
        <a href="counter-booking.php" class="btn-sm" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; padding:8px 14px; border-radius:6px; font-weight:700; text-decoration:none;">
            <i class="fas fa-arrow-left me-1"></i> New Booking
        </a>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn-search-bus" style="height: 38px; padding: 0 18px; font-size: 13px; background: #0284c7; border: none;">
                <i class="fas fa-print me-1"></i> Print Thermal (80mm)
            </button>
            <a href="../ticket.php?pnr=<?= urlencode($booking['booking_reference']); ?>" target="_blank" class="btn-nav-primary" style="background: #0b132b; padding: 8px 14px; font-size: 13px;">
                <i class="fas fa-file-pdf me-1"></i> A4 Format
            </a>
        </div>
    </div>

    <!-- 80mm Thermal Receipt Layout -->
    <div class="thermal-slip">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 6px;">
            <strong style="font-size: 14px; display: block; letter-spacing: 0.5px;">JAYAMBE TRAVELS</strong>
            <span style="font-size: 9px;">Gujarat State Deluxe Service</span><br>
            <span style="font-size: 9px;"><?= htmlspecialchars($booking['branch_office'] ?? 'Central Branch Office'); ?></span>
        </div>

        <div class="thermal-double-divider"></div>

        <!-- Meta -->
        <table style="margin-bottom: 6px;">
            <tr>
                <td><strong>PNR NO:</strong></td>
                <td style="text-align: right;"><strong style="font-size: 13px;"><?= htmlspecialchars($booking['booking_reference']); ?></strong></td>
            </tr>
            <tr>
                <td>DATE/TIME:</td>
                <td style="text-align: right;"><?= date('d/m/Y h:i A', strtotime($booking['created_at'])); ?></td>
            </tr>
            <tr>
                <td>CHANNEL:</td>
                <td style="text-align: right;"><strong><?= strtoupper($booking['booking_channel']); ?> (<?= htmlspecialchars($booking['counter_payment_mode']); ?>)</strong></td>
            </tr>
            <tr>
                <td>CASHIER:</td>
                <td style="text-align: right;"><?= htmlspecialchars($booking['cashier_name'] ?? 'Staff Agent'); ?></td>
            </tr>
        </table>

        <div class="thermal-divider"></div>

        <!-- Journey Info -->
        <table style="margin-bottom: 6px;">
            <tr>
                <td>FROM:</td>
                <td style="text-align: right;"><strong><?= strtoupper($booking['source_city']); ?></strong></td>
            </tr>
            <tr>
                <td>TO:</td>
                <td style="text-align: right;"><strong><?= strtoupper($booking['destination_city']); ?></strong></td>
            </tr>
            <tr>
                <td>DEPARTURE:</td>
                <td style="text-align: right;"><strong><?= date('d-M-Y', strtotime($booking['journey_date'])); ?> <?= date('h:i A', strtotime($booking['departure_time'])); ?></strong></td>
            </tr>
            <tr>
                <td>BUS NO:</td>
                <td style="text-align: right;"><?= htmlspecialchars($booking['bus_number']); ?></td>
            </tr>
            <tr>
                <td>CLASS:</td>
                <td style="text-align: right;"><?= htmlspecialchars($booking['bus_class']); ?></td>
            </tr>
            <tr>
                <td>BOARDING:</td>
                <td style="text-align: right;"><?= htmlspecialchars($booking['boarding_point']); ?></td>
            </tr>
        </table>

        <div class="thermal-divider"></div>

        <!-- Seat & Passenger List -->
        <div style="margin-bottom: 6px;">
            <strong>PASSENGERS (<?= $booking['total_passengers']; ?> SEAT<?= $booking['total_passengers'] > 1 ? 'S' : ''; ?>):</strong><br>
            <div style="font-size: 12px; margin: 4px 0;">
                SEAT NO(S): <strong style="font-size: 14px;"><?= htmlspecialchars($booking['seat_numbers']); ?></strong>
            </div>
            <?php foreach ($passengers as $idx => $p): ?>
                <div><?= ($idx + 1); ?>. <?= htmlspecialchars($p['name']); ?> (<?= $p['gender'][0]; ?>/<?= $p['age']; ?>) - Seat: <?= $p['seat']; ?></div>
            <?php endforeach; ?>
            <div style="margin-top: 4px; font-size: 10px;">Primary: <?= htmlspecialchars($booking['passenger_name']); ?> | <?= htmlspecialchars($booking['passenger_phone']); ?></div>
        </div>

        <div class="thermal-divider"></div>

        <!-- Fare Breakdown -->
        <table>
            <tr>
                <td>Base Fare (<?= $booking['total_passengers']; ?> Pax):</td>
                <td style="text-align: right;">₹<?= number_format($booking['base_fare'], 2); ?></td>
            </tr>
            <tr>
                <td>GST (5%):</td>
                <td style="text-align: right;">₹<?= number_format($booking['gst_amount'], 2); ?></td>
            </tr>
            <tr style="font-size: 13px;">
                <td><strong>TOTAL PAID:</strong></td>
                <td style="text-align: right;"><strong>₹<?= number_format($booking['total_amount'], 2); ?></strong></td>
            </tr>
            <tr>
                <td>TXN ID:</td>
                <td style="text-align: right;"><?= htmlspecialchars($booking['transaction_id']); ?></td>
            </tr>
        </table>

        <div class="thermal-divider"></div>

        <!-- QR Code & Barcode -->
        <div style="text-align: center; margin: 10px 0;">
            <?= $qrSvg; ?>
            <div style="margin-top: 8px;">
                <?= $barcodeSvg; ?>
            </div>
        </div>

        <div class="thermal-double-divider"></div>

        <!-- Footer Notice -->
        <div style="text-align: center; font-size: 9px;">
            Please report 15 mins prior to departure.<br>
            Helpline: <?= HELPLINE_NUMBER; ?><br>
            *** HAVE A SAFE JOURNEY ***
        </div>

    </div>

</body>
</html>
