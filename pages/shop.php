<?php
$catId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$queryStr = isset($_GET['q']) ? trim($_GET['q']) : '';
$minPrice = isset($_GET['min']) ? (float)$_GET['min'] : 0;
$maxPrice = isset($_GET['max']) ? (float)$_GET['max'] : 0;

$where = ["1=1"];
$params = [];

if ($catId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $catId;
}

if (!empty($queryStr)) {
    $where[] = "(p.name LIKE ? OR p.keywords LIKE ?)";
    $params[] = "%$queryStr%";
    $params[] = "%$queryStr%";
}

if ($minPrice > 0) {
    $where[] = "p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = "p.price <= ?";
    $params[] = $maxPrice;
}

$sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE " . implode(' AND ', $where) . " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$shopProducts = $stmt->fetchAll();

$allCategories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div class="page-title">
  <div class="container">
    <h1>Shop All Products</h1>
    <div class="breadcrumbs">Home / Shop</div>
  </div>
</div>

<div class="container shop-layout">
  <aside class="filters" id="filters">
    <form method="GET" action="index.php">
      <input type="hidden" name="page" value="shop">
      <div class="filter-group">
        <h3>Category</h3>
        <?php foreach ($allCategories as $c): ?>
          <label class="check">
            <input type="radio" name="category" value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'checked' : '' ?> onchange="this.form.submit()">
            <?= htmlspecialchars($c['name']) ?>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="filter-group">
        <h3>Price Range</h3>
        <div class="range-values">
          <input type="number" name="min" placeholder="Min" value="<?= $minPrice ?: '' ?>" />
          <input type="number" name="max" placeholder="Max" value="<?= $maxPrice ?: '' ?>" />
        </div>
      </div>

      <button type="submit" class="btn btn-green" style="width:100%; margin-top:10px;">Filter</button>
      <a href="index.php?page=shop" class="btn btn-outline" style="width:100%; margin-top:5px; text-align:center;">Reset</a>
    </form>
  </aside>

  <section class="shop-content">
    <div class="shop-toolbar">
      <strong><?= count($shopProducts) ?> products</strong>
    </div>
    <div class="products-grid">
      <?php 
      if (!empty($shopProducts)) {
          foreach ($shopProducts as $p) echo renderProductCard($p);
      } else {
          echo "<div class='empty' style='grid-column:1/-1'><h2>No products found</h2></div>";
      }
      ?>
    </div>
  </section>
</div>