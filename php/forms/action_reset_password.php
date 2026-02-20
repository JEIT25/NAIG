<?php
/**
 * Action Reset Password
 * Handles password reset via AJAX from the login modal
 */
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../database/db_connect.php';

// Check if user is verified via OTP and Security Questions
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_verified']) || !isset($_SESSION['security_verified'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
    exit;
}

$user_id = $_SESSION['reset_user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a new password']);
        exit;
    }
    elseif (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        exit;
    }
    elseif ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit;
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('ss', $hashed_password, $user_id);

    if ($stmt->execute()) {
        // Clear session
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['otp_verified']);
        unset($_SESSION['security_verified']);
        unset($_SESSION['otp_sent']);
        unset($_SESSION['otp_expires_at']);

        echo json_encode(['success' => true, 'message' => 'Password reset successfully! You can now login.']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
    }
    $stmt->close();
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
