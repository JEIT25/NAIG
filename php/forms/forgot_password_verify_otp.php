<?php
/**
 * Forgot Password - Step 3: Verify OTP
 * Verifies the OTP entered by the user
 */
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once '../database/db_connect.php';

// Check if OTP was sent
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_sent'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
    exit;
}

$user_id = $_SESSION['reset_user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp_input = trim($_POST['otp_code']);

    if (empty($otp_input)) {
        echo json_encode(['success' => false, 'message' => 'Please enter the verification code.']);
        exit;
    }

    // Check if OTP matches and is not expired
    $stmt = $conn->prepare("SELECT id, expires_at FROM password_reset_otp
                           WHERE user_id = ? AND otp_code = ? AND used = 0");
    $stmt->bind_param('ss', $user_id, $otp_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $otp_data = $result->fetch_assoc();

        if (strtotime($otp_data['expires_at']) < time()) {
            echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one.']);
        }
        else {
            // Mark OTP as used
            $stmt = $conn->prepare("UPDATE password_reset_otp SET used = 1 WHERE id = ?");
            $stmt->bind_param('i', $otp_data['id']);
            $stmt->execute();

            // Set session variable for next step
            $_SESSION['otp_verified'] = true;
            // Also set security_verified to true since we are using OTP as the verification method
            $_SESSION['security_verified'] = true;

            echo json_encode(['success' => true, 'redirect' => 'forgot_password_reset.php']);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
    }
    $stmt->close();
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
