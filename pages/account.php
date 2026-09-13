<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='index.php?page=login';</script>";
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user_id'], $_SESSION['user_name']);
    header("Location: index.php?page=login");
    exit;
}

// Fetch user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$userOrders = $stmt->fetchAll();

// Pre-fetch order items for each order
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");

$stages = ['Pending', 'Order Confirmed', 'Shipped', 'Out for Delivery', 'Delivered'];
function getStageIndex($currentStatus) {
    global $stages;
    $idx = array_search($currentStatus, $stages);
    return $idx === false ? 0 : $idx;
}
?>

<div class="page-title">
  <div class="container">
    <h1>My Account</h1>
  </div>
</div>

<div class="container account-layout">
  <aside class="account-nav">
    <a href="index.php?page=account" class="active">Dashboard</a>
    <a href="index.php?page=account&action=logout">Logout</a>
  </aside>

  <section class="account-content">
    <div class="account-card">
      <h2>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?> 👋</h2>
      <h3 style="margin-top: 20px;">Order History & Tracking</h3>
      
      <?php if (empty($userOrders)): ?>
        <p style="margin-top:10px; color:#68746d;">No orders placed yet.</p>
      <?php else: ?>
        <?php foreach ($userOrders as $o): 
          $currentIdx = getStageIndex($o['status']);
          $itemStmt->execute([$o['id']]);
          $items = $itemStmt->fetchAll();

          // Calculate items subtotal to derive delivery fee
          $itemsSubtotal = 0;
          foreach ($items as $item) {
              $itemsSubtotal += ($item['price'] * $item['quantity']);
          }
          $discount = $o['discount_amount'] ?? 0;
          $deliveryFee = max(0, $o['total_amount'] - ($itemsSubtotal - $discount));
        ?>
          <div class="order" style="padding:20px 0; border-bottom:1px solid #e4e9e6;">
            <div class="order-top">
              <strong>Order Code: <?= $o['order_code'] ?></strong>
              <span class="order-status"><?= $o['status'] ?></span>
            </div>

            <!-- Tracking Progress Bar -->
            <div style="display:flex; justify-content:space-between; margin:20px 0; position:relative;">
              <?php foreach ($stages as $i => $stage): 
                $completed = $i <= $currentIdx;
              ?>
                <div style="text-align:center; flex:1; position:relative; z-index:2;">
                  <div style="width:24px; height:24px; border-radius:50%; background:<?= $completed ? '#087443' : '#ccc' ?>; color:white; margin:auto; display:grid; place-items:center; font-size:12px; font-weight:bold;">
                    <?= $completed ? '✓' : ($i + 1) ?>
                  </div>
                  <span style="font-size:11px; display:block; margin-top:5px; color:<?= $completed ? '#087443' : '#888' ?>; font-weight:bold;"><?= $stage ?></span>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Purchased Items & Cost Breakdown -->
            <div style="background:#f9fbf9; border:1px solid #e8ece9; border-radius:8px; padding:12px 15px; margin-bottom:15px;">
              <strong style="font-size:13px; color:#333; display:block; margin-bottom:8px;">Purchased Items:</strong>
              <ul style="list-style:none; padding:0; margin:0 0 10px 0;">
                <?php foreach ($items as $item): ?>
                  <li style="display:flex; justify-content:space-between; font-size:13px; color:#555; padding:4px 0; border-bottom:1px dashed #eee;">
                    <span><?= htmlspecialchars($item['product_name']) ?> <strong style="color:#087443;">× <?= $item['quantity'] ?></strong></span>
                    <span><?= money($item['price'] * $item['quantity']) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>

              <!-- Summary Breakdown -->
              <div style="border-top:1px solid #e0e0e0; padding-top:8px; font-size:12px; color:#666;">
                <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                  <span>Items Subtotal:</span>
                  <strong><?= money($itemsSubtotal) ?></strong>
                </div>
                <?php if ($discount > 0): ?>
                  <div style="display:flex; justify-content:space-between; margin-bottom:3px; color:#087443;">
                    <span>Discount Coupon:</span>
                    <strong>- <?= money($discount) ?></strong>
                  </div>
                <?php endif; ?>
                <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
                  <span>Delivery Charge:</span>
                  <strong><?= $deliveryFee > 0 ? money($deliveryFee) : "FREE" ?></strong>
                </div>
              </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center;">
              <span style="font-size:13px; color:#68746d;">Placed on: <?= $o['created_at'] ?></span>
              <div>
                <span style="font-size:12px; color:#666;">Total Amount: </span>
                <strong style="color:#087443; font-size:16px;"><?= money($o['total_amount']) ?></strong>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>
</div>