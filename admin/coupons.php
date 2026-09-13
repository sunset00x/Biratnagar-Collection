<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

// Handle New Coupon Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['discount_type'];
    $value = (float)$_POST['discount_value'];
    $minAmount = (float)$_POST['min_order_amount'];
    $expiry = $_POST['expiry_date'];

    $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, expiry_date, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$code, $type, $value, $minAmount, $expiry]);
    header("Location: coupons.php?success=1");
    exit;
}

// Handle Status Toggle (Active / Inactive)
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $newStatus = $_GET['toggle'] === 'active' ? 'active' : 'inactive';
    $stmt = $pdo->prepare("UPDATE coupons SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, (int)$_GET['id']]);
    header("Location: coupons.php");
    exit;
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
?>

<!-- Add New Coupon Form -->
<div class="card" style="margin-bottom: 25px;">
  <h2>Create New Discount Coupon</h2>
  <form method="POST">
    <input type="hidden" name="create_coupon" value="1">
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px;">
      <div class="form-group">
        <label>Coupon Code</label>
        <input type="text" name="code" placeholder="e.g. DASHAIN20" required style="text-transform:uppercase;">
      </div>
      <div class="form-group">
        <label>Discount Type</label>
        <select name="discount_type" required>
          <option value="percentage">Percentage (%)</option>
          <option value="fixed">Fixed Amount (NPR)</option>
        </select>
      </div>
      <div class="form-group">
        <label>Discount Value</label>
        <input type="number" step="0.01" name="discount_value" required placeholder="10 or 200">
      </div>
      <div class="form-group">
        <label>Min Order Amount (NPR)</label>
        <input type="number" step="0.01" name="min_order_amount" value="500.00" required>
      </div>
      <div class="form-group">
        <label>Expiration Date</label>
        <input type="date" name="expiry_date" required>
      </div>
    </div>
    <button type="submit" class="btn-admin">Add Coupon</button>
  </form>
</div>

<!-- Coupons List Table -->
<div class="card">
  <h2>Active Store Coupons</h2>
  <table>
    <thead>
      <tr>
        <th>Code</th>
        <th>Type</th>
        <th>Value</th>
        <th>Min Order</th>
        <th>Expiry Date</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($coupons)): ?>
        <tr><td colspan="7">No coupons created yet.</td></tr>
      <?php else: ?>
        <?php foreach ($coupons as $c): ?>
          <tr>
            <td><strong style="color:#087443;"><?= htmlspecialchars($c['code']) ?></strong></td>
            <td><?= ucfirst($c['discount_type']) ?></td>
            <td><?= $c['discount_type'] === 'percentage' ? $c['discount_value'].'%' : money($c['discount_value']) ?></td>
            <td><?= money($c['min_order_amount']) ?></td>
            <td><?= $c['expiry_date'] ?></td>
            <td>
              <span class="order-status" style="background:<?= $c['status'] === 'active' ? '#eaf7f0' : '#fee2e2' ?>; color:<?= $c['status'] === 'active' ? '#087443' : '#dc2626' ?>;">
                <?= ucfirst($c['status']) ?>
              </span>
            </td>
            <td>
              <?php if ($c['status'] === 'active'): ?>
                <a href="coupons.php?toggle=inactive&id=<?= $c['id'] ?>" class="btn-admin btn-danger" style="padding:4px 8px; font-size:12px;">Deactivate</a>
              <?php else: ?>
                <a href="coupons.php?toggle=active&id=<?= $c['id'] ?>" class="btn-admin" style="padding:4px 8px; font-size:12px;">Activate</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>