<?php
/**
 * update_schema.php — Brings an existing database up to date.
 */
require_once 'db_connect.php';

// 1. users.status column
$result = $conn->query("SHOW COLUMNS FROM `users` LIKE 'status'");
if ($result && $result->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN status ENUM('pending','registered','rejected') NOT NULL DEFAULT 'pending' AFTER role");
    $conn->query("UPDATE users SET status = 'registered'");
} else {
    $conn->query("ALTER TABLE users MODIFY COLUMN status ENUM('pending','registered','rejected') NOT NULL DEFAULT 'pending'");
    $conn->query("UPDATE users SET status = 'registered' WHERE status = 'approved'");
}

$conn->query("UPDATE users SET status = 'registered' WHERE status = 'pending' AND id NOT IN (SELECT target_id FROM user_block_requests WHERE request_type = 'registration' AND status = 'pending')");

// 2. user_block_requests.request_type column
$result = $conn->query("SHOW COLUMNS FROM `user_block_requests` LIKE 'request_type'");
if ($result && $result->num_rows === 0) {
    $conn->query("ALTER TABLE user_block_requests ADD COLUMN request_type ENUM('block','unblock','registration') DEFAULT 'block' AFTER target_id");
} else {
    $conn->query("ALTER TABLE user_block_requests MODIFY COLUMN request_type ENUM('block','unblock','registration') DEFAULT 'block'");
}

echo "Schema updated successfully.";
$conn->close();
?>
