<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

// Get input values
$id = trim($_POST['id'] ?? ''); // If provided, it's an edit
$customId = trim($_POST['custom_id'] ?? ''); 
$firstName = trim($_POST['firstName'] ?? '');
$lastName = trim($_POST['lastName'] ?? '');
$middleInitial = trim($_POST['middleInitial'] ?? '');
$extension = trim($_POST['extension'] ?? '');
$sex = trim($_POST['sex'] ?? '');
$birthdate = trim($_POST['birthdate'] ?? '');
$purok = trim($_POST['purok'] ?? '');
$barangay = trim($_POST['barangay'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$zipCode = trim($_POST['zipCode'] ?? '');
$country = trim($_POST['country'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? 'consumer');
$password = trim($_POST['password'] ?? '');

if (!$firstName || !$lastName || !$username || !$email || !$sex || !$birthdate) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Calculate Age
$age = date_diff(date_create($birthdate), date_create('today'))->y;

// Check duplicate username/email
$sql = "SELECT id FROM users WHERE (username = ? OR email = ?)";
if ($id) $sql .= " AND id != ?";
$stmt = $conn->prepare($sql);
if ($id) $stmt->bind_param('sss', $username, $email, $id);
else $stmt->bind_param('ss', $username, $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Username or Email already exists']);
    exit;
}
$stmt->close();

$superadmin_swap = false;

if ($id) {
    // UPDATE USER
    $types = "ssssssisssssssss"; 
    $params = [
        $firstName, $lastName, $middleInitial, $extension, $sex, $birthdate, $age,
        $purok, $barangay, $city, $province, $zipCode, $country,
        $username, $email, $role
    ];

    $sql = "UPDATE users SET firstName=?, lastName=?, middleInitial=?, extension=?, sex=?, birthdate=?, age=?,
            purok=?, barangay=?, city=?, province=?, zipCode=?, country=?,
            username=?, email=?, role=?";

    if ($password) {
        $sql .= ", password=?";
        $types .= "s";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id=?";
    $types .= "s";
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    $conn->begin_transaction();
    try {
        if ($role === 'superadmin' && $id !== $_SESSION['user']['id']) {
            $currentSuperadminId = $_SESSION['user']['id'];
            $blockStmt = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
            $blockStmt->bind_param('s', $currentSuperadminId);
            $blockStmt->execute();
            $blockStmt->close();
            $superadmin_swap = true;
        }

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'superadmin_swap' => $superadmin_swap]);
        } else {
            throw new Exception("Database error: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    $stmt->close();
}
else {
    // CREATE USER
    if (!$password) {
        echo json_encode(['success' => false, 'error' => 'Password required for new user']);
        exit;
    }

    $newId = $customId ? $customId : sprintf('%04d-%04d', rand(0, 9999), rand(0, 9999));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $status = 'registered';
    $is_blocked = 0;

    $conn->begin_transaction();
    try {
        if ($role === 'superadmin') {
            // Escalation Policy: Block current superadmin
            $currentSuperadminId = $_SESSION['user']['id'];
            $blockStmt = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
            $blockStmt->bind_param('s', $currentSuperadminId);
            $blockStmt->execute();
            $blockStmt->close();
            $superadmin_swap = true;
        }

        $sql = "INSERT INTO users (
                    id, firstName, lastName, middleInitial, extension, sex, birthdate, age,
                    purok, barangay, city, province, zipCode, country,
                    username, email, password, role, status, is_blocked
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'sssssssissssssssssis',
            $newId, $firstName, $lastName, $middleInitial, $extension, $sex, $birthdate, $age,
            $purok, $barangay, $city, $province, $zipCode, $country,
            $username, $email, $hashedPassword, $role, $status, $is_blocked
        );
        
        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'superadmin_swap' => $superadmin_swap]);
        } else {
            throw new Exception("Database error: " . $conn->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    $stmt->close();
}
$conn->close();
?>
