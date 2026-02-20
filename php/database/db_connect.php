<?php
// Database connection parameters
$host = 'localhost';  // or 'localhost'
$dbname = 'naig_db';
$username = 'root';  // Default username for XAMPP
$password = '';      // Default password for XAMPP is empty

// Create a MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
