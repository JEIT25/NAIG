<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('consumer');
require_once 'db_connect.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];
$restaurantId = intval($_POST['restaurant_id'] ?? 0);
$tableId = !empty($_POST['table_id']) ? intval($_POST['table_id']) : null;
$date = trim($_POST['reservation_date'] ?? '');
$time = trim($_POST['reservation_time'] ?? '');
$partySize = intval($_POST['party_size'] ?? 1);
$specialRequests = trim($_POST['special_requests'] ?? '');

// Validate
if (!$restaurantId || !$date || !$time || $partySize < 1) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}
if ($partySize > 20) {
    echo json_encode(['success' => false, 'error' => 'Party size cannot exceed 20.']);
    exit;
}

// Check restaurant exists and is active
$stmt = $conn->prepare("SELECT id, opening_time, closing_time FROM restaurants WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $restaurantId);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();
if (!$restaurant) {
    echo json_encode(['success' => false, 'error' => 'Restaurant not found or is not available.']);
    exit;
}

// Validate date is not in the past
$reservationDate = new DateTime($date);
$today = new DateTime('today');
if ($reservationDate < $today) {
    echo json_encode(['success' => false, 'error' => 'Cannot make a reservation in the past.']);
    exit;
}

// Validate time is within operating hours
$resTime = $time . ':00';
if ($resTime < $restaurant['opening_time'] || $resTime >= $restaurant['closing_time']) {
    echo json_encode(['success' => false, 'error' => 'Reservation time is outside operating hours.']);
    exit;
}

// If table is specified, validate it exists and can accommodate the party
if ($tableId) {
    $stmt = $conn->prepare("SELECT id, capacity FROM restaurant_tables WHERE id = ? AND restaurant_id = ? AND is_available = 1");
    $stmt->bind_param("ii", $tableId, $restaurantId);
    $stmt->execute();
    $table = $stmt->get_result()->fetch_assoc();
    if (!$table) {
        echo json_encode(['success' => false, 'error' => 'Selected table is not available.']);
        exit;
    }
    if ($table['capacity'] < $partySize) {
        echo json_encode(['success' => false, 'error' => 'Table capacity (' . $table['capacity'] . ') is less than party size (' . $partySize . ').']);
        exit;
    }

    // Check if table is already reserved at this date/time (within 2 hour window)
    $stmt = $conn->prepare("SELECT id FROM reservations WHERE table_id = ? AND reservation_date = ? AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, ?)) < 120 AND status IN ('pending','confirmed')");
    $stmt->bind_param("iss", $tableId, $date, $resTime);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'This table is already reserved at the selected time.']);
        exit;
    }
}

// Create reservation
$stmt = $conn->prepare("INSERT INTO reservations (user_id, restaurant_id, table_id, reservation_date, reservation_time, party_size, special_requests) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("siissis", $userId, $restaurantId, $tableId, $date, $resTime, $partySize, $specialRequests);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Reservation created successfully!', 'reservation_id' => $stmt->insert_id]);
}
else {
    echo json_encode(['success' => false, 'error' => 'Failed to create reservation. Please try again.']);
}
$conn->close();
