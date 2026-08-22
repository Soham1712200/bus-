<?php
/**
 * Gopi Travels - Bus Fleet & Schedule Manager
 */

$pageTitle = 'Bus Fleet & Schedules';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

// Handle Status & Fare Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bus_status'])) {
    $busId = (int)($_POST['bus_id'] ?? 0);
    $newStatus = sanitize($_POST['status'] ?? 'Active');
    $newFare = (float)($_POST['fare'] ?? 0);

    if ($busId > 0 && in_array($newStatus, ['Active', 'Maintenance', 'Cancelled'])) {
        try {
            $upStmt = $pdo->prepare("UPDATE buses SET status = ?, fare = ? WHERE id = ?");
            $upStmt->execute([$newStatus, $newFare, $busId]);
            set_flash('success', "Bus #{$busId} updated successfully!");
        } catch (Exception $e) {
            set_flash('danger', "Update failed: " . $e->getMessage());
        }
    }
}

$routeFilter = isset($_GET['route_id']) ? (int)$_GET['route_id'] : 0;
$classFilter = isset($_GET['class']) ? sanitize($_GET['class']) : '';

$routes = [];
$buses = [];

if ($pdo) {
    $routesStmt = $pdo->query("SELECT id, source_city, destination_city FROM routes ORDER BY source_city, destination_city");
    $routes = $routesStmt->fetchAll();

    $sql = "SELECT b.*, r.source_city, r.destination_city, r.distance_km, r.estimated_time 
            FROM buses b 
            JOIN routes r ON b.route_id = r.id 
            WHERE 1=1";
    $params = [];

    if ($routeFilter > 0) {
        $sql .= " AND b.route_id = ?";
        $params[] = $routeFilter;
    }
    if (!empty($classFilter)) {
        $sql .= " AND b.bus_class = ?";
        $params[] = $classFilter;
    }

    $sql .= " ORDER BY r.source_city, r.destination_city, b.departure_time ASC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $buses = $stmt->fetchAll();
}
?>

<div class="container-fluid" style="max-width: 1440px; margin: 30px auto 60px; padding: 0 20px;">

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 900; color: #0f172a; margin-bottom: 4px;">
                <i class="fas fa-bus text-primary me-2"></i> Fleet Schedules & Operations Manager
            </h1>
            <p style="color: #64748b; font-size: 13px;">
                Real-time fleet status, departure times, passenger capacities, and corridor fare management.
            </p>
        </div>
        <div style="font-size: 13px; color: #64748b;">
            Total Fleet: <strong style="color:#0f172a;">560 Coaches</strong> across 28 Corridors
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--radius-lg); padding: 18px 24px; box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <form action="buses.php" method="GET" style="display: grid; grid-template-columns: 2fr 1.5fr auto auto; gap: 14px; align-items: end;">
            
            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-route"></i> Filter by Route Corridor</label>
                <select name="route_id" class="form-control-custom" style="height: 44px; font-size: 13px;">
                    <option value="0">All 28 Gujarat Corridors</option>
                    <?php foreach ($routes as $r): ?>
                        <option value="<?= $r['id']; ?>" <?= $r['id'] === $routeFilter ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($r['source_city']); ?> &rarr; <?= htmlspecialchars($r['destination_city']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-filter"></i> Bus Class</label>
                <select name="class" class="form-control-custom" style="height: 44px; font-size: 13px;">
                    <option value="">All Bus Classes</option>
                    <option value="Volvo AC 2+2" <?= $classFilter === 'Volvo AC 2+2' ? 'selected' : ''; ?>>Volvo AC Luxury</option>
                    <option value="Electric AC" <?= $classFilter === 'Electric AC' ? 'selected' : ''; ?>>Electric AC</option>
                    <option value="Sleeper 2+1" <?= $classFilter === 'Sleeper 2+1' ? 'selected' : ''; ?>>Sleeper 2+1 Berth</option>
                    <option value="AC Seater" <?= $classFilter === 'AC Seater' ? 'selected' : ''; ?>>AC Seater</option>
                    <option value="Gurjarnagri" <?= $classFilter === 'Gurjarnagri' ? 'selected' : ''; ?>>Gurjarnagri</option>
                    <option value="Express" <?= $classFilter === 'Express' ? 'selected' : ''; ?>>Express</option>
                </select>
            </div>

            <button type="submit" class="btn-search-bus" style="height: 44px; padding: 0 20px; font-size: 13px;">
                <i class="fas fa-filter me-1"></i> Apply Filter
            </button>

            <a href="buses.php" class="btn-nav-primary" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; height:44px; display:inline-flex; align-items:center; padding:0 16px; font-size:13px;">
                Reset
            </a>

        </form>
    </div>

    <!-- Buses Table -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="table-custom" style="width: 100%; font-size: 13px;">
                <thead>
                    <tr style="background: #0b132b; color: #ffffff;">
                        <th>Bus Plate Number</th>
                        <th>Route Corridor</th>
                        <th>Class & Type</th>
                        <th>Departure & Arrival</th>
                        <th>Capacity</th>
                        <th>Current Fare (₹)</th>
                        <th>Operating Status</th>
                        <th style="text-align: right;">Quick Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($buses)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px; color: #94a3b8;">
                                No buses found matching the selected filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($buses as $bus): ?>
                            <tr>
                                <td>
                                    <strong style="color: #0f172a; font-family: monospace; font-size: 14px;"><?= htmlspecialchars($bus['bus_number']); ?></strong>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($bus['source_city']); ?> &rarr; <?= htmlspecialchars($bus['destination_city']); ?></strong><br>
                                    <span style="font-size: 11px; color: #94a3b8;"><?= $bus['distance_km']; ?> km &bull; <?= $bus['estimated_time']; ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-express" style="font-size: 11px;"><?= htmlspecialchars($bus['bus_class']); ?></span>
                                </td>
                                <td>
                                    <strong><?= date('h:i A', strtotime($bus['departure_time'])); ?></strong> &rarr; <?= date('h:i A', strtotime($bus['arrival_time'])); ?>
                                </td>
                                <td>
                                    <strong><?= $bus['total_seats']; ?> Seats</strong>
                                </td>
                                <form action="buses.php" method="POST">
                                    <input type="hidden" name="bus_id" value="<?= $bus['id']; ?>">
                                    <td>
                                        <input type="number" name="fare" value="<?= $bus['fare']; ?>" step="5" min="50" style="width: 85px; height: 32px; padding: 2px 6px; border: 1px solid #cbd5e1; border-radius: 4px; font-weight: 700;">
                                    </td>
                                    <td>
                                        <select name="status" style="height: 32px; padding: 2px 6px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; font-weight: 700; <?= $bus['status'] === 'Active' ? 'color:#16a34a;' : ($bus['status'] === 'Maintenance' ? 'color:#d97706;' : 'color:#dc2626;'); ?>">
                                            <option value="Active" <?= $bus['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Maintenance" <?= $bus['status'] === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            <option value="Cancelled" <?= $bus['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="submit" name="update_bus_status" value="1" class="btn-sm" style="background:#0284c7; color:#fff; border:none; padding:6px 12px; border-radius:4px; font-weight:700; cursor:pointer;" title="Save Status & Fare">
                                            <i class="fas fa-check me-1"></i> Save
                                        </button>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
