<?php
require_once __DIR__ . '/includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid admin account credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; min-height:100vh; background:#045631;">
  <div class="card" style="width: 380px;">
    <h2>Admin Authentication</h2>
    <?php if ($error): ?><p style="color:red; margin-top:10px;"><?= $error ?></p><?php endif; ?>
    <form method="POST" style="margin-top:20px;">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn-admin" style="width:100%;">Login</button>
    </form>
  </div>
</body>
</html>