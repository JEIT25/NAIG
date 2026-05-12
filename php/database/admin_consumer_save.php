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

// Security Questions (Always required for consumer)
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

// Age calc
$age = date_diff(date_create($birthdate), date_create('today'))->y;

// Check duplicates
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

$role = 'consumer'; // Forced

if ($id) {
    // UPDATE
    $types = "ssssssissssssss"; // Correct order: age is 7th (i)
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

    // Update security questions if provided
    if ($secure_question && $secure_answer) {
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
    // CREATE
    if (!$password) {
        echo json_encode(['success' => false, 'error' => 'Password required for new user']);
        exit;
    }

    $newId = $customId ? $customId : sprintf('%04d-%04d', rand(0, 9999), rand(0, 9999));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $ans1 = password_hash($secure_answer, PASSWORD_DEFAULT);
    $ans2 = password_hash($secure_answer2, PASSWORD_DEFAULT);
    $ans3 = password_hash($secure_answer3, PASSWORD_DEFAULT);

    $status = 'registered';
    $sql = "INSERT INTO users (
                id, firstName, lastName, middleInitial, extension, sex, birthdate, age,
                purok, barangay, city, province, zipCode, country,
                username, email, password, role, status,
                secure_question, secure_answer, secure_question2, secure_answer2, secure_question3, secure_answer3
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'sssssssisssssssssssssssss',
        $newId, $firstName, $lastName, $middleInitial, $extension, $sex, $birthdate, $age,
        $purok, $barangay, $city, $province, $zipCode, $country,
        $username, $email, $hashedPassword, $role, $status,
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
