<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $pid => $q) {
        $q = (int)$q;
        if ($q <= 0) {
            unset($_SESSION['cart'][$pid]);
        } else {
            $_SESSION['cart'][$pid] = $q;
        }
    }
    header("Location: index.php?page=cart");
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$cartItems = [];

if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id IN ($placeholders)");
    $stmt->execute($ids);
    $cartItems = $stmt->fetchAll();
}
$totals = getCartTotals();
?>

<div class="page-title">
  <div class="container">
    <h1>Shopping Cart</h1>
    <div class="breadcrumbs">Home / Cart</div>
  </div>
</div>

<div class="container cart-layout">
  <div class="cart-items">
    <?php if (empty($cartItems)): ?>
      <div class="empty">
        <div class="empty-icon">🛒</div>
        <h2>Your cart is empty</h2>
        <a href="index.php?page=shop" class="btn btn-green" style="margin-top:15px">Continue Shopping</a>
      </div>
    <?php else: ?>
      <form method="POST">
        <input type="hidden" name="update_cart" value="1">
        <?php foreach ($cartItems as $item): 
          $qty = $cart[$item['id']];
        ?>
          <div class="cart-item">
            <div class="cart-item-image">
              <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
            </div>
            <div>
              <h3><?= htmlspecialchars($item['name']) ?></h3>
              <p><?= htmlspecialchars($item['category_name']) ?></p>
              <strong style="color:#087443"><?= money($item['price']) ?></strong>
            </div>
            <div class="cart-qty">
              <input type="number" name="qty[<?= $item['id'] ?>]" value="<?= $qty ?>" min="0" style="width:50px; text-align:center;">
            </div>
            <div style="text-align:right">
              <strong><?= money($item['price'] * $qty) ?></strong>
            </div>
          </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-outline" style="margin-top: 15px;">Update Cart</button>
      </form>
    <?php endif; ?>
  </div>

  <aside class="summary">
    <h2>Order Summary</h2>
    <div class="summary-row"><span>Subtotal</span><strong><?= money($totals['actual']) ?></strong></div>
    <div class="summary-row"><span>Discount</span><strong style="color:#087443">− <?= money($totals['discount']) ?></strong></div>
    <div class="summary-row"><span>Delivery</span><strong><?= $totals['delivery'] ? money($totals['delivery']) : "FREE" ?></strong></div>
    <div class="summary-row summary-total"><span>Total</span><strong><?= money($totals['total']) ?></strong></div>
    <?php if (!empty($cartItems)): ?>
        <a href="index.php?page=checkout" class="btn btn-green" style="margin-top:15px; width:100%; text-align:center;">Proceed to Checkout →</a>
    <?php endif; ?>
  </aside>
</div>