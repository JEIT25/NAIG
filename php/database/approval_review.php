<?php
/**
 * Review approval (superadmin only). Approve or reject; on approve, execute delete.
 */
header('Content-Type: application/json');
ob_start();
require_once __DIR__ . '/db_connect.php';
session_start();

if (!isset($_SESSION['user'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$currentUser = $_SESSION['user'];
$role = $currentUser['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$approvalId = (int)($_POST['approval_id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$reviewNotes = trim($_POST['review_notes'] ?? '');

if ($approvalId <= 0 || !in_array($action, ['approve', 'reject'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid approval_id or action']);
    exit;
}

try {
    $getStmt = $conn->prepare("SELECT * FROM approvals WHERE id = ? AND status = 'pending'");
    $getStmt->bind_param('i', $approvalId);
    $getStmt->execute();
    $res = $getStmt->get_result();
    if ($res->num_rows === 0) {
        $getStmt->close();
        $conn->close();
        ob_clean();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Request not found or already processed']);
        exit;
    }
    $approval = $res->fetch_assoc();
    $getStmt->close();

    // Permission check: superadmin can review everything; admin can only review registration approvals
    $actionType = $approval['action_type'] ?? '';
    if ($role !== 'superadmin' && !($role === 'admin' && $actionType === 'register_consumer')) {
        $conn->close();
        ob_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'You are not allowed to review this request.']);
        exit;
    }

    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $up = $conn->prepare("UPDATE approvals SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
    $up->bind_param('sssi', $newStatus, $currentUser['id'], $reviewNotes, $approvalId);
    $up->execute();
    $up->close();

    if ($action === 'approve') {
        $targetId = $approval['target_id'];
        $targetType = $approval['target_type'];
        $actionType = $approval['action_type'];

        if ($actionType === 'delete_user' && $targetType === 'user') {
            $del = $conn->prepare("DELETE FROM users WHERE id = ?");
            $del->bind_param('s', $targetId);
            $del->execute();
            $del->close();
            $msg = 'User deleted.';
        } elseif ($actionType === 'delete_restaurant' && $targetType === 'restaurant') {
            $tid = (int) $targetId;
            $del = $conn->prepare("DELETE FROM restaurants WHERE id = ?");
            $del->bind_param('i', $tid);
            $del->execute();
            $del->close();
            $msg = 'Restaurant deleted.';
        } elseif ($actionType === 'delete_menu_item' && $targetType === 'menu_item') {
            $tid = (int) $targetId;
            $del = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $del->bind_param('i', $tid);
            $del->execute();
            $del->close();
            $msg = 'Menu item deleted.';
        } elseif ($actionType === 'register_consumer' && $targetType === 'user') {
            // Approve new consumer registration: activate account
            $upd = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
            $upd->bind_param('s', $targetId);
            $upd->execute();
            $upd->close();
            $msg = 'Registration approved and account activated.';
        } else {
            $msg = 'Approval approved.';
        }
    } else {
        $msg = 'Request rejected.';
    }

    $conn->close();
    ob_clean();
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (Exception $e) {
    $conn->close();
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
ob_end_flush();
