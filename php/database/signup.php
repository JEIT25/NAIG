<?php
session_start();
require '../database/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get input values
    $id = $_POST['id'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $middleInitial = $_POST['middleInitial'];
    $extension = $_POST['extension'];
    $purok = $_POST['purok'];
    $barangay = $_POST['barangay'];
    $city = $_POST['city'];
    $province = $_POST['province'];
    $zipCode = $_POST['zipCode'];
    $country = $_POST['country'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $birthdate = $_POST['birthdate'];
    $age = date_diff(date_create($birthdate), date_create('today'))->y;
    $secure_question = $_POST['secure_question'];
    $secure_answerRaw = $_POST['secure_answer'];
    $secure_question2 = $_POST['secure_question2'];
    $secure_answer2Raw = $_POST['secure_answer2'];
    $secure_question3 = $_POST['secure_question3'];
    $secure_answer3Raw = $_POST['secure_answer3'];

    // Hash password AND all security answers
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $secure_answerHashed = password_hash($secure_answerRaw, PASSWORD_DEFAULT);
    $secure_answer2Hashed = password_hash($secure_answer2Raw, PASSWORD_DEFAULT);
    $secure_answer3Hashed = password_hash($secure_answer3Raw, PASSWORD_DEFAULT);

    // --- CHECK FOR DUPLICATES (Server-side safety check) ---
    // 1. Check ID
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "This ID is already registered.";
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // 2. Check Username
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "This username is already taken.";
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // 3. Check Email
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "This email is already registered.";
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // --- END DUPLICATE CHECK ---

    // New users from public signup are always consumers; admin/superadmin are set manually in DB
    $role = 'consumer';

    // Prepare SQL statement (includes role and all security questions)
    // New public registrations start as blocked (is_blocked = 1) until approved
    $sql = "INSERT INTO users (
                id, firstName, lastName, middleInitial, extension,
                purok, barangay, city, province, zipCode, country,
                username, email, password, birthdate, age,
                secure_question, secure_answer, secure_question2, secure_answer2,
                secure_question3, secure_answer3, role, is_blocked
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $isBlocked = 1; // pending approval
        $stmt->bind_param(
            'sssssssssssssssssssssssi', // 24 params: 23 strings, 1 int (is_blocked)
            $id,
            $firstName,
            $lastName,
            $middleInitial,
            $extension,
            $purok,
            $barangay,
            $city,
            $province,
            $zipCode,
            $country,
            $username,
            $email,
            $hashedPassword,
            $birthdate,
            $age,
            $secure_question,
            $secure_answerHashed,
            $secure_question2,
            $secure_answer2Hashed,
            $secure_question3,
            $secure_answer3Hashed,
            $role,
            $isBlocked
        );

        if ($stmt->execute()) {
            // After creating the user, create a registration approval request
            $reason = 'New consumer registration';
            $approval = $conn->prepare("INSERT INTO approvals (requested_by, action_type, target_type, target_id, reason, status) VALUES (?, 'register_consumer', 'user', ?, ?, 'pending')");
            if ($approval) {
                $approval->bind_param('sss', $id, $id, $reason);
                $approval->execute();
                $approval->close();
            }
            echo "Registration submitted for approval!";
        }
        else {
            if ($conn->errno == 1062) {
                echo "An error occurred: Duplicate entry for a unique field.";
            }
            else {
                echo "Signup failed. Please try again. Error: " . $stmt->error;
            }
        }

        $stmt->close();
    }
    else {
        die("Database Error: " . $conn->error);
    }

    $conn->close();
}
?>