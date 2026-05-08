<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
require_once '../database/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id'] ?? '');

    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'Please enter your ID number.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, firstName, lastName, email, is_blocked FROM users WHERE id = ?");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // --- NEW: Block forgot password for pending registrations ---
        if ((int) ($user['is_blocked'] ?? 0) === 1) {
            $regStmt = $conn->prepare("SELECT status FROM approvals WHERE action_type = 'register_consumer' AND target_type = 'user' AND target_id = ? ORDER BY created_at DESC LIMIT 1");
            if ($regStmt) {
                $regStmt->bind_param('s', $user['id']);
                $regStmt->execute();
                $regRes = $regStmt->get_result();
                if ($row = $regRes->fetch_assoc()) {
                    if (($row['status'] ?? '') === 'pending') {
                        echo json_encode(['success' => false, 'message' => 'Your account is still pending approval. You cannot reset your password yet.']);
                        exit;
                    }
                }
                $regStmt->close();
            }
        }
        // --- END NEW ---

        // Mask the email for security (e.g., j*****@gmail.com)
        $email_parts = explode('@', $user['email']);
        $name_part = $email_parts[0];
        $domain_part = $email_parts[1];
        $masked_email = substr($name_part, 0, 1) . '*****' . substr($name_part, -1) . '@' . $domain_part;

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['firstName'] . ' ' . $user['lastName'],
                'email' => $masked_email, // Send masked email to frontend
                'real_email' => $user['email'] // Send real email if needed for debugging or internal use, but ideally we only show masked
            ]
        ]);

        // Store ID in session for the next steps
        session_start();
        $_SESSION['reset_user_id'] = $user['id'];

    } else {
        echo json_encode(['success' => false, 'message' => 'ID not found in our system.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
$conn->close();
?>