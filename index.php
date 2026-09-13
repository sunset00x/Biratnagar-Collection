<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'cart_add') {
        $id = (int)($_GET['id'] ?? 0);
        $qty = (int)($_GET['qty'] ?? 1);

        $stmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product || $product['stock'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product is out of stock.']);
            exit;
        }

        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $current = $_SESSION['cart'][$id] ?? 0;
        $_SESSION['cart'][$id] = min($current + $qty, $product['stock']);

        echo json_encode([
            'success' => true, 
            'message' => $product['name'] . ' added to cart.',
            'cartCount' => array_sum($_SESSION['cart'])
        ]);
        exit;
    }

    if ($action === 'wishlist_toggle') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login first.']);
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $userId = $_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $id]);
        if ($stmt->fetch()) {
            $del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $del->execute([$userId, $id]);
            $msg = 'Removed from wishlist';
        } else {
            $ins = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $ins->execute([$userId, $id]);
            $msg = 'Added to wishlist ❤️';
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $countStmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => $msg,
            'wishlistCount' => $countStmt->fetchColumn()
        ]);
        exit;
    }
}

$page = $_GET['page'] ?? 'home';
$allowedPages = [
    'home', 'shop', 'product', 'cart', 'checkout', 'confirmation', 
    'wishlist', 'account', 'login', 'register', 'offers', 'new-arrivals', 
    'about', 'contact', 'categories', 'faq', 'privacy', 'terms', 'delivery'
];

if (!in_array($page, $allowedPages)) {
    $page = 'home';
}

require_once __DIR__ . '/includes/header.php';

$file = __DIR__ . '/pages/' . $page . '.php';
if (file_exists($file)) {
    require_once $file;
} else {
    echo "<div class='container section'><h2>Page under development</h2></div>";
}

require_once __DIR__ . '/includes/footer.php';
?>