<?php
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $stmt = $pdo->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
    $stmt->execute([trim($_POST['name']), trim($_POST['icon'])]);
    header("Location: categories.php");
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    header("Location: categories.php");
    exit;
}

$cats = $pdo->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.id DESC")->fetchAll();
?>

<div class="card" style="max-width: 400px; margin-bottom: 20px;">
  <h2>Add New Category</h2>
  <form method="POST">
    <input type="hidden" name="add_category" value="1">
    <div class="form-group">
      <label>Name</label>
      <input type="text" name="name" required>
    </div>
    <div class="form-group">
      <label>Icon Emoji</label>
      <input type="text" name="icon" value="📦" required>
    </div>
    <button type="submit" class="btn-admin">Create Category</button>
  </form>
</div>

<div class="card">
  <h2>Category Directory</h2>
  <table>
    <thead>
      <tr><th>Icon</th><th>Name</th><th>Products</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($cats as $c): ?>
        <tr>
          <td><?= $c['icon'] ?></td>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td><?= $c['product_count'] ?></td>
          <td>
            <a href="categories.php?delete=<?= $c['id'] ?>" onclick="return confirm('Deleting category will delete associated products!')" class="btn-admin btn-danger">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>