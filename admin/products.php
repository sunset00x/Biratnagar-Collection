<?php
require_once __DIR__ . '/includes/header.php';

if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$delId]);
    header("Location: products.php");
    exit;
}

$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
$productList = $stmt->fetchAll();
?>

<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center;">
    <h2>Product Inventory</h2>
    <a href="product-add.php" class="btn-admin">+ Add New Product</a>
  </div>
  <table>
    <thead>
      <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($productList as $p): ?>
        <tr>
          <td><img src="<?= htmlspecialchars($p['image']) ?>" style="width:40px; height:40px; border-radius:4px; object-fit:cover;"></td>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td><?= htmlspecialchars($p['category_name']) ?></td>
          <td><?= money($p['price']) ?></td>
          <td><?= $p['stock'] ?></td>
          <td>
            <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn-admin">Edit</a>
            <a href="products.php?delete=<?= $p['id'] ?>" onclick="return confirm('Delete item?');" class="btn-admin btn-danger">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>