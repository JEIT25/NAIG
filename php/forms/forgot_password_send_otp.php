<?php
/**
 * Forgot Password - Step 2: Send OTP
 * Generates and sends OTP to user's email
 */
header('Content-Type: application/json');
ob_start();
error_reporting(0); // Suppress errors to prevent JSON corruption
ini_set('display_errors', 0);

session_start();
require_once '../database/db_connect.php';
require_once '../config/email_config.php';

// Check if user is verified
if (!isset($_SESSION['reset_user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Session expired. Please verify your ID again.']);
    exit;
}

$user_id = $_SESSION['reset_user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get user email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $email = $user['email'];

        // Check if there's a recent OTP (within 1 minute) to prevent spam
        $stmt = $conn->prepare("SELECT id, resend_count, last_resend_at FROM password_reset_otp
                               WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) AND used = 0");
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        $recent_otp = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($recent_otp && $recent_otp['resend_count'] >= 3) {
            echo json_encode(['success' => false, 'message' => 'Too many OTP requests. Please wait 15 minutes before trying again.']);
            exit;
        } else {
            // Generate 6-digit OTP
            $otp_code = sprintf('%06d', mt_rand(0, 999999));
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

            // Delete any existing unused OTPs for this user
            $stmt = $conn->prepare("DELETE FROM password_reset_otp WHERE user_id = ? AND used = 0");
            $stmt->bind_param('s', $user_id);
            $stmt->execute();
            $stmt->close();

            // Insert new OTP
            $resend_count = $recent_otp ? $recent_otp['resend_count'] + 1 : 1;
            $stmt = $conn->prepare("INSERT INTO password_reset_otp
                                   (user_id, otp_code, expires_at, resend_count, ip_address)
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssis', $user_id, $otp_code, $expires_at, $resend_count, $ip_address);

            if ($stmt->execute()) {
                // Send OTP via email
                if (sendOTPEmail($email, $otp_code)) {
                    $_SESSION['otp_sent'] = true;
                    $_SESSION['otp_expires_at'] = $expires_at;
                    ob_clean(); // Clean any previous output
                    echo json_encode(['success' => true, 'message' => 'Verification code sent to your email!']);
                } else {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
                }
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Failed to generate verification code. Please try again.']);
            }
            $stmt->close();
        }
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
} else {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Received: ' . $_SERVER['REQUEST_METHOD'] . ' from ' . $_SERVER['REMOTE_ADDR']]);
}

$conn->close();
?>