<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionId = session_id();$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');
$imageBase64 =$input['image'] ?? null;

if (empty($userMessage) && empty($imageBase64)) {
    echo json_encode(['reply' => 'Namaste! I am your Ramro Pasal assistant. How can I help you today?', 'type' => 'text']);
    exit;
}

// Log user query to chat history
$stmt =$pdo->prepare("INSERT INTO ai_chat_sessions (session_id, role, message) VALUES (?, 'user', ?)");
$stmt->execute([$sessionId,$userMessage]);

$lowerMsg = strtolower($userMessage);

// =============================================================
// INTENT 1: ADD PRODUCT TO CART DIRECTLY
// =============================================================
if (preg_match('/(?:add|put|buy)\s+(?:(\d+)\s+)?(?:pack|kg|bags?|items?|pcs?)?\s*(?:of\s+)?([a-z0-9\s]+)\s+(?:to\s+cart|in\s+cart)?/i', $lowerMsg, $matches)) {$qty = !empty($matches[1]) ? (int)$matches[1] : 1;
    $productQuery = trim($matches[2]);

    $stmt =$pdo->prepare("SELECT id, name, price, stock FROM products WHERE name LIKE ? AND stock > 0 LIMIT 1");
    $stmt->execute(["\%{$productQuery}%"]);
    $product =$stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Add item into session cart
        if (!isset($_SESSION['cart'])) {$_SESSION['cart'] = [];
        }
        $pid = $product['id'];$_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) +$qty;

        // Log into database cart table
        $cartStmt =$pdo->prepare("INSERT INTO ai_cart_sessions (session_id, product_id, quantity) VALUES (?, ?, ?)");
        $cartStmt->execute([$sessionId, $pid,$qty]);

        $reply = "🛒 **Added to Cart!**\n" .
                 "• **Item:** {$product['name']}\n" .
                 "• **Quantity:** {$qty}\n" .
                 "• **Unit Price:** Rs. " . number_format($product['price']) . "\n\n" .
                 "Would you like to proceed to checkout or search for anything else?";

        saveAiReply($sessionId,$reply);
        echo json_encode(['reply' => $reply, 'type' => 'cart_action', 'product' =>$product, 'action' => 'added']);
        exit;
    }
}

// =============================================================
// INTENT 2: APPLY PROMO CODE & COUPON VALIDATION
// =============================================================
if (preg_match('/(?:apply|use|coupon|code|promo)\s+([a-z0-9]+)/i', $lowerMsg,$matches)) {
    $couponCode = strtoupper($matches[1]);
    
    $stmt =$pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'Active'");
    $stmt->execute([$couponCode]);
    $coupon =$stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        $_SESSION['applied_coupon'] =$coupon['code'];
        $_SESSION['discount_percent'] =$coupon['discount_percent'];

        $reply = "🎉 **Coupon Applied Successfully!**\n" .
                 "• **Code:** `{$coupon['code']}`\n" .
                 "• **Discount:** {$coupon['discount_percent']}% Off\n" .
                 "Your savings will automatically reflect at checkout!";

        saveAiReply($sessionId,$reply);
        echo json_encode(['reply' => $reply, 'type' => 'coupon', 'applied' => true]);
        exit;
    } else {
        $reply = "❌ Sorry, the coupon code **'{$couponCode}'** is invalid or expired. Try using **RAMRO10** for 10% off!";
        saveAiReply($sessionId,$reply);
        echo json_encode(['reply' => $reply, 'type' => 'coupon', 'applied' => false]);
        exit;
    }
}

// =============================================================
// INTENT 3: ORDER TRACKING WITH RIDER INFO
// =============================================================
if (preg_match('/BRP-[A-Z0-9]{8}/i', $userMessage,$matches)) {
    $orderCode = strtoupper($matches[0]);
    $stmt =$pdo->prepare("SELECT o.*, r.name as rider_name, r.phone as rider_phone FROM orders o LEFT JOIN riders r ON o.rider_id = r.id WHERE o.order_code = ?");
    $stmt->execute([$orderCode]);
    $order =$stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $itemStmt =$pdo->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $items =$itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $itemList = "";
        foreach ($items as $it) {$itemList .= "• {$it['product_name']} (x{$it['quantity']}) - Rs. " . number_format($it['price'] *$it['quantity']) . "\n";
        }

        $riderDetails = "• **Assigned Rider:** Unassigned (Pending Dispatch)";
        if (!empty($order['rider_name'])) {$riderDetails = "• **Assigned Rider:** {$order['rider_name']} (📞 {$order['rider_phone']})";
        }

        $reply = "📦 **Order Receipt #{$order['order_code']}**\n\n" .
                 "• **Status:** {$order['status']}\n" .
                 "• **Payment:** {$order['payment_method']}\n" .
                 "• **Total:** Rs. " . number_format($order['total_amount']) . "\n" .
                 $riderDetails . "\n\n" .
                 "**Items Ordered:**\n" . $itemList;

        saveAiReply($sessionId,$reply);
        echo json_encode(['reply' => $reply, 'type' => 'order_status', 'order' =>$order]);
        exit;
    }
}

// =============================================================
// INTENT 4: MULTILINGUAL & NEPLISH CATALOG SEARCH (RAG)
// =============================================================
$synonyms = [
    'chiya' => 'tea', 'tarkari' => 'vegetable', 'tel' => 'oil',
    'patti' => 'tea', 'masala' => 'spice', 'chamal' => 'rice', 
    'gini' => 'sugar', 'chini' => 'sugar', 'doodh' => 'milk'
];

$words = explode(' ', $lowerMsg);$translatedWords = [];
foreach ($words as$w) {
    $translatedWords[] =$synonyms[$w] ?? $w;
}
$cleanQuery = implode(' ',$translatedWords);
$cleanQuery = preg_replace('/(show\vert{}me\vert{}buy\vert{}search\vert{}items\vert{}products\vert{}chahiyo\vert{}cha\vert{}add\vert{}want\vert{}need)/i', '',$cleanQuery);
$cleanQuery = trim($cleanQuery);

if (!empty($cleanQuery)) {
    $stmt =$pdo->prepare("SELECT id, name, price, old_price, image, stock FROM products WHERE (name LIKE ? OR description LIKE ?) AND stock > 0 LIMIT 4");
    $searchTerm = "\%{$cleanQuery}%";
    $stmt->execute([$searchTerm,$searchTerm]);
    $products =$stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($products)) {
        $reply = "Found " . count($products) . " items matching **'" . ucfirst($cleanQuery) . "'** in our store:";
        saveAiReply($sessionId,$reply);

        echo json_encode([
            'reply' => $reply,
            'type' => 'product_list',
            'products' => $products
        ]);
        exit;
    }
}

// =============================================================
// FALLBACK CONVERSATIONAL ASSISTANT
// =============================================================
$fallback = "Namaste! I am your AI Sales Assistant at Biratnagar Ramro Pasal.\n\n" .
            "• **Add to Cart:** Say `Add 2 bags of Basmati Rice to cart`\n" .
            "• **Apply Promo:** Say `Apply RAMRO10`\n" .
            "• **Search Items:** Say `Chiya patti` or `Pure Ghee`\n" .
            "• **Track Delivery:** Type your order ID (e.g. `BRP-A1B2C3D4`)";

saveAiReply($sessionId,$fallback);
echo json_encode(['reply' => $fallback, 'type' => 'fallback']);
exit;

function saveAiReply($sid,$msg) {
    global $pdo;
    $stmt =$pdo->prepare("INSERT INTO ai_chat_sessions (session_id, role, message) VALUES (?, 'model', ?)");
    $stmt->execute([$sid,$msg]);
}
?>