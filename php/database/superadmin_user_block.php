<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

$user_id = trim($_POST['user_id'] ?? '');
$action = trim($_POST['action'] ?? ''); // 'block' or 'unblock'

if (!$user_id || !in_array($action, ['block', 'unblock'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Prevent blocking self
if ($user_id === $_SESSION['user']['id']) {
    echo json_encode(['success' => false, 'error' => 'Cannot block yourself']);
    exit;
}



$superadminSwap = false;

// Determine target role for unblock superadmin logic
$roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$roleStmt->bind_param('s', $user_id);
$roleStmt->execute();
$targetRole = $roleStmt->get_result()->fetch_assoc()['role'] ?? '';
$roleStmt->close();

if ($action === 'unblock') {
    $stmt = $conn->prepare("UPDATE users SET is_blocked = 0, status = 'registered' WHERE id = ?");
    $stmt->bind_param('s', $user_id);
    
    if ($targetRole === 'superadmin') {
        $currId = $_SESSION['user']['id'];
        $blockCurr = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
        $blockCurr->bind_param('s', $currId);
        $blockCurr->execute();
        $blockCurr->close();
        $superadminSwap = true;
    }
} else {
    $stmt = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
    $stmt->bind_param('s', $user_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'superadmin_swap' => $superadminSwap]);
}
else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
$stmt->close();
$conn->close();
?>
