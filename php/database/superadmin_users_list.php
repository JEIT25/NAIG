<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$role = isset($_GET['role']) ? $conn->real_escape_string($_GET['role']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Base where clause
$where = "1=1";
if (!empty($search)) {
    $where .= " AND (firstName LIKE '%$search%' OR lastName LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%')";
}
if (!empty($role)) {
    $where .= " AND role = '$role'";
}
if (!empty($status)) {
    if ($status === 'active') $where .= " AND is_blocked = 0";
    else if ($status === 'blocked') $where .= " AND is_blocked = 1";
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM users WHERE $where";
$countResult = $conn->query($countQuery);
$totalUsers = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

// Fetch users
$query = "SELECT id, firstName, lastName, middleInitial, extension, sex, birthdate, purok, barangay, city, province, zipCode, country, username, email, role, is_blocked 
          FROM users 
          WHERE $where 
          ORDER BY role, username 
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$conn->close();

echo json_encode([
    'success' => true,
    'users' => $users,
    'pagination' => [
        'current_page' => $page,
        'limit' => $limit,
        'total_users' => $totalUsers,
        'total_pages' => $totalPages
    ]
]);
