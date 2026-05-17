<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

// Get input values (consumer specific)
$id = trim($_POST['id'] ?? '');
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
$password = trim($_POST['password'] ?? '');

if (!$firstName || !$lastName || !$username || !$email || !$sex || !$birthdate) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Age calc
$age = date_diff(date_create($birthdate), date_create('today'))->y;

// Check duplicates
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

$role = 'consumer'; // Forced

if ($id) {
    // UPDATE
    $types = "ssssssissssssss"; 
    $params = [
        $firstName, $lastName, $middleInitial, $extension, $sex, $birthdate, $age,
        $purok, $barangay, $city, $province, $zipCode, $country,
        $username, $email
    ];
    $sql = "UPDATE users SET firstName=?, lastName=?, middleInitial=?, extension=?, sex=?, birthdate=?, age=?,
            purok=?, barangay=?, city=?, province=?, zipCode=?, country=?,
            username=?, email=?, role='consumer'";

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
}
else {
    // CREATE
    if (!$password) {
        echo json_encode(['success' => false, 'error' => 'Password required for new user']);
        exit;
    }

    $newId = $customId ? $customId : sprintf('%04d-%04d', rand(0, 9999), rand(0, 9999));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $status = 'registered';
    $is_blocked = 0;

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
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
}
else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
}
$stmt->close();
$conn->close();
?>
