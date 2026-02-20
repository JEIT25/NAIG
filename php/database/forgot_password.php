<?php
/**
 * Forgot password with OTP (AMORA-style).
 * Actions: verify_user_id, send_otp, verify_otp, get_security_question, verify_security_question, change_password.
 */
session_start();
require_once __DIR__ . '/db_connect.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Step 1: Verify User ID
if ($action === 'verify_user_id') {
    $userId = trim($_POST['user_id'] ?? '');
    if (empty($userId)) {
        echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
        exit;
    }
    $stmt = $conn->prepare("SELECT id, username, email, firstName, lastName FROM users WHERE id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['forgot_password_user_id'] = $user['id'];
        $_SESSION['forgot_password_username'] = $user['username'];
        $_SESSION['forgot_password_email'] = $user['email'];
        $fullName = $user['firstName'] . ' ' . $user['lastName'];
        echo json_encode([
            'status' => 'success',
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'fullname' => $fullName
        ]);
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'User ID not found.']);
    }
    $stmt->close();
    exit;
}

// Step 2: Send OTP (with resend cooldown 60s, expiry 15 min)
if ($action === 'send_otp') {
    if (!isset($_SESSION['forgot_password_user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Please verify your User ID first.']);
        exit;
    }
    $userId = $_SESSION['forgot_password_user_id'];

    $checkSql = "SELECT id, otp_code, expires_at FROM password_reset_otp WHERE user_id = ? AND used = 0 ORDER BY created_at DESC LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('s', $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        $otpRecord = $checkResult->fetch_assoc();
        $expiresAt = strtotime($otpRecord['expires_at']);
        if ($expiresAt > time()) {
            $createdTime = $expiresAt - (15 * 60);
            $resendAvailableIn = max(0, min(60, 60 - (time() - $createdTime)));
            if ($resendAvailableIn > 0) {
                $checkStmt->close();
                echo json_encode([
                    'status' => 'existing_otp',
                    'message' => 'You already have a valid OTP. Check your email.',
                    'remaining_seconds' => $resendAvailableIn,
                    'email' => $_SESSION['forgot_password_email'] ?? ''
                ]);
                exit;
            }
        }
    }
    $checkStmt->close();

    $del = $conn->prepare("DELETE FROM password_reset_otp WHERE user_id = ? AND used = 0");
    $del->bind_param('s', $userId);
    $del->execute();
    $del->close();
    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60));
    $ins = $conn->prepare("INSERT INTO password_reset_otp (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
    $ins->bind_param('sss', $userId, $otp, $expiresAt);
    if (!$ins->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to generate OTP.']);
        exit;
    }
    $ins->close();

    $email = $_SESSION['forgot_password_email'];

    // Send using Mailjet via email_config.php
    require_once __DIR__ . '/../config/email_config.php';
    $sent = sendOTPEmail($email, $otp);

    echo json_encode([
        'status' => $sent ? 'success' : 'error',
        'message' => $sent ? 'OTP sent to your email.' : 'Email could not be sent. Check Mailjet configuration.',
        'email' => $email,
        'remaining_seconds' => 60
    ]);
    exit;
}

// Step 3: Verify OTP
if ($action === 'verify_otp') {
    if (!isset($_SESSION['forgot_password_user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Start over.']);
        exit;
    }
    $userId = $_SESSION['forgot_password_user_id'];
    $userOtp = trim($_POST['otp'] ?? '');
    if (strlen($userOtp) !== 6 || !ctype_digit($userOtp)) {
        echo json_encode(['status' => 'error', 'message' => 'OTP must be 6 digits.']);
        exit;
    }
    $stmt = $conn->prepare("SELECT id, expires_at FROM password_reset_otp WHERE user_id = ? AND otp_code = ? AND used = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param('ss', $userId, $userOtp);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (strtotime($row['expires_at']) > time()) {
            $conn->query("UPDATE password_reset_otp SET used = 1 WHERE id = " . (int)$row['id']);
            $_SESSION['forgot_password_otp_verified'] = true;
            echo json_encode(['status' => 'success', 'message' => 'OTP verified.']);
        }
        else {
            echo json_encode(['status' => 'error', 'message' => 'OTP expired. Request a new one.']);
        }
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid OTP.']);
    }
    $stmt->close();
    exit;
}

// Get security question (single; NAIG has one question)
if ($action === 'get_security_question') {
    if (!isset($_SESSION['forgot_password_user_id']) || !isset($_SESSION['forgot_password_otp_verified'])) {
        echo json_encode(['status' => 'error', 'message' => 'Complete previous steps first.']);
        exit;
    }
    $userId = $_SESSION['forgot_password_user_id'];
    $stmt = $conn->prepare("SELECT secure_question, secure_question2, secure_question3 FROM users WHERE id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check if questions are set
        if (empty($row['secure_question']) || empty($row['secure_question2']) || empty($row['secure_question3'])) {
            echo json_encode(['status' => 'error', 'message' => 'Security questions not set for this account. Cannot proceed.']);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'question1' => $row['secure_question'],
            'question2' => $row['secure_question2'],
            'question3' => $row['secure_question3']
        ]);
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }
    $stmt->close();
    exit;
}

// Verify security answer and allow password change
if ($action === 'verify_security_question') {
    if (!isset($_SESSION['forgot_password_user_id']) || !isset($_SESSION['forgot_password_otp_verified'])) {
        echo json_encode(['status' => 'error', 'message' => 'Complete previous steps first.']);
        exit;
    }
    $userId = $_SESSION['forgot_password_user_id'];
    $answer1 = trim($_POST['answer1'] ?? '');
    $answer2 = trim($_POST['answer2'] ?? '');
    $answer3 = trim($_POST['answer3'] ?? '');

    if (empty($answer1) || empty($answer2) || empty($answer3)) {
        echo json_encode(['status' => 'error', 'message' => 'All answers are required.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT secure_answer, secure_answer2, secure_answer3 FROM users WHERE id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $correctCount = 0;

        if (password_verify($answer1, $row['secure_answer']))
            $correctCount++;
        if (password_verify($answer2, $row['secure_answer2']))
            $correctCount++;
        if (password_verify($answer3, $row['secure_answer3']))
            $correctCount++;

        if ($correctCount >= 2) {
            $_SESSION['forgot_password_security_verified'] = true;
            echo json_encode(['status' => 'success', 'message' => 'Identity verified. Set new password.']);
        }
        else {
            echo json_encode(['status' => 'error', 'message' => "Verification failed. You got $correctCount/3 correct. Need at least 2."]);
        }
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'User not found.']);
    }
    $stmt->close();
    exit;
}

// Change password
if ($action === 'change_password') {
    if (!isset($_SESSION['forgot_password_user_id']) || !isset($_SESSION['forgot_password_otp_verified']) || !isset($_SESSION['forgot_password_security_verified'])) {
        echo json_encode(['status' => 'error', 'message' => 'Complete all steps first.']);
        exit;
    }
    $userId = $_SESSION['forgot_password_user_id'];
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    if (empty($newPassword) || $newPassword !== $confirmPassword) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match or are empty.']);
        exit;
    }
    if (strlen($newPassword) < 8 || strlen($newPassword) > 25) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be 8–25 characters.']);
        exit;
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('ss', $hash, $userId);
    if ($stmt->execute()) {
        $del2 = $conn->prepare("DELETE FROM password_reset_otp WHERE user_id = ?");
        $del2->bind_param('s', $userId);
        $del2->execute();
        $del2->close();
        unset($_SESSION['forgot_password_user_id'], $_SESSION['forgot_password_username'], $_SESSION['forgot_password_email'], $_SESSION['forgot_password_otp_verified'], $_SESSION['forgot_password_security_verified']);
        echo json_encode(['status' => 'success', 'message' => 'Password changed. You can login now.']);
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
$conn->close();
