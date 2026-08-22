<?php
/**
 * Gopi Travels - Demo Net Banking Payment Gateway
 * Simulates Indian Net Banking with interactive OTP prompt and bank authorization
 */

$pageTitle = 'Demo Net Banking Payment';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['bus_id']) || empty($_POST['seat_numbers'])) {
    set_flash('danger', 'Invalid booking request. Please select a bus and seats first.');
    header('Location: search.php');
    exit;
}

$busId = (int)$_POST['bus_id'];
$journeyDate = sanitize($_POST['journey_date']);
$seatNumbersStr = sanitize($_POST['seat_numbers']);
$seatNumbers = array_filter(explode(',', $seatNumbersStr));
$seatCount = count($seatNumbers);

$passengerNames = isset($_POST['passenger_names']) ? (array)$_POST['passenger_names'] : [];
$passengerAges = isset($_POST['passenger_ages']) ? (array)$_POST['passenger_ages'] : [];
$passengerGenders = isset($_POST['passenger_genders']) ? (array)$_POST['passenger_genders'] : [];
$contactPhone = sanitize($_POST['contact_phone'] ?? '');
$contactEmail = sanitize($_POST['contact_email'] ?? '');

// Fetch bus details
$stmt = $pdo->prepare("SELECT b.*, r.source_city, r.destination_city, r.distance_km, r.estimated_time 
                       FROM buses b 
                       JOIN routes r ON b.route_id = r.id 
                       WHERE b.id = ?");
$stmt->execute([$busId]);
$bus = $stmt->fetch();

if (!$bus || $seatCount === 0) {
    set_flash('danger', 'Selected bus or seats are no longer available.');
    header('Location: search.php');
    exit;
}

// Calculate financial breakdown
$baseFare = (float)$bus['fare'] * $seatCount;
$gstAmount = round($baseFare * 0.05, 2);
$reservationFee = 15.00;
$grandTotal = $baseFare + $gstAmount + $reservationFee;

// Build passenger array
$passengers = [];
foreach ($seatNumbers as $idx => $seat) {
    $passengers[] = [
        'name' => sanitize($passengerNames[$idx] ?? 'Passenger ' . ($idx + 1)),
        'age' => (int)($passengerAges[$idx] ?? 30),
        'gender' => sanitize($passengerGenders[$idx] ?? 'Male'),
        'seat' => $seat
    ];
}
$passengersJson = json_encode($passengers);
?>

<div class="booking-checkout-container">
    <div style="text-align: center; margin-bottom: 30px;">
        <span class="section-tag">Step 2 of 2</span>
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a;">UPI Payment Gateway</h1>
        <p style="color: #64748b; font-size: 14px;">Complete your booking securely using UPI.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: start;">
        
        <!-- UPI Payment Gateway -->
        <div>
            <div class="netbanking-card" style="text-align: center;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:20px;">
                    <div>
                        <h3 style="font-size:18px; font-weight:800; color:#0f172a; text-align:left;"><i class="fas fa-qrcode text-primary me-2"></i> Scan & Pay with UPI</h3>
                        <span style="font-size:12px; color:#64748b; display:block; text-align:left;">Secure UPI Payment Gateway</span>
                    </div>
                    <span class="badge" style="background:#d1fae5; color:#065f46; font-size:12px; padding:6px 12px; border-radius:999px; font-weight:700;">
                        <i class="fas fa-lock me-1"></i> Secured
                    </span>
                </div>

                <div style="margin: 20px auto;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=upi://pay?pa=9081020835@ybl%26pn=Gopi+Travels%26am=<?= $grandTotal ?>%26cu=INR" alt="UPI QR Code" style="border: 2px solid #e2e8f0; border-radius: 12px; padding: 10px; max-width: 250px; background: white;">
                </div>

                <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 5px;">UPI ID: <span style="color: var(--primary);">9081020835@ybl</span></div>
                <div style="font-size: 14px; color: #64748b; margin-bottom: 20px;">Scan the QR code using any UPI app (GPay, PhonePe, Paytm, etc.) to complete the payment of <strong><?= format_price($grandTotal); ?></strong>.</div>

                <!-- Simulated UTR Form -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin:20px 0; text-align: left;">
                    <div style="font-size:14px; font-weight:700; color:#0f172a; margin-bottom:14px;">
                        <i class="fas fa-check-double text-emerald me-1"></i> Verify Payment
                    </div>
                    <div>
                        <label class="form-label-custom" style="font-size:11px;">Enter 12-digit UTR / Reference No. after successful payment</label>
                        <input type="text" id="utrNumber" class="form-control-custom" style="height:42px; font-size:13px;" placeholder="e.g. 123456789012" required pattern="\d{12}">
                    </div>
                </div>

                <div style="text-align: center;">
                    <button type="button" id="btnConfirmUpiPay" class="btn-proceed-booking" style="width: 100%; padding: 14px 32px;" onclick="verifyUPIPayment()">
                        <i class="fas fa-shield-check me-2"></i> Confirm Payment & Book Ticket &rarr;
                    </button>
                </div>
            </div>
        </div>

        <!-- Trip & Fare Summary Sidebar -->
        <div>
            <div class="booking-summary-sidebar">
                <div>
                    <div class="summary-title">
                        <i class="fas fa-ticket text-primary me-1"></i> Booking Summary
                    </div>

                    <div style="margin-bottom:16px; font-size:13px; color:#475569;">
                        <div style="font-weight:800; font-size:16px; color:#0f172a; margin-bottom:4px;">
                            <?= htmlspecialchars($bus['source_city']); ?> &rarr; <?= htmlspecialchars($bus['destination_city']); ?>
                        </div>
                        <div>Date: <strong><?= format_date($journeyDate); ?></strong></div>
                        <div>Departure: <strong><?= format_time($bus['departure_time']); ?></strong></div>
                        <div>Bus: <strong><?= htmlspecialchars($bus['bus_number']); ?> (<?= $bus['bus_class']; ?>)</strong></div>
                    </div>

                    <div style="font-size:12px; font-weight:700; color:#334155; margin-bottom:6px;">Assigned Seats:</div>
                    <div class="summary-seats-list" style="margin-bottom:16px;">
                        <?php foreach ($seatNumbers as $sn): ?>
                            <span class="selected-seat-tag"><?= htmlspecialchars($sn); ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div style="font-size:12px; font-weight:700; color:#334155; margin-bottom:6px;">Passenger Roster:</div>
                    <ul style="list-style:none; font-size:13px; color:#64748b; margin-bottom:16px; display:flex; flex-direction:column; gap:4px;">
                        <?php foreach ($passengers as $p): ?>
                            <li><i class="fas fa-user-check text-emerald me-1" style="font-size:11px;"></i> <?= htmlspecialchars($p['name']); ?> (<?= $p['gender']; ?>, <?= $p['age']; ?>y) &bull; Seat <?= $p['seat']; ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <table class="fare-breakdown-table">
                        <tr>
                            <td>Seats (<?= $seatCount; ?> &times; <?= format_price($bus['fare']); ?>):</td>
                            <td><?= format_price($baseFare); ?></td>
                        </tr>
                        <tr>
                            <td>GST (5%):</td>
                            <td><?= format_price($gstAmount); ?></td>
                        </tr>
                        <tr>
                            <td>Toll / Booking Fee:</td>
                            <td><?= format_price($reservationFee); ?></td>
                        </tr>
                        <tr class="grand-total">
                            <td>Total Amount:</td>
                            <td><?= format_price($grandTotal); ?></td>
                        </tr>
                    </table>
                </div>

                <div style="font-size:11px; color:#94a3b8; text-align:center; padding-top:12px; border-top:1px solid #e2e8f0;">
                    Instant E-Ticket generation upon successful OTP verification.
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function verifyUPIPayment() {
    const utr = document.getElementById('utrNumber').value.trim();
    if (utr.length !== 12 || isNaN(utr)) {
        alert("Please enter a valid 12-digit UTR number.");
        return;
    }
    
    const btn = document.getElementById('btnConfirmUpiPay');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Verifying Payment...';
    
    // Simulate banking network roundtrip
    setTimeout(() => {
        document.getElementById('paymentMethodInput').value = 'UPI (UTR: ' + utr + ')';
        document.getElementById('bookingPaymentForm').submit();
    }, 1500);
}
</script>

<!-- Hidden Actual Form that submits to process-booking.php -->
<form action="process-booking.php" method="POST" id="bookingPaymentForm" style="display:none;">
    <input type="hidden" name="bus_id" value="<?= $bus['id']; ?>">
    <input type="hidden" name="journey_date" value="<?= htmlspecialchars($journeyDate); ?>">
    <input type="hidden" name="seat_numbers" value="<?= htmlspecialchars($seatNumbersStr); ?>">
    <input type="hidden" name="contact_phone" id="contactPhone" value="<?= htmlspecialchars($contactPhone); ?>">
    <input type="hidden" name="contact_email" id="contactEmail" value="<?= htmlspecialchars($contactEmail); ?>">
    <input type="hidden" name="passenger_name" value="<?= htmlspecialchars($passengers[0]['name'] ?? 'Passenger'); ?>">
    <input type="hidden" name="passengers_json" value="<?= htmlspecialchars($passengersJson, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="base_fare" value="<?= $baseFare; ?>">
    <input type="hidden" name="gst_amount" value="<?= $gstAmount; ?>">
    <input type="hidden" name="total_amount" value="<?= $grandTotal; ?>">
    <input type="hidden" name="payment_method" id="paymentMethodInput" value="UPI">
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
