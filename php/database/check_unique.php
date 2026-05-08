<?php
require './db_connect.php';

// We must set the content type to JSON for the fetch() response
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    $exclude_id = $_POST['exclude_id'] ?? '';

    // A whitelist of fields we are allowed to check
    $allowed_fields = ['id', 'username', 'email'];

    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['error' => 'Invalid validation field.']);
        exit;
    }

    if (empty($value)) {
        echo json_encode(['exists' => false]);
        exit;
    }

    $sql = "SELECT 1 FROM users WHERE $field = ?";
    if ($exclude_id) {
        $sql .= " AND id != ?";
    }
    $sql .= " LIMIT 1";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['error' => 'Database prepare error.']);
        exit;
    }

    if ($exclude_id) {
        $stmt->bind_param('ss', $value, $exclude_id);
    } else {
        $stmt->bind_param('s', $value);
    }

    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }

    $stmt->close();
    $conn->close();
} else {
    // Not a POST request
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}
?>