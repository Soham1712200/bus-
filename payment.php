<?php
/**
 * Jayambe Travels - Demo Net Banking Payment Gateway
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
        <h1 style="font-size: 28px; font-weight: 800; color: #0f172a;">Demo Net Banking Payment Gateway</h1>
        <p style="color: #64748b; font-size: 14px;">Select your bank and complete the interactive OTP verification simulation.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: start;">
        
        <!-- Net Banking Selector & Gateway -->
        <div>
            <div class="netbanking-card">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:20px;">
                    <div>
                        <h3 style="font-size:18px; font-weight:800; color:#0f172a;"><i class="fas fa-building-columns text-primary me-2"></i> Select Net Banking</h3>
                        <span style="font-size:12px; color:#64748b;">Simulated Net Banking Environment (RBI Mock)</span>
                    </div>
                    <span class="badge" style="background:#d1fae5; color:#065f46; font-size:12px; padding:6px 12px; border-radius:999px; font-weight:700;">
                        <i class="fas fa-lock me-1"></i> 256-Bit SSL Encrypted
                    </span>
                </div>

                <div style="font-size:13px; font-weight:700; color:#334155; margin-bottom:10px;">Popular Indian Banks:</div>
                <div class="bank-grid-selector">
                    <div class="bank-item-option selected" data-bank="State Bank of India">
                        <i class="fas fa-landmark bank-logo-icon" style="color:#0072bc;"></i>
                        <span>State Bank of India</span>
                    </div>
                    <div class="bank-item-option" data-bank="HDFC Bank">
                        <i class="fas fa-building-columns bank-logo-icon" style="color:#004c8f;"></i>
                        <span>HDFC Bank</span>
                    </div>
                    <div class="bank-item-option" data-bank="ICICI Bank">
                        <i class="fas fa-university bank-logo-icon" style="color:#bd2227;"></i>
                        <span>ICICI Bank</span>
                    </div>
                    <div class="bank-item-option" data-bank="Axis Bank">
                        <i class="fas fa-vault bank-logo-icon" style="color:#861f41;"></i>
                        <span>Axis Bank</span>
                    </div>
                    <div class="bank-item-option" data-bank="Bank of Baroda">
                        <i class="fas fa-building bank-logo-icon" style="color:#f26522;"></i>
                        <span>Bank of Baroda</span>
                    </div>
                    <div class="bank-item-option" data-bank="Kotak Mahindra Bank">
                        <i class="fas fa-landmark-dome bank-logo-icon" style="color:#ed1c24;"></i>
                        <span>Kotak Mahindra</span>
                    </div>
                    <div class="bank-item-option" data-bank="Punjab National Bank">
                        <i class="fas fa-building-ngo bank-logo-icon" style="color:#9e1b32;"></i>
                        <span>Punjab National</span>
                    </div>
                    <div class="bank-item-option" data-bank="Canara Bank">
                        <i class="fas fa-landmark-flag bank-logo-icon" style="color:#0093dd;"></i>
                        <span>Canara Bank</span>
                    </div>
                </div>

                <!-- Simulated Credentials Form -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px; margin:20px 0;">
                    <div style="font-size:14px; font-weight:700; color:#0f172a; margin-bottom:14px;">
                        <i class="fas fa-shield-halved text-emerald me-1"></i> Net Banking Login Credentials Simulation
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                        <div>
                            <label class="form-label-custom" style="font-size:11px;">Customer User ID</label>
                            <input type="text" class="form-control-custom" style="height:42px; font-size:13px;" value="JAY_CUST_9824" placeholder="Enter User ID" required>
                        </div>
                        <div>
                            <label class="form-label-custom" style="font-size:11px;">Internet Banking Password</label>
                            <input type="password" class="form-control-custom" style="height:42px; font-size:13px;" value="••••••••••••" placeholder="Enter Password" required>
                        </div>
                    </div>
                </div>

                <div style="text-align: right;">
                    <button type="button" class="btn-proceed-booking" style="width: auto; padding: 14px 32px; display: inline-flex;" onclick="NetBankingDemo.openOtpModal()">
                        <i class="fas fa-shield-check me-2"></i> Authenticate & Proceed to OTP &rarr;
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

<!-- Simulated Interactive OTP Modal Overlay -->
<div class="otp-modal-overlay" id="otpModalOverlay" style="display: none;">
    <div class="otp-modal-card">
        <div class="otp-icon-wrap">
            <i class="fas fa-shield-halved"></i>
        </div>
        
        <h2 style="font-size:22px; font-weight:800; color:#0f172a; margin-bottom:6px;">Bank OTP Verification</h2>
        <p style="font-size:13px; color:#64748b; margin-bottom: 18px;">
            Please enter the 6-digit OTP sent to your registered mobile number.
        </p>

        <!-- 6 Digit OTP Fields -->
        <div class="otp-inputs-box">
            <input type="text" maxlength="1" class="otp-digit-field" pattern="[0-9]" inputmode="numeric">
            <input type="text" maxlength="1" class="otp-digit-field" pattern="[0-9]" inputmode="numeric">
            <input type="text" maxlength="1" class="otp-digit-field" pattern="[0-9]" inputmode="numeric">
            <input type="text" maxlength="1" class="otp-digit-field" pattern="[0-9]" inputmode="numeric">
            <input type="text" maxlength="1" class="otp-digit-field" pattern="[0-9]" inputmode="numeric">
            <input type="text" maxlength="1" class="otp-digit-field" pattern="[0-9]" inputmode="numeric">
        </div>

        <div class="otp-timer-text">
            Time Remaining: <strong id="otpTimerCountdown" style="color:var(--primary);">01:00</strong>
            <button type="button" id="btnResendOtp" style="display:none; background:none; border:none; color:var(--primary); font-weight:700; cursor:pointer; text-decoration:underline; margin-left:8px;" onclick="NetBankingDemo.startTimer()">Resend OTP</button>
        </div>

        <div style="display:flex; gap:12px;">
            <button type="button" class="btn-proceed-booking" style="background:#cbd5e1; color:#334155; box-shadow:none; flex:1;" onclick="NetBankingDemo.closeOtpModal()">
                Cancel
            </button>
            <button type="button" class="btn-proceed-booking" id="btnConfirmOtpPay" style="flex:2;" onclick="NetBankingDemo.verifyAndPay()">
                <i class="fas fa-check-circle me-1"></i> Confirm & Pay <?= format_price($grandTotal); ?>
            </button>
        </div>
    </div>
</div>

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
    <input type="hidden" name="selected_bank" id="selectedBankInput" value="State Bank of India">
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
