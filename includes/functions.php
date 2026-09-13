<?php
require_once __DIR__ . '/db.php';

/**
 * Format currency in Nepalese Rupees (NPR)
 */
function money($amount) {
    return "Rs. " . number_format((float)$amount, 0);
}

/**
 * Calculate percentage discount between current and old price
 */
function discountOf($price, $oldPrice) {
    if (!$oldPrice || $oldPrice <= $price) return 0;
    return round((1 - ($price / $oldPrice)) * 100);
}

/**
 * Render standard product card component HTML
 */
function renderProductCard($p) {
    $discount = discountOf($p['price'], $p['old_price']);
    $isWishlisted = false;
    
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $p['id']]);
        $isWishlisted = (bool)$stmt->fetch();
    }
    
    $img = !empty($p['image']) ? $p['image'] : 'https://picsum.photos/seed/'.$p['id'].'/700/700';
    $outOfStock = ($p['stock'] <= 0);

    ob_start();
    ?>
    <article class="product-card">
      <div class="product-image">
        <?php if ($discount > 0): ?>
            <span class="discount">-<?= $discount ?>%</span>
        <?php endif; ?>
        <button class="wish <?= $isWishlisted ? 'active' : '' ?>" 
                onclick="toggleWishlist(<?= $p['id'] ?>)" 
                aria-label="Wishlist"><?= $isWishlisted ? '♥' : '♡' ?></button>
        <img loading="lazy" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
             onerror="this.src='https://picsum.photos/seed/<?= $p['id'] ?>/700/700'">
      </div>

      <div class="product-body">
        <div class="product-category"><?= htmlspecialchars($p['category_name'] ?? 'General') ?></div>

        <a href="index.php?page=product&id=<?= $p['id'] ?>" class="product-name">
          <?= htmlspecialchars($p['name']) ?>
        </a>

        <div class="rating">
          ★ <?= number_format($p['rating'], 1) ?>
          <span>(<?= $p['reviews'] ?>)</span>
        </div>

        <div class="price-row">
          <span class="price"><?= money($p['price']) ?></span>
          <?php if (!empty($p['old_price'])): ?>
            <span class="old-price"><?= money($p['old_price']) ?></span>
          <?php endif; ?>
        </div>

        <div class="stock <?= $outOfStock ? 'out' : '' ?>">
          <?= $outOfStock ? 'Out of stock' : '✓ In stock ('.$p['stock'].')' ?>
        </div>

        <div class="card-buttons">
          <button class="small-btn add-btn" 
                  onclick="addToCart(<?= $p['id'] ?>)" 
                  <?= $outOfStock ? 'disabled' : '' ?>>
            🛒 Add to Cart
          </button>

          <button class="small-btn buy-btn" 
                  onclick="buyNow(<?= $p['id'] ?>)" 
                  <?= $outOfStock ? 'disabled' : '' ?>>
            Buy Now
          </button>
        </div>
      </div>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Calculate cart subtotal, discount, delivery charges, and final payable total
 */
function getCartTotals() {
    global $pdo;
    $cart = $_SESSION['cart'] ?? [];
    $actual = 0;
    $subtotal = 0;

    if (!empty($cart)) {
        $ids = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, price, old_price FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $products = $stmt->fetchAll();

        foreach ($products as $p) {
            $qty = $cart[$p['id']];
            $actual += $p['price'] * $qty;
            $subtotal += ($p['old_price'] ?: $p['price']) * $qty;
        }
    }

    $discount = $subtotal - $actual;
    $delivery = ($actual === 0 || $actual >= 2000) ? 0 : 100;
    $total = $actual + $delivery;

    return compact('subtotal', 'actual', 'discount', 'delivery', 'total');
}

/**
 * Trigger inventory warning alert email to admin when stock falls below threshold
 */
function checkLowStockAlert($productId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name, stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $p = $stmt->fetch();

    if ($p && $p['stock'] <= 5) {
        $to = "admin@biratnagar.com";
        $subject = "LOW STOCK ALERT: " . $p['name'];
        $message = "Product '{$p['name']}' has dropped to low stock level: {$p['stock']} remaining units.";
        $headers = "From: store@biratnagarramropasal.com";
        @mail($to, $subject, $message, $headers);
    }
}

/**
 * Send automated HTML receipt email to customer upon order placement
 */
function sendOrderEmailReceipt($orderId) {
    global $pdo;
    
    // Fetch Order Details
    $oStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $oStmt->execute([$orderId]);
    $order = $oStmt->fetch();

    if (!$order || empty($order['email'])) return false;

    // Fetch Order Items
    $iStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $iStmt->execute([$orderId]);
    $items = $iStmt->fetchAll();

    $to = $order['email'];
    $subject = "Order Receipt - " . $order['order_code'] . " | Biratnagar Ramro Pasal";

    $itemRows = "";
    foreach ($items as $item) {
        $itemRows .= "<tr>
            <td style='padding:8px; border-bottom:1px solid #eee;'>{$item['product_name']}</td>
            <td style='padding:8px; border-bottom:1px solid #eee;'>x{$item['quantity']}</td>
            <td style='padding:8px; border-bottom:1px solid #eee;'>Rs. " . number_format($item['price'] * $item['quantity']) . "</td>
        </tr>";
    }

    $message = "
    <html>
    <head>
      <title>Order Receipt</title>
    </head>
    <body style='font-family:Arial, sans-serif; background:#f5f7f6; padding:20px;'>
      <div style='max-width:600px; background:#fff; margin:auto; padding:25px; border-radius:12px; border:1px solid #e4e9e6;'>
        <h2 style='color:#087443; margin-top:0;'>Thank you for your order!</h2>
        <p>Hi <strong>{$order['customer_name']}</strong>, your order <strong>#{$order['order_code']}</strong> has been received and confirmed.</p>
        
        <table style='width:100%; border-collapse:collapse; margin:20px 0;'>
          <thead>
            <tr style='background:#f4f6f5; text-align:left;'>
              <th style='padding:8px;'>Product</th>
              <th style='padding:8px;'>Qty</th>
              <th style='padding:8px;'>Total</th>
            </tr>
          </thead>
          <tbody>
            {$itemRows}
          </tbody>
        </table>

        <p style='text-align:right; font-size:16px;'><strong>Total Paid: Rs. " . number_format($order['total_amount']) . "</strong></p>
        <hr style='border:0; border-top:1px solid #eee; margin:20px 0;'>
        <p style='font-size:12px; color:#888; text-align:center;'>Biratnagar Ramro Pasal • Morang, Nepal</p>
      </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@biratnagarramropasal.com" . "\r\n";

    return @mail($to, $subject, $message, $headers);
}
?>