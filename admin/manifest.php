<?php
/**
 * Gopi Travels - Bus Driver/Conductor Passenger Trip Manifest
 */

$pageTitle = 'Bus Trip Passenger Manifest';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

$selectedBusId = isset($_GET['bus_id']) ? (int)$_GET['bus_id'] : 1;
$selectedDate = isset($_GET['journey_date']) ? sanitize($_GET['journey_date']) : date('Y-m-d');

// Fetch all buses for selector
$allBuses = [];
if ($pdo) {
    $bStmt = $pdo->query("SELECT b.*, r.source_city, r.destination_city 
                          FROM buses b 
                          JOIN routes r ON b.route_id = r.id 
                          WHERE b.status = 'Active' 
                          ORDER BY r.source_city, r.destination_city, b.departure_time ASC");
    $allBuses = $bStmt->fetchAll();
}

// Fetch selected bus details
$busInfo = null;
$passengerRoster = [];
$totalCollected = 0;

if ($pdo && $selectedBusId > 0) {
    $infoStmt = $pdo->prepare("SELECT b.*, r.source_city, r.destination_city, r.distance_km, r.estimated_time, r.via_cities 
                              FROM buses b 
                              JOIN routes r ON b.route_id = r.id 
                              WHERE b.id = ?");
    $infoStmt->execute([$selectedBusId]);
    $busInfo = $infoStmt->fetch();

    if ($busInfo) {
        $bkStmt = $pdo->prepare("SELECT bk.*, u.name as cashier_name 
                                FROM bookings bk 
                                LEFT JOIN users u ON bk.booked_by_user_id = u.id
                                WHERE bk.bus_id = ? AND bk.journey_date = ? AND bk.booking_status = 'Confirmed'
                                ORDER BY bk.id ASC");
        $bkStmt->execute([$selectedBusId, $selectedDate]);
        $bookings = $bkStmt->fetchAll();

        foreach ($bookings as $bItem) {
            $totalCollected += (float)$bItem['total_amount'];
            $paxList = !empty($bItem['passenger_details']) ? json_decode($bItem['passenger_details'], true) : [];

            if (!empty($paxList)) {
                foreach ($paxList as $p) {
                    $passengerRoster[] = [
                        'seat' => $p['seat'] ?? $bItem['seat_numbers'],
                        'name' => $p['name'] ?? $bItem['passenger_name'],
                        'age' => $p['age'] ?? 30,
                        'gender' => $p['gender'] ?? 'Male',
                        'phone' => $bItem['passenger_phone'],
                        'pnr' => $bItem['booking_reference'],
                        'channel' => $bItem['booking_channel'],
                        'boarding' => $bItem['boarding_point'],
                        'fare' => $busInfo['fare']
                    ];
                }
            } else {
                $passengerRoster[] = [
                    'seat' => $bItem['seat_numbers'],
                    'name' => $bItem['passenger_name'],
                    'age' => 30,
                    'gender' => 'Male',
                    'phone' => $bItem['passenger_phone'],
                    'pnr' => $bItem['booking_reference'],
                    'channel' => $bItem['booking_channel'],
                    'boarding' => $bItem['boarding_point'],
                    'fare' => $bItem['total_amount']
                ];
            }
        }

        // Sort by seat number
        usort($passengerRoster, function($a, $b) {
            return strnatcasecmp($a['seat'], $b['seat']);
        });
    }
}

$bookedCount = count($passengerRoster);
$totalSeats = $busInfo['total_seats'] ?? 40;
$vacantCount = max(0, $totalSeats - $bookedCount);
?>

<style>
@media print {
    .admin-navbar, .top-bar, .admin-footer, .manifest-controls, .btn-print-manifest {
        display: none !important;
    }
    body {
        background: #ffffff !important;
        padding: 0 !important;
    }
    .manifest-card {
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>

<div class="container-fluid" style="max-width: 1440px; margin: 30px auto 60px; padding: 0 20px;">

    <!-- Controls Selector Toolbar (Hidden in Print) -->
    <div class="manifest-controls" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--radius-lg); padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <form action="manifest.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto auto; gap: 14px; align-items: end;">
            
            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-bus"></i> Select Bus Trip Schedule</label>
                <select name="bus_id" class="form-control-custom" style="height: 46px; font-size: 13px;" required>
                    <?php foreach ($allBuses as $b): ?>
                        <option value="<?= $b['id']; ?>" <?= $b['id'] == $selectedBusId ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($b['source_city']); ?> &rarr; <?= htmlspecialchars($b['destination_city']); ?> | <?= date('h:i A', strtotime($b['departure_time'])); ?> | <?= htmlspecialchars($b['bus_number']); ?> (<?= htmlspecialchars($b['bus_class']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-calendar-day"></i> Journey Date</label>
                <input type="date" name="journey_date" class="form-control-custom" style="height: 46px; font-size: 13px;" value="<?= htmlspecialchars($selectedDate); ?>" required>
            </div>

            <button type="submit" class="btn-search-bus" style="height: 46px; font-size: 14px; padding: 0 20px;">
                <i class="fas fa-filter me-1"></i> Generate Roster
            </button>

            <button type="button" onclick="window.print()" class="btn-search-bus btn-print-manifest" style="height: 46px; font-size: 14px; padding: 0 20px; background: #0284c7; border: none;">
                <i class="fas fa-print me-1"></i> Print Manifest
            </button>

        </form>
    </div>

    <!-- Official Printable Manifest Document -->
    <?php if ($busInfo): ?>
        <div class="manifest-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--radius-lg); padding: 30px; box-shadow: var(--shadow-md);">
            
            <!-- Manifest Header -->
            <div style="border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-start;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <img src="../assets/images/logo.svg" alt="Gopi Travels" style="height: 48px; width: auto;" onerror="this.style.display='none';">
                    <div>
                        <h2 style="font-size: 20px; font-weight: 900; color: #0f172a; margin: 0;">JAYAMBE TRAVELS</h2>
                        <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">
                            Official Bus Passenger Trip Manifest & Conductor Duty Slip
                        </span>
                    </div>
                </div>

                <div style="text-align: right; font-size: 12px; color: #475569;">
                    <div><strong>MANIFEST REF:</strong> MAN-<?= date('Ymd', strtotime($selectedDate)); ?>-<?= $busInfo['id']; ?></div>
                    <div><strong>DATE OF PRINT:</strong> <?= date('d/m/Y h:i A'); ?></div>
                </div>
            </div>

            <!-- Bus Schedule Specs Grid -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; font-size: 13px;">
                <div>
                    <span style="color: #64748b; font-size: 11px; display: block; text-transform: uppercase;">Corridor / Route</span>
                    <strong style="color: #0f172a; font-size: 15px;"><?= htmlspecialchars($busInfo['source_city']); ?> &rarr; <?= htmlspecialchars($busInfo['destination_city']); ?></strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; display: block; text-transform: uppercase;">Departure Time</span>
                    <strong style="color: var(--primary); font-size: 15px;"><?= date('h:i A', strtotime($busInfo['departure_time'])); ?> (<?= date('d M Y', strtotime($selectedDate)); ?>)</strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; display: block; text-transform: uppercase;">Bus Number & Class</span>
                    <strong style="color: #0f172a;"><?= htmlspecialchars($busInfo['bus_number']); ?> (<?= htmlspecialchars($busInfo['bus_class']); ?>)</strong>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; display: block; text-transform: uppercase;">Passenger Occupancy</span>
                    <strong style="color: #0284c7;"><?= $bookedCount; ?> Booked / <?= $vacantCount; ?> Vacant (Total: <?= $totalSeats; ?>)</strong>
                </div>
            </div>

            <!-- Passenger List Table -->
            <table class="table-custom" style="width: 100%; font-size: 12px; margin-bottom: 24px;">
                <thead>
                    <tr style="background: #0b132b; color: #ffffff;">
                        <th style="width: 40px; text-align: center;">#</th>
                        <th style="width: 70px; text-align: center;">Seat</th>
                        <th>Passenger Name</th>
                        <th>Age / Sex</th>
                        <th>Mobile Number</th>
                        <th>PNR Number</th>
                        <th>Boarding Point</th>
                        <th>Booking Channel</th>
                        <th>Collection</th>
                        <th style="width: 80px; text-align: center;">Onboard [✓]</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($passengerRoster)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 30px; color: #94a3b8;">
                                No passengers booked on this schedule for <?= date('d M Y', strtotime($selectedDate)); ?>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($passengerRoster as $i => $p): 
                            $isOnline = ($p['channel'] === 'online');
                        ?>
                            <tr>
                                <td style="text-align: center; color: #64748b;"><?= ($i + 1); ?></td>
                                <td style="text-align: center;">
                                    <strong style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 2px 6px; border-radius: 4px; font-size: 13px; color: #0f172a;">
                                        <?= htmlspecialchars($p['seat']); ?>
                                    </strong>
                                </td>
                                <td><strong style="color: #0f172a;"><?= htmlspecialchars($p['name']); ?></strong></td>
                                <td><?= htmlspecialchars($p['age']); ?> / <?= htmlspecialchars($p['gender'][0]); ?></td>
                                <td><?= htmlspecialchars($p['phone']); ?></td>
                                <td><span style="font-family: monospace; font-weight: 700; color: var(--primary);"><?= htmlspecialchars($p['pnr']); ?></span></td>
                                <td><?= htmlspecialchars($p['boarding']); ?></td>
                                <td>
                                    <?php if ($isOnline): ?>
                                        <span class="badge-channel-online" style="font-size: 10px;">Web</span>
                                    <?php else: ?>
                                        <span class="badge-channel-counter" style="font-size: 10px;">Counter</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong style="color: #10b981;">PAID</strong></td>
                                <td style="text-align: center;">
                                    <div style="width: 18px; height: 18px; border: 1.5px solid #0f172a; border-radius: 3px; margin: 0 auto;"></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Duty Slip Signoff Footer -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-top: 40px; padding-top: 20px; border-top: 1px dashed #cbd5e1; font-size: 12px; color: #475569;">
                <div>
                    <strong>Driver Name & Sign:</strong>
                    <div style="height: 40px; border-bottom: 1px solid #94a3b8; margin-top: 10px;"></div>
                </div>
                <div>
                    <strong>Conductor Name & Sign:</strong>
                    <div style="height: 40px; border-bottom: 1px solid #94a3b8; margin-top: 10px;"></div>
                </div>
                <div>
                    <strong>Station Master Verification Stamp:</strong>
                    <div style="height: 40px; border-bottom: 1px solid #94a3b8; margin-top: 10px;"></div>
                </div>
            </div>

        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
