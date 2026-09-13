<?php
$newItems = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 15")->fetchAll();
?>
<div class="page-title"><div class="container"><h1>New Arrivals</h1></div></div>
<div class="section">
  <div class="container">
    <div class="products-grid">
      <?php foreach ($newItems as $p) echo renderProductCard($p); ?>
    </div>
  </div>
</div>