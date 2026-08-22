<?php
/**
 * Gopi Travels - Official E-Ticket View & Printable Layout
 */

$pageTitle = 'E-Ticket Confirmation';
require_once __DIR__ . '/includes/header.php';

$pnr = isset($_GET['pnr']) ? sanitize($_GET['pnr']) : '';

if (empty($pnr)) {
    set_flash('danger', 'Please provide a valid Booking Reference (PNR).');
    header('Location: print-ticket.php');
    exit;
}

$pdo = get_db_connection();
$booking = null;

if ($pdo) {
    $stmt = $pdo->prepare("SELECT bk.*, b.bus_number, b.bus_class, b.departure_time, b.arrival_time, b.is_sleeper, b.is_ac, b.amenities,
                                  r.source_city, r.destination_city, r.distance_km, r.estimated_time, r.via_cities
                           FROM bookings bk
                           JOIN buses b ON bk.bus_id = b.id
                           JOIN routes r ON b.route_id = r.id
                           WHERE bk.booking_reference = ?");
    $stmt->execute([$pnr]);
    $booking = $stmt->fetch();
}

if (!$booking) {
    set_flash('danger', 'No ticket found matching PNR: ' . htmlspecialchars($pnr));
    header('Location: print-ticket.php');
    exit;
}

$passengers = json_decode($booking['passenger_details'], true) ?: [];
$classMeta = get_bus_class_meta($booking['bus_class']);
$isCancelled = ($booking['booking_status'] === 'Cancelled');

// Reporting time (15 mins prior to departure)
$depTimestamp = strtotime($booking['departure_time']);
$reportingTime = date('h:i A', $depTimestamp - 900);
?>

<div class="ticket-page-container">
    
    <!-- Ticket Action Buttons Bar (Hidden on Print) -->
    <div class="ticket-actions-bar">
        <div>
            <a href="index.php" class="btn-ticket-print" style="background:#f1f5f9; color:#334155;">
                <i class="fas fa-arrow-left me-1"></i> Back to Home
            </a>
        </div>
        <div style="display:flex; gap:10px;">
            <?php if (!$isCancelled): ?>
                <a href="cancel-ticket.php?pnr=<?= urlencode($booking['booking_reference']); ?>" class="btn-ticket-print" style="background:#fee2e2; color:#b91c1c;">
                    <i class="fas fa-ban me-1"></i> Cancel Ticket
                </a>
            <?php endif; ?>
            <button type="button" class="btn-ticket-print" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print E-Ticket / Save PDF
            </button>
        </div>
    </div>

    <!-- Official Printable E-Ticket Document -->
    <div class="e-ticket-card">
        
        <!-- Ticket Header -->
        <div class="ticket-header">
            <div class="ticket-brand-title">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:40px; height:40px; background:#f97316; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px;">
                        <i class="fas fa-bus-simple"></i>
                    </div>
                    <div>
                        <h2>JAYAMBE TRAVELS (GUJARAT)</h2>
                        <p>Gujarat State Deluxe Bus Service &bull; Electronic Reservation Slip (ERS)</p>
                    </div>
                </div>
            </div>
            
            <div class="pnr-highlight-box">
                <div class="pnr-lbl">Booking Reference / PNR</div>
                <div class="pnr-val"><?= htmlspecialchars($booking['booking_reference']); ?></div>
                <div style="font-size:10px; color:#cbd5e1;"><?= $isCancelled ? '<span style="color:#ef4444; font-weight:800;">CANCELLED</span>' : '<span style="color:#10b981; font-weight:800;">CONFIRMED</span>'; ?></div>
            </div>
        </div>

        <div class="ticket-body">
            
            <!-- Journey Overview Grid -->
            <div class="ticket-route-grid">
                <div class="ticket-city-block">
                    <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Origin / Boarding Station</span>
                    <h4><?= htmlspecialchars($booking['source_city']); ?> Central Office / Terminal</h4>
                    <p><i class="fas fa-calendar-check me-1 text-primary"></i> <?= format_date($booking['journey_date']); ?></p>
                    <p><i class="fas fa-clock me-1 text-primary"></i> Departure: <strong><?= format_time($booking['departure_time']); ?></strong></p>
                    <p style="font-size:11px; color:#dc2626;"><i class="fas fa-bell me-1"></i> Reporting Time: <strong><?= $reportingTime; ?></strong></p>
                </div>

                <div style="text-align:center;">
                    <div style="font-size:12px; font-weight:700; color:var(--primary);"><?= htmlspecialchars($booking['distance_km']); ?> KM</div>
                    <i class="fas fa-bus text-primary" style="font-size:22px; margin: 4px 0;"></i>
                    <div style="font-size:11px; color:#64748b;"><?= htmlspecialchars($booking['estimated_time']); ?></div>
                </div>

                <div class="ticket-city-block" style="text-align:right;">
                    <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Destination / Drop Station</span>
                    <h4><?= htmlspecialchars($booking['destination_city']); ?> Central Office / Terminal</h4>
                    <p><i class="fas fa-calendar-check me-1 text-primary"></i> <?= format_date($booking['journey_date']); ?></p>
                    <p><i class="fas fa-clock me-1 text-primary"></i> Est. Arrival: <strong><?= format_time($booking['arrival_time']); ?></strong></p>
                    <p style="font-size:11px; color:#10b981;"><i class="fas fa-circle-check me-1"></i> Service Status: On Time</p>
                </div>
            </div>

            <!-- Coach & Service Details Row -->
            <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:12px; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:20px; font-size:13px;">
                <div>
                    <span style="color:#64748b; font-size:11px; display:block;">Service / Bus No.</span>
                    <strong><?= htmlspecialchars($booking['bus_number']); ?></strong>
                </div>
                <div>
                    <span style="color:#64748b; font-size:11px; display:block;">Bus Class</span>
                    <strong><?= htmlspecialchars($booking['bus_class']); ?></strong>
                </div>
                <div>
                    <span style="color:#64748b; font-size:11px; display:block;">Total Passengers</span>
                    <strong><?= htmlspecialchars($booking['total_passengers']); ?> Pax</strong>
                </div>
                <div>
                    <span style="color:#64748b; font-size:11px; display:block;">Confirmed Seat(s)</span>
                    <strong style="color:var(--primary); font-size:15px;"><?= htmlspecialchars($booking['seat_numbers']); ?></strong>
                </div>
            </div>

            <!-- Via Stations -->
            <?php if (!empty($booking['via_cities'])): ?>
                <div style="font-size:12px; color:#475569; margin-bottom:18px; padding:8px 12px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
                    <strong>Via Stations:</strong> <?= htmlspecialchars($booking['via_cities']); ?>
                </div>
            <?php endif; ?>

            <!-- Passenger Table -->
            <h4 style="font-size:14px; font-weight:800; color:#0f172a; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">
                Passenger Details
            </h4>
            <table class="ticket-table">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Passenger Name</th>
                        <th style="width:70px;">Age</th>
                        <th style="width:80px;">Gender</th>
                        <th style="width:100px;">Seat No.</th>
                        <th style="width:100px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($passengers)): ?>
                        <?php foreach ($passengers as $idx => $pax): ?>
                            <tr>
                                <td><?= $idx + 1; ?></td>
                                <td><strong><?= htmlspecialchars($pax['name']); ?></strong></td>
                                <td><?= htmlspecialchars($pax['age']); ?> yrs</td>
                                <td><?= htmlspecialchars($pax['gender']); ?></td>
                                <td><span style="font-weight:800; color:var(--primary);"><?= htmlspecialchars($pax['seat'] ?? $booking['seat_numbers']); ?></span></td>
                                <td><span style="color:<?= $isCancelled ? '#ef4444' : '#10b981'; ?>; font-weight:700;"><?= $booking['booking_status']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td>1</td>
                            <td><strong><?= htmlspecialchars($booking['passenger_name']); ?></strong></td>
                            <td>32 yrs</td>
                            <td>Male</td>
                            <td><span style="font-weight:800; color:var(--primary);"><?= htmlspecialchars($booking['seat_numbers']); ?></span></td>
                            <td><span style="color:<?= $isCancelled ? '#ef4444' : '#10b981'; ?>; font-weight:700;"><?= $booking['booking_status']; ?></span></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Fare Summary Table -->
            <h4 style="font-size:14px; font-weight:800; color:#0f172a; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.5px;">
                Fare & Payment Breakdown
            </h4>
            <table class="ticket-table">
                <tbody>
                    <tr>
                        <td><strong>Basic Passenger Fare:</strong></td>
                        <td style="text-align:right;"><?= format_price($booking['base_fare']); ?></td>
                        <td><strong>Payment Mode:</strong></td>
                        <td style="text-align:right;"><?= htmlspecialchars($booking['payment_method']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>GST (CGST 2.5% + SGST 2.5%):</strong></td>
                        <td style="text-align:right;"><?= format_price($booking['gst_amount']); ?></td>
                        <td><strong>Transaction Ref:</strong></td>
                        <td style="text-align:right; font-family:monospace;"><?= htmlspecialchars($booking['transaction_id']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Toll & Service Fee:</strong></td>
                        <td style="text-align:right;">₹15.00</td>
                        <td><strong>Payment Status:</strong></td>
                        <td style="text-align:right; color:#10b981; font-weight:700;"><?= htmlspecialchars($booking['payment_status']); ?></td>
                    </tr>
                    <tr style="background:#f8fafc; font-size:15px; font-weight:800;">
                        <td colspan="3" style="text-align:right;">Total Amount Paid (INR):</td>
                        <td style="text-align:right; color:var(--primary); font-size:16px;"><?= format_price($booking['total_amount']); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Security Barcode & QR Code Section -->
            <div class="ticket-footer-security">
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <div style="font-size:11px; color:#64748b; font-weight:700;">DIGITAL SECURITY BARCODE (CODE-128)</div>
                    <div>
                        <?= generate_barcode_svg($booking['booking_reference']); ?>
                    </div>
                    <div style="font-size:10px; color:#94a3b8;">
                        Booked On: <?= date('d M Y, h:i A', strtotime($booking['created_at'])); ?> &bull; Helpline: <?= HELPLINE_NUMBER; ?>
                    </div>
                </div>

                <div style="text-align:right; display:flex; align-items:center; gap:14px;">
                    <div style="text-align:right;">
                        <div style="font-size:11px; font-weight:700; color:#0f172a;">DIGITAL PASS</div>
                        <div style="font-size:10px; color:#64748b;">Scan to verify with conductor handheld terminal</div>
                    </div>
                    <div>
                        <?= generate_qr_svg($booking['booking_reference'] . '|' . $booking['journey_date'] . '|' . $booking['seat_numbers']); ?>
                    </div>
                </div>
            </div>

            <!-- Terms & Conditions Footer -->
            <div style="margin-top:24px; padding-top:14px; border-top:1px solid #e2e8f0; font-size:11px; color:#64748b; line-height:1.5;">
                <strong>Important Instructions for Passenger:</strong>
                <ol style="margin-left:18px; margin-top:4px;">
                    <li>Passenger must carry this Electronic Reservation Slip (ERS) along with a valid Original Government Photo ID (Aadhaar Card, Driving Licence, Voter ID, or Passport).</li>
                    <li>Please report at the origin boarding office / platform at least 15 minutes prior to the scheduled departure time.</li>
                    <li>Luggage up to 20 kg per passenger is allowed free of charge. Commercial freight is subject to tariff rules.</li>
                    <li>Smoking, consumption of alcohol, and carrying hazardous materials is strictly prohibited inside the coach.</li>
                </ol>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
