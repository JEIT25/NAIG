<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

// Must be at least an admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$currentUser = $_SESSION['user'];
$role = $currentUser['role'];

$request_id = trim($_POST['request_id'] ?? '');
$source_table = trim($_POST['source_table'] ?? '');
$action = trim($_POST['action'] ?? ''); // 'approve' or 'reject'
$reviewNotes = trim($_POST['review_notes'] ?? '');

if (!$request_id || !$source_table || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$conn->begin_transaction();

try {
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    if ($source_table === 'user_block_requests') {
        // Permissions: Only superadmin can approve/reject block requests in some designs,
        // but here let's allow superadmin to process any.
        // If admins can also process, then no check.
        // In DA-MALERIO, superadmin processes these.
        if ($role !== 'superadmin') {
            throw new Exception("Only superadmin can process block requests.");
        }

        // Update status
        $up = $conn->prepare("UPDATE user_block_requests SET status = ? WHERE id = ?");
        $up->bind_param('si', $status, $request_id);
        $up->execute();
        $up->close();

        if ($action === 'approve') {
            // Apply the actual block/unblock
            $get = $conn->prepare("SELECT target_id, request_type FROM user_block_requests WHERE id = ?");
            $get->bind_param('i', $request_id);
            $get->execute();
            $row = $get->get_result()->fetch_assoc();
            $get->close();

            if ($row) {
                $isBlocked = ($row['request_type'] === 'unblock') ? 0 : 1;
                $upd = $conn->prepare("UPDATE users SET is_blocked = ? WHERE id = ?");
                $upd->bind_param('is', $isBlocked, $row['target_id']);
                $upd->execute();
                $upd->close();
            }
        }

    }
    elseif ($source_table === 'approvals') {
        // Handle registration approvals (Admins and Superadmins can do this)
        $get = $conn->prepare("SELECT * FROM approvals WHERE id = ?");
        $get->bind_param('i', $request_id);
        $get->execute();
        $approval = $get->get_result()->fetch_assoc();
        $get->close();

        if (!$approval)
            throw new Exception("Approval not found.");

        $actionType = $approval['action_type'];

        // Permission check for approvals table
        if ($role !== 'superadmin' && $actionType !== 'register_consumer') {
            throw new Exception("You do not have permission to approve this type of request.");
        }

        // Update status
        $up = $conn->prepare("UPDATE approvals SET status = ? WHERE id = ?");
        $up->bind_param('si', $status, $request_id);
        $up->execute();
        $up->close();

        if ($action === 'approve') {
            $targetId = $approval['target_id'];
            if ($actionType === 'register_consumer') {
                $upd = $conn->prepare("UPDATE users SET is_blocked = 0, status = 'registered' WHERE id = ?");
                $upd->bind_param('s', $targetId);
                $upd->execute();
                $upd->close();
            }
        // Add other approval types if needed (delete_user, etc. from DAMALERIO logic)
        }
    }
    else {
        throw new Exception("Invalid source table.");
    }

    $conn->commit();
    echo json_encode(['success' => true]);

}
catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
