<?php
// Suppress warnings that disrupt JSON output
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Resilient DB path lookup
if (file_exists(__DIR__ . '/../includes/db.php')) {
    require_once __DIR__ . '/../includes/db.php';
} elseif (file_exists(__DIR__ . '/includes/db.php')) {
    require_once __DIR__ . '/includes/db.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionId = session_id();$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$userMessage = trim($input['message'] ?? '');
$imageBase64 =$input['image'] ?? null;

if (empty($userMessage) && empty($imageBase64)) {
    echo json_encode(['reply' => 'Namaste! How can I assist you with your shopping today?', 'type' => 'text']);
    exit;
}

$lowerMsg = strtolower($userMessage);

// =============================================================
// INTENT 1: RECIPE INGREDIENTS BUNDLE (e.g. "Momo", "Biryani")
// =============================================================
if (preg_match('/(?:recipe|ingredients?|bundle|make|cook)\s*(?:for)?\s*([a-z0-9\s]+)/i', $lowerMsg, $matches)) {$dish = trim($matches[1]);$recipeKeywords = [];

    if (strpos($dish, 'momo') !== false) {$recipeKeywords = ['maida', 'flour', 'keema', 'chicken', 'masala', 'oil'];
    } elseif (strpos($dish, 'biryani') !== false) {$recipeKeywords = ['basmati', 'rice', 'chicken', 'ghee', 'masala'];
    } elseif (strpos($dish, 'tea') !== false || strpos($dish, 'chiya') !== false) {$recipeKeywords = ['tea', 'chiya', 'milk', 'sugar', 'chini'];
    } else {
        $recipeKeywords = explode(' ',$dish);
    }

    $products = [];
    foreach ($recipeKeywords as$kw) {
        $stmt =$pdo->prepare("SELECT id, name, price, image FROM products WHERE (name LIKE ? OR description LIKE ?) LIMIT 1");
        $stmt->execute(["%{$kw}\%", "\%{$kw}%"]);
        $item =$stmt->fetch(PDO::FETCH_ASSOC);
        if ($item) {
            $products[] =$item;
        }
    }

    if (!empty($products)) {$reply = "🍳 **Recipe Ingredients Bundle for " . ucfirst($dish) . "**\nHere are the essential items available in our store:";
        echo json_encode(['reply' => $reply, 'type' => 'recipe_bundle', 'products' =>$products]);
        exit;
    }
}

// =============================================================
// INTENT 2: BUDGET & PRICE FILTER (e.g. "under 300", "below 500")
// =============================================================
$maxPrice = null;
if (preg_match('/(?:under|below|less than|within)\s*(?:rs\.?|npr)?\s*(\d+)/i', $lowerMsg,$matches)) {
    $maxPrice = (float)$matches[1];
}

// =============================================================
// INTENT 3: NEPLISH & MULTILINGUAL CATALOG SEARCH
// =============================================================
$synonyms = [
    'chiya' => 'tea', 'patti' => 'tea', 'tarkari' => 'vegetable', 
    'tel' => 'oil', 'masala' => 'spice', 'chamal' => 'rice', 
    'gini' => 'sugar', 'chini' => 'sugar', 'doodh' => 'milk', 'coca' => 'coca', 'cocacola' => 'coca'
];

$words = explode(' ', $lowerMsg);$searchTerms = [];
foreach ($words as$w) {
    if (isset($synonyms[$w])) {$searchTerms[] = $synonyms[$w];
    }
    $searchTerms[] =$w;
}

// Remove filler words
$filteredWords = array_diff($searchTerms, ['show', 'me', 'buy', 'search', 'get', 'want', 'need', 'chahiyo', 'cha', 'under', 'below', 'less', 'than', 'price']);
$cleanTerm = implode('\%', array_unique($filteredWords));

$query = "SELECT id, name, price, old_price, image, stock FROM products WHERE (name LIKE ? OR description LIKE ?)";
$params = ["%{$cleanTerm}\%", "\%{$cleanTerm}%"];

if ($maxPrice !== null) {$query .= " AND price <= ?";
    $params[] =$maxPrice;
}

$query .= " LIMIT 4";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products =$stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($products)) {
        $priceText =$maxPrice ? " under Rs. {$maxPrice}" : "";
        $reply = "🛒 Here are matching items found in our catalog{$priceText}:";
        echo json_encode(['reply' => $reply, 'type' => 'product_list', 'products' =>$products]);
        exit;
    }
} catch (PDOException $e) {
    // Graceful fallback for database exception handling
}

// =============================================================
// INTENT 4: PROMO CODE VALIDATION
// =============================================================
if (preg_match('/(?:apply|use|coupon|code|promo)\s+([a-z0-9]+)/i', $lowerMsg,$matches)) {
    $code = strtoupper($matches[1]);
    if ($code === 'RAMRO10') {$_SESSION['applied_coupon'] = 'RAMRO10';
        echo json_encode(['reply' => "🎉 **Coupon RAMRO10 Applied!** You saved 10% on your order.", 'type' => 'coupon']);
        exit;
    }
}

// =============================================================
// INTENT 5: GENERAL / GREETING FALLBACK
// =============================================================
if (strpos($lowerMsg, 'namaste') !== false \vert{}\vert{} strpos($lowerMsg, 'hello') !== false || strpos($lowerMsg, 'hi') !== false) {$reply = "🙏 Namaste! Welcome to Biratnagar Ramro Pasal.\n\nI can help you:\n• Find groceries (e.g. `Chiya patti` or `Coca-Cola`)\n• Filter by budget (e.g. `Tea under 300`)\n• Get recipe bundles (e.g. `Ingredients for Momo`)";
    echo json_encode(['reply' => $reply, 'type' => 'text']);
    exit;
}

// Item not found explicit response
$reply = "Sorry, no items matching **'" . htmlspecialchars($userMessage) . "'** were found in our store inventory right now.\n\nTry asking for `Basmati Rice`, `Coca-Cola`, `Chiya patti`, or `Ghee`!";
echo json_encode(['reply' => $reply, 'type' => 'text']);
exit;
?>