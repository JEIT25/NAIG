<?php
/**
 * Redirect to the actual signup page (Register lives in php/forms/signup.php).
 */
session_start();
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = 'http://' . $host . '/NAIG';
header('Location: ' . $base . '/php/forms/signup.php', true, 302);
exit;
