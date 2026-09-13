<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')");
        $stmt->execute([$name, $email, $phone, $password]);
        
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_name'] = $name;
        header("Location: index.php?page=account");
        exit;
    } catch (\PDOException $e) {
        $error = "Email already registered.";
    }
}
?>

<div class="auth-wrap">
  <div class="auth-card">
    <h1>Create Account</h1>
    <?php if ($error): ?><p style="color:red"><?= $error ?></p><?php endif; ?>
    <form method="POST">
      <div class="field"><label>Full Name *</label><input name="name" required placeholder="Your full name"></div>
      <div class="field"><label>Email *</label><input type="email" name="email" required placeholder="you@example.com"></div>
      <div class="field"><label>Phone Number *</label><input name="phone" required placeholder="98XXXXXXXX"></div>
      <div class="field"><label>Password *</label><input type="password" name="password" required placeholder="At least 6 characters"></div>
      <button class="btn btn-green" style="width:100%">Create Account</button>
    </form>
  </div>
</div>