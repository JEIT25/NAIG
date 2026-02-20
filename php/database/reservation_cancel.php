<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once 'db_connect.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];
$reservationId = intval($_POST['reservation_id'] ?? 0);

if (!$reservationId) {
    echo json_encode(['success' => false, 'error' => 'Reservation ID is required.']);
    exit;
}

// Consumers can only cancel their own reservations
if ($userRole === 'consumer') {
    $stmt = $conn->prepare("SELECT id, status FROM reservations WHERE id = ? AND user_id = ?");
    $stmt->bind_param("is", $reservationId, $userId);
}
else {
    $stmt = $conn->prepare("SELECT id, status FROM reservations WHERE id = ?");
    $stmt->bind_param("i", $reservationId);
}
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();

if (!$reservation) {
    echo json_encode(['success' => false, 'error' => 'Reservation not found.']);
    exit;
}

if (!in_array($reservation['status'], ['pending', 'confirmed'])) {
    echo json_encode(['success' => false, 'error' => 'Only pending or confirmed reservations can be cancelled.']);
    exit;
}

$stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
$stmt->bind_param("i", $reservationId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Reservation cancelled successfully.']);
}
else {
    echo json_encode(['success' => false, 'error' => 'Failed to cancel reservation.']);
}
$conn->close();
