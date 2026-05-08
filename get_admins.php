<?php
require_once 'c:\xampp\htdocs\NAIG\php\database\db_connect.php';
$r = $conn->query("SELECT id, username, role FROM users WHERE role IN ('admin', 'superadmin')");
while($row = $r->fetch_assoc()) {
    print_r($row);
}
