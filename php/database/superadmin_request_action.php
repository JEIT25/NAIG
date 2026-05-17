<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

$request_id = trim($_POST['request_id'] ?? '');
$action = trim($_POST['action'] ?? ''); // 'approve' or 'reject'

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Transaction
$conn->begin_transaction();

try {
    // Update request status
    $stmt = $conn->prepare("UPDATE user_block_requests SET status = ? WHERE id = ?");
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    $stmt->bind_param('si', $status, $request_id);
    $stmt->execute();
    $stmt->close();

    if ($action === 'approve') {
        // Get target user id and request type
        $stmt = $conn->prepare("SELECT target_id, request_type FROM user_block_requests WHERE id = ?");
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $target_id = $row['target_id'];
            $request_type = $row['request_type'] ?? 'block';

            $superadminSwap = false;
            
            // Determine target role
            $roleStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $roleStmt->bind_param('s', $target_id);
            $roleStmt->execute();
            $targetRole = $roleStmt->get_result()->fetch_assoc()['role'] ?? '';
            $roleStmt->close();

            // Apply block or unblock based on request_type
            $isBlocked = ($request_type === 'unblock') ? 0 : 1;
            if ($isBlocked === 0) {
                $stmt2 = $conn->prepare("UPDATE users SET is_blocked = 0, status = 'registered' WHERE id = ?");
                
                if ($targetRole === 'superadmin') {
                    $currId = $_SESSION['user']['id'];
                    $blockCurr = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
                    $blockCurr->bind_param('s', $currId);
                    $blockCurr->execute();
                    $blockCurr->close();
                    $superadminSwap = true;
                }
            } else {
                $stmt2 = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
            }
            $stmt2->bind_param('s', $target_id);
            $stmt2->execute();
            $stmt2->close();
        }
        $stmt->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'superadmin_swap' => $superadminSwap ?? false]);

}
catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
