<?php
/** Demo payment gateway integrated with the bus booking checkout. */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$result = null;
$message = '';
$messageType = 'info';
$methods = ['Cards', 'Net Banking', 'UPI', 'PayPal', 'Diners Club'];
$selectedMethod = 'Net Banking';
$checkout = $_SESSION['demo_checkout'] ?? null;

// Validate the demo selection only. This page does not process or confirm payment.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gateway_submit'])) {
    $token = (string)($_POST['csrf_token'] ?? '');
    $checkoutToken = (string)($_POST['checkout_token'] ?? '');
    $method = (string)($_POST['payment_method'] ?? '');
    $simulation = (string)($_POST['simulate'] ?? '');
    if (in_array($method, $methods, true)) $selectedMethod = $method;
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        set_flash('danger', 'Invalid request. Return to bus search and try again.');
        header('Location: search.php');
        exit;
    } elseif (!$checkout || time() - (int)$checkout['created_at'] > 1800 || !hash_equals($checkout['token'], $checkoutToken)) {
        set_flash('danger', 'Checkout expired. Return to bus search and try again.');
        header('Location: search.php');
        exit;
    } elseif (!in_array($method, $methods, true) || !in_array($simulation, ['success', 'failed'], true)) {
        $message = 'Please select a valid payment method.';
        $messageType = 'error';
    } else {
        $success = $simulation === 'success';
        $transactionId = 'DUMMY' . strtoupper(bin2hex(random_bytes(5)));
        $_SESSION['payment'] = [
            'customer' => $checkout['booking']['passenger_name'],
            'amount' => number_format((float)$checkout['amount'], 2, '.', ''),
            'method' => $method,
            'status' => $success ? 'success' : 'failed',
            'transaction_id' => $transactionId,
            'time' => date('Y-m-d H:i:s'),
        ];
        if ($success) {
            $_SESSION['pending_booking'] = $checkout['booking'];
            $_SESSION['pending_booking']['amount'] = (float)$checkout['amount'];
            $_SESSION['pending_booking']['transaction_id'] = $transactionId;
            $_SESSION['pending_booking']['method'] = $method;
        } else {
            unset($_SESSION['pending_booking']);
        }
        unset($_SESSION['demo_checkout']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: result.php');
        exit;
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $checkout = $_SESSION['demo_checkout'] ?? null;
}

// The first POST comes from the bus selection form. Recalculate price from the database.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['gateway_submit'])) {
    if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
        $result = ['status' => 'error', 'message' => 'Invalid request. Refresh the search page and try again.'];
    } else {
        $pdo = get_db_connection();
        $busId = (int)($_POST['bus_id'] ?? 0);
        $journeyDate = (string)($_POST['journey_date'] ?? '');
        $seatNumbers = array_values(array_unique(array_filter(array_map('trim', explode(',', (string)($_POST['seat_numbers'] ?? ''))))));
        if (!$pdo || !$busId || !$seatNumbers || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $journeyDate)) {
            $result = ['status' => 'error', 'message' => 'The selected trip details are invalid. Please select again.'];
        } else {
            $stmt = $pdo->prepare('SELECT b.*, r.source_city, r.destination_city FROM buses b JOIN routes r ON r.id=b.route_id WHERE b.id=?');
            $stmt->execute([$busId]);
            $bus = $stmt->fetch();
            if (!$bus) {
                $result = ['status' => 'error', 'message' => 'The selected bus could not be found.'];
            } elseif (array_intersect($seatNumbers, get_booked_seats($busId, $journeyDate))) {
                $result = ['status' => 'error', 'message' => 'One or more selected seats are no longer available. Please select again.'];
            } else {
                $baseFare = round((float)$bus['fare'] * count($seatNumbers), 2);
                $gstAmount = round($baseFare * 0.05, 2);
                $total = round($baseFare + $gstAmount + 15, 2);
                $phone = preg_replace('/\D+/', '', (string)($_POST['contact_phone'] ?? ''));
                $email = filter_var($_POST['contact_email'] ?? '', FILTER_VALIDATE_EMAIL);
                if (strlen($phone) < 10 || strlen($phone) > 15 || !$email) {
                    $result = ['status' => 'error', 'message' => 'Enter a valid contact phone number and email address.'];
                } else {
                    $passengers = [];
                    foreach ($seatNumbers as $index => $seat) {
                        $passengers[] = [
                            'name' => trim((string)($_POST['passenger_names'][$index] ?? 'Passenger')),
                            'age' => max(1, (int)($_POST['passenger_ages'][$index] ?? 30)),
                            'gender' => in_array($_POST['passenger_genders'][$index] ?? '', ['Male', 'Female', 'Other'], true) ? $_POST['passenger_genders'][$index] : 'Other',
                            'seat' => $seat,
                        ];
                    }
                    $passengerName = $passengers[0]['name'] ?: 'Passenger';
                    $token = bin2hex(random_bytes(32));
                    $booking = [
                        'bus_id' => $busId,
                        'journey_date' => $journeyDate,
                        'seat_numbers' => implode(',', $seatNumbers),
                        'base_fare' => $baseFare,
                        'gst_amount' => $gstAmount,
                        'passengers_json' => json_encode($passengers),
                        'passenger_name' => $passengerName,
                        'contact_phone' => $phone,
                        'contact_email' => $email,
                    ];
                    $_SESSION['demo_checkout'] = ['token' => $token, 'amount' => $total, 'booking' => $booking, 'created_at' => time()];
                    $checkout = $_SESSION['demo_checkout'];
                }
            }
        }
    }
}

if (!$checkout && $result) {
    set_flash('danger', $result['message']);
    header('Location: search.php');
    exit;
}
if (!$checkout && !$result) {
    header('Location: search.php');
    exit;
}
if ($checkout) {
    $booking = $checkout['booking'];
    $pdo = get_db_connection();
    if (!$pdo) {
        unset($_SESSION['demo_checkout']);
        set_flash('danger', 'Booking service is temporarily unavailable. Please try again.');
        header('Location: search.php');
        exit;
    }
}
if ($checkout) {
    $stmt = $pdo->prepare('SELECT b.*, r.source_city, r.destination_city FROM buses b JOIN routes r ON r.id=b.route_id WHERE b.id=?');
    $stmt->execute([(int)$booking['bus_id']]);
    $bus = $stmt->fetch();
    if (!$bus) {
        unset($_SESSION['demo_checkout']);
        set_flash('danger', 'The selected bus could not be found.');
        header('Location: search.php');
        exit;
    }
}

function payment_escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
$pageTitle = 'Dummy Payment Gateway';
require_once __DIR__ . '/includes/header.php';
?>
<style>
* { box-sizing:border-box; }
body { margin:0; min-height:100vh; background:#f6f6ff; color:#170844; font-family:Arial,Helvetica,sans-serif; display:block; padding:0; }
.checkout-heading { max-width:960px; margin:34px auto 18px; padding:0 16px; display:flex; align-items:center; justify-content:space-between; gap:16px; }
.checkout-heading h1 { margin:0 0 5px; color:#172033; font-size:25px; }
.checkout-heading p { margin:0; color:#64748b; font-size:14px; }
.secure-pill { padding:8px 12px; border:1px solid #bbf7d0; border-radius:999px; color:#166534; background:#f0fdf4; font-size:12px; font-weight:700; white-space:nowrap; }
.demo-layout { width:100%; max-width:960px; display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); align-items:start; margin:0 auto 64px; padding:0 16px; }
.demo-checkout { background:#fff; border:1px solid #dedee9; border-radius:12px; overflow:hidden; box-shadow:0 8px 28px #21104b0d; }
.demo-brand { background:#210850; color:#fff; padding:22px 26px; display:flex; align-items:center; gap:15px; font-size:23px; font-weight:bold; }
.demo-logo { width:44px; height:44px; display:grid; place-items:center; border-radius:5px; background:linear-gradient(135deg,#ff7caa,#f21d57); font:italic bold 34px Georgia,serif; color:#fff; }
.demo-checkout-body { padding:22px 26px 12px; }
.demo-amount { border:1px solid #dedee9; border-radius:7px; padding:22px; margin-bottom:26px; }
.demo-amount-label { font-size:16px; margin-bottom:8px; }
.demo-amount-value { font-size:34px; font-weight:bold; }
.trip-summary { display:flex; justify-content:space-between; gap:12px; margin:0 0 25px; padding:14px; border-radius:8px; background:#f8f7fc; color:#51466c; font-size:13px; line-height:1.6; }
.trip-summary strong { display:block; color:#201344; font-size:14px; }
.demo-subheading { color:#8586a8; margin-bottom:8px; }
.demo-method { display:flex; align-items:center; gap:14px; width:100%; padding:14px 0; border:0; border-bottom:1px solid #e1e1eb; background:#fff; color:#111b50; font-size:17px; text-align:left; cursor:pointer; }
.demo-method:hover,.demo-method.active { color:#6732e8; }
.demo-method-icon { width:38px; height:38px; display:grid; place-items:center; background:#f6f3ff; border-radius:5px; color:#7036e8; font-size:22px; }
.demo-radio { margin-left:auto; width:28px; height:28px; border:2px solid #ececf4; border-radius:50%; display:grid; place-items:center; color:#fff; }
.active .demo-radio { background:#7036dc; border-color:#7036dc; }
.demo-details { background:#fff; border:1px solid #dcdce8; border-radius:12px; box-shadow:0 8px 28px #24104b0d; overflow:hidden; z-index:2; }
.demo-details-header { padding:22px; background:#f0eff8; display:flex; align-items:center; gap:14px; font-size:22px; color:#7036e8; }
.demo-details-icon { width:42px; height:42px; display:grid; place-items:center; background:#fff; border-radius:6px; font-size:26px; }
.demo-details-body { padding:26px; }
.demo-field { margin-bottom:24px; }
.demo-field label { display:block; color:#8c80a7; font-size:14px; margin:0 0 8px 10px; }
.demo-field input,.demo-field select { width:100%; padding:14px 16px; border:1.5px solid #bdb0d8; border-radius:7px; font-size:16px; color:#250d57; background:#fff; }
.demo-field input[readonly] { background:#f9f8fc; }
.demo-card-grid { display:grid; grid-template-columns:minmax(0,1fr) 112px; gap:12px; }
.demo-card-expiry { max-width:112px; }
.demo-card-fields input { letter-spacing:.04em; }
.demo-help { font-size:12px; color:#77758f; margin-top:7px; line-height:1.5; }
.demo-submit { width:100%; padding:15px; border:0; border-radius:7px; background:#6e35df; color:#fff; font-size:16px; font-weight:bold; cursor:pointer; }
.demo-submit:hover { background:#5526c2; }
.demo-submit.demo-fail { margin-top:10px; background:#e2e8f0; color:#172033; }
.demo-test-fail { display:block; width:100%; margin-top:12px; border:0; padding:7px; color:#64748b; background:transparent; font-size:12px; cursor:pointer; text-decoration:underline; }
.demo-notice { margin-top:16px; padding:12px; background:#f1edff; color:#492394; border-radius:6px; font-size:13px; line-height:1.6; }
.demo-notice.error { background:#fff0f0; color:#a21d32; }
.demo-hidden { display:none; }
@media(max-width:700px) { .checkout-heading { margin:24px auto 14px; } .checkout-heading h1 { font-size:21px; } .secure-pill { font-size:0; padding:8px; } .secure-pill i { font-size:13px; } .demo-layout { grid-template-columns:1fr; gap:16px; max-width:492px; margin:0 auto 48px; } .demo-brand { padding:18px; font-size:21px; } .demo-checkout-body { padding:18px; } .demo-details-header { padding:18px; font-size:20px; } .demo-details-body { padding:20px 18px; } }
</style>
<div class="checkout-heading">
  <div><h1>Complete your booking</h1><p>Choose a payment option to continue.</p></div>
  <span class="secure-pill"><i class="fas fa-lock"></i> Demo checkout</span>
</div>
<div class="demo-layout">
  <section class="demo-checkout">
    <div class="demo-brand"><div class="demo-logo">G</div><span>Paying Gopi Travels</span></div>
    <div class="demo-checkout-body">
      <div class="demo-amount"><div class="demo-amount-label">Booking amount</div><div class="demo-amount-value"><?= format_price($checkout['amount'] ?? 0) ?></div></div>
      <div class="trip-summary"><div><strong><?= payment_escape($bus['source_city'] . ' → ' . $bus['destination_city']) ?></strong><?= payment_escape(format_date($booking['journey_date'])) ?></div><div style="text-align:right"><strong>Seat<?= strpos($booking['seat_numbers'], ',') !== false ? 's' : '' ?></strong><?= payment_escape($booking['seat_numbers']) ?></div></div>
      <div class="demo-subheading">Select payment option</div>
      <div id="methodList">
        <?php $icons = ['Cards' => '▰', 'Net Banking' => '▤', 'UPI' => '➤', 'PayPal' => '℘', 'Diners Club' => '◉']; foreach ($methods as $method): $active = ($method === $selectedMethod); ?>
          <button type="button" class="demo-method <?= $active ? 'active' : '' ?>" data-method="<?= payment_escape($method) ?>" onclick="selectMethod(this)" aria-pressed="<?= $active ? 'true' : 'false' ?>">
            <span class="demo-method-icon"><?= $icons[$method] ?></span><span><?= payment_escape($method) ?></span><span class="demo-radio"><?= $active ? '✓' : '' ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <section class="demo-details">
    <div class="demo-details-header"><div class="demo-details-icon" id="detailIcon">▤</div><span id="detailTitle"><?= payment_escape($selectedMethod) ?></span></div>
    <div class="demo-details-body">
      <form method="POST" action="payment.php" id="paymentForm">
        <input type="hidden" name="gateway_submit" value="1">
        <input type="hidden" name="csrf_token" value="<?= payment_escape($_SESSION['csrf_token']) ?>">
        <input type="hidden" name="checkout_token" value="<?= payment_escape($checkout['token'] ?? '') ?>">
        <input type="hidden" name="payment_method" id="paymentMethod" value="<?= payment_escape($selectedMethod) ?>">
        <div class="demo-field payment-fields" id="netFields"><label for="bank">Bank</label><select id="bank"><option>HDFC Bank</option><option>State Bank of India</option><option>ICICI Bank</option><option>Axis Bank</option><option>Other Bank</option></select></div>
        <div class="demo-field payment-fields demo-hidden demo-card-fields" id="cardFields">
          <label for="demoCardNumber">Card number</label>
          <input id="demoCardNumber" type="text" inputmode="numeric" autocomplete="off" maxlength="23" placeholder="1234 5678 9012 3456" aria-describedby="cardDemoNote">
          <div class="demo-card-grid" style="margin-top:16px">
            <div><label for="demoCardExpiry">Expiry date</label><input id="demoCardExpiry" type="text" inputmode="numeric" autocomplete="off" maxlength="5" placeholder="MM/YY"></div>
            <div><label for="demoCardCvv">CVV</label><input id="demoCardCvv" type="password" inputmode="numeric" autocomplete="off" maxlength="4" placeholder="•••"></div>
          </div>
          <div class="demo-help" id="cardDemoNote">Demo fields only. These values are not submitted, stored, or sent to a payment provider.</div>
        </div>
        <div class="demo-field payment-fields demo-hidden" id="upiFields"><label>UPI ID (example only)</label><input type="text" value="demo@example" readonly><div class="demo-help">This is a demonstration value, not a real UPI ID.</div></div>
        <div class="demo-field payment-fields demo-hidden" id="paypalFields"><label>PayPal</label><input type="text" value="Demo checkout — no account connection" readonly></div>
        <button type="submit" name="simulate" value="success" class="demo-submit"><i class="fas fa-lock"></i> Pay <?= format_price($checkout['amount'] ?? 0) ?> (Demo)</button>
        <button type="submit" name="simulate" value="failed" class="demo-test-fail">Test a declined payment</button>
        <div class="demo-notice"><strong>Demo mode</strong><br>This checkout simulates a result only. It does not connect to a bank or payment provider and never charges money. Do not enter real payment credentials.</div>
        <?php if ($message !== ''): ?><div class="demo-notice <?= $messageType === 'error' ? 'error' : '' ?>" role="status"><?= payment_escape($message) ?></div><?php endif; ?>
      </form>
    </div>
  </section>
</div>
<script>
const fieldIds = {'Cards':'cardFields','Net Banking':'netFields','UPI':'upiFields','PayPal':'paypalFields','Diners Club':'cardFields'};
const icons = {'Cards':'▰','Net Banking':'▤','UPI':'➤','PayPal':'℘','Diners Club':'◉'};
function selectMethod(button) {
  const method = button.dataset.method;
  document.getElementById('paymentMethod').value = method;
  document.querySelectorAll('.demo-method').forEach(item => {
    const active = item.dataset.method === method;
    item.classList.toggle('active', active);
    item.setAttribute('aria-pressed', String(active));
    item.querySelector('.demo-radio').textContent = active ? '✓' : '';
  });
  document.querySelectorAll('.payment-fields').forEach(panel => panel.classList.add('demo-hidden'));
  document.getElementById(fieldIds[method]).classList.remove('demo-hidden');
  document.getElementById('detailTitle').textContent = method;
  document.getElementById('detailIcon').textContent = icons[method];
}
document.addEventListener('DOMContentLoaded', () => {
  const selected = document.querySelector('.demo-method[data-method="' + document.getElementById('paymentMethod').value + '"]');
  if (selected) selectMethod(selected);
  const cardNumber = document.getElementById('demoCardNumber');
  const expiry = document.getElementById('demoCardExpiry');
  const cvv = document.getElementById('demoCardCvv');
  cardNumber?.addEventListener('input', () => {
    const digits = cardNumber.value.replace(/\D/g, '').slice(0, 19);
    cardNumber.value = digits.replace(/(.{4})/g, '$1 ').trim();
  });
  expiry?.addEventListener('input', () => {
    const digits = expiry.value.replace(/\D/g, '').slice(0, 4);
    expiry.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
  });
  cvv?.addEventListener('input', () => {
    cvv.value = cvv.value.replace(/\D/g, '').slice(0, 4);
  });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
