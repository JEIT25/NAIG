<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

$sql = "SELECT r.id, r.reason, r.status, r.created_at, r.request_type,
               u1.firstName as r_first, u1.lastName as r_last, u1.role as r_role,
               u2.firstName as t_first, u2.lastName as t_last, u2.username as t_username, u2.id as t_id
        FROM user_block_requests r
        JOIN users u1 ON r.requester_id = u1.id
        JOIN users u2 ON r.target_id = u2.id
        ORDER BY r.created_at DESC";

$result = $conn->query($sql);
$list = [];
while ($row = $result->fetch_assoc()) {
    // Fallback in case older rows don’t have request_type populated
    if (empty($row['request_type'])) {
        $row['request_type'] = 'block';
    }
    $list[] = $row;
}
$conn->close();

echo json_encode(['success' => true, 'requests' => $list]);
?>
