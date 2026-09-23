<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// -------------------------------------------------------------
// AUTO-SETUP: Create admin_users table & default admin if missing
// -------------------------------------------------------------
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    if ($tableCheck->rowCount() === 0) {
        // Create table using clean string formatting
        $createTableSql = 'CREATE TABLE admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        
        $pdo->exec($createTableSql);

        // Insert default admin user ('admin' / 'admin123')
        $defaultUser = 'admin';
        $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)');
        $stmt->execute([$defaultUser, $defaultPass, 'admin@ramropasal.com']);
    }
} catch (PDOException $e) {
    // Silently continue if table already exists or execution handles it
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Biratnagar Ramro Pasal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background-color: #f8fafc;
      color: #0f172a;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }

    .login-card {
      background: #ffffff;
      width: 100%;
      max-width: 400px;
      padding: 40px;
      border-radius: 16px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
    }

    .brand-header {
      text-align: center;
      margin-bottom: 32px;
    }

    .brand-logo {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 48px;
      height: 48px;
      background: #eaf7f0;
      color: #087443;
      border-radius: 12px;
      font-size: 22px;
      margin-bottom: 16px;
    }

    .brand-header h1 {
      font-size: 22px;
      font-weight: 700;
      color: #0f172a;
      letter-spacing: -0.02em;
    }

    .brand-header p {
      font-size: 14px;
      color: #64748b;
      margin-top: 6px;
    }

    .alert-error {
      background-color: #fef2f2;
      border: 1px solid #fee2e2;
      color: #dc2626;
      padding: 12px 14px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 24px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #334155;
      margin-bottom: 8px;
    }

    .form-control {
      width: 100%;
      padding: 12px 14px;
      font-size: 14px;
      font-family: inherit;
      color: #0f172a;
      background-color: #ffffff;
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      outline: none;
      transition: all 0.2s ease;
    }

    .form-control:focus {
      border-color: #087443;
      box-shadow: 0 0 0 4px rgba(8, 116, 67, 0.1);
    }

    .form-control::placeholder {
      color: #94a3b8;
    }

    .btn-submit {
      width: 100%;
      padding: 12px;
      font-size: 14px;
      font-weight: 600;
      font-family: inherit;
      color: #ffffff;
      background-color: #087443;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background-color 0.2s ease, transform 0.1s ease;
      margin-top: 8px;
    }

    .btn-submit:hover {
      background-color: #065f37;
    }

    .btn-submit:active {
      transform: scale(0.99);
    }

    .login-footer {
      text-align: center;
      margin-top: 28px;
      font-size: 12px;
      color: #94a3b8;
    }
  </style>
</head>
<body>

  <div class="login-card">
    <div class="brand-header">
      <div class="brand-logo">🛍️</div>
      <h1>Admin Portal</h1>
      <p>Sign in to manage Biratnagar Ramro Pasal</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-error">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Username</label>
        <input 
          type="text" 
          id="username" 
          name="username" 
          class="form-control" 
          placeholder="Enter your username" 
          required 
          autofocus 
        />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input 
          type="password" 
          id="password" 
          name="password" 
          class="form-control" 
          placeholder="••••••••" 
          required 
        />
      </div>

      <button type="submit" class="btn-submit">Sign In</button>
    </form>

    <div class="login-footer">
      &copy; <?= date('Y') ?> Biratnagar Ramro Pasal. All rights reserved.
    </div>
  </div>

</body>
</html>