<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Ensure user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// -------------------------------------------------------------
// AUTO-SCHEMA UPDATE: Ensure role & status columns exist
// -------------------------------------------------------------
try {
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN role VARCHAR(50) DEFAULT 'Store Manager'");
    $pdo->exec("ALTER TABLE admin_users ADD COLUMN status ENUM('Active', 'Inactive') DEFAULT 'Active'");
} catch (PDOException $e) {
    // Columns already exist
}

$successMsg = '';
$errorMsg = '';

// -------------------------------------------------------------
// 1. HANDLE CURRENT ADMIN PASSWORD CHANGE
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_change_my_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
        $errorMsg = 'All password fields are required.';
    } elseif ($newPass !== $confirmPass) {
        $errorMsg = 'New passwords do not match.';
    } elseif (strlen($newPass) < 6) {
        $errorMsg = 'New password must be at least 6 characters long.';
    } else {
        $stmt = $pdo->prepare('SELECT password FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($currentPass, $user['password'])) {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE admin_users SET password = ? WHERE id = ?');
            $update->execute([$newHash, $_SESSION['admin_id']]);
            $successMsg = 'Your personal password has been updated successfully.';
        } else {
            $errorMsg = 'Incorrect current password.';
        }
    }
}

// -------------------------------------------------------------
// 2. HANDLE CREATE NEW USER & ROLE WITH INITIAL PASSWORD
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'Store Manager';

    if (empty($username) || empty($password)) {
        $errorMsg = 'Username and password are required to create a user.';
    } elseif (strlen($password) < 6) {
        $errorMsg = 'User password must be at least 6 characters long.';
    } else {
        $check = $pdo->prepare('SELECT id FROM admin_users WHERE username = ?');
        $check->execute([$username]);
        if ($check->rowCount() > 0) {
            $errorMsg = 'Username already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO admin_users (username, password, email, role, status) VALUES (?, ?, ?, ?, "Active")');
            $stmt->execute([$username, $hash, $email, $role]);
            $successMsg = "New account '{$username}' created with role '{$role}'.";
        }
    }
}

// -------------------------------------------------------------
// 3. HANDLE OVERRIDE PASSWORD FOR ANY SPECIFIC USER
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reset_user_password'])) {
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $newUserPass  = $_POST['user_new_password'] ?? '';

    if ($targetUserId <= 0 || empty($newUserPass)) {
        $errorMsg = 'Please select a valid user and provide a new password.';
    } elseif (strlen($newUserPass) < 6) {
        $errorMsg = 'Password must be at least 6 characters long.';
    } else {
        $newHash = password_hash($newUserPass, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE admin_users SET password = ? WHERE id = ?');
        $update->execute([$newHash, $targetUserId]);
        $successMsg = "Password successfully updated for User #{$targetUserId}.";
    }
}

// Fetch all system users for management table
$usersList = $pdo->query('SELECT id, username, email, role, status, created_at FROM admin_users ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings & User Roles | Admin Panel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; padding: 30px; }
    .container { max-width: 1100px; margin: 0 auto; }
    .header { margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
    .header h1 { font-size: 24px; font-weight: 700; color: #0f172a; }
    .btn-back { background: #cbd5e1; color: #1e293b; text-decoration: none; padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; }
    
    .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; margin-bottom: 20px; }
    .alert-success { background: #eaf7f0; color: #087443; border: 1px solid #bbf7d0; }
    .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 30px; }
    .card { background: #ffffff; padding: 24px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .card h2 { font-size: 16px; font-weight: 700; margin-bottom: 18px; color: #0f172a; display: flex; align-items: center; gap: 8px; }
    
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
    .form-control { width: 100%; padding: 10px 12px; font-size: 14px; font-family: inherit; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; }
    .form-control:focus { border-color: #087443; box-shadow: 0 0 0 3px rgba(8, 116, 67, 0.1); }
    
    .btn-primary { background: #087443; color: white; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; width: 100%; }
    .btn-primary:hover { background: #065f37; }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 12px 14px; text-align: left; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
    th { background: #f8fafc; font-weight: 600; color: #475569; }
    .badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; }
    .badge-admin { background: #dbeafe; color: #1e40af; }
    .badge-manager { background: #fef3c7; color: #92400e; }
    .badge-active { background: #dcfce7; color: #166534; }
    
    .inline-form { display: flex; gap: 6px; align-items: center; }
    .inline-form input { padding: 6px 8px; font-size: 12px; width: 140px; }
    .inline-form button { width: auto; padding: 6px 10px; font-size: 11px; }
  </style>
</head>
<body>

<div class="container">
  <div class="header">
    <h1>⚙️ Dashboard Settings & User Roles</h1>
    <a href="index.php" class="btn-back">← Back to Dashboard</a>
  </div>

  <?php if (!empty($successMsg)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
  <?php endif; ?>
  <?php if (!empty($errorMsg)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
  <?php endif; ?>

  <div class="grid">
    <!-- 1. CHANGE YOUR PERSONAL PASSWORD -->
    <div class="card">
      <h2>🔐 Change My Password</h2>
      <form method="POST">
        <input type="hidden" name="action_change_my_password" value="1">
        <div class="form-group">
          <label>Current Password</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>New Password</label>
          <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn-primary">Update My Password</button>
      </form>
    </div>

    <!-- 2. ADD NEW STAFF USER & ASSIGN ROLE -->
    <div class="card">
      <h2>👤 Add New Staff User</h2>
      <form method="POST">
        <input type="hidden" name="action_create_user" value="1">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" class="form-control" placeholder="e.g. birat_dispatch" required>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="staff@ramropasal.com">
        </div>
        <div class="form-group">
          <label>Set Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label>Assign Role</label>
          <select name="role" class="form-control">
            <option value="Super Admin">Super Admin (Full Access)</option>
            <option value="Store Manager">Store Manager (Catalog & Prices)</option>
            <option value="Order Dispatcher">Order Dispatcher (Riders & Delivery)</option>
            <option value="Inventory Specialist">Inventory Specialist (Stock Only)</option>
          </select>
        </div>
        <button type="submit" class="btn-primary">Create Staff User</button>
      </form>
    </div>
  </div>

  <!-- 3. SYSTEM USERS & DIRECT PASSWORD OVERRIDE TABLE -->
  <div class="card">
    <h2>👥 System Users & Role Permissions</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Set New Password</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usersList as $u): ?>
          <tr>
            <td>#<?= $u['id'] ?></td>
            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
            <td><?= htmlspecialchars($u['email'] ?? 'N/A') ?></td>
            <td>
              <span class="badge <?= $u['role'] === 'Super Admin' ? 'badge-admin' : 'badge-manager' ?>">
                <?= htmlspecialchars($u['role'] ?? 'Store Manager') ?>
              </span>
            </td>
            <td><span class="badge badge-active"><?= htmlspecialchars($u['status'] ?? 'Active') ?></span></td>
            <td>
              <form method="POST" class="inline-form">
                <input type="hidden" name="action_reset_user_password" value="1">
                <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                <input type="password" name="user_new_password" class="form-control" placeholder="New Password" required>
                <button type="submit" class="btn-primary">Update</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>