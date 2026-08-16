<?php
/**
 * Jayambe Travels - Passenger Dashboard & Booking History
 */

$pageTitle = 'My Bookings';
require_once __DIR__ . '/includes/header.php';

require_login('login.php');
$currentUser = current_user();

$pdo = get_db_connection();
$bookings = [];

if ($pdo && $currentUser) {
    $stmt = $pdo->prepare("SELECT bk.*, b.bus_number, b.bus_class, b.departure_time, b.arrival_time, 
                                  r.source_city, r.destination_city, r.distance_km, r.estimated_time
                           FROM bookings bk
                           JOIN buses b ON bk.bus_id = b.id
                           JOIN routes r ON b.route_id = r.id
                           WHERE bk.user_id = ? OR bk.passenger_phone = ? OR bk.passenger_email = ?
                           ORDER BY bk.journey_date DESC, bk.id DESC");
    $stmt->execute([$currentUser['id'], $currentUser['phone'], $currentUser['email']]);
    $bookings = $stmt->fetchAll();
}
?>

<div class="container" style="padding: 40px 20px 80px;">
    
    <!-- User Profile Header Banner -->
    <div style="background: linear-gradient(135deg, #0b132b, #1c2541); border-radius: 16px; padding: 30px; color: #ffffff; margin-bottom: 36px; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow-md);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 64px; height: 64px; background: var(--primary); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 900;">
                <?= strtoupper(substr($currentUser['name'], 0, 1)); ?>
            </div>
            <div>
                <h1 style="font-size: 24px; font-weight: 800;"><?= htmlspecialchars($currentUser['name']); ?></h1>
                <div style="font-size: 13px; color: #94a3b8; display: flex; gap: 16px; margin-top: 4px;">
                    <span><i class="fas fa-envelope text-primary me-1"></i> <?= htmlspecialchars($currentUser['email']); ?></span>
                    <span><i class="fas fa-phone text-primary me-1"></i> <?= htmlspecialchars($currentUser['phone']); ?></span>
                </div>
            </div>
        </div>

        <div>
            <a href="search.php" class="btn-search-bus" style="height: 44px; padding: 0 20px;">
                <i class="fas fa-ticket me-1"></i> Book New Bus
            </a>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-size: 20px; font-weight: 800; color: #0f172a;">
            <i class="fas fa-clock-rotate-left text-primary me-2"></i> Your Trip Bookings (<?= count($bookings); ?>)
        </h2>
    </div>

    <?php if (empty($bookings)): ?>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 60px 20px; text-align: center; box-shadow: var(--shadow-sm);">
            <i class="fas fa-bus-simple" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
            <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">No Bookings Found Yet</h3>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">
                You haven't reserved any bus tickets under this account.
            </p>
            <a href="search.php" class="btn-search-bus" style="display: inline-flex; width: auto;">
                <i class="fas fa-magnifying-glass me-2"></i> Search & Book Buses Across Gujarat
            </a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($bookings as $b): 
                $isCancelled = ($b['booking_status'] === 'Cancelled');
                $classMeta = get_bus_class_meta($b['bus_class']);
            ?>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; box-shadow: var(--shadow-sm); display: grid; grid-template-columns: 2fr 1.5fr 1fr auto; gap: 20px; align-items: center;">
                    
                    <!-- Route & PNR -->
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                            <span class="badge" style="background:#ffedd5; color:#c2410c; font-family:monospace; font-weight:800; font-size:13px; padding:4px 8px; border-radius:6px;">
                                <?= $b['booking_reference']; ?>
                            </span>
                            <span class="bus-class-badge <?= $classMeta['badge']; ?>" style="font-size:11px; padding:2px 8px;">
                                <?= $b['bus_class']; ?>
                            </span>
                        </div>
                        <div style="font-size: 17px; font-weight: 800; color: #0f172a;">
                            <?= htmlspecialchars($b['source_city']); ?> &rarr; <?= htmlspecialchars($b['destination_city']); ?>
                        </div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                            Bus: <strong><?= htmlspecialchars($b['bus_number']); ?></strong> &bull; <?= $b['distance_km']; ?> km
                        </div>
                    </div>

                    <!-- Date & Time -->
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a;">
                            <i class="fas fa-calendar-day text-primary me-1"></i> <?= format_date($b['journey_date']); ?>
                        </div>
                        <div style="font-size: 13px; color: #64748b; margin-top: 2px;">
                            Departure: <strong><?= format_time($b['departure_time']); ?></strong> &rarr; Arrival: <strong><?= format_time($b['arrival_time']); ?></strong>
                        </div>
                        <div style="font-size: 12px; color: #475569; margin-top: 4px;">
                            Seats: <strong style="color:var(--primary);"><?= htmlspecialchars($b['seat_numbers']); ?></strong> (<?= $b['total_passengers']; ?> Pax)
                        </div>
                    </div>

                    <!-- Amount & Status -->
                    <div>
                        <div style="font-size: 18px; font-weight: 800; color: #0f172a;">
                            <?= format_price($b['total_amount']); ?>
                        </div>
                        <div style="margin-top: 4px;">
                            <span class="badge" style="background:<?= $isCancelled ? '#fee2e2' : '#d1fae5'; ?>; color:<?= $isCancelled ? '#991b1b' : '#065f46'; ?>; padding:3px 8px; border-radius:4px; font-size:11px; font-weight:700;">
                                <?= $b['booking_status']; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <a href="ticket.php?pnr=<?= urlencode($b['booking_reference']); ?>" class="btn-search-bus" style="height: 36px; font-size: 12px; padding: 0 14px;">
                            <i class="fas fa-print me-1"></i> E-Ticket
                        </a>
                        <?php if (!$isCancelled): ?>
                            <a href="cancel-ticket.php?pnr=<?= urlencode($b['booking_reference']); ?>" class="btn-ticket-print" style="background:#f1f5f9; color:#dc2626; padding:6px 12px; font-size:12px; text-align:center;">
                                <i class="fas fa-ban me-1"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
