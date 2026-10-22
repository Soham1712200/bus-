<?php
/**
 * Gopi Travels - Secure Payment Gateway & Demo Net Banking
 * Supports live/test Razorpay API when configured, and provides an interactive
 * Demo Payment Simulator (Net Banking / UPI / 1-Click) so bookings never fail.
 */

$pageTitle = 'Secure Payment';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/razorpay.php';
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

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
$passengersJson = json_encode($passengers);

// Check if valid Razorpay API keys are configured (not default placeholders)
$isRazorpayConfigured = defined('RAZORPAY_KEY_ID') &&
                        !empty(RAZORPAY_KEY_ID) &&
                        RAZORPAY_KEY_ID !== 'rzp_test_YOUR_KEY_ID' &&
                        defined('RAZORPAY_KEY_SECRET') &&
                        !empty(RAZORPAY_KEY_SECRET) &&
                        RAZORPAY_KEY_SECRET !== 'YOUR_KEY_SECRET';

$razorpayOrderId = null;
$razorpayError = null;

if ($isRazorpayConfigured && class_exists('Razorpay\Api\Api')) {
    try {
        $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
        $orderData = [
            'receipt'         => 'rcptid_' . rand(1000, 9999),
            'amount'          => round($grandTotal * 100), // amount in paise
            'currency'        => 'INR',
            'payment_capture' => 1 // auto capture
        ];
        $razorpayOrder = $api->order->create($orderData);
        $razorpayOrderId = $razorpayOrder['id'] ?? null;
    } catch (Exception $e) {
        $razorpayError = $e->getMessage();
    }
}
?>

<div class="booking-checkout-container" style="max-width: 1100px; margin: 30px auto; padding: 0 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <span class="section-tag" style="background:#ffedd5; color:#ea580c; font-weight:700; font-size:12px; padding:4px 12px; border-radius:999px;">Step 2 of 2</span>
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a; margin-top:8px;">Secure Payment &amp; Confirmation</h1>
        <p style="color: #64748b; font-size: 14px;">Select your preferred payment method to issue your Gujarat ST E-Ticket instantly.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 360px; gap: 28px; align-items: start;">
        
        <!-- Payment Methods Card -->
        <div>
            <div class="netbanking-card" style="text-align: left;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:20px;">
                    <div>
                        <h3 style="font-size:18px; font-weight:800; color:#0f172a; margin-bottom:2px;">
                            <i class="fas fa-shield-halved text-primary me-2"></i> Choose Payment Mode
                        </h3>
                        <span style="font-size:12px; color:#64748b; display:block;">
                            <?= $razorpayOrderId ? 'Razorpay Secure Gateway &amp; Demo Net Banking' : 'Simulated Instant Banking &amp; UPI Demo Gateway'; ?>
                        </span>
                    </div>
                    <span class="badge" style="background:#d1fae5; color:#065f46; font-size:12px; padding:6px 12px; border-radius:999px; font-weight:700;">
                        <i class="fas fa-lock me-1"></i> 256-bit SSL Encrypted
                    </span>
                </div>

                <?php if (!empty($razorpayOrderId)): ?>
                    <!-- Razorpay Online Checkout (When API keys configured) -->
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h4 style="font-size:16px; font-weight:800; color:#166534; margin-bottom:4px;">
                                    <i class="fas fa-bolt text-success me-1"></i> Razorpay Online Checkout
                                </h4>
                                <p style="font-size:13px; color:#15803d; margin:0;">
                                    Pay securely using UPI (Google Pay, PhonePe, Paytm), Credit/Debit Cards, or Net Banking.
                                </p>
                            </div>
                            <span class="badge" style="background:#22c55e; color:#ffffff; font-size:11px; padding:4px 8px; border-radius:4px;">LIVE READY</span>
                        </div>

                        <div style="margin-top: 18px;">
                            <button type="button" id="btnConfirmPay" class="btn-proceed-booking" style="width: 100%; padding: 14px 32px;" onclick="openRazorpayCheckout()">
                                <i class="fas fa-credit-card me-2"></i> Pay <?= format_price($grandTotal); ?> via Razorpay &rarr;
                            </button>
                        </div>
                    </div>

                    <div style="text-align:center; position:relative; margin: 24px 0;">
                        <hr style="border:none; border-top:1px solid #e2e8f0;">
                        <span style="position:absolute; top:-10px; left:50%; transform:translateX(-50%); background:#fff; padding:0 12px; font-size:12px; color:#94a3b8; font-weight:700;">OR USE SIMULATED GATEWAY</span>
                    </div>
                <?php endif; ?>

                <!-- Demo Gateway & Net Banking Simulator Section -->
                <div>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px;">
                        <label style="font-size:13px; font-weight:700; color:#0f172a;">
                            <i class="fas fa-building-columns text-primary me-1"></i> Select Bank (Net Banking Simulation)
                        </label>
                        <span style="font-size:11px; color:#0284c7; background:#e0f2fe; padding:2px 8px; border-radius:4px; font-weight:700;">Instant Demo Approval</span>
                    </div>

                    <input type="hidden" id="selectedBankInput" value="State Bank of India">

                    <!-- Bank Grid -->
                    <div class="bank-grid-selector" style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px;">
                        <div class="bank-item-option selected" data-bank="State Bank of India">
                            <i class="fas fa-landmark bank-logo-icon"></i>
                            <div>State Bank of India</div>
                        </div>
                        <div class="bank-item-option" data-bank="HDFC Bank">
                            <i class="fas fa-building bank-logo-icon"></i>
                            <div>HDFC Bank</div>
                        </div>
                        <div class="bank-item-option" data-bank="ICICI Bank">
                            <i class="fas fa-piggy-bank bank-logo-icon"></i>
                            <div>ICICI Bank</div>
                        </div>
                        <div class="bank-item-option" data-bank="Axis Bank">
                            <i class="fas fa-vault bank-logo-icon"></i>
                            <div>Axis Bank</div>
                        </div>
                        <div class="bank-item-option" data-bank="Bank of Baroda">
                            <i class="fas fa-coins bank-logo-icon"></i>
                            <div>Bank of Baroda</div>
                        </div>
                        <div class="bank-item-option" data-bank="Kotak Mahindra Bank">
                            <i class="fas fa-hand-holding-dollar bank-logo-icon"></i>
                            <div>Kotak Mahindra</div>
                        </div>
                    </div>

                    <!-- Alternate Demo Methods: UPI & Card Simulation -->
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom: 20px;">
                        <button type="button" class="btn-ticket-print" style="background:#f8fafc; border:2px solid #e2e8f0; color:#334155; height:44px; display:flex; align-items:center; justify-content:center; gap:8px; font-weight:700; font-size:12px; cursor:pointer;" onclick="selectPaymentMethod('UPI / QR Code')">
                            <i class="fas fa-qrcode text-primary"></i> Pay via Demo UPI / QR
                        </button>
                        <button type="button" class="btn-ticket-print" style="background:#f8fafc; border:2px solid #e2e8f0; color:#334155; height:44px; display:flex; align-items:center; justify-content:center; gap:8px; font-weight:700; font-size:12px; cursor:pointer;" onclick="selectPaymentMethod('Debit/Credit Card')">
                            <i class="fas fa-credit-card text-primary"></i> Demo Debit / Credit Card
                        </button>
                    </div>

                    <!-- Demo Action Buttons -->
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <!-- Primary 1-Click Confirmation -->
                        <button type="button" id="btnInstantDemoPay" class="btn-proceed-booking" style="width: 100%; padding: 14px 24px; font-size:15px;" onclick="submitInstantPayment()">
                            <i class="fas fa-circle-check me-2"></i> Confirm Payment (<?= format_price($grandTotal); ?>) &amp; Issue Ticket &rarr;
                        </button>

                        <!-- Secondary Realistic 6-Digit OTP Simulation -->
                        <button type="button" class="btn-ticket-print" style="width: 100%; background: #f1f5f9; color: #475569; padding: 10px; font-size: 13px; font-weight: 600; text-align: center; border-radius: 8px;" onclick="NetBankingDemo.openOtpModal()">
                            <i class="fas fa-mobile-screen-button me-1"></i> Or Verify with 6-Digit Demo OTP Simulator
                        </button>
                    </div>

                    <!-- Non-blocking Informational Footnote -->
                    <div style="margin-top: 18px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12px; color: #64748b; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fas fa-info-circle text-primary me-1"></i> Demo transactions are authorized immediately without deductions.</span>
                        <span style="color:#94a3b8; font-size:11px;">Keys in <code>config/razorpay.php</code></span>
                    </div>

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
                    <ul style="list-style:none; font-size:13px; color:#64748b; margin-bottom:16px; display:flex; flex-direction:column; gap:4px; padding:0;">
                        <?php foreach ($passengers as $p): ?>
                            <li><i class="fas fa-user-check text-emerald me-1" style="font-size:11px; color:#10b981;"></i> <?= htmlspecialchars($p['name']); ?> (<?= $p['gender']; ?>, <?= $p['age']; ?>y) &bull; Seat <?= $p['seat']; ?></li>
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

                <div style="font-size:11px; color:#94a3b8; text-align:center; padding-top:12px; border-top:1px solid #e2e8f0; margin-top:16px;">
                    Instant E-Ticket generation with conductor verification barcode.
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Interactive Demo OTP Verification Modal -->
<div class="otp-modal-overlay" id="otpModalOverlay" style="display:none;">
    <div class="otp-modal-card">
        <div class="otp-icon-wrap">
            <i class="fas fa-lock"></i>
        </div>
        <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
            Simulated 2FA Bank Authentication
        </h3>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">
            Bank: <strong id="modalSelectedBankName" style="color:var(--primary);">State Bank of India</strong><br>
            A simulated 6-digit OTP has been dispatched to <strong><?= htmlspecialchars($contactPhone ?: '9876543210'); ?></strong>.
        </p>

        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:8px 12px; font-size:12px; color:#1d4ed8; margin-bottom:16px;">
            <i class="fas fa-lightbulb me-1"></i> Demo OTP: <strong>123456</strong> (or enter any 6 digits)
        </div>

        <div class="otp-inputs-box">
            <input type="text" class="otp-digit-field" maxlength="1" value="1" autofocus>
            <input type="text" class="otp-digit-field" maxlength="1" value="2">
            <input type="text" class="otp-digit-field" maxlength="1" value="3">
            <input type="text" class="otp-digit-field" maxlength="1" value="4">
            <input type="text" class="otp-digit-field" maxlength="1" value="5">
            <input type="text" class="otp-digit-field" maxlength="1" value="6">
        </div>

        <div style="font-size: 13px; color: #94a3b8; margin-bottom: 20px;">
            Time Remaining: <strong id="otpTimerCountdown" style="color:#0f172a;">01:00</strong>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn-ticket-print" style="flex: 1; background: #f1f5f9; color: #475569;" onclick="NetBankingDemo.closeOtpModal()">
                Cancel
            </button>
            <button type="button" id="btnConfirmOtpPay" class="btn-proceed-booking" style="flex: 2; margin: 0;" onclick="NetBankingDemo.verifyAndPay()">
                Verify &amp; Confirm Booking
            </button>
        </div>
    </div>
</div>

<!-- Hidden Form that Submits to process-booking.php -->
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
    <input type="hidden" name="payment_method" id="paymentMethodInput" value="Demo Net Banking">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="">
    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature" value="">
</form>

<script>
function selectPaymentMethod(methodName) {
    var bankInput = document.getElementById('selectedBankInput');
    if (bankInput) bankInput.value = methodName;
    var hiddenMethod = document.getElementById('paymentMethodInput');
    if (hiddenMethod) hiddenMethod.value = methodName;
    
    // Unselect bank options
    document.querySelectorAll('.bank-item-option').forEach(function(b) {
        b.classList.remove('selected');
    });

    submitInstantPayment();
}

function submitInstantPayment() {
    var selectedBank = document.getElementById('selectedBankInput').value || 'Demo Net Banking';
    var hiddenMethod = document.getElementById('paymentMethodInput');
    if (hiddenMethod) {
        hiddenMethod.value = selectedBank;
    }

    var btn = document.getElementById('btnInstantDemoPay');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Authorizing ' + selectedBank + '...';
    }

    setTimeout(function() {
        document.getElementById('bookingPaymentForm').submit();
    }, 600);
}
</script>

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
        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
        document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
        document.getElementById('razorpay_signature').value = response.razorpay_signature;
        document.getElementById('paymentMethodInput').value = 'Razorpay';
        
        var btn = document.getElementById('btnConfirmPay');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Confirming Payment...';
            btn.disabled = true;
        }
        
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
