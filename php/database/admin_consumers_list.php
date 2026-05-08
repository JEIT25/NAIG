<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$whereClause = "role = 'consumer'";
$params = [];
$types = '';

if ($status === 'active') {
    $whereClause .= " AND is_blocked = 0";
} elseif ($status === 'blocked') {
    $whereClause .= " AND is_blocked = 1";
}

if ($search !== '') {
    $whereClause .= " AND (firstName LIKE ? OR lastName LIKE ? OR username LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
    $types = 'ssss';
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM users WHERE $whereClause";
$countStmt = $conn->prepare($countQuery);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result()->fetch_assoc();
$total = (int)$totalResult['total'];
$countStmt->close();

// Fetch paginated
$query = "SELECT id, firstName, lastName, middleInitial, extension, sex, birthdate, age, purok, barangay, city, province, zipCode, country, username, email, is_blocked FROM users WHERE $whereClause ORDER BY username LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$fetchTypes = $types . 'ii';
$fetchParams = array_merge($params, [$limit, $offset]);
$stmt->bind_param($fetchTypes, ...$fetchParams);
$stmt->execute();
$result = $stmt->get_result();

$list = [];
while ($row = $result->fetch_assoc()) {
    $list[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode([
    'success' => true, 
    'consumers' => $list,
    'pagination' => [
        'current_page' => $page,
        'limit' => $limit,
        'total_requests' => $total,
        'total_pages' => ceil($total / $limit)
    ]
]);
?>
