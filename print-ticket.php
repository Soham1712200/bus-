<?php
/**
 * Gopi Travels - Standalone E-Ticket Search & Reprint Page
 * Lookup by Booking Reference (PNR) or Registered Mobile Number
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = get_db_connection();
$searchType = isset($_GET['type']) ? sanitize($_GET['type']) : 'pnr';
$query = isset($_GET['query']) ? sanitize($_GET['query']) : '';
$results = [];
$searched = false;

if (!empty($query) && $pdo) {
    $searched = true;
    if ($searchType === 'pnr') {
        // Direct redirect if valid PNR format
        $checkStmt = $pdo->prepare("SELECT booking_reference FROM bookings WHERE booking_reference = ?");
        $checkStmt->execute([$query]);
        if ($checkStmt->fetch()) {
            header("Location: ticket.php?pnr=" . urlencode($query));
            exit;
        } else {
            set_flash('danger', "No booking found for PNR: " . htmlspecialchars($query));
        }
    } else {
        // Search by phone
        $stmt = $pdo->prepare("SELECT bk.*, b.bus_number, b.bus_class, b.departure_time, r.source_city, r.destination_city 
                               FROM bookings bk
                               JOIN buses b ON bk.bus_id = b.id
                               JOIN routes r ON b.route_id = r.id
                               WHERE bk.passenger_phone = ? OR bk.passenger_email = ?
                               ORDER BY bk.journey_date DESC, bk.id DESC");
        $stmt->execute([$query, $query]);
        $results = $stmt->fetchAll();
        if (empty($results)) {
            set_flash('warning', "No bookings found for contact: " . htmlspecialchars($query));
        }
    }
}

// Fetch 5 recent demo bookings for quick 1-click test lookup
$recentBookings = [];
if ($pdo) {
    try {
        $recentBookings = $pdo->query("SELECT bk.booking_reference, bk.passenger_name, bk.passenger_phone, bk.journey_date, r.source_city, r.destination_city, b.bus_class 
                                       FROM bookings bk 
                                       JOIN buses b ON bk.bus_id = b.id 
                                       JOIN routes r ON b.route_id = r.id 
                                       ORDER BY bk.id DESC LIMIT 5")->fetchAll();
    } catch (Exception $e) {}
}

$pageTitle = 'Print E-Ticket';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 50px 20px 80px;">
    <div style="max-width: 760px; margin: 0 auto;">
        
        <div style="text-align: center; margin-bottom: 36px;">
            <div style="width: 60px; height: 60px; background: #ffedd5; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px;">
                <i class="fas fa-ticket"></i>
            </div>
            <h1 style="font-size: 30px; font-weight: 800; color: #0f172a;">Print / Download E-Ticket</h1>
            <p style="color: #64748b; font-size: 15px; margin-top: 6px;">
                Enter your Booking Reference Number (PNR) or Registered Mobile Number to retrieve and print your ticket.
            </p>
        </div>

        <!-- Search Form Card -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; box-shadow: var(--shadow-md); margin-bottom: 30px;">
            <form action="print-ticket.php" method="GET">
                
                <div style="display: flex; gap: 20px; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 14px;">
                        <input type="radio" name="type" value="pnr" <?= $searchType === 'pnr' ? 'checked' : ''; ?> onchange="document.getElementById('ticketQueryInput').placeholder='Enter 10-character PNR (e.g. JAY-XXXXXX)'">
                        <span>Search by PNR / Booking Ref</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 14px;">
                        <input type="radio" name="type" value="phone" <?= $searchType === 'phone' ? 'checked' : ''; ?> onchange="document.getElementById('ticketQueryInput').placeholder='Enter 10-digit Mobile Number'">
                        <span>Search by Mobile Number</span>
                    </label>
                </div>

                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="flex: 1; position: relative;">
                        <input type="text" name="query" id="ticketQueryInput" class="form-control-custom" placeholder="<?= $searchType === 'phone' ? 'Enter 10-digit Mobile Number' : 'Enter PNR Number (e.g. JAY-XXXXXX)'; ?>" value="<?= htmlspecialchars($query); ?>" required>
                    </div>
                    <button type="submit" class="btn-search-bus" style="height: 52px; padding: 0 28px;">
                        <i class="fas fa-magnifying-glass me-2"></i> Retrieve Ticket
                    </button>
                </div>
            </form>
        </div>

        <!-- Search Results (when searching by phone) -->
        <?php if ($searchType === 'phone' && !empty($results)): ?>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; box-shadow: var(--shadow-sm); margin-bottom: 30px;">
                <h3 style="font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 16px;">
                    <i class="fas fa-list-check text-primary me-2"></i> Found <?= count($results); ?> Booking(s)
                </h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($results as $res): ?>
                        <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                            <div>
                                <div style="font-size: 15px; font-weight: 800; color: #0f172a;">
                                    <?= htmlspecialchars($res['source_city']); ?> &rarr; <?= htmlspecialchars($res['destination_city']); ?>
                                </div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                    <span>PNR: <strong style="color:var(--primary);"><?= $res['booking_reference']; ?></strong></span> &bull; 
                                    <span>Date: <strong><?= format_date($res['journey_date']); ?></strong></span> &bull; 
                                    <span>Seats: <strong><?= $res['seat_numbers']; ?></strong></span>
                                </div>
                            </div>
                            <div>
                                <a href="ticket.php?pnr=<?= urlencode($res['booking_reference']); ?>" class="btn-search-bus" style="height: 38px; font-size: 13px; padding: 0 16px;">
                                    <i class="fas fa-print me-1"></i> View & Print
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Demo PNR Helper -->
        <?php if (!empty($recentBookings)): ?>
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 20px;">
                <div style="font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 10px;">
                    <i class="fas fa-bolt text-primary me-1"></i> Quick Test: Click any recent booking to view E-Ticket instantly:
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($recentBookings as $rb): ?>
                        <a href="ticket.php?pnr=<?= urlencode($rb['booking_reference']); ?>" class="route-chip" style="background:#ffffff; border:1px solid #cbd5e1;">
                            <strong style="color:var(--primary);"><?= $rb['booking_reference']; ?></strong>
                            <span>(<?= $rb['source_city']; ?> &rarr; <?= $rb['destination_city']; ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
