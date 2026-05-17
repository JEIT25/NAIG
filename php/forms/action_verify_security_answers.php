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
    $q1 = trim($_POST['question1'] ?? '');
    $ans1 = trim($_POST['secure_answer'] ?? '');
    $q2 = trim($_POST['question2'] ?? '');
    $ans2 = trim($_POST['secure_answer2'] ?? '');
    $q3 = trim($_POST['question3'] ?? '');
    $ans3 = trim($_POST['secure_answer3'] ?? '');

    if (empty($q1) || empty($ans1) || empty($q2) || empty($ans2) || empty($q3) || empty($ans3)) {
        echo json_encode(['success' => false, 'message' => 'Please select and answer all three security questions.']);
        exit;
    }

    $ans1 = strtolower($ans1);
    $ans2 = strtolower($ans2);
    $ans3 = strtolower($ans3);

    $stmt = $conn->prepare("SELECT secure_question, secure_answer, secure_question2, secure_answer2, secure_question3, secure_answer3 FROM users WHERE id = ?");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        $correct_count = 0;
        if ($q1 === $user['secure_question'] && password_verify($ans1, $user['secure_answer'] ?? ''))
            $correct_count++;
        if ($q2 === $user['secure_question2'] && password_verify($ans2, $user['secure_answer2'] ?? ''))
            $correct_count++;
        if ($q3 === $user['secure_question3'] && password_verify($ans3, $user['secure_answer3'] ?? ''))
            $correct_count++;

        if ($correct_count === 3) {
            $_SESSION['security_verified'] = true;
            echo json_encode(['success' => true, 'message' => 'Verification successful!']);
        }
        else {
            echo json_encode(['success' => false, 'message' => 'Verification failed. Questions or answers do not match our records.']);
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
