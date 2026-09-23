<?php
require_once __DIR__ . '/includes/config.php';

$username = 'admin';
$password = 'adminpassword'; // Change this to your desired password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Insert or update admin user
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?) ON DUPLICATE KEY UPDATE password = ?");
    $stmt->execute([$username, $hashedPassword, $hashedPassword]);

    echo "Admin user created/updated successfully!<br>";
    echo "<strong>Username:</strong> admin<br>";
    echo "<strong>Password:</strong> adminpassword";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>