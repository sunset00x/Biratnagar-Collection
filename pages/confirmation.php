<?php
$orderId = $_SESSION['last_order_id'] ?? 0;
if (!$orderId) {
    echo "<script>window.location.href='index.php?page=home';</script>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();
?>

<div class="section">
  <div class="container">
    <div class="content-card" style="max-width:850px;text-align:center">
      <div style="font-size:65px">🎉</div>
      <h1 style="color:#087443;margin-top:5px">Order Successfully Placed!</h1>
      <p>Thank you for shopping with Biratnagar Collection.</p>

      <div style="background:#f2f8f4;border-radius:12px;padding:20px;text-align:left;margin-top:25px">
        <div class="summary-row"><span>Order Code</span><strong><?= $order['order_code'] ?></strong></div>
        <div class="summary-row"><span>Total Paid</span><strong><?= money($order['total_amount']) ?></strong></div>
        <div class="summary-row"><span>Payment</span><strong><?= $order['payment_method'] ?></strong></div>
      </div>

      <div style="text-align:left;margin-top:25px">
        <h2>Ordered Items</h2>
        <?php foreach ($items as $i): ?>
          <div class="summary-row">
            <span><?= htmlspecialchars($i['product_name']) ?> × <?= $i['quantity'] ?></span>
            <strong><?= money($i['price'] * $i['quantity']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>

      <a href="index.php?page=shop" class="btn btn-green" style="margin-top:20px;">Continue Shopping</a>
    </div>
  </div>
</div>