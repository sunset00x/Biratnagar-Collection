<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

$totalSales = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status != 'Cancelled'")->fetchColumn() ?: 0;
$orderCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStock = $pdo->query("SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC")->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="grid-4">
  <div class="stat-box"><h3>Total Revenue</h3><p><?= money($totalSales) ?></p></div>
  <div class="stat-box"><h3>Total Orders</h3><p><?= $orderCount ?></p></div>
  <div class="stat-box"><h3>Active Products</h3><p><?= $productCount ?></p></div>
  <div class="stat-box"><h3>Low Stock Warning</h3><p><?= count($lowStock) ?></p></div>
</div>

<div class="card" style="margin-top:25px;">
  <h2>Monthly Sales Trend (2026)</h2>
  <canvas id="salesChart" style="max-height:280px;"></canvas>
</div>

<div class="card" style="margin-top: 30px;">
  <h2>Low Stock Inventory Alerts</h2>
  <table>
    <thead>
      <tr><th>ID</th><th>Product Name</th><th>Stock Level</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php if (empty($lowStock)): ?>
        <tr><td colspan="4">No low stock items.</td></tr>
      <?php else: ?>
        <?php foreach ($lowStock as $p): ?>
          <tr>
            <td>#<?= $p['id'] ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td style="color:red; font-weight:bold;"><?= $p['stock'] ?></td>
            <td><a href="product-edit.php?id=<?= $p['id'] ?>" class="btn-admin">Restock</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
  const ctx = document.getElementById('salesChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
      datasets: [{
        label: 'Revenue (NPR)',
        data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 35000, <?= (float)$totalSales ?>],
        borderColor: '#087443',
        backgroundColor: 'rgba(8, 116, 67, 0.1)',
        fill: true,
        tension: 0.3
      }]
    }
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>