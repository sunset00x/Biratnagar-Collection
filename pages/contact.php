<?php
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $message]);
        $successMsg = "Thank you! Your message has been sent successfully.";
    } else {
        $errorMsg = "Please fill in all required fields.";
    }
}

// Fetch dynamic contact info from database
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM store_settings");
$settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$phone = $settings['contact_phone'] ?? '+977 9812345678';
$email = $settings['contact_email'] ?? 'support@biratnagarramropasal.com';
$address = $settings['contact_address'] ?? 'Biratnagar, Morang, Nepal';
?>

<div class="container info-page-wrap" style="padding-top: 50px;">
  <div class="info-card" style="max-width: 960px; padding: 45px 50px;">
    
    <h1 style="font-size: 34px; font-weight: 900; color: #17211b; margin-bottom: 30px; letter-spacing: -0.5px;">We're Here to Help</h1>

    <!-- 3 Light-Green Info Cards -->
    <div class="contact-grid">
      <div class="contact-box">
        <div class="contact-box-title">
          <span style="color:#b91c1c; font-size: 16px;">📞</span>
          <span>Phone</span>
        </div>
        <p><?= htmlspecialchars($phone) ?></p>
      </div>

      <div class="contact-box">
        <div class="contact-box-title">
          <span style="color:#1e293b; font-size: 16px;">✉️</span>
          <span>Email</span>
        </div>
        <p><?= htmlspecialchars($email) ?></p>
      </div>

      <div class="contact-box">
        <div class="contact-box-title">
          <span style="color:#b91c1c; font-size: 16px;">📍</span>
          <span>Store</span>
        </div>
        <p><?= htmlspecialchars($address) ?></p>
      </div>
    </div>

    <!-- Alert Notifications -->
    <?php if (!empty($successMsg)): ?>
      <div style="background:#eaf7f0; color:#087443; padding:14px; border-radius:10px; margin-bottom:25px; font-weight:700;">
        <?= $successMsg ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
      <div style="background:#fee2e2; color:#dc2626; padding:14px; border-radius:10px; margin-bottom:25px; font-weight:700;">
        <?= $errorMsg ?>
      </div>
    <?php endif; ?>

    <!-- Contact Form -->
    <form method="POST" class="contact-form">
      <input type="hidden" name="send_contact" value="1">
      
      <div class="form-grid" style="gap: 20px;">
        <div class="field">
          <label style="font-weight: 800; font-size: 13px; margin-bottom: 8px;">Name *</label>
          <input type="text" name="name" required class="contact-input" />
        </div>
        <div class="field">
          <label style="font-weight: 800; font-size: 13px; margin-bottom: 8px;">Email *</label>
          <input type="email" name="email" required class="contact-input" />
        </div>
        <div class="field full">
          <label style="font-weight: 800; font-size: 13px; margin-bottom: 8px;">Message *</label>
          <textarea name="message" required class="contact-input" style="min-height: 160px; resize: vertical;"></textarea>
        </div>
      </div>

      <div style="margin-top: 10px;">
        <button type="submit" class="btn-contact-submit">Send Message</button>
      </div>
    </form>

  </div>
</div>