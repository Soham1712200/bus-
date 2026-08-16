<?php
/**
 * Jayambe Travels - Master Reservation Lookup & Management
 */

$pageTitle = 'Master Bookings Directory';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

$channelFilter = isset($_GET['channel']) ? sanitize($_GET['channel']) : 'all'; // 'all', 'online', 'counter', 'cancelled'
$searchQuery = isset($_GET['q']) ? sanitize($_GET['q']) : '';

$bookings = [];
if ($pdo) {
    $sql = "SELECT bk.*, b.bus_number, b.bus_class, b.departure_time, r.source_city, r.destination_city, u.name as cashier_name
            FROM bookings bk
            JOIN buses b ON bk.bus_id = b.id
            JOIN routes r ON b.route_id = r.id
            LEFT JOIN users u ON bk.booked_by_user_id = u.id
            WHERE 1=1";
    $params = [];

    if ($channelFilter === 'online') {
        $sql .= " AND bk.booking_channel = 'online' AND bk.booking_status = 'Confirmed'";
    } elseif ($channelFilter === 'counter') {
        $sql .= " AND bk.booking_channel = 'counter' AND bk.booking_status = 'Confirmed'";
    } elseif ($channelFilter === 'cancelled') {
        $sql .= " AND bk.booking_status = 'Cancelled'";
    }

    if (!empty($searchQuery)) {
        $sql .= " AND (bk.booking_reference LIKE ? OR bk.passenger_phone LIKE ? OR bk.passenger_name LIKE ? OR b.bus_number LIKE ? OR bk.journey_date LIKE ?)";
        $wild = "%{$searchQuery}%";
        $params = array_merge($params, [$wild, $wild, $wild, $wild, $wild]);
    }

    $sql .= " ORDER BY bk.id DESC LIMIT 150";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
}
?>

<div class="container-fluid" style="max-width: 1440px; margin: 30px auto 60px; padding: 0 20px;">

    <!-- Title Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 26px; font-weight: 900; color: #0f172a; margin-bottom: 4px;">
                <i class="fas fa-list-check text-primary me-2"></i> Master Reservation Directory
            </h1>
            <p style="color: #64748b; font-size: 13px;">
                Unified passenger ledger spanning both online web portals and all physical branch office counter desks.
            </p>
        </div>
        <a href="counter-booking.php" class="btn-search-bus" style="height: 44px; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; background: #0284c7; border: none; padding: 0 20px;">
            <i class="fas fa-plus"></i> New Counter Booking
        </a>
    </div>

    <!-- Filter & Search Controls Bar -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: var(--radius-lg); padding: 18px 24px; box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            
            <!-- Channel Filter Tabs -->
            <div style="display: flex; gap: 8px;">
                <a href="bookings.php?channel=all<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" 
                   class="btn-sm" style="padding: 8px 16px; border-radius: 6px; font-weight: 700; text-decoration: none; <?= $channelFilter === 'all' ? 'background:#0b132b; color:#fff;' : 'background:#f1f5f9; color:#475569;'; ?>">
                    All (<?= count($bookings); ?>)
                </a>
                <a href="bookings.php?channel=online<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" 
                   class="btn-sm" style="padding: 8px 16px; border-radius: 6px; font-weight: 700; text-decoration: none; <?= $channelFilter === 'online' ? 'background:#ef4444; color:#fff;' : 'background:#fee2e2; color:#991b1b;'; ?>">
                    <i class="fas fa-globe me-1"></i> Online Web
                </a>
                <a href="bookings.php?channel=counter<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" 
                   class="btn-sm" style="padding: 8px 16px; border-radius: 6px; font-weight: 700; text-decoration: none; <?= $channelFilter === 'counter' ? 'background:#0284c7; color:#fff;' : 'background:#e0f2fe; color:#075985;'; ?>">
                    <i class="fas fa-store me-1"></i> Branch Counter
                </a>
                <a href="bookings.php?channel=cancelled<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" 
                   class="btn-sm" style="padding: 8px 16px; border-radius: 6px; font-weight: 700; text-decoration: none; <?= $channelFilter === 'cancelled' ? 'background:#64748b; color:#fff;' : 'background:#f1f5f9; color:#64748b;'; ?>">
                    <i class="fas fa-ban me-1"></i> Cancelled
                </a>
            </div>

            <!-- Instant Search Box -->
            <form action="bookings.php" method="GET" style="display: flex; gap: 8px; flex: 1; max-width: 440px;">
                <input type="hidden" name="channel" value="<?= htmlspecialchars($channelFilter); ?>">
                <div class="input-icon-wrapper" style="width: 100%;">
                    <i class="fas fa-search input-icon"></i>
                    <input type="text" name="q" id="adminUniversalSearch" class="form-control-custom" style="height: 42px; font-size: 13px;" placeholder="Search PNR, Mobile, Name, Bus No..." value="<?= htmlspecialchars($searchQuery); ?>">
                </div>
                <button type="submit" class="btn-search-bus" style="height: 42px; padding: 0 16px; font-size: 13px; white-space: nowrap;">
                    Search
                </button>
            </form>

        </div>
    </div>

    <!-- Master Bookings Table -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="table-custom" style="width: 100%; font-size: 13px;">
                <thead>
                    <tr style="background: #0b132b; color: #ffffff;">
                        <th>PNR Reference</th>
                        <th>Journey Date & Time</th>
                        <th>Passenger Info</th>
                        <th>Route Corridor</th>
                        <th>Bus Details</th>
                        <th>Seat(s)</th>
                        <th>Channel / Mode</th>
                        <th>Total Paid</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px; color: #94a3b8;">
                                <i class="fas fa-ticket fa-2x mb-2 text-muted"></i>
                                <p style="font-size: 14px; font-weight: 600;">No bookings found matching your search filter.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $bk): 
                            $isOnline = ($bk['booking_channel'] === 'online');
                            $isCancelled = ($bk['booking_status'] === 'Cancelled');
                        ?>
                            <tr class="admin-data-row">
                                <td>
                                    <strong style="font-family: monospace; font-size: 14px; color: var(--primary); display: block;">
                                        <?= htmlspecialchars($bk['booking_reference']); ?>
                                    </strong>
                                    <span style="font-size: 10px; color: #94a3b8;"><?= date('d M, h:i A', strtotime($bk['created_at'])); ?></span>
                                </td>
                                <td>
                                    <strong><?= date('d M Y', strtotime($bk['journey_date'])); ?></strong><br>
                                    <span style="color: #64748b; font-size: 12px;"><i class="fas fa-clock text-primary me-1"></i> <?= date('h:i A', strtotime($bk['departure_time'])); ?></span>
                                </td>
                                <td>
                                    <strong style="color: #0f172a;"><?= htmlspecialchars($bk['passenger_name']); ?></strong><br>
                                    <span style="color: #64748b; font-size: 11px;"><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($bk['passenger_phone']); ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($bk['source_city']); ?> &rarr; <?= htmlspecialchars($bk['destination_city']); ?></strong><br>
                                    <span style="font-size: 11px; color: #94a3b8;"><?= htmlspecialchars($bk['boarding_point']); ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($bk['bus_number']); ?></strong><br>
                                    <span class="badge badge-express" style="font-size: 10px;"><?= htmlspecialchars($bk['bus_class']); ?></span>
                                </td>
                                <td>
                                    <span class="badge" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #1e293b; font-size: 12px; font-weight: 800;">
                                        <?= htmlspecialchars($bk['seat_numbers']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isOnline): ?>
                                        <span class="badge-channel-online"><i class="fas fa-globe"></i> Web NetBanking</span>
                                    <?php else: ?>
                                        <span class="badge-channel-counter"><i class="fas fa-store"></i> Counter (<?= htmlspecialchars($bk['counter_payment_mode']); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="font-size: 14px; color: #0f172a;"><?= format_price($bk['total_amount']); ?></strong>
                                </td>
                                <td>
                                    <?php if ($isCancelled): ?>
                                        <span class="badge badge-danger">Cancelled</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Confirmed</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="receipt.php?pnr=<?= urlencode($bk['booking_reference']); ?>" class="btn-sm" style="background: #0284c7; color: #fff; padding: 6px 10px; border-radius: 4px; font-size: 12px; text-decoration: none;" title="Print 80mm Thermal Receipt">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="../ticket.php?pnr=<?= urlencode($bk['booking_reference']); ?>" target="_blank" class="btn-sm" style="background: #0b132b; color: #fff; padding: 6px 10px; border-radius: 4px; font-size: 12px; text-decoration: none;" title="View Full E-Ticket">
                                            <i class="fas fa-ticket"></i>
                                        </a>
                                        <?php if (!$isCancelled): ?>
                                            <a href="../cancel-ticket.php?pnr=<?= urlencode($bk['booking_reference']); ?>" class="btn-sm" style="background: #fee2e2; color: #dc2626; padding: 6px 8px; border-radius: 4px; font-size: 12px; text-decoration: none;" title="Cancel Ticket">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
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
