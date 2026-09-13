<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header("Location: index.php?page=account");
        exit;
    } else {
        $error = "Invalid credentials.";
    }
}
?>

<div class="auth-wrap">
  <div class="auth-card">
    <h1>Welcome Back</h1>
    <?php if ($error): ?><p style="color:red"><?= $error ?></p><?php endif; ?>
    <form method="POST">
      <div class="field">
        <label>Email *</label>
        <input type="email" name="email" required placeholder="you@example.com">
      </div>
      <div class="field">
        <label>Password *</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button class="btn btn-green" style="width:100%">Login</button>
    </form>
    <p style="text-align:center;margin:18px 0 0">
      Don't have an account? <a href="index.php?page=register" style="color:#087443;font-weight:800">Create one</a>
    </p>
  </div>
</div>