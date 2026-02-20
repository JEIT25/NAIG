<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin', 'superadmin');
require_once 'db_connect.php';

header('Content-Type: application/json');

$reservationId = intval($_POST['reservation_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$validStatuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];

if (!$reservationId || !in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid reservation ID or status.']);
    exit;
}

$stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $reservationId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Reservation status updated to ' . $newStatus . '.']);
}
else {
    echo json_encode(['success' => false, 'error' => 'Reservation not found or status unchanged.']);
}
$conn->close();
