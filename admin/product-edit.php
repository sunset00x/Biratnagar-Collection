<?php
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageUrl = $product['image'];

    if (!empty($_POST['image_url'])) {
        $imageUrl = trim($_POST['image_url']);
    }

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_') . '.' . $ext;
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }
        move_uploaded_file($_FILES['image_file']['tmp_name'], UPLOAD_DIR . $filename);
        $imageUrl = 'admin/assets/uploads/' . $filename;
    }

    $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, price = ?, old_price = ?, stock = ?, rating = ?, reviews = ?, keywords = ?, description = ?, specs = ?, image = ? WHERE id = ?");
    $stmt->execute([
        $_POST['category_id'], $_POST['name'], $_POST['price'], 
        $_POST['old_price'] ?: NULL, $_POST['stock'], $_POST['rating'], 
        $_POST['reviews'], $_POST['keywords'], $_POST['description'], 
        $_POST['specs'], $imageUrl, $id
    ]);

    header("Location: products.php");
    exit;
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div class="card" style="max-width: 600px;">
  <h2>Edit Product Specifications</h2>
  <form method="POST" enctype="multipart/form-data">
    <div class="form-group">
      <label>Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
    </div>
    <div class="form-group">
      <label>Category</label>
      <select name="category_id" required>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Price</label>
      <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>
    </div>
    <div class="form-group">
      <label>Old Price</label>
      <input type="number" step="0.01" name="old_price" value="<?= $product['old_price'] ?>">
    </div>
    <div class="form-group">
      <label>Stock Qty</label>
      <input type="number" name="stock" value="<?= $product['stock'] ?>" required>
    </div>
    <div class="form-group">
      <label>Rating</label>
      <input type="number" step="0.1" name="rating" value="<?= $product['rating'] ?>">
    </div>
    <div class="form-group">
      <label>Reviews</label>
      <input type="number" name="reviews" value="<?= $product['reviews'] ?>">
    </div>
    <div class="form-group">
      <label>Keywords</label>
      <input type="text" name="keywords" value="<?= htmlspecialchars($product['keywords']) ?>">
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
    </div>
    <div class="form-group">
      <label>Specs</label>
      <input type="text" name="specs" value="<?= htmlspecialchars($product['specs']) ?>">
    </div>
    <div class="form-group">
      <label>Upload New File</label>
      <input type="file" name="image_file">
    </div>
    <div class="form-group">
      <label>Image URL</label>
      <input type="text" name="image_url" value="<?= htmlspecialchars($product['image']) ?>">
    </div>
    <button type="submit" class="btn-admin">Update Product</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>