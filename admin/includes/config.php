<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');

$host = '127.0.0.1';
$db   = 'Biratnagar-Collection';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (!function_exists('money')) {
    function money($amount) {
        return "Rs. " . number_format((float)$amount, 0);
    }
}
?>