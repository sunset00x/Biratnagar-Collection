<?php
// Suppress warnings that break JSON parsing
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Resilient DB file lookup
if (file_exists(__DIR__ . '/../includes/db.php')) {
    require_once __DIR__ . '/../includes/db.php';
} elseif (file_exists(__DIR__ . '/includes/db.php')) {
    require_once __DIR__ . '/includes/db.php';
} else {
    echo json_encode(['reply' => 'Database configuration file missing.', 'type' => 'error']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$userMessage = trim($input['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Namaste! How can I assist you with your shopping today?', 'type' => 'text']);
    exit;
}

$lowerMsg = strtolower($userMessage);

// Synonyms map for English / Neplish
$synonyms = [
    'chiya' => 'tea', 'patti' => 'tea', 'tarkari' => 'vegetable', 
    'tel' => 'oil', 'masala' => 'spice', 'chamal' => 'rice', 
    'gini' => 'sugar', 'chini' => 'sugar', 'doodh' => 'milk', 'cocacola' => 'coca'
];

$words = explode(' ', $lowerMsg);$searchTerms = [];
foreach ($words as$w) {
    if (isset($synonyms[$w])) {$searchTerms[] = $synonyms[$w];
    }
    $searchTerms[] =$w;
}

// Remove filler words
$filteredWords = array_diff($searchTerms, ['show', 'me', 'buy', 'search', 'get', 'want', 'need', 'chahiyo', 'cha', 'under', 'below']);
$cleanTerm = implode('\%', array_unique($filteredWords));

try {
    $stmt =$pdo->prepare("SELECT id, name, price, image FROM products WHERE name LIKE ? OR description LIKE ? LIMIT 4");
    $searchTerm = "\%{$cleanTerm}%";
    $stmt->execute([$searchTerm,$searchTerm]);
    $products =$stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($products)) {$reply = "🛒 Here are matching items found in our catalog:";
        echo json_encode(['reply' => $reply, 'type' => 'product_list', 'products' =>$products]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['reply' => "Database error: " . $e->getMessage(), 'type' => 'error']);
    exit;
}

// Fallback greeting if no product matched
if (strpos($lowerMsg, 'namaste') !== false \vert{}\vert{} strpos($lowerMsg, 'hi') !== false || strpos($lowerMsg, 'hello') !== false) {$reply = "🙏 Namaste! Welcome to Biratnagar Ramro Pasal.\n\nAsk me for `Chiya patti`, `Basmati Rice`, or `Coca-Cola`!";
    echo json_encode(['reply' => $reply, 'type' => 'text']);
    exit;
}

$reply = "Sorry, no items matching **'" . htmlspecialchars($userMessage) . "'** were found in our store inventory right now.\n\nTry searching for `Chiya patti`, `Coca-Cola`, or `Rice`!";
echo json_encode(['reply' => $reply, 'type' => 'text']);
exit;
?>