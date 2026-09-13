<?php
$offers = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.old_price > p.price")->fetchAll();
?>
<div class="page-title"><div class="container"><h1>Special Offers</h1></div></div>
<div class="section">
  <div class="container">
    <div class="products-grid">
      <?php foreach ($offers as $p) echo renderProductCard($p); ?>
    </div>
  </div>
</div>