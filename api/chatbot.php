<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Please enter a valid message or question.', 'type' => 'text']);
    exit;
}

$lowerMsg = strtolower($userMessage);

if (preg_match('/BRP-[A-Z0-9]{8}/i', $userMessage, $matches)) {
    $orderCode = strtoupper($matches[0]);
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ?");
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $pdo->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$order['id']]);
        $items = $itemStmt->fetchAll();

        $itemList = "";
        foreach ($items as $it) {
            $itemList .= "• {$it['product_name']} (x{$it['quantity']}) - Rs. " . number_format($it['price'] * $it['quantity']) . "\n";
        }

        $reply = "📦 **Order Status for #{$order['order_code']}**\n\n" .
                 "• **Customer:** {$order['customer_name']}\n" .
                 "• **Status:** {$order['status']}\n" .
                 "• **Payment Method:** {$order['payment_method']}\n" .
                 "• **Total Amount:** Rs. " . number_format($order['total_amount']) . "\n\n" .
                 "**Ordered Items:**\n" . $itemList;

        echo json_encode(['reply' => $reply, 'type' => 'order_status', 'order' => $order]);
        exit;
    } else {
        echo json_encode(['reply' => "Sorry, I couldn't find any order with code **{$orderCode}**. Please double-check your receipt.", 'type' => 'text']);
        exit;
    }
}

if (strpos($lowerMsg, 'buy') !== false || strpos($lowerMsg, 'show') !== false || strpos($lowerMsg, 'search') !== false || strpos($lowerMsg, 'price') !== false || strpos($lowerMsg, 'under') !== false) {
    
    // Extract price cap if present (e.g., "under 500")
    $maxPrice = 999999;
    if (preg_match('/under\s*(?:rs\.?|npr)?\s*(\d+)/i', $lowerMsg, $pMatches)) {
        $maxPrice = (float)$pMatches[1];
    }

    // Extract search query by removing common stop words
    $cleanQuery = preg_replace('/(show|me|buy|search|items|products|under|rs\.?|npr|\d+)/i', '', $lowerMsg);
    $cleanQuery = trim($cleanQuery);

    if (!empty($cleanQuery)) {
        $stmt = $pdo->prepare("SELECT id, name, price, old_price, image, stock FROM products WHERE (name LIKE ? OR description LIKE ?) AND price <= ? AND stock > 0 LIMIT 4");
        $searchTerm = "%{$cleanQuery}%";
        $stmt->execute([$searchTerm, $searchTerm, $maxPrice]);
    } else {
        $stmt = $pdo->prepare("SELECT id, name, price, old_price, image, stock FROM products WHERE price <= ? AND stock > 0 ORDER BY rating DESC LIMIT 4");
        $stmt->execute([$maxPrice]);
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($products)) {
        echo json_encode([
            'reply' => "Here are the best matching items I found for you:",
            'type' => 'product_list',
            'products' => $products
        ]);
        exit;
    }
}

$faqs = $pdo->query("SELECT * FROM chatbot_faq")->fetchAll();
foreach ($faqs as $faq) {
    $keywords = explode(',', $faq['keywords']);
    foreach ($keywords as $kw) {
        $kw = trim($kw);
        if (!empty($kw) && strpos($lowerMsg, strtolower($kw)) !== false) {
            echo json_encode(['reply' => $faq['answer'], 'type' => 'text']);
            exit;
        }
    }
}

$fallback = "I'm here to help! You can ask me to:\n" .
            "1. **Track an Order**: Type `Track BRP-XXXXXXXX`\n" .
            "2. **Search Products**: Type `Show me tea under 500`\n" .
            "3. **Ask Questions**: Ask about delivery, payment methods, or returns.";

echo json_encode(['reply' => $fallback, 'type' => 'fallback']);
exit;
?>