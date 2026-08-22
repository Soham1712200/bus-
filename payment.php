<?php
/**
 * Gopi Travels - Demo Net Banking Payment Gateway
 * Simulates Indian Net Banking with interactive OTP prompt and bank authorization
 */

$pageTitle = 'Secure Payment';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/razorpay.php';
require_once __DIR__ . '/vendor/autoload.php';
use Razorpay\Api\Api;

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
}
$passengersJson = json_encode($passengers);

$razorpayOrderId = null;
$razorpayError = null;
try {
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    $orderData = [
        'receipt'         => 'rcptid_' . rand(1000, 9999),
        'amount'          => round($grandTotal * 100), // amount in paise
        'currency'        => 'INR',
        'payment_capture' => 1 // auto capture
    ];
    $razorpayOrder = $api->order->create($orderData);
    $razorpayOrderId = $razorpayOrder['id'];
} catch (Exception $e) {
    $razorpayError = $e->getMessage();
}
?>

<div class="booking-checkout-container">
    <div style="text-align: center; margin-bottom: 30px;">
        <span class="section-tag">Step 2 of 2</span>
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a;">Secure Payment</h1>
        <p style="color: #64748b; font-size: 14px;">Complete your booking securely using Razorpay.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: start;">
        
        <!-- Razorpay Payment Gateway -->
        <div>
            <div class="netbanking-card" style="text-align: left;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:20px;">
                    <div>
                        <h3 style="font-size:18px; font-weight:800; color:#0f172a;"><i class="fas fa-credit-card text-primary me-2"></i> Pay Online</h3>
                        <span style="font-size:12px; color:#64748b; display:block;">Powered by Razorpay (Test Mode)</span>
                    </div>
                    <span class="badge" style="background:#d1fae5; color:#065f46; font-size:12px; padding:6px 12px; border-radius:999px; font-weight:700;">
                        <i class="fas fa-lock me-1"></i> SSL Secured
                    </span>
                </div>

                <?php if (empty($razorpayOrderId)): ?>
                    <div class="alert alert-danger" style="font-size: 14px;">
                        <strong><i class="fas fa-exclamation-triangle"></i> Configuration Error:</strong><br>
                        Unable to initialize Razorpay Order. Please ensure your Test API Keys are correctly configured in <code>config/razorpay.php</code>.
                        <div style="margin-top: 8px; font-family: monospace; font-size: 12px; background: rgba(0,0,0,0.05); padding: 6px; border-radius: 4px;">
                            <?= htmlspecialchars($razorpayError ?? 'Unknown error') ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.5;">
                        <i class="fas fa-info-circle me-1"></i> You will be redirected to the Razorpay secure checkout window. You can use any test UPI or Card details to complete this demo payment.
                    </div>

                    <div style="text-align: center;">
                        <button type="button" id="btnConfirmPay" class="btn-proceed-booking" style="width: 100%; padding: 14px 32px;" onclick="openRazorpayCheckout()">
                            <i class="fas fa-shield-check me-2"></i> Pay <?= format_price($grandTotal); ?> & Book &rarr;
                        </button>
                    </div>
                <?php endif; ?>
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

<?php if (!empty($razorpayOrderId)): ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    "key": "<?= RAZORPAY_KEY_ID ?>",
    "amount": "<?= round($grandTotal * 100) ?>", 
    "currency": "INR",
    "name": "Gopi Travels",
    "description": "Bus Ticket Booking",
    "image": "https://cdn-icons-png.flaticon.com/512/1036/1036145.png",
    "order_id": "<?= $razorpayOrderId ?>",
    "handler": function (response){
        // Populate hidden fields and submit
        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
        document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
        document.getElementById('razorpay_signature').value = response.razorpay_signature;
        document.getElementById('paymentMethodInput').value = 'Razorpay';
        
        var btn = document.getElementById('btnConfirmPay');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Confirming Payment...';
        btn.disabled = true;
        
        document.getElementById('bookingPaymentForm').submit();
    },
    "prefill": {
        "name": "<?= htmlspecialchars($passengers[0]['name'] ?? 'Passenger') ?>",
        "email": "<?= htmlspecialchars($contactEmail) ?>",
        "contact": "<?= htmlspecialchars($contactPhone) ?>"
    },
    "theme": {
        "color": "#0284c7"
    }
};
var rzp1 = new Razorpay(options);
rzp1.on('payment.failed', function (response){
        alert("Payment Failed: " + response.error.description);
});
function openRazorpayCheckout() {
    rzp1.open();
}
</script>
<?php endif; ?>

<!-- Hidden Actual Form that submits to process-booking.php -->
<form action="process-booking.php" method="POST" id="bookingPaymentForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
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
    <input type="hidden" name="payment_method" id="paymentMethodInput" value="Razorpay">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="">
    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature" value="">
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
