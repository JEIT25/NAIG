<?php
/**
 * Action Get Security Questions
 * Fetches the 3 security questions for the user in session
 */
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../database/db_connect.php';

if (!isset($_SESSION['reset_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired.']);
    exit;
}

$user_id = $_SESSION['reset_user_id'];

$stmt = $conn->prepare("SELECT secure_question, secure_question2, secure_question3 FROM users WHERE id = ?");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'questions' => [
            $user['secure_question'],
            $user['secure_question2'],
            $user['secure_question3']
        ]
    ]);
}
else {
    echo json_encode(['success' => false, 'message' => 'Questions not found for this user.']);
}

$stmt->close();
$conn->close();
?>
