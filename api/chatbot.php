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
    echo json_encode(['reply' => 'Namaste! How can I help you today?', 'type' => 'text']);
    exit;
}

// Log user message
$stmt =$pdo->prepare("INSERT INTO ai_chat_sessions (session_id, role, message) VALUES (?, 'user', ?)");
$stmt->execute([$sessionId,$userMessage]);

$lowerMsg = strtolower($userMessage);

// -------------------------------------------------------------
// 1. ORDER TRACKING INTENT
// -------------------------------------------------------------
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

        $riderDetails = !empty($order['rider_name']) 
            ? "• **Rider:** {$order['rider_name']} (📞 {$order['rider_phone']})" 
            : "• **Rider:** Unassigned";

        $reply = "📦 **Order Status for #{$order['order_code']}**\n\n" .
                 "• **Status:** {$order['status']}\n" .
                 "• **Total:** Rs. " . number_format($order['total_amount']) . "\n" .
                 $riderDetails . "\n\n" .
                 "**Items:**\n" . $itemList;

        saveAiReply($sessionId,$reply);
        echo json_encode(['reply' => $reply, 'type' => 'order_status', 'order' =>$order]);
        exit;
    }
}

// -------------------------------------------------------------
// 2. ADD TO CART INTENT
// -------------------------------------------------------------
if (preg_match('/(?:add|put|buy)\s+(?:(\d+)\s+)?(?:pack|kg|bags?|pcs?)?\s*(?:of\s+)?([a-z0-9\s]+)\s+(?:to\s+cart|in\s+cart)?/i', $lowerMsg, $matches)) {$qty = !empty($matches[1]) ? (int)$matches[1] : 1;
    $productQuery = trim($matches[2]);

    $stmt =$pdo->prepare("SELECT id, name, price, stock FROM products WHERE name LIKE ? AND stock > 0 LIMIT 1");
    $stmt->execute(["\%{$productQuery}%"]);
    $product =$stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];$pid = $product['id'];$_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) +$qty;

        $reply = "🛒 **Added to Cart!**\n• **Item:** {$product['name']}\n• **Qty:** {$qty}\n• **Price:** Rs. " . number_format($product['price']);
        saveAiReply($sessionId,$reply);
        echo json_encode(['reply' => $reply, 'type' => 'cart_action', 'product' =>$product, 'action' => 'added']);
        exit;
    }
}

// -------------------------------------------------------------
// 3. COUPON DISCOUNTS INTENT
// -------------------------------------------------------------
if (preg_match('/(?:apply|use|coupon|code|promo)\s+([a-z0-9]+)/i', $lowerMsg,$matches)) {
    $couponCode = strtoupper($matches[1]);
    $stmt =$pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'Active'");
    $stmt->execute([$couponCode]);
    $coupon =$stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {$_SESSION['applied_coupon'] = $coupon['code'];$reply = "🎉 **Coupon Applied!** Code `{$coupon['code']}` gives you {$coupon['discount_percent']}% Off!";
        saveAiReply($sessionId,$reply);
        echo json_encode(['reply' => $reply, 'type' => 'coupon', 'applied' => true]);
        exit;
    } else {
        $reply = "❌ Coupon **'{$couponCode}'** is invalid. Try using **RAMRO10**!";
        saveAiReply($sessionId,$reply);
        echo json_encode(['reply' => $reply, 'type' => 'coupon', 'applied' => false]);
        exit;
    }
}

// -------------------------------------------------------------
// 4. ACCURATE PRODUCT CATALOG SEARCH (ENGLISH & NEPLISH)
// -------------------------------------------------------------
$synonyms = [
    'chiya' => 'tea', 'tarkari' => 'vegetable', 'tel' => 'oil',
    'patti' => 'tea', 'masala' => 'spice', 'chamal' => 'rice', 
    'gini' => 'sugar', 'chini' => 'sugar', 'doodh' => 'milk'
];

// Clean intent filler verbs
$cleanSearch = preg_replace('/^(show\vert{}me\vert{}buy\vert{}search\vert{}get\vert{}i\vert{}want\vert{}need\vert{}chahiyo\vert{}cha\vert{}please)\s+/i', '',$lowerMsg);
$cleanSearch = trim($cleanSearch);

// Build search term list (including translated synonyms)
$searchWords = explode(' ', $cleanSearch);$translatedWords = [];
foreach ($searchWords as$w) {
    if (isset($synonyms[$w])) {$translatedWords[] = $synonyms[$w];
    }
    $translatedWords[] =$w;
}

$rawTerm = "\%{$cleanSearch}%";
$translatedTerm = "\%" . implode('\%', array_unique($translatedWords)) . "%";

$stmt =$pdo->prepare("
    SELECT id, name, price, old_price, image, stock 
    FROM products 
    WHERE (name LIKE ? OR description LIKE ? OR name LIKE ? OR description LIKE ?) 
    AND stock > 0 
    LIMIT 4
");
$stmt->execute([$rawTerm,$rawTerm, $translatedTerm,$translatedTerm]);
$products =$stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($products)) {$reply = "Here are matching products for **'" . ucfirst($cleanSearch) . "'**:";
    saveAiReply($sessionId,$reply);

    echo json_encode([
        'reply' => $reply,
        'type' => 'product_list',
        'products' => $products
    ]);
    exit;
} elseif (strlen($cleanSearch) >= 3) {     // Return explicit Not Found message instead of falling through to default greeting$reply = "Sorry, no active products were found matching **'" . ucfirst($cleanSearch) . "'** in our inventory right now.\n\nTry searching for `Rice`, `Oil`, `Tea`, or `Masala`!";
    saveAiReply($sessionId,$reply);

    echo json_encode(['reply' => $reply, 'type' => 'text']);
    exit;
}

// -------------------------------------------------------------
// 5. DEFAULT CONVERSATIONAL FALLBACK
// -------------------------------------------------------------
$fallback = "Namaste! I am your AI Sales Assistant at Biratnagar Collection.\n\n" .
            "• **Search Items:** Say `Chiya patti` or `Basmati Rice`\n" .
            "• **Add to Cart:** Say `Add 2 bags of Rice to cart`\n" .
            "• **Apply Promo:** Say `Apply RAMRO10`\n" .
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