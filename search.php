<?php
/**
 * Gopi Travels - Bus Search & Live Interactive Seat Matrix
 * Complete search results, multi-filter sidebar, live 2x2 seater / 2x1 sleeper layout
 */

$pageTitle = 'Bus Search Results';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

// Extract and sanitize search parameters
$source = isset($_GET['source']) ? sanitize($_GET['source']) : 'Ahmedabad';
$destination = isset($_GET['destination']) ? sanitize($_GET['destination']) : 'Surat';
$journeyDate = isset($_GET['journey_date']) && !empty($_GET['journey_date']) ? sanitize($_GET['journey_date']) : date('Y-m-d');
$selectedClass = isset($_GET['bus_class']) ? sanitize($_GET['bus_class']) : '';
$timeSlot = isset($_GET['time_slot']) ? sanitize($_GET['time_slot']) : '';
$sortBy = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'dep_asc';
$isAcFilter = isset($_GET['is_ac']) ? (int)$_GET['is_ac'] : -1;
$isSleeperFilter = isset($_GET['is_sleeper']) ? (int)$_GET['is_sleeper'] : -1;

// Fetch all cities for the top search modification bar
$allCities = [];
if ($pdo) {
    try {
        $allCities = $pdo->query("SELECT DISTINCT source_city FROM routes UNION SELECT DISTINCT destination_city FROM routes ORDER BY 1 ASC")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}
}

// Find route
$route = null;
$buses = [];

if ($pdo) {
    $routeStmt = $pdo->prepare("SELECT * FROM routes WHERE source_city = ? AND destination_city = ? LIMIT 1");
    $routeStmt->execute([$source, $destination]);
    $route = $routeStmt->fetch();

    if ($route) {
        $sql = "SELECT b.*, r.source_city, r.destination_city, r.distance_km, r.estimated_time, r.via_cities 
                FROM buses b 
                JOIN routes r ON b.route_id = r.id 
                WHERE b.route_id = ? AND b.status = 'Active'";
        $params = [$route['id']];

        if (!empty($selectedClass)) {
            $sql .= " AND b.bus_class = ?";
            $params[] = $selectedClass;
        }

        if ($isAcFilter !== -1) {
            $sql .= " AND b.is_ac = ?";
            $params[] = $isAcFilter;
        }

        if ($isSleeperFilter !== -1) {
            $sql .= " AND b.is_sleeper = ?";
            $params[] = $isSleeperFilter;
        }

        // Time slot filter
        if ($timeSlot === 'morning') {
            $sql .= " AND b.departure_time BETWEEN '05:00:00' AND '11:59:59'";
        } elseif ($timeSlot === 'afternoon') {
            $sql .= " AND b.departure_time BETWEEN '12:00:00' AND '16:59:59'";
        } elseif ($timeSlot === 'evening') {
            $sql .= " AND b.departure_time BETWEEN '17:00:00' AND '20:59:59'";
        } elseif ($timeSlot === 'night') {
            $sql .= " AND (b.departure_time >= '21:00:00' OR b.departure_time < '05:00:00')";
        }

        // Sorting
        switch ($sortBy) {
            case 'fare_asc':
                $sql .= " ORDER BY b.fare ASC";
                break;
            case 'fare_desc':
                $sql .= " ORDER BY b.fare DESC";
                break;
            case 'dep_desc':
                $sql .= " ORDER BY b.departure_time DESC";
                break;
            case 'dep_asc':
            default:
                $sql .= " ORDER BY b.departure_time ASC";
                break;
        }

        $busStmt = $pdo->prepare($sql);
        $busStmt->execute($params);
        $buses = $busStmt->fetchAll();
    }
}
?>

<!-- Modify Search Top Bar -->
<div style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 18px 0; box-shadow: var(--shadow-sm);">
    <div class="container">
        <form action="search.php" method="GET" class="search-form-grid" style="align-items: center; gap: 10px;">
            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-location-dot"></i> From</label>
                <select name="source" id="sourceCitySelect" class="form-control-custom" style="height:44px; padding:6px 12px 6px 36px;" required>
                    <?php foreach ($allCities as $c): ?>
                        <option value="<?= htmlspecialchars($c); ?>" <?= $c === $source ? 'selected' : ''; ?>><?= htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="button" class="btn-swap-locations" id="btnSwapCities" style="width:40px; height:40px; margin-top:20px;">
                    <i class="fas fa-right-left"></i>
                </button>
            </div>

            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-map-pin"></i> To</label>
                <select name="destination" id="destCitySelect" class="form-control-custom" style="height:44px; padding:6px 12px 6px 36px;" required>
                    <?php foreach ($allCities as $c): ?>
                        <option value="<?= htmlspecialchars($c); ?>" <?= $c === $destination ? 'selected' : ''; ?>><?= htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-calendar"></i> Date of Journey</label>
                <input type="date" name="journey_date" id="journeyDateInput" class="form-control-custom" style="height:44px; padding:6px 12px 6px 36px;" value="<?= htmlspecialchars($journeyDate); ?>" required>
            </div>

            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-filter"></i> Bus Class</label>
                <select name="bus_class" class="form-control-custom" style="height:44px; padding:6px 12px 6px 36px;">
                    <option value="">All Bus Classes</option>
                    <option value="Volvo AC 2+2" <?= $selectedClass === 'Volvo AC 2+2' ? 'selected' : ''; ?>>Volvo AC Luxury</option>
                    <option value="Electric AC" <?= $selectedClass === 'Electric AC' ? 'selected' : ''; ?>>Electric AC</option>
                    <option value="Sleeper 2+1" <?= $selectedClass === 'Sleeper 2+1' ? 'selected' : ''; ?>>Sleeper 2+1</option>
                    <option value="AC Seater" <?= $selectedClass === 'AC Seater' ? 'selected' : ''; ?>>AC Seater</option>
                    <option value="Gurjarnagri" <?= $selectedClass === 'Gurjarnagri' ? 'selected' : ''; ?>>Gurjarnagri</option>
                    <option value="Express" <?= $selectedClass === 'Express' ? 'selected' : ''; ?>>Express</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn-search-bus" style="height:44px; padding: 0 20px; margin-top:20px;">
                    <i class="fas fa-rotate"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>

<div class="container">
    <div class="search-results-layout">
        
        <!-- Filter Sidebar -->
        <aside class="filter-sidebar">
            <div class="filter-header">
                <h3><i class="fas fa-sliders text-primary"></i> Filters</h3>
                <a href="search.php?source=<?= urlencode($source); ?>&destination=<?= urlencode($destination); ?>&journey_date=<?= urlencode($journeyDate); ?>" class="btn-reset-filters">Reset All</a>
            </div>

            <form method="GET" action="search.php" id="sidebarFilterForm">
                <input type="hidden" name="source" value="<?= htmlspecialchars($source); ?>">
                <input type="hidden" name="destination" value="<?= htmlspecialchars($destination); ?>">
                <input type="hidden" name="journey_date" value="<?= htmlspecialchars($journeyDate); ?>">

                <!-- Bus Class Filter -->
                <div class="filter-group">
                    <div class="filter-title">Bus Fleet Type</div>
                    <div class="filter-checkbox-list">
                        <?php 
                        $classList = ['Volvo AC 2+2', 'Electric AC', 'Sleeper 2+1', 'AC Seater', 'Gurjarnagri', 'Express'];
                        foreach ($classList as $cls):
                        ?>
                            <label class="custom-checkbox">
                                <input type="radio" name="bus_class" value="<?= $cls; ?>" <?= $selectedClass === $cls ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <span><?= $cls; ?></span>
                            </label>
                        <?php endforeach; ?>
                        <label class="custom-checkbox">
                            <input type="radio" name="bus_class" value="" <?= empty($selectedClass) ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span>All Classes</span>
                        </label>
                    </div>
                </div>

                <!-- Departure Time Window Filter -->
                <div class="filter-group">
                    <div class="filter-title">Departure Time</div>
                    <div class="filter-checkbox-list">
                        <label class="custom-checkbox">
                            <input type="radio" name="time_slot" value="morning" <?= $timeSlot === 'morning' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><i class="fas fa-sun text-warning me-1"></i> Morning (05:00 - 12:00)</span>
                        </label>
                        <label class="custom-checkbox">
                            <input type="radio" name="time_slot" value="afternoon" <?= $timeSlot === 'afternoon' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><i class="fas fa-cloud-sun text-primary me-1"></i> Afternoon (12:00 - 17:00)</span>
                        </label>
                        <label class="custom-checkbox">
                            <input type="radio" name="time_slot" value="evening" <?= $timeSlot === 'evening' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><i class="fas fa-cloud-moon text-indigo me-1"></i> Evening (17:00 - 21:00)</span>
                        </label>
                        <label class="custom-checkbox">
                            <input type="radio" name="time_slot" value="night" <?= $timeSlot === 'night' ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><i class="fas fa-moon text-secondary me-1"></i> Night (21:00 - 05:00)</span>
                        </label>
                    </div>
                </div>

                <!-- Coach Feature Filter -->
                <div class="filter-group">
                    <div class="filter-title">Coach Specifications</div>
                    <div class="filter-checkbox-list">
                        <label class="custom-checkbox">
                            <input type="radio" name="is_ac" value="1" <?= $isAcFilter === 1 ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><i class="fas fa-snowflake text-info me-1"></i> AC Buses Only</span>
                        </label>
                        <label class="custom-checkbox">
                            <input type="radio" name="is_ac" value="0" <?= $isAcFilter === 0 ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span>Non-AC Buses</span>
                        </label>
                        <label class="custom-checkbox">
                            <input type="radio" name="is_sleeper" value="1" <?= $isSleeperFilter === 1 ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span><i class="fas fa-bed text-purple me-1"></i> Sleeper Berths Only</span>
                        </label>
                    </div>
                </div>
            </form>
        </aside>

        <!-- Main Bus Results Column -->
        <main>
            <!-- Results Header Bar -->
            <div class="results-header-bar">
                <div class="results-summary">
                    <?= htmlspecialchars($source); ?> <i class="fas fa-arrow-right text-primary mx-1" style="font-size:12px;"></i> <?= htmlspecialchars($destination); ?>
                    <span>(<?= count($buses); ?> Buses Found)</span>
                    <div style="font-size:12px; color:#64748b; font-weight:500; margin-top:2px;">
                        Date: <strong><?= format_date($journeyDate); ?></strong>
                        <?php if ($route): ?>
                            &bull; Distance: <?= $route['distance_km']; ?> km &bull; Approx: <?= $route['estimated_time']; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sort Control -->
                <div class="sort-dropdown-wrap">
                    <label for="sortSelector">Sort By:</label>
                    <select id="sortSelector" class="sort-select" onchange="location.href='search.php?source=<?= urlencode($source); ?>&destination=<?= urlencode($destination); ?>&journey_date=<?= urlencode($journeyDate); ?>&bus_class=<?= urlencode($selectedClass); ?>&time_slot=<?= urlencode($timeSlot); ?>&sort=' + this.value;">
                        <option value="dep_asc" <?= $sortBy === 'dep_asc' ? 'selected' : ''; ?>>Departure: Early to Late</option>
                        <option value="dep_desc" <?= $sortBy === 'dep_desc' ? 'selected' : ''; ?>>Departure: Late to Early</option>
                        <option value="fare_asc" <?= $sortBy === 'fare_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="fare_desc" <?= $sortBy === 'fare_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Route Via Stations Banner -->
            <?php if ($route && !empty($route['via_cities'])): ?>
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 16px; margin-bottom: 20px; font-size: 13px; color: #1e40af; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-route"></i>
                    <span><strong>Via Stations:</strong> <?= htmlspecialchars($route['via_cities']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (empty($buses)): ?>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 50px 20px; text-align: center; box-shadow: var(--shadow-sm);">
                    <i class="fas fa-bus-simple" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                    <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">No Buses Found For Selected Route / Filters</h3>
                    <p style="color: #64748b; font-size: 14px; max-width: 480px; margin: 0 auto 20px;">
                        Try resetting your filters or selecting one of the popular high-frequency routes across Gujarat.
                    </p>
                    <a href="search.php?source=Ahmedabad&destination=Surat&journey_date=<?= date('Y-m-d'); ?>" class="btn-search-bus" style="display:inline-flex; width:auto;">
                        <i class="fas fa-rotate me-2"></i> View Ahmedabad &rarr; Surat Fleet
                    </a>
                </div>
            <?php else: ?>

                <!-- List of Bus Cards -->
                <?php foreach ($buses as $bus): 
                    $classMeta = get_bus_class_meta($bus['bus_class']);
                    $bookedSeats = get_booked_seats($bus['id'], $journeyDate);
                    $availableSeatsCount = max(0, $bus['total_seats'] - count($bookedSeats));
                    $jsonBookedSeats = htmlspecialchars(json_encode($bookedSeats), ENT_QUOTES, 'UTF-8');
                ?>
                    <div class="bus-card" id="busCard_<?= $bus['id']; ?>">
                        <div class="bus-card-main">
                            
                            <!-- Bus Info -->
                            <div class="bus-info-block">
                                <span class="bus-class-badge <?= $classMeta['badge']; ?>">
                                    <i class="fas <?= $classMeta['icon']; ?>"></i> <?= $classMeta['tag']; ?>
                                </span>
                                <div class="bus-plate-number"><?= htmlspecialchars($bus['bus_number']); ?></div>
                                <div style="font-size:12px; color:#64748b;">
                                    <?= $bus['is_sleeper'] ? '<i class="fas fa-bed me-1 text-purple"></i> Sleeper Coach' : '<i class="fas fa-chair me-1 text-primary"></i> 2x2 Pushback Seater'; ?>
                                </div>
                            </div>

                            <!-- Timing & Journey Duration -->
                            <div class="bus-timing-block">
                                <div class="time-col">
                                    <div class="time-val"><?= format_time($bus['departure_time']); ?></div>
                                    <div class="time-city"><?= htmlspecialchars($bus['source_city']); ?></div>
                                </div>
                                <div class="journey-duration-col">
                                    <div class="duration-text"><?= htmlspecialchars($bus['estimated_time']); ?></div>
                                    <div class="duration-line"></div>
                                    <div style="font-size:10px; color:#94a3b8; text-transform:uppercase;">Direct Route</div>
                                </div>
                                <div class="time-col">
                                    <div class="time-val"><?= format_time($bus['arrival_time']); ?></div>
                                    <div class="time-city"><?= htmlspecialchars($bus['destination_city']); ?></div>
                                </div>
                            </div>

                            <!-- Seats Available -->
                            <div class="bus-seats-block">
                                <div class="seats-available-badge <?= $availableSeatsCount < 10 ? 'low' : ''; ?>">
                                    <i class="fas fa-chair"></i> <?= $availableSeatsCount; ?> Seats Left
                                </div>
                                <div style="font-size:11px; color:#94a3b8;">out of <?= $bus['total_seats']; ?> total</div>
                            </div>

                            <!-- Price & CTA -->
                            <div class="bus-pricing-cta-block">
                                <div class="fare-display">
                                    <?= format_price($bus['fare']); ?> <small>/ seat</small>
                                </div>
                                <button type="button" class="btn-select-seat" id="btnSelectSeat_<?= $bus['id']; ?>" onclick="BookingApp.toggleSeatMatrix('<?= $bus['id']; ?>', '<?= $bus['fare']; ?>', '<?= $bus['is_sleeper']; ?>', '<?= $bus['bus_number']; ?>', <?= $jsonBookedSeats; ?>, <?= (int)$bus['total_seats']; ?>)">
                                    <i class="fas fa-couch"></i> Select Seats
                                </button>
                            </div>

                        </div>

                        <!-- Amenities Footer -->
                        <div class="bus-card-footer">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <strong style="color:#334155;"><i class="fas fa-sparkles text-primary me-1"></i> Amenities:</strong>
                                <?= render_amenities_icons($bus['amenities']); ?>
                            </div>
                            <div>
                                <i class="fas fa-clock-rotate-left text-muted me-1"></i> Live Cancellation Available
                            </div>
                        </div>

                        <!-- Expandable Interactive Live Seat Matrix -->
                        <div class="seat-selection-container" id="seatContainer_<?= $bus['id']; ?>">
                            <div class="seat-matrix-wrapper">
                                
                                <!-- Seat Layout Chassis -->
                                <div class="coach-chassis">
                                    <div class="coach-front">
                                        <div class="driver-cabin">
                                            <i class="fas fa-life-ring driver-wheel-icon"></i>
                                            <span>Driver Cabin</span>
                                        </div>
                                        <div class="coach-entry-door">
                                            <i class="fas fa-door-open me-1"></i> Passenger Entry
                                        </div>
                                    </div>

                                    <!-- Dynamic Seat Matrix Grid (Rendered by JS) -->
                                    <div id="seatMatrix_<?= $bus['id']; ?>">
                                        <!-- Injected by booking.js -->
                                    </div>

                                    <!-- Legend Indicator -->
                                    <div class="seat-legend-bar">
                                        <div class="legend-item">
                                            <div class="legend-sample avail"></div>
                                            <span>Available</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-sample sel"></div>
                                            <span>Selected</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-sample book"></div>
                                            <span>Booked</span>
                                        </div>
                                        <div class="legend-item">
                                            <div class="legend-sample fem"></div>
                                            <span>Female Reserved</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Booking Summary & Passenger Details Panel -->
                                <div class="booking-summary-sidebar">
                                    <div>
                                        <div class="summary-title">
                                            <i class="fas fa-receipt text-primary me-1"></i> Trip Reservation Summary
                                        </div>

                                        <div style="font-size:13px; color:#64748b; margin-bottom:12px;">
                                            <div>Bus: <strong><?= htmlspecialchars($bus['bus_number']); ?> (<?= $bus['bus_class']; ?>)</strong></div>
                                            <div>Date: <strong><?= format_date($journeyDate); ?></strong></div>
                                        </div>

                                        <div style="font-size:12px; font-weight:700; color:#475569; margin-bottom:6px;">Selected Seats:</div>
                                        <div class="summary-seats-list" id="selectedSeatsList_<?= $bus['id']; ?>">
                                            <span style="color:#94a3b8; font-size:13px;">No seats selected yet</span>
                                        </div>

                                        <!-- Fare Breakdown -->
                                        <table class="fare-breakdown-table">
                                            <tr>
                                                <td>Seat Count:</td>
                                                <td id="selectedCount_<?= $bus['id']; ?>">0</td>
                                            </tr>
                                            <tr>
                                                <td>Base Fare:</td>
                                                <td id="baseFareTotal_<?= $bus['id']; ?>">₹0.00</td>
                                            </tr>
                                            <tr>
                                                <td>GST (5%):</td>
                                                <td id="gstAmount_<?= $bus['id']; ?>">₹0.00</td>
                                            </tr>
                                            <tr>
                                                <td>Reservation / Toll:</td>
                                                <td>₹15.00</td>
                                            </tr>
                                            <tr class="grand-total">
                                                <td>Total Payable:</td>
                                                <td id="grandTotal_<?= $bus['id']; ?>">₹0.00</td>
                                            </tr>
                                        </table>

                                        <!-- Passenger Form & Checkout CTA -->
                                        <form action="payment.php" method="POST" id="bookingForm_<?= $bus['id']; ?>">
                                            <input type="hidden" name="bus_id" value="<?= $bus['id']; ?>">
                                            <input type="hidden" name="journey_date" value="<?= htmlspecialchars($journeyDate); ?>">
                                            <input type="hidden" name="seat_numbers" id="inputSeatNumbers_<?= $bus['id']; ?>" value="">

                                            <!-- Dynamic Passenger Detail Inputs Generated By booking.js -->
                                            <div id="passengerDetailsList_<?= $bus['id']; ?>">
                                                <!-- Dynamic Passenger rows injected here -->
                                            </div>

                                            <!-- Primary Contact Details -->
                                            <div style="margin: 16px 0 20px;">
                                                <div style="font-size:13px; font-weight:700; color:#0f172a; margin-bottom:8px;">
                                                    <i class="fas fa-address-card text-primary me-1"></i> Primary Contact Information
                                                </div>
                                                <div style="display:flex; flex-direction:column; gap:8px;">
                                                    <input type="tel" name="contact_phone" class="form-control-custom" style="height:40px; font-size:13px; padding:6px 12px;" placeholder="Mobile Number (10 digits)" pattern="[0-9]{10}" value="<?= $currentUser ? htmlspecialchars($currentUser['phone']) : ''; ?>" required>
                                                    <input type="email" name="contact_email" class="form-control-custom" style="height:40px; font-size:13px; padding:6px 12px;" placeholder="Email for E-Ticket receipt" value="<?= $currentUser ? htmlspecialchars($currentUser['email']) : ''; ?>" required>
                                                </div>
                                            </div>

                                            <button type="submit" class="btn-proceed-booking" id="btnProceed_<?= $bus['id']; ?>" disabled>
                                                <i class="fas fa-lock me-1"></i> Proceed to Demo Net Banking
                                            </button>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </main>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
