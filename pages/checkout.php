<?php
// Correct relative path pointing to the root includes directory
require_once dirname(__DIR__) . '/includes/sms.php';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: index.php?page=cart');
    exit;
}

$totals = getCartTotals();
$discount = $_SESSION['applied_coupon']['discount'] ?? 0.00;

if (isset($_POST['apply_coupon'])) {
    $code = trim($_POST['coupon_code']);
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND expiry_date >= CURDATE()");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if ($coupon && $totals['actual'] >= $coupon['min_order_amount']) {
        $cDiscount = ($coupon['discount_type'] === 'percentage') 
            ? ($totals['actual'] * ($coupon['discount_value'] / 100))
            : $coupon['discount_value'];

        $_SESSION['applied_coupon'] = [
            'code' => $coupon['code'],
            'discount' => $cDiscount
        ];
        header("Location: index.php?page=checkout");
        exit;
    } else {
        $couponError = "Invalid or expired coupon code.";
    }
}

$payableTotal = max(0, $totals['total'] - $discount);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $orderCode = "BRP-" . strtoupper(substr(md5(uniqid()), 0, 8));
    $userId = $_SESSION['user_id'] ?? NULL;
    $paymentMethod = $_POST['payment'];
    $initialStatus = ($paymentMethod === 'eSewa') ? 'Pending' : 'Order Confirmed';

    $stmt = $pdo->prepare("INSERT INTO orders (order_code, user_id, customer_name, phone, email, province, district, city, address, landmark, instructions, payment_method, discount_amount, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $orderCode, $userId, $_POST['name'], $_POST['phone'], $_POST['email'],
        $_POST['province'], $_POST['district'], $_POST['city'], $_POST['address'],
        $_POST['landmark'] ?? '', $_POST['instructions'] ?? '', $paymentMethod,
        $discount, $payableTotal, $initialStatus
    ]);
    
    $orderId = $pdo->lastInsertId();

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pStmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
    $pStmt->execute($ids);
    $products = $pStmt->fetchAll();

    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
    $stockUpdate = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($products as $p) {
        $q = $cart[$p['id']];
        $itemStmt->execute([$orderId, $p['id'], $p['name'], $q, $p['price']]);
        $stockUpdate->execute([$q, $p['id']]);
        checkLowStockAlert($p['id']);
    }

    // Trigger Order Placed SMS Alert
    if (function_exists('triggerOrderSMS')) {
        triggerOrderSMS($orderId, 'placed');
    }

    // OFFICIAL ESEWA EPAY V2 FLOW
    if ($paymentMethod === 'eSewa') {
        $merchantCode = "EPAYTEST";
        $secretKey = "8gBm/:&EnhH.1/q";
        
        $amount = number_format((float)$payableTotal, 2, '.', '');
        $tax_amount = "0.00";
        $total_amount = $amount;
        $transaction_uuid = $orderCode;
        $product_code = $merchantCode;
        $product_service_charge = "0.00";
        $product_delivery_charge = "0.00";
        
        $successUrl = "http://localhost/biratnagar-collection/index.php?page=payment-callback";
        $failureUrl = "http://localhost/biratnagar-collection/index.php?page=checkout&error=payment_failed";
        
        $signedFieldNames = "total_amount,transaction_uuid,product_code";
        $signatureData = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
        $signature = base64_encode(hash_hmac('sha256', $signatureData, $secretKey, true));
        ?>
        <!DOCTYPE html>
        <html>
        <head><title>Connecting to eSewa...</title></head>
        <body onload="document.forms['officialEsewaForm'].submit();">
          <div style="text-align:center; padding:60px 20px; font-family:sans-serif;">
            <h2 style="color:#087443;">Redirecting to Official eSewa Portal...</h2>
            <p>Please wait while we establish a secure connection.</p>
          </div>
          <form id="officialEsewaForm" action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
            <input type="hidden" name="amount" value="<?= $amount ?>">
            <input type="hidden" name="tax_amount" value="<?= $tax_amount ?>">
            <input type="hidden" name="total_amount" value="<?= $total_amount ?>">
            <input type="hidden" name="transaction_uuid" value="<?= $transaction_uuid ?>">
            <input type="hidden" name="product_code" value="<?= $product_code ?>">
            <input type="hidden" name="product_service_charge" value="<?= $product_service_charge ?>">
            <input type="hidden" name="product_delivery_charge" value="<?= $product_delivery_charge ?>">
            <input type="hidden" name="success_url" value="<?= $successUrl ?>">
            <input type="hidden" name="failure_url" value="<?= $failureUrl ?>">
            <input type="hidden" name="signed_field_names" value="<?= $signedFieldNames ?>">
            <input type="hidden" name="signature" value="<?= $signature ?>">
          </form>
        </body>
        </html>
        <?php
        exit;
    }

    // CASH ON DELIVERY FLOW
    $_SESSION['cart'] = [];
    unset($_SESSION['applied_coupon']);
    $_SESSION['last_order_id'] = $orderId;
    header("Location: index.php?page=confirmation");
    exit;
}
?>

<div class="page-title">
  <div class="container">
    <h1>Checkout</h1>
    <div class="breadcrumbs">Home / Cart / Checkout</div>
  </div>
</div>

<div class="container checkout-layout">
  <form method="POST">
    <input type="hidden" name="place_order" value="1">
    
    <?php if (isset($_GET['error']) && $_GET['error'] === 'payment_failed'): ?>
      <div style="background:#fee2e2; color:#dc2626; padding:12px; border-radius:8px; margin-bottom:15px; font-weight:bold;">
        ⚠ eSewa payment failed or was cancelled. Please try again or select Cash on Delivery.
      </div>
    <?php endif; ?>

    <div class="form-card">
      <h2>Customer Information</h2>
      <div class="form-grid">
        <div class="field"><label>Full Name *</label><input name="name" required placeholder="Your full name" /></div>
        <div class="field"><label>Phone Number *</label><input name="phone" required placeholder="98XXXXXXXX" /></div>
        <div class="field"><label>Email *</label><input type="email" name="email" required placeholder="you@example.com" /></div>
        <div class="field">
          <label>Province *</label>
          <select name="province" required>
            <option>Koshi Province</option>
            <option>Bagmati Province</option>
            <option>Madhesh Province</option>
          </select>
        </div>
        <div class="field"><label>District *</label><input name="district" required value="Morang" /></div>
        <div class="field"><label>City *</label><input name="city" required value="Biratnagar" /></div>
        <div class="field full"><label>Full Address *</label><textarea name="address" required placeholder="Street address..."></textarea></div>
      </div>
    </div>

    <div class="form-card">
      <h2>Payment Method</h2>
      <div class="payment-options">
        <label class="payment-option">
          <input type="radio" name="payment" value="Cash on Delivery" checked />
          <strong>Cash on Delivery</strong>
        </label>
        <label class="payment-option">
          <input type="radio" name="payment" value="eSewa" />
          <strong>eSewa (Online Payment)</strong>
        </label>
      </div>
    </div>

    <button class="btn btn-green" style="width:100%;font-size:15px" type="submit">Place Order</button>
  </form>

  <aside class="summary">
    <h2>Order Summary</h2>
    <form method="POST" style="margin-bottom:15px; display:flex; gap:5px;">
      <input type="text" name="coupon_code" placeholder="Coupon Code" required style="padding:8px; border:1px solid #ccc; border-radius:4px; flex:1;">
      <button type="submit" name="apply_coupon" class="btn btn-green" style="padding:8px 12px;">Apply</button>
    </form>
    <?php if (isset($couponError)): ?><p style="color:red; font-size:12px; margin-bottom:10px;"><?= $couponError ?></p><?php endif; ?>

    <div class="summary-row"><span>Subtotal</span><strong><?= money($totals['actual']) ?></strong></div>
    <div class="summary-row"><span>Delivery</span><strong><?= $totals['delivery'] ? money($totals['delivery']) : "FREE" ?></strong></div>
    
    <?php if (isset($_SESSION['applied_coupon'])): ?>
      <div class="summary-row"><span>Coupon Discount (<?= $_SESSION['applied_coupon']['code'] ?>)</span><strong style="color:#087443;">- <?= money($discount) ?></strong></div>
    <?php endif; ?>

    <div class="summary-row summary-total"><span>Total Payable</span><strong><?= money($payableTotal) ?></strong></div>
  </aside>
</div>