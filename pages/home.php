<?php
$categories = $pdo->query("SELECT * FROM categories LIMIT 12")->fetchAll();

$flash = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.old_price > p.price LIMIT 5")->fetchAll();

$featured = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY (p.rating * p.reviews) DESC LIMIT 10")->fetchAll();
?>

<div class="container">
  <div class="hero">
    <article class="hero-slide active">
      <div class="hero-copy">
        <div class="eyebrow">Biratnagar's Everyday Store</div>
        <h1>Everything You Need, All in One Place.</h1>
        <p>Shop groceries, fresh produce, household essentials, personal care, beverages and much more — conveniently online.</p>
        <div>
          <a href="index.php?page=shop" class="btn btn-primary">Shop Now →</a>
          <a href="index.php?page=offers" class="btn btn-light">View Offers</a>
        </div>
      </div>
      <div class="hero-art"></div>
    </article>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Shop by Category</h2>
        <p>Everything your household needs, organized for you.</p>
      </div>
      <a class="text-link" href="index.php?page=categories">View All →</a>
    </div>
    <div class="categories-grid">
      <?php foreach ($categories as $cat): ?>
        <article class="category-card" onclick="window.location.href='index.php?page=shop&category=<?= $cat['id'] ?>'">
          <div class="category-icon"><?= $cat['icon'] ?></div>
          <strong><?= htmlspecialchars($cat['name']) ?></strong>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="flash">
      <div class="sale-head">
        <div>
          <div class="eyebrow" style="color:#d97706">Limited Time</div>
          <h2>⚡ Flash Sale</h2>
        </div>
        <div class="timer" id="countdown">08:00:00</div>
      </div>
      <div class="products-grid">
        <?php foreach ($flash as $p) echo renderProductCard($p); ?>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Featured Products</h2>
        <p>Popular products customers love.</p>
      </div>
      <a class="text-link" href="index.php?page=shop">Shop All →</a>
    </div>
    <div class="products-grid">
      <?php foreach ($featured as $p) echo renderProductCard($p); ?>
    </div>
  </div>
</section>

<script>
  let seconds = 8 * 60 * 60;
  setInterval(() => {
    seconds--;
    if (seconds < 0) seconds = 8 * 60 * 60;
    const h = String(Math.floor(seconds / 3600)).padStart(2, "0");
    const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, "0");
    const s = String(seconds % 60).padStart(2, "0");
    document.getElementById("countdown").textContent = `${h}:${m}:${s}`;
  }, 1000);
</script>