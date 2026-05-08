<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

// Get input values
$id = trim($_POST['id'] ?? ''); // If provided, it's an edit
$customId = trim($_POST['custom_id'] ?? ''); // For new users, we might want to allow custom ID or auto-gen
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
$role = trim($_POST['role'] ?? '');
if (!$role) $role = 'consumer';
$password = trim($_POST['password'] ?? '');

// Security Questions (Only for consumers)
$secure_question = $_POST['secure_question'] ?? null;
$secure_answer = $_POST['secure_answer'] ?? null;
$secure_question2 = $_POST['secure_question2'] ?? null;
$secure_answer2 = $_POST['secure_answer2'] ?? null;
$secure_question3 = $_POST['secure_question3'] ?? null;
$secure_answer3 = $_POST['secure_answer3'] ?? null;

if (!$firstName || !$lastName || !$username || !$email || !$sex || !$birthdate) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Calculate Age
$age = date_diff(date_create($birthdate), date_create('today'))->y;

// Check duplicate username/email
$sql = "SELECT id FROM users WHERE (username = ? OR email = ?)";
if ($id)
    $sql .= " AND id != ?";
$stmt = $conn->prepare($sql);
if ($id)
    $stmt->bind_param('sss', $username, $email, $id);
else
    $stmt->bind_param('ss', $username, $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Username or Email already exists']);
    exit;
}
$stmt->close();

if ($id) {
    // UPDATE USER
    $types = "ssssssisssssssss"; // Correct order: age is 7th (i), role is 16th (s)
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

    // Update security questions only if role is consumer and provided
    if ($role === 'consumer' && $secure_question && $secure_answer) {
        $sql .= ", secure_question=?, secure_answer=?, secure_question2=?, secure_answer2=?, secure_question3=?, secure_answer3=?";
        $types .= "ssssss";
        $params[] = $secure_question;
        $params[] = password_hash($secure_answer, PASSWORD_DEFAULT);
        $params[] = $secure_question2;
        $params[] = password_hash($secure_answer2, PASSWORD_DEFAULT);
        $params[] = $secure_question3;
        $params[] = password_hash($secure_answer3, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id=?";
    $types .= "s";
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
}
else {
    // CREATE USER
    if (!$password) {
        echo json_encode(['success' => false, 'error' => 'Password required for new user']);
        exit;
    }

    // Use provided ID or generate one
    $newId = $customId ? $customId : sprintf('%04d-%04d', rand(0, 9999), rand(0, 9999));

    // Check ID uniqueness if needed (omitted for brevity, assuming low collision or error catch)

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $ans1 = ($role === 'consumer' && $secure_answer) ? password_hash($secure_answer, PASSWORD_DEFAULT) : null;
    $ans2 = ($role === 'consumer' && $secure_answer2) ? password_hash($secure_answer2, PASSWORD_DEFAULT) : null;
    $ans3 = ($role === 'consumer' && $secure_answer3) ? password_hash($secure_answer3, PASSWORD_DEFAULT) : null;

    // Ensure questions are null if not consumer
    if ($role !== 'consumer') {
        $secure_question = $secure_question2 = $secure_question3 = null;
    }

    $sql = "INSERT INTO users (
                id, firstName, lastName, middleInitial, extension, sex, birthdate, age,
                purok, barangay, city, province, zipCode, country,
                username, email, password, role,
                secure_question, secure_answer, secure_question2, secure_answer2, secure_question3, secure_answer3
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'sssssssissssssssssssssss',
        $newId, $firstName, $lastName, $middleInitial, $extension, $sex, $birthdate, $age,
        $purok, $barangay, $city, $province, $zipCode, $country,
        $username, $email, $hashedPassword, $role,
        $secure_question, $ans1, $secure_question2, $ans2, $secure_question3, $ans3
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
