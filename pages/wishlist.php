<?php
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='index.php?page=login';</script>";
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM wishlist w JOIN products p ON w.product_id = p.id JOIN categories c ON p.category_id = c.id WHERE w.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();
?>

<div class="page-title">
  <div class="container"><h1>My Wishlist</h1></div>
</div>

<section class="section">
  <div class="container">
    <div class="products-grid">
      <?php 
      if (!empty($items)) {
          foreach ($items as $p) echo renderProductCard($p);
      } else {
          echo "<div class='empty' style='grid-column:1/-1'><h2>Your wishlist is empty</h2></div>";
      }
      ?>
    </div>
  </div>
</section>