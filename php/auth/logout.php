<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';

// Log logout if user is logged in
if (isset($_SESSION['user']['id'])) {
    $uid = $_SESSION['user']['id'];
    try {
        $stmt = $conn->prepare("INSERT INTO login_logs (user_id, action) VALUES (?, 'logout')");
        $stmt->bind_param('s', $uid);
        $stmt->execute();
        $stmt->close();
    }
    catch (Exception $e) {
    // Silent fail
    }
}

session_unset();
session_destroy();

// Redirect to login
$base = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/NAIG';
header('Location: ' . $base . '/php/auth/login.php');
exit;
?>
