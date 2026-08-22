<?php
/**
 * Gopi Travels - Demo Net Banking Payment Gateway
 * Simulates Indian Net Banking with interactive OTP prompt and bank authorization
 */

$pageTitle = 'Demo Net Banking Payment';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/stripe.php';

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
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a;">Secure Payment</h1>
        <p style="color: #64748b; font-size: 14px;">Complete your booking securely using Stripe.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: start;">
        
        <!-- Stripe Payment Gateway -->
        <div>
            <div class="netbanking-card" style="text-align: left;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:20px;">
                    <div>
                        <h3 style="font-size:18px; font-weight:800; color:#0f172a;"><i class="fab fa-stripe text-primary me-2" style="font-size:24px; vertical-align:middle;"></i> Pay with Card</h3>
                        <span style="font-size:12px; color:#64748b; display:block;">Powered by Stripe (Test Mode)</span>
                    </div>
                    <span class="badge" style="background:#d1fae5; color:#065f46; font-size:12px; padding:6px 12px; border-radius:999px; font-weight:700;">
                        <i class="fas fa-lock me-1"></i> SSL Secured
                    </span>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="form-label-custom" style="font-size:14px; margin-bottom: 8px; display:block;">Credit or Debit Card Details</label>
                    <div id="card-element" style="border: 1px solid #cbd5e1; padding: 12px; border-radius: 8px; background: #f8fafc;">
                        <!-- A Stripe Element will be inserted here. -->
                    </div>
                    <!-- Used to display form errors. -->
                    <div id="card-errors" role="alert" style="color: #dc2626; font-size: 13px; margin-top: 8px;"></div>
                </div>
                
                <div style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
                    <i class="fas fa-info-circle me-1"></i> Note: This is a demo application. Use Stripe test cards (e.g. 4242 4242 4242 4242).
                </div>

                <div style="text-align: center;">
                    <button type="button" id="btnConfirmStripePay" class="btn-proceed-booking" style="width: 100%; padding: 14px 32px;" onclick="processStripePayment()">
                        <i class="fas fa-shield-check me-2"></i> Pay <?= format_price($grandTotal); ?> & Book &rarr;
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

<script src="https://js.stripe.com/v3/"></script>
<script>
// Initialize Stripe
var stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
var elements = stripe.elements();

// Custom styling can be passed to options when creating an Element.
var style = {
  base: {
    color: '#0f172a',
    fontFamily: '"Inter", Helvetica, sans-serif',
    fontSmoothing: 'antialiased',
    fontSize: '16px',
    '::placeholder': {
      color: '#94a3b8'
    }
  },
  invalid: {
    color: '#dc2626',
    iconColor: '#dc2626'
  }
};

// Create an instance of the card Element.
var card = elements.create('card', {style: style});
card.mount('#card-element');

// Handle real-time validation errors from the card Element.
card.on('change', function(event) {
  var displayError = document.getElementById('card-errors');
  if (event.error) {
    displayError.textContent = event.error.message;
  } else {
    displayError.textContent = '';
  }
});

function processStripePayment() {
    var btn = document.getElementById('btnConfirmStripePay');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing Securely...';
    
    stripe.createToken(card).then(function(result) {
        if (result.error) {
            // Inform the user if there was an error.
            var errorElement = document.getElementById('card-errors');
            errorElement.textContent = result.error.message;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-shield-check me-2"></i> Pay <?= format_price($grandTotal); ?> & Book &rarr;';
        } else {
            // Send the token to your server.
            document.getElementById('stripeTokenInput').value = result.token.id;
            document.getElementById('paymentMethodInput').value = 'Stripe Card';
            document.getElementById('bookingPaymentForm').submit();
        }
    });
}
</script>

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
    <input type="hidden" name="payment_method" id="paymentMethodInput" value="Stripe Card">
    <input type="hidden" name="stripeToken" id="stripeTokenInput" value="">
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
