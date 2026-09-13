<?php
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if (!$p) {
    echo "<script>window.location.href='index.php?page=shop';</script>";
    exit;
}

// Fetch Secondary Images
$imgStmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
$imgStmt->execute([$id]);
$secondaryImages = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?page=login");
        exit;
    }
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);
    
    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$p['id'], $_SESSION['user_id'], $rating, $comment]);
    echo "<script>alert('Review submitted for approval!'); window.location.href='index.php?page=product&id={$p['id']}';</script>";
    exit;
}

// Fetch Approved Reviews
$revStmt = $pdo->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.id DESC");
$revStmt->execute([$p['id']]);
$approvedReviews = $revStmt->fetchAll();

$discount = discountOf($p['price'], $p['old_price']);
?>

<div class="container product-detail">
  <div style="margin-bottom:15px;color:#68746d;font-size:13px">
    Home / <?= htmlspecialchars($p['category_name']) ?> / <?= htmlspecialchars($p['name']) ?>
  </div>

  <div class="detail-grid">
    <div>
      <div class="gallery-main">
        <img id="galleryMain" src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
      </div>
      <?php if (!empty($secondaryImages)): ?>
        <div class="thumbs" style="display:flex; gap:10px; margin-top:10px;">
          <img src="<?= htmlspecialchars($p['image']) ?>" onclick="document.getElementById('galleryMain').src=this.src" class="thumb active" style="width:60px; height:60px; object-fit:cover; cursor:pointer; border-radius:6px;">
          <?php foreach ($secondaryImages as $sImg): ?>
            <img src="<?= htmlspecialchars($sImg) ?>" onclick="document.getElementById('galleryMain').src=this.src" class="thumb" style="width:60px; height:60px; object-fit:cover; cursor:pointer; border-radius:6px;">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="detail-info">
      <div class="product-category"><?= htmlspecialchars($p['category_name']) ?></div>
      <h1><?= htmlspecialchars($p['name']) ?></h1>

      <div class="rating">
        ★ <?= number_format($p['rating'], 1) ?>
        <span>(<?= count($approvedReviews) ?> customer reviews)</span>
      </div>

      <div class="detail-price">
        <span class="price"><?= money($p['price']) ?></span>
        <?php if ($p['old_price']): ?>
            <span class="old-price"><?= money($p['old_price']) ?></span>
        <?php endif; ?>
        <?php if ($discount > 0): ?>
            <span class="discount">-<?= $discount ?>%</span>
        <?php endif; ?>
      </div>

      <div class="stock">
        ✓ <?= $p['stock'] > 0 ? "In stock — {$p['stock']} available" : "Out of stock" ?>
      </div>

      <p class="detail-description"><?= htmlspecialchars($p['description']) ?></p>

      <div>
        <div class="quantity">
          <button onclick="let input=document.getElementById('detailQty'); input.value=Math.max(1, parseInt(input.value)-1)">−</button>
          <input id="detailQty" value="1" readonly>
          <button onclick="let input=document.getElementById('detailQty'); input.value=parseInt(input.value)+1">+</button>
        </div>

        <button class="btn btn-green" onclick="addToCart(<?= $p['id'] ?>, parseInt(document.getElementById('detailQty').value))">
          🛒 Add to Cart
        </button>
        <button class="btn btn-primary" onclick="buyNow(<?= $p['id'] ?>)">Buy Now</button>
      </div>

      <div class="info-box" style="margin-top: 20px;">
        <strong>Specifications</strong>
        <p><?= htmlspecialchars($p['specs']) ?></p>
      </div>
    </div>
  </div>

  <!-- Customer Reviews Section -->
  <div class="form-card" style="margin-top:30px;">
    <h2>Customer Reviews</h2>
    
    <?php if (isset($_SESSION['user_id'])): ?>
      <form method="POST" style="margin-bottom:25px; padding:15px; background:#f9fbf9; border-radius:8px;">
        <h3>Leave a Review</h3>
        <div class="field" style="margin-top:10px;">
          <label>Rating</label>
          <select name="rating" required style="width:120px;">
            <option value="5">5 ★★★★★</option>
            <option value="4">4 ★★★★☆</option>
            <option value="3">3 ★★★☆☆</option>
            <option value="2">2 ★★☆☆☆</option>
            <option value="1">1 ★☆☆☆☆</option>
          </select>
        </div>
        <div class="field">
          <label>Your Review</label>
          <textarea name="comment" required placeholder="Write your thoughts..."></textarea>
        </div>
        <button type="submit" name="submit_review" class="btn btn-green">Submit Review</button>
      </form>
    <?php else: ?>
      <p style="margin-bottom:15px;"><a href="index.php?page=login" style="color:#087443; font-weight:bold;">Login</a> to submit a review.</p>
    <?php endif; ?>

    <div class="reviews-list">
      <?php if (empty($approvedReviews)): ?>
        <p>No reviews yet. Be the first to review!</p>
      <?php else: ?>
        <?php foreach ($approvedReviews as $r): ?>
          <div class="review-item" style="border-bottom:1px solid #eee; padding:12px 0;">
            <strong><?= htmlspecialchars($r['user_name']) ?></strong> 
            <span style="color:#e69b00; font-size:13px; font-weight:bold; margin-left:8px;"><?= str_repeat('★', $r['rating']) ?></span>
            <p style="margin-top:5px; color:#555;"><?= htmlspecialchars($r['comment']) ?></p>
            <small style="color:#999; font-size:11px;"><?= $r['created_at'] ?></small>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>