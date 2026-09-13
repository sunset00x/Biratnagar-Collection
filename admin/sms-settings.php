<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/sms.php';

// Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sms_settings'])) {
    $keys = [
        'sms_provider' => $_POST['sms_provider'],
        'sms_api_token' => trim($_POST['sms_api_token']),
        'sms_sender_id' => trim($_POST['sms_sender_id']),
        'sms_trigger_placed' => isset($_POST['sms_trigger_placed']) ? '1' : '0',
        'sms_trigger_dispatched' => isset($_POST['sms_trigger_dispatched']) ? '1' : '0',
        'sms_trigger_delivered' => isset($_POST['sms_trigger_delivered']) ? '1' : '0',
    ];

    $uStmt = $pdo->prepare("REPLACE INTO store_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($keys as $k => $v) {
        $uStmt->execute([$k, $v]);
    }

    header("Location: sms-settings.php?saved=1");
    exit;
}

// Fetch current SMS settings
$settings = $pdo->query("SELECT setting_key, setting_value FROM store_settings WHERE setting_key LIKE 'sms_%'")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch recent SMS logs
$smsLogs = $pdo->query("SELECT * FROM sms_logs ORDER BY id DESC LIMIT 20")->fetchAll();
$totalSent = $pdo->query("SELECT COUNT(*) FROM sms_logs WHERE status = 'SENT'")->fetchColumn();
?>

<div class="top-header">
  <h1>SMS Gateway & Notifications</h1>
</div>

<?php if (isset($_GET['saved'])): ?>
  <div style="background:#eaf7f0; color:#087443; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-weight:bold;">
    ✓ SMS Settings and Notification Triggers updated successfully!
  </div>
<?php endif; ?>

<div style="display:grid; grid-template-columns: 1fr 320px; gap:25px;">
  <div>
    <!-- Settings Form -->
    <div class="card">
      <h2>SMS Provider Configuration</h2>
      <form method="POST">
        <input type="hidden" name="save_sms_settings" value="1">

        <div class="form-group">
          <label>SMS Gateway Provider</label>
          <select name="sms_provider">
            <option value="aakash" <?= ($settings['sms_provider'] ?? '') === 'aakash' ? 'selected' : '' ?>>Aakash SMS (Nepal)</option>
            <option value="sparrow" <?= ($settings['sms_provider'] ?? '') === 'sparrow' ? 'selected' : '' ?>>Sparrow SMS (Nepal)</option>
          </select>
        </div>

        <div class="form-group">
          <label>API Auth Token / Key</label>
          <input type="text" name="sms_api_token" value="<?= htmlspecialchars($settings['sms_api_token'] ?? '') ?>" placeholder="Enter API Token..." required>
        </div>

        <div class="form-group">
          <label>Sender Identity (Identity Tag / Identity Name)</label>
          <input type="text" name="sms_sender_id" value="<?= htmlspecialchars($settings['sms_sender_id'] ?? 'RamroPasal') ?>" placeholder="e.g. RamroPasal">
        </div>

        <h3 style="font-size:15px; margin:20px 0 10px; color:#17211b;">Automated Notification Triggers</h3>

        <div class="form-group">
          <label style="display:flex; align-items:center; gap:10px; font-weight:600; cursor:pointer;">
            <input type="checkbox" name="sms_trigger_placed" value="1" <?= ($settings['sms_trigger_placed'] ?? '1') === '1' ? 'checked' : '' ?>>
            Order Placed Alert ("Hi [Name], your order #[Code] of Rs. [Total] is confirmed...")
          </label>
        </div>

        <div class="form-group">
          <label style="display:flex; align-items:center; gap:10px; font-weight:600; cursor:pointer;">
            <input type="checkbox" name="sms_trigger_dispatched" value="1" <?= ($settings['sms_trigger_dispatched'] ?? '1') === '1' ? 'checked' : '' ?>>
            Out for Delivery Alert ("Hi [Name], your order #[Code] is out for delivery with rider...")
          </label>
        </div>

        <div class="form-group">
          <label style="display:flex; align-items:center; gap:10px; font-weight:600; cursor:pointer;">
            <input type="checkbox" name="sms_trigger_delivered" value="1" <?= ($settings['sms_trigger_delivered'] ?? '1') === '1' ? 'checked' : '' ?>>
            Order Delivered Alert ("Thank you for shopping with us! Order #[Code] has been delivered...")
          </label>
        </div>

        <button type="submit" class="btn-admin" style="margin-top:10px;">Save Configuration</button>
      </form>
    </div>

    <!-- Sent SMS Activity Logs -->
    <div class="card">
      <h2>Recent Sent SMS History</h2>
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Recipient</th>
            <th>Event</th>
            <th>Message Preview</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($smsLogs)): ?>
            <tr><td colspan="5">No SMS notifications sent yet.</td></tr>
          <?php else: ?>
            <?php foreach ($smsLogs as $log): ?>
              <tr>
                <td style="font-size:12px; color:#666; width:130px;"><?= $log['sent_at'] ?></td>
                <td><strong><?= htmlspecialchars($log['phone']) ?></strong></td>
                <td><span style="font-size:11px; font-weight:800; color:#555;"><?= $log['event'] ?></span></td>
                <td style="max-width:260px; font-size:12px;"><?= htmlspecialchars($log['message']) ?></td>
                <td>
                  <span class="order-status" style="background:<?= $log['status'] === 'SENT' ? '#eaf7f0' : '#fee2e2' ?>; color:<?= $log['status'] === 'SENT' ? '#087443' : '#dc2626' ?>;">
                    <?= $log['status'] ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div>
    <!-- SMS Status Card -->
    <div class="stat-box" style="margin-bottom:20px;">
      <h3>Total SMS Sent</h3>
      <p><?= number_format($totalSent) ?></p>
    </div>

    <div class="card" style="font-size:13px; color:#555; line-height:1.6;">
      <h3>💡 Setup Instructions</h3>
      <ol style="margin-left:18px; margin-top:10px;">
        <li>Register an account with <strong>Aakash SMS</strong> or <strong>Sparrow SMS</strong>.</li>
        <li>Copy your official API Key / Token into the <strong>API Auth Token</strong> input.</li>
        <li>Enable/disable automated event checkboxes as needed.</li>
      </ol>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>