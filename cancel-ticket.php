<?php
/**
 * Gopi Travels - Ticket Cancellation & Instant Refund System
 */

$pageTitle = 'Cancel Ticket & Refund';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

$pnr = isset($_REQUEST['pnr']) ? sanitize($_REQUEST['pnr']) : '';
$phone = isset($_REQUEST['phone']) ? sanitize($_REQUEST['phone']) : '';
$booking = null;
$cancelledSuccessfully = false;
$refundInfo = null;

// Handle Cancellation Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cancellation'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Invalid security token. Please try again.');
        header('Location: cancel-ticket.php');
        exit;
    }

    $bookingId = (int)$_POST['booking_id'];
    $confirmPnr = sanitize($_POST['pnr']);
    $refundAmount = (float)$_POST['refund_amount'];
    $cancellationCharge = (float)$_POST['cancellation_charge'];
    $reason = sanitize($_POST['cancellation_reason'] ?? 'Change of Plans');
    $refundUpiId = sanitize($_POST['refund_upi_id'] ?? '');

    if ($pdo) {
        try {
            $pdo->beginTransaction();

            // Update booking status
            $updStmt = $pdo->prepare("UPDATE bookings SET booking_status = 'Cancelled' WHERE id = ? AND booking_reference = ?");
            $updStmt->execute([$bookingId, $confirmPnr]);

            // Insert cancellation record
            $cancStmt = $pdo->prepare("INSERT INTO cancellations (booking_id, booking_reference, refund_amount, cancellation_charge, cancellation_reason, refund_upi_id) VALUES (?, ?, ?, ?, ?, ?)");
            $cancStmt->execute([$bookingId, $confirmPnr, $refundAmount, $cancellationCharge, $reason, $refundUpiId]);

            $pdo->commit();

            $cancelledSuccessfully = true;
            $refundInfo = [
                'pnr' => $confirmPnr,
                'refund_amount' => $refundAmount,
                'cancellation_charge' => $cancellationCharge,
                'reason' => $reason,
                'refund_upi_id' => $refundUpiId,
                'refund_ref' => 'REF-' . strtoupper(bin2hex(random_bytes(4)))
            ];
            set_flash('success', "Ticket {$confirmPnr} cancelled successfully. Refund of " . format_price($refundAmount) . " initiated.");
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('danger', 'Unable to process cancellation: ' . $e->getMessage());
        }
    }
}

// Fetch booking if PNR provided
if (!empty($pnr) && $pdo && !$cancelledSuccessfully) {
    $stmt = $pdo->prepare("SELECT bk.*, b.bus_number, b.bus_class, b.departure_time, r.source_city, r.destination_city 
                           FROM bookings bk
                           JOIN buses b ON bk.bus_id = b.id
                           JOIN routes r ON b.route_id = r.id
                           WHERE bk.booking_reference = ?");
    $stmt->execute([$pnr]);
    $booking = $stmt->fetch();

    if (!$booking) {
        set_flash('danger', "No active ticket found matching PNR: {$pnr}");
    } elseif ($booking['booking_status'] === 'Cancelled') {
        set_flash('warning', "This ticket ({$pnr}) is ALREADY CANCELLED.");
    }
}

// Calculate refund percentages if booking is active
$refundPercentage = 90;
$cancellationChargePct = 10;

if ($booking && $booking['booking_status'] === 'Confirmed') {
    $journeyDateTimeStr = $booking['journey_date'] . ' ' . $booking['departure_time'];
    $journeyTimestamp = strtotime($journeyDateTimeStr);
    $currentTimestamp = time();
    $diffHours = ($journeyTimestamp - $currentTimestamp) / 3600;

    if ($diffHours > 24) {
        $refundPercentage = 90;
        $cancellationChargePct = 10;
    } elseif ($diffHours > 12) {
        $refundPercentage = 75;
        $cancellationChargePct = 25;
    } elseif ($diffHours > 4) {
        $refundPercentage = 50;
        $cancellationChargePct = 50;
    } else {
        $refundPercentage = 0;
        $cancellationChargePct = 100;
    }

    $cancellationCharge = round(($booking['total_amount'] * $cancellationChargePct) / 100, 2);
    $refundAmount = max(0, $booking['total_amount'] - $cancellationCharge);
}
?>

<div class="container" style="padding: 50px 20px 80px;">
    <div style="max-width: 780px; margin: 0 auto;">
        
        <div style="text-align: center; margin-bottom: 36px;">
            <div style="width: 60px; height: 60px; background: #fee2e2; color: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px;">
                <i class="fas fa-ban"></i>
            </div>
            <h1 style="font-size: 30px; font-weight: 800; color: #0f172a;">Cancel Ticket & Refund Request</h1>
            <p style="color: #64748b; font-size: 15px; margin-top: 6px;">
                Instant online cancellation with transparent passenger refund slabs.
            </p>
        </div>

        <?php if ($cancelledSuccessfully && $refundInfo): ?>
            <!-- Cancellation Success Receipt -->
            <div style="background: #ffffff; border: 2px solid #10b981; border-radius: 16px; padding: 36px; box-shadow: var(--shadow-lg); text-align: center;">
                <div style="width: 70px; height: 70px; background: #d1fae5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;">
                    <i class="fas fa-check"></i>
                </div>
                <h2 style="font-size: 24px; font-weight: 800; color: #065f46; margin-bottom: 8px;">Cancellation Confirmed</h2>
                <p style="color: #64748b; font-size: 14px; margin-bottom: 24px;">
                    Your booking reference <strong><?= htmlspecialchars($refundInfo['pnr']); ?></strong> has been successfully cancelled.
                </p>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; max-width: 500px; margin: 0 auto 28px; text-align: left;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span style="color:#64748b; font-size:13px;">Refund Reference:</span>
                        <strong style="font-family:monospace;"><?= $refundInfo['refund_ref']; ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span style="color:#64748b; font-size:13px;">Cancellation Fee Deducted:</span>
                        <strong style="color:#dc2626;"><?= format_price($refundInfo['cancellation_charge']); ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:16px; border-top:1px dashed #cbd5e1; padding-top:10px;">
                        <span style="color:#0f172a; font-weight:700;">Net Refund Amount:</span>
                        <strong style="color:#059669; font-size:18px;"><?= format_price($refundInfo['refund_amount']); ?></strong>
                    </div>
                    <div style="font-size:11px; color:#94a3b8; margin-top:10px;">
                        * Refund will be credited back to your provided UPI ID (<strong><?= htmlspecialchars($refundInfo['refund_upi_id'] ?? 'Original Source'); ?></strong>) after 15 days.
                    </div>
                </div>

                <div style="display: flex; justify-content: center; gap: 14px;">
                    <a href="index.php" class="btn-search-bus" style="height: 46px; padding: 0 24px;">
                        <i class="fas fa-house me-2"></i> Return to Home
                    </a>
                    <a href="search.php" class="btn-ticket-print" style="background:#0b132b; color:#fff;">
                        <i class="fas fa-ticket me-2"></i> Book New Ticket
                    </a>
                </div>
            </div>

        <?php elseif ($booking && $booking['booking_status'] === 'Confirmed'): ?>
            <!-- Review & Confirm Cancellation Form -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; box-shadow: var(--shadow-md); margin-bottom: 30px;">
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 16px; display:flex; justify-content:space-between; align-items:center;">
                    <span><i class="fas fa-ticket-simple text-primary me-2"></i> Ticket Details</span>
                    <span class="badge" style="background:#ffedd5; color:#c2410c; font-size:13px; font-family:monospace; padding:4px 10px; border-radius:6px;"><?= $booking['booking_reference']; ?></span>
                </h3>

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 24px;">
                    <div>
                        <span style="font-size: 11px; color: #64748b; display: block;">Route</span>
                        <strong><?= htmlspecialchars($booking['source_city']); ?> &rarr; <?= htmlspecialchars($booking['destination_city']); ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: #64748b; display: block;">Journey Date & Time</span>
                        <strong><?= format_date($booking['journey_date']); ?>, <?= format_time($booking['departure_time']); ?></strong>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: #64748b; display: block;">Seats</span>
                        <strong style="color:var(--primary);"><?= htmlspecialchars($booking['seat_numbers']); ?> (<?= $booking['total_passengers']; ?> Pax)</strong>
                    </div>
                </div>

                <!-- Refund Slab Calculation Box -->
                <div style="border: 2px dashed #f97316; border-radius: 12px; padding: 20px; background: #fff7ed; margin-bottom: 24px;">
                    <div style="font-size: 14px; font-weight: 800; color: #9a3412; margin-bottom: 12px;">
                        <i class="fas fa-calculator me-1"></i> Refund Slab Calculation (Standard Policy)
                    </div>
                    <table style="width: 100%; font-size: 13px;">
                        <tr>
                            <td style="padding: 4px 0; color: #7c2d12;">Total Fare Paid:</td>
                            <td style="text-align: right; font-weight: 700;"><?= format_price($booking['total_amount']); ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0; color: #7c2d12;">Cancellation Fee (<?= $cancellationChargePct; ?>%):</td>
                            <td style="text-align: right; font-weight: 700; color: #dc2626;">- <?= format_price($cancellationCharge); ?></td>
                        </tr>
                        <tr style="border-top: 1px solid #fed7aa; font-size: 16px; font-weight: 800;">
                            <td style="padding-top: 8px; color: #431407;">Estimated Refund (<?= $refundPercentage; ?>%):</td>
                            <td style="padding-top: 8px; text-align: right; color: #059669; font-size: 18px;"><?= format_price($refundAmount); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Cancellation Form -->
                <form action="cancel-ticket.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel ticket <?= $booking['booking_reference']; ?>? This action is irreversible.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()); ?>">
                    <input type="hidden" name="booking_id" value="<?= $booking['id']; ?>">
                    <input type="hidden" name="pnr" value="<?= htmlspecialchars($booking['booking_reference']); ?>">
                    <input type="hidden" name="refund_amount" value="<?= $refundAmount; ?>">
                    <input type="hidden" name="cancellation_charge" value="<?= $cancellationCharge; ?>">

                    <div style="margin-bottom: 20px;">
                        <label class="form-label-custom"><i class="fas fa-comment-dots"></i> Reason for Cancellation</label>
                        <select name="cancellation_reason" class="form-control-custom" style="height: 44px;" required>
                            <option value="Change of travel schedule">Change of travel schedule</option>
                            <option value="Personal / Medical emergency">Personal / Medical emergency</option>
                            <option value="Booked alternate bus / train">Booked alternate bus / train</option>
                            <option value="Incorrect date selected">Incorrect date selected</option>
                            <option value="Other reasons">Other reasons</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <label class="form-label-custom"><i class="fas fa-qrcode"></i> UPI ID for Refund</label>
                        <input type="text" name="refund_upi_id" class="form-control-custom" style="height: 44px;" placeholder="Enter your UPI ID (e.g. name@bank)" required>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="print-ticket.php" style="color: #64748b; font-weight: 600; font-size: 14px;">
                            <i class="fas fa-arrow-left me-1"></i> Don't Cancel
                        </a>
                        <button type="submit" name="confirm_cancellation" value="1" class="btn-search-bus" style="background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 8px 20px -4px rgba(239, 68, 68, 0.4);">
                            <i class="fas fa-ban me-2"></i> Confirm Ticket Cancellation
                        </button>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <!-- Initial Lookup Form -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; box-shadow: var(--shadow-md); margin-bottom: 30px;">
                <form action="cancel-ticket.php" method="GET">
                    <div class="form-group-custom" style="margin-bottom: 16px;">
                        <label class="form-label-custom"><i class="fas fa-ticket"></i> Enter PNR / Booking Reference</label>
                        <input type="text" name="pnr" class="form-control-custom" placeholder="e.g. JAY-849201" value="<?= htmlspecialchars($pnr); ?>" required>
                    </div>

                    <div class="form-group-custom" style="margin-bottom: 24px;">
                        <label class="form-label-custom"><i class="fas fa-phone"></i> Registered Passenger Mobile Number</label>
                        <input type="tel" name="phone" class="form-control-custom" placeholder="10-digit mobile number" value="<?= htmlspecialchars($phone); ?>" pattern="[0-9]{10}" required>
                    </div>

                    <button type="submit" class="btn-search-bus" style="width: 100%;">
                        <i class="fas fa-magnifying-glass me-2"></i> Find Ticket & Calculate Refund
                    </button>
                </form>
            </div>

            <!-- Cancellation Policy Info -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: var(--shadow-sm);">
                <h4 style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">
                    <i class="fas fa-info-circle text-primary me-2"></i> Gopi Travels Cancellation Policy
                </h4>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; font-size: 12px; text-align: center;">
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px;">
                        <strong style="color: #166534; display: block; font-size: 14px;">90% Refund</strong>
                        <span style="color: #15803d;">> 24 hours before departure</span>
                    </div>
                    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px;">
                        <strong style="color: #92400e; display: block; font-size: 14px;">75% Refund</strong>
                        <span style="color: #b45309;">12 to 24 hours before</span>
                    </div>
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px;">
                        <strong style="color: #991b1b; display: block; font-size: 14px;">50% Refund</strong>
                        <span style="color: #b91c1c;">4 to 12 hours before</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
