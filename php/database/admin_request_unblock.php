<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$target_id = trim($_POST['target_id'] ?? '');
$reason = trim($_POST['reason'] ?? '');

if (!$target_id || !$reason) {
    echo json_encode(['success' => false, 'error' => 'Target ID and Reason required']);
    exit;
}

$requester_id = $_SESSION['user']['id'];

// Check for existing pending UNBLOCK request for this target
$check = $conn->prepare("SELECT id FROM user_block_requests WHERE target_id = ? AND status = 'pending' AND request_type = 'unblock'");
$check->bind_param('s', $target_id);
$check->execute();
$existing = $check->get_result();
if ($existing->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'A pending unblock request already exists for this user.']);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// Insert unblock request
$stmt = $conn->prepare("INSERT INTO user_block_requests (requester_id, target_id, reason, status, request_type) VALUES (?, ?, ?, 'pending', 'unblock')");
$stmt->bind_param('sss', $requester_id, $target_id, $reason);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
$stmt->close();
$conn->close();
?>

