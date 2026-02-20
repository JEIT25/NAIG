<?php
/**
 * Returns list of active restaurants with reservation-relevant details (JSON).
 * No auth required for browsing.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$search = trim($_GET['search'] ?? '');
$cuisine = trim($_GET['cuisine'] ?? '');

$where = ['r.is_active = 1'];
$params = [];
$types = '';

if ($search) {
    $where[] = '(r.name LIKE ? OR r.cuisine_type LIKE ? OR r.address LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
    $types .= 'sss';
}
if ($cuisine) {
    $where[] = 'r.cuisine_type = ?';
    $params[] = $cuisine;
    $types .= 's';
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT r.*,
        (SELECT COUNT(*) FROM restaurant_tables rt WHERE rt.restaurant_id = r.id AND rt.is_available = 1) as available_tables
        FROM restaurants r
        $whereClause
        ORDER BY r.rating DESC, r.name ASC";

$stmt = $conn->prepare($sql);
if ($types)
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$list = [];
while ($row = $result->fetch_assoc()) {
    $list[] = $row;
}

// Get distinct cuisine types for filter
$cuisines = $conn->query("SELECT DISTINCT cuisine_type FROM restaurants WHERE is_active = 1 ORDER BY cuisine_type");
$cuisineList = [];
while ($c = $cuisines->fetch_assoc())
    $cuisineList[] = $c['cuisine_type'];

echo json_encode(['success' => true, 'restaurants' => $list, 'cuisines' => $cuisineList]);
$conn->close();
