<?php
$cats = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<div class="page-title">
  <div class="container"><h1>All Categories</h1></div>
</div>
<section class="section">
  <div class="container">
    <div class="categories-grid">
      <?php foreach ($cats as $c): ?>
        <article class="category-card" onclick="window.location.href='index.php?page=shop&category=<?= $c['id'] ?>'">
          <div class="category-icon"><?= $c['icon'] ?></div>
          <strong><?= htmlspecialchars($c['name']) ?></strong>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>