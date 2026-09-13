<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$order = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$order->execute([$id]);
$o = $order->fetch();

if (!$o) die("Order not found.");

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$id]);
$itemList = $items->fetchAll();

$vat = $o['total_amount'] * 0.13; // 13% VAT
$subtotal = $o['total_amount'] - $vat;
?>
<!DOCTYPE html>
<html>
<head>
  <title>Invoice - <?= $o['order_code'] ?></title>
  <style>
    body { font-family: Arial, sans-serif; padding: 30px; color: #333; }
    .invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid #087443; padding-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #f4f6f5; }
    .totals { margin-top: 20px; text-align: right; }
  </style>
</head>

<body onload="window.print()">
  <div class="invoice-header">
    <div>
      <h1 style="color:#087443; margin:0;">Biratnagar Collection</h1>
      <p>Biratnagar, Morang, Koshi Province, Nepal</p>
    </div>
    <div style="text-align:right;">
      <h2>INVOICE</h2>
      <p><strong>Code:</strong> <?= $o['order_code'] ?></p>
      <p><strong>Date:</strong> <?= $o['created_at'] ?></p>
    </div>
  </div>

  <div style="margin-top:20px;">
    <h3>Customer Details</h3>
    <p><strong>Name:</strong> <?= htmlspecialchars($o['customer_name']) ?><br>
    <strong>Phone:</strong> <?= htmlspecialchars($o['phone']) ?><br>
    <strong>Address:</strong> <?= htmlspecialchars($o['address']) ?>, <?= htmlspecialchars($o['city']) ?></p>
  </div>

  <table>
    <thead>
      <tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>
    </thead>
    <tbody>
      <?php foreach ($itemList as $item): ?>
        <tr>
          <td><?= htmlspecialchars($item['product_name']) ?></td>
          <td><?= $item['quantity'] ?></td>
          <td><?= money($item['price']) ?></td>
          <td><?= money($item['price'] * $item['quantity']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="totals">
    <p>Subtotal (Excl. VAT): <strong><?= money($subtotal) ?></strong></p>
    <p>VAT (13%): <strong><?= money($vat) ?></strong></p>
    <p>Discount: <strong>- <?= money($o['discount_amount']) ?></strong></p>
    <h2>Total Payable: <?= money($o['total_amount']) ?></h2>
  </div>
</body>
</html>