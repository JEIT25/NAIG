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

    // Count
    $countRes = $conn->query("SELECT COUNT(*) as total FROM restaurants");
    $total = $countRes->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);

    // Fetch
    $stmt = $conn->prepare("SELECT id, name, description, address, image_path, cuisine_type, capacity, opening_time, closing_time, price_range, is_active FROM restaurants ORDER BY name LIMIT ? OFFSET ?");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $list = [];
    while ($row = $result->fetch_assoc())
        $list[] = $row;

    echo json_encode(['success' => true, 'restaurants' => $list, 'pagination' => ['current' => $page, 'total_pages' => $totalPages, 'total_records' => $total]]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $image_path = trim($_POST['image_path'] ?? '');
        $cuisine = trim($_POST['cuisine_type'] ?? 'General');
        $capacity = (int)($_POST['capacity'] ?? 50);
        $openTime = $_POST['opening_time'] ?? '09:00';
        $closeTime = $_POST['closing_time'] ?? '22:00';
        $price = $_POST['price_range'] ?? '$$';
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if ($name === '') {
            echo json_encode(['success' => false, 'error' => 'Name required']);
            exit;
        }
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE restaurants SET name=?, description=?, address=?, image_path=?, cuisine_type=?, capacity=?, opening_time=?, closing_time=?, price_range=?, is_active=? WHERE id=?");
            $stmt->bind_param('sssssisssii', $name, $description, $address, $image_path, $cuisine, $capacity, $openTime, $closeTime, $price, $is_active, $id);
        }
        else {
            $stmt = $conn->prepare("INSERT INTO restaurants (name, description, address, image_path, cuisine_type, capacity, opening_time, closing_time, price_range, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssssisssi', $name, $description, $address, $image_path, $cuisine, $capacity, $openTime, $closeTime, $price, $is_active);
        }
        if ($stmt->execute()) {
            $newId = $id > 0 ? $id : $conn->insert_id;
            echo json_encode(['success' => true, 'id' => $newId]);
        }
        else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    }
    elseif ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $status = (int)($_POST['status'] ?? 0); // 1 or 0
        if ($id <= 0) {
            echo json_encode(['success' => false]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE restaurants SET is_active = ? WHERE id = ?");
        $stmt->bind_param('ii', $status, $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }
    else {
        echo json_encode(['success' => false]);
    }
}
$conn->close();
