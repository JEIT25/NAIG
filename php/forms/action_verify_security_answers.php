<?php
/**
 * Action Verify Security Answers
 * Verifies the 3 security answers for the user in session
 */
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_verified'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired.']);
    exit;
}

$user_id = $_SESSION['reset_user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ans1 = trim($_POST['secure_answer'] ?? '');
    $ans2 = trim($_POST['secure_answer2'] ?? '');
    $ans3 = trim($_POST['secure_answer3'] ?? '');

    if (empty($ans1) || empty($ans2) || empty($ans3)) {
        echo json_encode(['success' => false, 'message' => 'Please answer all three security questions.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT secure_answer, secure_answer2, secure_answer3 FROM users WHERE id = ?");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        $correct_count = 0;
        if (strtolower($ans1) === strtolower($user['secure_answer']))
            $correct_count++;
        if (strtolower($ans2) === strtolower($user['secure_answer2']))
            $correct_count++;
        if (strtolower($ans3) === strtolower($user['secure_answer3']))
            $correct_count++;

        if ($correct_count >= 2) {
            $_SESSION['security_verified'] = true;
            echo json_encode(['success' => true, 'message' => 'Verification successful! (' . $correct_count . '/3 correct)']);
        }
        else {
            echo json_encode(['success' => false, 'message' => 'Verification failed. At least 2 answers must be correct. (' . $correct_count . '/3 correct)']);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }

    $stmt->close();
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>
