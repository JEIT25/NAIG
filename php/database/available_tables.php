<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once 'db_connect.php';

header('Content-Type: application/json');

$restaurantId = intval($_GET['restaurant_id'] ?? 0);
$date = trim($_GET['date'] ?? '');
$time = trim($_GET['time'] ?? '');
$partySize = intval($_GET['party_size'] ?? 1);

if (!$restaurantId) {
    echo json_encode(['success' => false, 'error' => 'Restaurant ID is required.']);
    exit;
}

// Get all available tables for this restaurant that fit the party size
$sql = "SELECT rt.* FROM restaurant_tables rt
        WHERE rt.restaurant_id = ? AND rt.is_available = 1 AND rt.capacity >= ?";
$params = [$restaurantId, $partySize];
$types = 'ii';

// If date and time provided, exclude tables with existing reservations at that time
if ($date && $time) {
    $resTime = $time . ':00';
    $sql = "SELECT rt.* FROM restaurant_tables rt
            WHERE rt.restaurant_id = ? AND rt.is_available = 1 AND rt.capacity >= ?
            AND rt.id NOT IN (
                SELECT table_id FROM reservations
                WHERE restaurant_id = ? AND reservation_date = ?
                AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, ?)) < 120
                AND status IN ('pending','confirmed')
                AND table_id IS NOT NULL
            )";
    $params = [$restaurantId, $partySize, $restaurantId, $date, $resTime];
    $types = 'iiiss';
}

$sql .= " ORDER BY rt.capacity ASC, rt.table_number ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$tables = [];
while ($row = $result->fetch_assoc()) {
    $tables[] = $row;
}

echo json_encode(['success' => true, 'tables' => $tables]);
$conn->close();
