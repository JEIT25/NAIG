<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';

// Force redirect to the new dashboard in /auth/
$url = getBaseUrl() . '/php/auth/dashboard.php';
header("Location: " . $url);
exit;
?>