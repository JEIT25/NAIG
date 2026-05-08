<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

$user_id = trim($_POST['user_id'] ?? '');

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid request: User ID missing.']);
    exit;
}

// Prevent deleting self
if ($user_id === $_SESSION['user']['id']) {
    echo json_encode(['success' => false, 'error' => 'Security Error: You cannot delete your own account while logged in.']);
    exit;
}

// Check for active dependencies (Orders, Reservations, etc.)
// In a production environment, we might want to check these explicitly or let the FK constraint fail.
// We'll use a try-catch or check the affected rows.

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param('s', $user_id);

try {
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'User not found or already deleted.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: Could not complete deletion.']);
    }
} catch (mysqli_sql_exception $e) {
    // Check for FK constraint violation (errno 1451)
    if ($e->getCode() == 1451 || strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete user: This account has active history (Orders or Reservations) and cannot be removed.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
    }
}

$stmt->close();
$conn->close();
?>
