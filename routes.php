<?php
/**
 * Jayambe Travels - Gujarat Route Schedule & Timetable Directory
 */

$pageTitle = 'Gujarat Route Schedule & Timetable';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

$searchSource = isset($_GET['from_city']) ? sanitize($_GET['from_city']) : '';
$searchDest = isset($_GET['to_city']) ? sanitize($_GET['to_city']) : '';

$routes = [];
if ($pdo) {
    $sql = "SELECT r.*, COUNT(b.id) as total_buses, MIN(b.fare) as min_fare, MAX(b.fare) as max_fare
            FROM routes r 
            LEFT JOIN buses b ON r.id = b.route_id 
            WHERE 1=1";
    $params = [];

    if (!empty($searchSource)) {
        $sql .= " AND r.source_city LIKE ?";
        $params[] = "%{$searchSource}%";
    }
    if (!empty($searchDest)) {
        $sql .= " AND r.destination_city LIKE ?";
        $params[] = "%{$searchDest}%";
    }

    $sql .= " GROUP BY r.id ORDER BY r.source_city ASC, r.destination_city ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $routes = $stmt->fetchAll();
}
?>

<div class="container" style="padding: 40px 20px 80px;">
    
    <div style="text-align: center; max-width: 700px; margin: 0 auto 36px;">
        <span class="section-tag">Network Timetable</span>
        <h1 style="font-size: 32px; font-weight: 800; color: #0f172a;">Gujarat State Route Schedule</h1>
        <p style="color: #64748b; font-size: 15px; margin-top: 6px;">
            Explore all 28 major highway corridors across Gujarat with 20 daily luxury & express services per route.
        </p>
    </div>

    <!-- Filter Bar -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 30px;">
        <form action="routes.php" method="GET" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 14px; align-items: end;">
            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-location-dot"></i> Filter by Departure City</label>
                <input type="text" name="from_city" class="form-control-custom" placeholder="e.g. Ahmedabad, Surat" value="<?= htmlspecialchars($searchSource); ?>">
            </div>

            <div class="form-group-custom">
                <label class="form-label-custom"><i class="fas fa-map-pin"></i> Filter by Destination City</label>
                <input type="text" name="to_city" class="form-control-custom" placeholder="e.g. Rajkot, Vadodara" value="<?= htmlspecialchars($searchDest); ?>">
            </div>

            <div>
                <button type="submit" class="btn-search-bus" style="height: 52px; padding: 0 24px;">
                    <i class="fas fa-filter me-2"></i> Filter Routes
                </button>
            </div>
        </form>
    </div>

    <!-- Routes Table -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: var(--shadow-md); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="ticket-table" style="margin: 0;">
                <thead>
                    <tr>
                        <th>Route Corridor</th>
                        <th>Via Stations</th>
                        <th>Distance</th>
                        <th>Approx Duration</th>
                        <th>Daily Frequency</th>
                        <th>Fares (Min - Max)</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($routes)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                                No routes found matching the selected cities.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($routes as $r): ?>
                            <tr>
                                <td>
                                    <div style="font-size: 15px; font-weight: 800; color: #0f172a;">
                                        <?= htmlspecialchars($r['source_city']); ?> &rarr; <?= htmlspecialchars($r['destination_city']); ?>
                                    </div>
                                    <span style="font-size: 11px; color: #94a3b8;">Direct State Highway Service</span>
                                </td>
                                <td style="max-width: 250px; font-size: 12px; color: #64748b;">
                                    <?= htmlspecialchars($r['via_cities'] ?: 'Direct Non-Stop Express'); ?>
                                </td>
                                <td>
                                    <strong><?= $r['distance_km']; ?> km</strong>
                                </td>
                                <td>
                                    <span><i class="fas fa-clock text-primary me-1"></i> <?= htmlspecialchars($r['estimated_time']); ?></span>
                                </td>
                                <td>
                                    <span class="badge" style="background:#e0f2fe; color:#0369a1; font-weight:700; padding:4px 8px; border-radius:4px;">
                                        <?= $r['total_buses'] ?: 20; ?> Buses / Day
                                    </span>
                                </td>
                                <td>
                                    <strong style="color:var(--primary); font-size:15px;">₹<?= (int)$r['min_fare']; ?></strong>
                                    <span style="font-size:11px; color:#94a3b8;">- ₹<?= (int)$r['max_fare']; ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="search.php?source=<?= urlencode($r['source_city']); ?>&destination=<?= urlencode($r['destination_city']); ?>&journey_date=<?= date('Y-m-d'); ?>" class="btn-search-bus" style="height: 36px; font-size: 12px; padding: 0 16px; display: inline-flex;">
                                        Book Bus <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
