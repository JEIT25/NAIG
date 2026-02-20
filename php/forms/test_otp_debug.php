<?php
// Debugging script for OTP sending
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting OTP Send Debug...\n";
echo "--------------------------\n";

// 1. Test Database Connectivity
echo "Testing DB Connection...\n";
require_once 'c:/xampp/htdocs/NAIG/php/database/db_connect.php';

if ($conn->connect_error) {
    echo "DB Connection Failed: " . $conn->connect_error . "\n";
    exit;
}
else {
    echo "DB Connection Successful.\n";
}

// 2. Test Email Config
echo "Testing Email Config include...\n";
require_once 'c:/xampp/htdocs/NAIG/php/config/email_config.php';
echo "Email Config included.\n";

// 3. Mock Session and User Data
session_start();
// Use a known user or fetch one
$mock_email = 'jerold.amora@csucc.edu.ph'; // Using the sender email itself for test, or fetch a real user
$sql = "SELECT id, email FROM users LIMIT 1";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "Found user: ID={$user['id']}, Email={$user['email']}\n";
    $_SESSION['reset_user_id'] = $user['id'];
}
else {
    echo "No users found in DB to test with.\n";
    exit;
}

// 4. Simulate sending OTP Logic (Copy-paste essential parts or include the file if possible, but include might exit)
// Let's call the function directly
echo "Attempting to send OTP to {$user['email']}...\n";

$otp_code = '123456';
$config = getEmailConfig();
echo "Config Loaded. Sender: " . $config['mailjet']['sender_email'] . "\n";

// Use the function
$success = sendViaMailjet($user['email'], $otp_code, $config['mailjet']);

if ($success) {
    echo "SUCCESS: sendViaMailjet returned true.\n";
}
else {
    echo "FAILURE: sendViaMailjet returned false.\n";
}

echo "--------------------------\n";
echo "End of Debug.\n";
?>
