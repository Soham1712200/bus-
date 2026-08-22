<?php
/**
 * Gopi Travels - JSON API: Get Cities
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$pdo = get_db_connection();
if (!$pdo) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT DISTINCT source_city FROM routes UNION SELECT DISTINCT destination_city FROM routes ORDER BY 1 ASC");
    $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['status' => 'success', 'data' => $cities]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
