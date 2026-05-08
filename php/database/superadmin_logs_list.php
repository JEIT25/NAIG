<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

$date = trim($_GET['date'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

$whereClause = "WHERE l.action = 'login'";
$types = "";
$params = [];

if ($date) {
    $whereClause .= " AND DATE(l.log_time) = ?";
    $types .= "s";
    $params[] = $date;
}

$search = trim($_GET['search'] ?? '');
if ($search) {
    $searchTerm = "%$search%";
    $whereClause .= " AND (u.username LIKE ? OR u.firstName LIKE ? OR u.lastName LIKE ?)";
    $types .= "sss";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$role = trim($_GET['role'] ?? '');
if ($role) {
    $whereClause .= " AND u.role = ?";
    $types .= "s";
    $params[] = $role;
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM login_logs l LEFT JOIN users u ON l.user_id = u.id $whereClause";
$stmt = $conn->prepare($countSql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalResult = $stmt->get_result()->fetch_assoc();
$totalRecords = (int)$totalResult['total'];
$totalPages = ceil($totalRecords / $limit);
$stmt->close();

// Get paired logs
$sql = "SELECT
            l.id,
            l.user_id,
            l.log_time as login_time,
            (SELECT log_time FROM login_logs l2
             WHERE l2.user_id = l.user_id
             AND l2.action = 'logout'
             AND l2.log_time > l.log_time
             ORDER BY l2.log_time ASC LIMIT 1) as logout_time,
            u.firstName, u.lastName, u.role, u.username
        FROM login_logs l
        LEFT JOIN users u ON l.user_id = u.id
        $whereClause
        ORDER BY l.log_time DESC
        LIMIT ? OFFSET ?";

$limitParams = [$limit, $offset];
$limitTypes = "ii";

$finalParams = array_merge($params, $limitParams);
$finalTypes = $types . $limitTypes;

$stmt = $conn->prepare($sql);
if (!empty($finalParams)) {
    $stmt->bind_param($finalTypes, ...$finalParams);
}
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
    'logs' => $list,
    'pagination' => [
        'current_page' => $page,
        'limit' => $limit,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords
    ]
]);
?>
