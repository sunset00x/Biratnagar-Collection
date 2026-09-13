<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

// Handle updating store contact details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact_info'])) {
    $uPhone = $pdo->prepare("REPLACE INTO store_settings (setting_key, setting_value) VALUES ('contact_phone', ?)");
    $uPhone->execute([trim($_POST['contact_phone'])]);

    $uEmail = $pdo->prepare("REPLACE INTO store_settings (setting_key, setting_value) VALUES ('contact_email', ?)");
    $uEmail->execute([trim($_POST['contact_email'])]);

    $uAddress = $pdo->prepare("REPLACE INTO store_settings (setting_key, setting_value) VALUES ('contact_address', ?)");
    $uAddress->execute([trim($_POST['contact_address'])]);

    header("Location: messages.php?updated=1");
    exit;
}

// Delete Message
if (isset($_GET['delete'])) {
    $delStmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $delStmt->execute([(int)$_GET['delete']]);
    header("Location: messages.php");
    exit;
}

// Fetch Contact Info Settings
$settings = $pdo->query("SELECT setting_key, setting_value FROM store_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch Messages
$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY id DESC")->fetchAll();
?>

<!-- Form to Update Contact Info Boxes -->
<div class="card" style="margin-bottom: 25px;">
 
  <?php if (isset($_GET['updated'])): ?>
    <div style="color:#087443; font-weight:bold; margin-bottom:10px;">✓ Contact information updated successfully!</div>
  <?php endif; ?>
  <form method="POST">
    <input type="hidden" name="update_contact_info" value="1">
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px;">
      <div class="form-group">
        <label>Display Phone Number</label>
        <input type="text" name="contact_phone" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Display Email Address</label>
        <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Display Store Address</label>
        <input type="text" name="contact_address" value="<?= htmlspecialchars($settings['contact_address'] ?? '') ?>" required>
      </div>
    </div>
    <button type="submit" class="btn-admin">Save Info Settings</button>
  </form>
</div>

<!-- Table Displaying Submitted Messages -->
<div class="card">
  <h2>Customer Contact Messages</h2>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Customer Name</th>
        <th>Email</th>
        <th>Message Content</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($messages)): ?>
        <tr><td colspan="5">No contact messages received yet.</td></tr>
      <?php else: ?>
        <?php foreach ($messages as $m): ?>
          <tr>
            <td style="font-size:12px; color:#666; width:140px;"><?= $m['created_at'] ?></td>
            <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
            <td><a href="mailto:<?= htmlspecialchars($m['email']) ?>" style="color:#087443;"><?= htmlspecialchars($m['email']) ?></a></td>
            <td style="max-width:380px;"><?= nl2br(htmlspecialchars($m['message'])) ?></td>
            <td>
              <a href="messages.php?delete=<?= $m['id'] ?>" onclick="return confirm('Delete message?')" class="btn-admin btn-danger" style="padding:4px 8px; font-size:12px;">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>