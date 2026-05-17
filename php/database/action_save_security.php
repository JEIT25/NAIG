<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user']['id'];
$q1 = trim($_POST['secure_question'] ?? '');
$a1 = trim($_POST['secure_answer'] ?? '');
$q2 = trim($_POST['secure_question2'] ?? '');
$a2 = trim($_POST['secure_answer2'] ?? '');
$q3 = trim($_POST['secure_question3'] ?? '');
$a3 = trim($_POST['secure_answer3'] ?? '');

if (!$q1 || !$a1 || !$q2 || !$a2 || !$q3 || !$a3) {
    echo json_encode(['success' => false, 'error' => 'Please answer all questions']);
    exit;
}

$h1 = password_hash($a1, PASSWORD_DEFAULT);
$h2 = password_hash($a2, PASSWORD_DEFAULT);
$h3 = password_hash($a3, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET secure_question = ?, secure_answer = ?, secure_question2 = ?, secure_answer2 = ?, secure_question3 = ?, secure_answer3 = ? WHERE id = ?");
$stmt->bind_param('sssssss', $q1, $h1, $q2, $h2, $q3, $h3, $userId);

if ($stmt->execute()) {
    $user = $_SESSION['user'];
    $redirect = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/NAIG';
    if ($user['role'] === 'admin') $redirect .= '/php/admin/index.php';
    elseif ($user['role'] === 'superadmin') $redirect .= '/php/superadmin/index.php';
    else $redirect .= '/php/auth/dashboard.php';
    
    echo json_encode(['success' => true, 'redirect' => $redirect]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
$stmt->close();
$conn->close();
?>
