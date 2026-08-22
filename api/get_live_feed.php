<?php
/**
 * Gopi Travels - Live Activity Feed API
 * Returns real-time reservation stream for Admin Dashboard
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = get_db_connection();
if (!$pdo) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT bk.id, bk.booking_reference, bk.journey_date, bk.seat_numbers, bk.total_passengers,
                                bk.total_amount, bk.booking_channel, bk.counter_payment_mode, bk.payment_method,
                                bk.passenger_name, bk.created_at, b.bus_number, b.bus_class,
                                r.source_city, r.destination_city, u.name as booked_by_staff
                         FROM bookings bk
                         JOIN buses b ON bk.bus_id = b.id
                         JOIN routes r ON b.route_id = r.id
                         LEFT JOIN users u ON bk.booked_by_user_id = u.id
                         ORDER BY bk.created_at DESC, bk.id DESC 
                         LIMIT 15");
    $feed = $stmt->fetchAll();

    $items = [];
    foreach ($feed as $item) {
        $timeAgo = strtotime($item['created_at']);
        $diff = time() - $timeAgo;
        $timeAgoStr = 'Just now';
        if ($diff > 60 && $diff < 3600) {
            $timeAgoStr = floor($diff / 60) . ' mins ago';
        } elseif ($diff >= 3600 && $diff < 86400) {
            $timeAgoStr = floor($diff / 3600) . ' hrs ago';
        } elseif ($diff >= 86400) {
            $timeAgoStr = date('d M', $timeAgo);
        }

        $items[] = [
            'id' => (int)$item['id'],
            'pnr' => $item['booking_reference'],
            'channel' => $item['booking_channel'], // 'online' or 'counter'
            'passenger' => $item['passenger_name'],
            'route' => "{$item['source_city']} → {$item['destination_city']}",
            'bus' => "{$item['bus_number']} ({$item['bus_class']})",
            'seats' => $item['seat_numbers'],
            'amount' => format_price($item['total_amount']),
            'payment' => ($item['booking_channel'] === 'counter' ? $item['counter_payment_mode'] : 'Web NetBanking'),
            'staff' => $item['booked_by_staff'] ?: 'Online Website',
            'created_at' => date('h:i A', $timeAgo),
            'time_ago' => $timeAgoStr
        ];
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($items),
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $items
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
