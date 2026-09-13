<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/sms.php';

// Handle Order Status Updates & SMS Triggers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];

    $uStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $uStmt->execute([$newStatus, $orderId]);

    // Send SMS Notification Triggers based on status change
    if ($newStatus === 'Shipped' || $newStatus === 'Out for Delivery') {
        triggerOrderSMS($orderId, 'dispatched', [
            'rider_name' => $_POST['rider_name'] ?? 'Rider',
            'rider_phone' => $_POST['rider_phone'] ?? '9800000000'
        ]);
    } elseif ($newStatus === 'Delivered') {
        triggerOrderSMS($orderId, 'delivered');
    }

    header("Location: orders.php?updated=1");
    exit;
}

// Fetch all orders
$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
?>

<div class="top-header">
  <h1>Order Management</h1>
  <a href="export-orders.php" class="btn-admin" style="background:#1d4ed8;">📥 Export Orders to CSV</a>
</div>

<?php if (isset($_GET['updated'])): ?>
  <div style="background:#eaf7f0; color:#087443; padding:12px; border-radius:8px; margin-bottom:20px; font-weight:bold;">
    ✓ Order status updated and SMS notification sent successfully!
  </div>
<?php endif; ?>

<div class="card">
  <h2>Customer Orders</h2>
  <table>
    <thead>
      <tr>
        <th>Order Code</th>
        <th>Customer</th>
        <th>Phone</th>
        <th>Payment</th>
        <th>Total Amount</th>
        <th>Status</th>
        <th>Date</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="8">No orders found.</td></tr>
      <?php else: ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><strong style="color:#087443;"><?= htmlspecialchars($o['order_code']) ?></strong></td>
            <td><?= htmlspecialchars($o['customer_name']) ?></td>
            <td><?= htmlspecialchars($o['phone']) ?></td>
            <td><?= htmlspecialchars($o['payment_method']) ?></td>
            <td><strong>Rs. <?= number_format($o['total_amount']) ?></strong></td>
            <td>
              <span class="order-status"><?= htmlspecialchars($o['status']) ?></span>
            </td>
            <td style="font-size:12px; color:#666;"><?= $o['created_at'] ?></td>
            <td>
              <form method="POST" style="display:inline-flex; gap:5px; align-items:center;">
                <input type="hidden" name="update_status" value="1">
                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                
                <select name="status" style="padding:4px 8px; font-size:12px; border-radius:4px; border:1px solid #ccc;">
                  <option value="Pending" <?= $o['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Order Confirmed" <?= $o['status'] === 'Order Confirmed' ? 'selected' : '' ?>>Order Confirmed</option>
                  <option value="Shipped" <?= $o['status'] === 'Shipped' ? 'selected' : '' ?>>Out for Delivery</option>
                  <option value="Delivered" <?= $o['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                  <option value="Cancelled" <?= $o['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>

                <button type="submit" class="btn-admin" style="padding:4px 8px; font-size:12px;">Save</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>