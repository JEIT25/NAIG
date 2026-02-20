<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Fetch Restaurants for dropdown
    $restaurants = [];
    $rRes = $conn->query("SELECT id, name FROM restaurants ORDER BY name");
    while ($r = $rRes->fetch_assoc())
        $restaurants[] = $r;

    // Count
    $countRes = $conn->query("SELECT COUNT(*) as total FROM restaurant_tables");
    $total = $countRes->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);

    // Fetch Tables
    $sql = "SELECT rt.id, rt.table_number, rt.capacity, rt.location, rt.is_available, rt.restaurant_id, r.name as restaurant_name
            FROM restaurant_tables rt
            JOIN restaurants r ON rt.restaurant_id = r.id
            ORDER BY r.name, rt.table_number
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $list = [];
    while ($row = $result->fetch_assoc())
        $list[] = $row;

    echo json_encode([
        'success' => true,
        'tables' => $list,
        'restaurants' => $restaurants,
        'pagination' => ['current' => $page, 'total_pages' => $totalPages, 'total_records' => $total]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $restaurant_id = (int)($_POST['restaurant_id'] ?? 0);
        $table_number = trim($_POST['table_number'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 2);
        $location = $_POST['location'] ?? 'indoor';
        $is_available = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 1;

        if ($restaurant_id <= 0 || $table_number === '') {
            echo json_encode(['success' => false, 'error' => 'Restaurant and Table Number required']);
            exit;
        }

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE restaurant_tables SET restaurant_id=?, table_number=?, capacity=?, location=?, is_available=? WHERE id=?");
            $stmt->bind_param('isisii', $restaurant_id, $table_number, $capacity, $location, $is_available, $id);
        }
        else {
            // Check duplicate table number in same restaurant
            $check = $conn->prepare("SELECT id FROM restaurant_tables WHERE restaurant_id = ? AND table_number = ?");
            $check->bind_param('is', $restaurant_id, $table_number);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'error' => 'Table number already exists for this restaurant']);
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO restaurant_tables (restaurant_id, table_number, capacity, location, is_available) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('isisi', $restaurant_id, $table_number, $capacity, $location, $is_available);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        }
        else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM restaurant_tables WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute())
                echo json_encode(['success' => true]);
            else
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            $stmt->close();
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
    }
}
$conn->close();
