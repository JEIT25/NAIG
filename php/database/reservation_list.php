<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once 'db_connect.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query - consumers see only their own, admins/superadmins see all
$where = [];
$params = [];
$types = '';

if ($userRole === 'consumer') {
    $where[] = 'r.user_id = ?';
    $params[] = $userId;
    $types .= 's';
}

if ($status && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])) {
    $where[] = 'r.status = ?';
    $params[] = $status;
    $types .= 's';
}

if ($search) {
    $where[] = '(rest.name LIKE ? OR r.id LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countSql = "SELECT COUNT(*) as total FROM reservations r JOIN restaurants rest ON r.restaurant_id = rest.id $whereClause";
$stmt = $conn->prepare($countSql);
if ($types)
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];

// Get reservations
$sql = "SELECT r.*, rest.name AS restaurant_name, rest.cuisine_type, rest.address AS restaurant_address,
        rt.table_number, rt.capacity AS table_capacity, rt.location AS table_location,
        u.firstName, u.lastName
        FROM reservations r
        JOIN restaurants rest ON r.restaurant_id = rest.id
        LEFT JOIN restaurant_tables rt ON r.table_id = rt.id
        JOIN users u ON r.user_id = u.id
        $whereClause
        ORDER BY r.reservation_date DESC, r.reservation_time DESC
        LIMIT ? OFFSET ?";

$paramsFull = array_merge($params, [$limit, $offset]);
$typesFull = $types . 'ii';
$stmt = $conn->prepare($sql);
$stmt->bind_param($typesFull, ...$paramsFull);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}

echo json_encode([
    'success' => true,
    'reservations' => $reservations,
    'total' => intval($total),
    'page' => $page,
    'pages' => ceil($total / $limit),
    'total_pages' => ceil($total / $limit)
]);
$conn->close();
