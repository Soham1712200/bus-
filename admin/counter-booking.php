<?php
/**
 * Gopi Travels - Offline Branch Office Counter Booking Desk
 * Interactive 4-Color Seat Matrix with Real-Time Locking & Instant Thermal Receipt
 */

require_once __DIR__ . '/includes/auth_guard.php';

$pdo = get_db_connection();

// Process Counter Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_counter_booking'])) {
    $busId = (int)($_POST['bus_id'] ?? 0);
    $journeyDate = sanitize($_POST['journey_date'] ?? date('Y-m-d'));
    $seatNumbersStr = sanitize($_POST['seat_numbers'] ?? '');
    $passengerName = sanitize($_POST['passenger_name'] ?? '');
    $passengerPhone = sanitize($_POST['passenger_phone'] ?? '');
    $passengerEmail = sanitize($_POST['passenger_email'] ?? 'counter@gopitravels.com');
    $boardingPoint = sanitize($_POST['boarding_point'] ?? 'Geeta Mandir Central Terminal');
    $counterPaymentMode = sanitize($_POST['counter_payment_mode'] ?? 'Cash');
    $totalAmount = (float)($_POST['total_amount'] ?? 0);
    
    $passengerNames = $_POST['passenger_names'] ?? [];
    $passengerAges = $_POST['passenger_ages'] ?? [];
    $passengerGenders = $_POST['passenger_genders'] ?? [];

    $seatsArray = array_filter(explode(',', $seatNumbersStr));

    if (empty($seatsArray) || $busId <= 0 || empty($passengerName) || empty($passengerPhone)) {
        set_flash('danger', 'Please select at least one seat and provide passenger name and mobile number.');
    } else {
        try {
            // Check for double booking conflicts
            $conflictStmt = $pdo->prepare("SELECT seat_numbers FROM bookings WHERE bus_id = ? AND journey_date = ? AND booking_status = 'Confirmed'");
            $conflictStmt->execute([$busId, $journeyDate]);
            $existingBookings = $conflictStmt->fetchAll();
            $alreadyBookedSeats = [];
            foreach ($existingBookings as $eb) {
                foreach (explode(',', $eb['seat_numbers']) as $s) {
                    $alreadyBookedSeats[] = trim($s);
                }
            }

            $clashes = array_intersect($seatsArray, $alreadyBookedSeats);
            if (!empty($clashes)) {
                set_flash('danger', 'Seat(s) ' . implode(', ', $clashes) . ' have just been booked by another passenger. Please select alternative seats.');
            } else {
                // Generate Unique PNR and Transaction ID
                $pnr = generate_pnr($pdo);
                $txnId = 'TXN-CTR-' . strtoupper(bin2hex(random_bytes(3)));
                
                // Fetch Bus fare
                $busDataStmt = $pdo->prepare("SELECT fare FROM buses WHERE id = ?");
                $busDataStmt->execute([$busId]);
                $bData = $busDataStmt->fetch();
                $baseFare = ($bData['fare'] ?? 300) * count($seatsArray);
                $gstAmount = round($baseFare * 0.05, 2);
                $grandTotal = $baseFare + $gstAmount;

                // Build Passenger Details JSON
                $paxDetails = [];
                foreach ($seatsArray as $idx => $st) {
                    $paxDetails[] = [
                        'name' => $passengerNames[$idx] ?? ($idx === 0 ? $passengerName : "Passenger " . ($idx + 1)),
                        'age' => (int)($passengerAges[$idx] ?? 30),
                        'gender' => $passengerGenders[$idx] ?? 'Male',
                        'seat' => trim($st)
                    ];
                }

                $insStmt = $pdo->prepare("INSERT INTO bookings (
                    booking_reference, user_id, bus_id, journey_date, seat_numbers, total_passengers,
                    base_fare, gst_amount, total_amount, payment_method, payment_status, transaction_id,
                    passenger_name, passenger_phone, passenger_email, passenger_details, booking_status,
                    booking_channel, booked_by_user_id, boarding_point, counter_payment_mode
                ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid', ?, ?, ?, ?, ?, 'Confirmed', 'counter', ?, ?, ?)");

                $insStmt->execute([
                    $pnr,
                    $busId,
                    $journeyDate,
                    implode(',', $seatsArray),
                    count($seatsArray),
                    $baseFare,
                    $gstAmount,
                    $grandTotal,
                    "Counter ({$counterPaymentMode})",
                    $txnId,
                    $passengerName,
                    $passengerPhone,
                    $passengerEmail,
                    json_encode($paxDetails),
                    $staffUser['id'],
                    $boardingPoint,
                    $counterPaymentMode
                ]);

                // Clear seat locks
                $delLock = $pdo->prepare("DELETE FROM seat_locks WHERE bus_id = ? AND journey_date = ? AND FIND_IN_SET(seat_number, ?)");
                $delLock->execute([$busId, $journeyDate, implode(',', $seatsArray)]);

                set_flash('success', "Counter ticket issued successfully with PNR: {$pnr}");
                header("Location: receipt.php?pnr=" . urlencode($pnr));
                exit;
            }
        } catch (Exception $e) {
            set_flash('danger', 'Error processing counter booking: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Counter Ticket Booking';
require_once __DIR__ . '/includes/header.php';

// Fetch all buses grouped by routes
$busesList = [];
$routesList = [];
if ($pdo) {
    $routesStmt = $pdo->query("SELECT id, source_city, destination_city, via_cities FROM routes ORDER BY source_city, destination_city");
    $routesList = $routesStmt->fetchAll();

    $busesStmt = $pdo->query("SELECT b.*, r.source_city, r.destination_city 
                              FROM buses b 
                              JOIN routes r ON b.route_id = r.id 
                              WHERE b.status = 'Active' 
                              ORDER BY r.source_city, r.destination_city, b.departure_time ASC");
    $busesList = $busesStmt->fetchAll();
}
?>

<div class="container-fluid" style="max-width: 1440px; margin: 24px auto 60px; padding: 0 20px;">

    <!-- Hidden Staff Name for Remote Lock Meta -->
    <input type="hidden" id="activeStaffName" value="<?= htmlspecialchars($staffUser['name'] ?? 'Counter Agent'); ?>">

    <!-- Top Action Heading -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 900; color: #0f172a; margin-bottom: 4px;">
                <i class="fas fa-cash-register text-primary me-2"></i> Offline Counter Ticket Booking Desk
            </h1>
            <p style="color: #64748b; font-size: 13px;">
                Issue manual counter tickets with live 4-color seat locking, cash tender calculations, and 80mm thermal receipt printing.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="bookings.php" class="btn-nav-primary" style="background:#0b132b; font-size:13px; padding:8px 14px;">
                <i class="fas fa-search me-1"></i> Lookup PNR
            </a>
        </div>
    </div>

    <!-- Bus & Journey Date Selector Toolbar -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--radius-lg); padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <div style="display: grid; grid-template-columns: 2fr 1.2fr 1fr; gap: 16px; align-items: end;">
            
            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-bus-simple"></i> Select Gujarat Route & Bus Schedule</label>
                <select id="counterBusSelect" class="form-control-custom" style="height: 48px; font-size: 14px;" required>
                    <option value="">-- Choose Bus Corridor & Departure Time --</option>
                    <?php foreach ($busesList as $b): ?>
                        <option value="<?= $b['id']; ?>" 
                                data-fare="<?= $b['fare']; ?>" 
                                data-sleeper="<?= $b['is_sleeper']; ?>" 
                                data-total-seats="<?= $b['total_seats']; ?>"
                                data-route="<?= htmlspecialchars($b['source_city'] . ' → ' . $b['destination_city']); ?>">
                            <?= htmlspecialchars($b['source_city']); ?> &rarr; <?= htmlspecialchars($b['destination_city']); ?> | <?= date('h:i A', strtotime($b['departure_time'])); ?> | <?= htmlspecialchars($b['bus_class']); ?> | <?= htmlspecialchars($b['bus_number']); ?> (₹<?= number_format($b['fare'], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-calendar-day"></i> Date of Journey</label>
                <input type="date" id="counterDateInput" class="form-control-custom" style="height: 48px; font-size: 14px;" value="<?= date('Y-m-d'); ?>" min="<?= date('Y-m-d'); ?>">
            </div>

            <div>
                <button type="button" class="btn-search-bus" onclick="CounterBooking.onBusOrDateChange()" style="width: 100%; height: 48px; font-size: 14px;">
                    <i class="fas fa-arrows-rotate me-1"></i> Refresh Grid
                </button>
            </div>

        </div>
    </div>

    <!-- 2-Column Booking Workstation -->
    <div style="display: grid; grid-template-columns: 1fr 480px; gap: 24px; align-items: start;">
        
        <!-- Left: Real-Time 4-Color Seat Matrix Panel -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm);">
            
            <!-- Real-Time Seat Status Summary Bar -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px; text-align: center;">
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px;">
                    <div style="font-size: 18px; font-weight: 900; color: #16a34a;" id="summaryCountAvailable">--</div>
                    <div style="font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase;">Available</div>
                </div>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 10px;">
                    <div style="font-size: 18px; font-weight: 900; color: #dc2626;" id="summaryCountOnline">--</div>
                    <div style="font-size: 11px; font-weight: 700; color: #991b1b; text-transform: uppercase;">Booked Online</div>
                </div>
                <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 10px;">
                    <div style="font-size: 18px; font-weight: 900; color: #0284c7;" id="summaryCountCounter">--</div>
                    <div style="font-size: 11px; font-weight: 700; color: #075985; text-transform: uppercase;">Booked Counter</div>
                </div>
                <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 10px;">
                    <div style="font-size: 18px; font-weight: 900; color: #ea580c;" id="summaryCountLocked">--</div>
                    <div style="font-size: 11px; font-weight: 700; color: #9a3412; text-transform: uppercase;">Locked (5m Hold)</div>
                </div>
            </div>

            <!-- Interactive Seat Grid Container -->
            <div style="min-height: 380px; display: flex; align-items: center; justify-content: center; background: #fafafa; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 24px;">
                <div id="counterSeatGridContainer" style="width: 100%;">
                    <div style="text-align: center; color: #94a3b8; padding: 40px 20px;">
                        <i class="fas fa-hand-pointer fa-2x mb-3 text-primary"></i>
                        <p style="font-weight: 600; font-size: 15px;">Please select a bus schedule and journey date above to display the seat matrix.</p>
                    </div>
                </div>
            </div>

            <!-- 4-Color Status Legend Bar -->
            <div class="seat-legend-4colors">
                <div class="legend-chip">
                    <div class="chip-sample avail"></div>
                    <span>Available</span>
                </div>
                <div class="legend-chip">
                    <div class="chip-sample online"></div>
                    <span>Booked Online (Web)</span>
                </div>
                <div class="legend-chip">
                    <div class="chip-sample counter"></div>
                    <span>Booked Offline (Counter)</span>
                </div>
                <div class="legend-chip">
                    <div class="chip-sample locked"></div>
                    <span>Locked / In-Progress</span>
                </div>
                <div class="legend-chip">
                    <div class="chip-sample selected"></div>
                    <span>Selected (Counter)</span>
                </div>
            </div>

        </div>

        <!-- Right: Passenger & Instant Checkout Panel -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm);">
            
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <span><i class="fas fa-user-pen text-primary me-2"></i> Passenger & Payment Roster</span>
                <span id="counterSelectedSeatsTags">
                    <span style="color:#94a3b8; font-size:12px;">No seats selected</span>
                </span>
            </h3>

            <form action="counter-booking.php" method="POST" id="counterBookingForm">
                <!-- Hidden inputs synced by JS -->
                <input type="hidden" name="bus_id" id="counterHiddenBusId">
                <input type="hidden" name="journey_date" id="counterHiddenJourneyDate">
                <input type="hidden" name="seat_numbers" id="counterHiddenSeats">
                <input type="hidden" name="total_amount" id="counterHiddenTotal">

                <!-- Primary Passenger Contact -->
                <div class="form-group-custom" style="margin-bottom: 12px;">
                    <label class="form-label-custom"><i class="fas fa-user"></i> Primary Passenger Name</label>
                    <input type="text" name="passenger_name" class="form-control-custom" style="height:44px;" placeholder="e.g. Ketan Patel" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="fas fa-phone"></i> Mobile Number</label>
                        <input type="tel" name="passenger_phone" class="form-control-custom" style="height:44px;" placeholder="9825000000" pattern="[0-9]{10}" required>
                    </div>
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="fas fa-map-location-dot"></i> Boarding Point</label>
                        <input type="text" name="boarding_point" class="form-control-custom" style="height:44px;" value="Central Office Terminal Platform 2" required>
                    </div>
                </div>

                <!-- Dynamic Multi-Passenger Roster List -->
                <div style="margin-bottom: 16px;">
                    <label class="form-label-custom" style="margin-bottom: 6px;"><i class="fas fa-users"></i> Individual Seat Passengers</label>
                    <div id="counterPassengerFieldsContainer">
                        <p style="color:#94a3b8; font-size:12px;">Select seat(s) to enter individual passenger names.</p>
                    </div>
                </div>

                <!-- Payment Method Selector -->
                <div class="form-group-custom" style="margin-bottom: 16px;">
                    <label class="form-label-custom"><i class="fas fa-wallet"></i> Counter Payment Mode</label>
                    <select name="counter_payment_mode" class="form-control-custom" style="height:44px;">
                        <option value="Cash">Cash Received at Counter</option>
                        <option value="POS Card">POS / Card Swipe Machine</option>
                        <option value="Counter UPI">Counter Dynamic UPI QR</option>
                    </select>
                </div>

                <!-- Cash Tender & Change Calculator -->
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px; margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                        <span style="color:#64748b;">Selected Seats (<span id="counterSelectedCount">0</span>):</span>
                        <strong id="counterBaseFareDisplay">₹0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                        <span style="color:#64748b;">GST (5%):</span>
                        <span id="counterGstDisplay" style="color:#64748b;">₹0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 900; color: #0f172a; padding-top: 8px; border-top: 1px solid #e2e8f0; margin-bottom: 12px;">
                        <span>Total Payable:</span>
                        <span id="counterTotalAmountDisplay" style="color: var(--primary);">₹0.00</span>
                    </div>

                    <!-- Tender Input -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; align-items: center;">
                        <div>
                            <label style="font-size: 11px; font-weight: 700; color: #475569;">Cash Tendered (₹)</label>
                            <input type="number" id="counterCashTenderInput" class="form-control-custom" style="height:36px; padding:4px 8px; font-size:13px;" placeholder="e.g. 1000">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 700; color: #475569;">Change to Return</label>
                            <div id="counterChangeReturnDisplay" style="font-size: 16px; font-weight: 900; color: #94a3b8; padding-top: 4px;">₹0.00</div>
                        </div>
                    </div>
                </div>

                <!-- Instant Submit Button -->
                <button type="submit" name="submit_counter_booking" value="1" id="btnIssueCounterTicket" class="btn-search-bus" style="width: 100%; height: 50px; font-size: 15px; background: #0284c7; border: none;" disabled>
                    <i class="fas fa-print me-2"></i> Issue & Print Counter Ticket
                </button>
            </form>

        </div>

    </div>

</div>

<!-- Counter Booking Logic Script -->
<script src="../assets/js/counter-booking.js"></script>
<script>
    // Sync Hidden Inputs on Form Submission
    document.getElementById('counterBookingForm').addEventListener('submit', function(e) {
        const busSelect = document.getElementById('counterBusSelect');
        const dateInput = document.getElementById('counterDateInput');
        document.getElementById('counterHiddenBusId').value = busSelect.value;
        document.getElementById('counterHiddenJourneyDate').value = dateInput.value;
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
