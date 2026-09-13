<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageUrl = trim($_POST['image_url'] ?? '');

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_') . '.' . $ext;
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }
        move_uploaded_file($_FILES['image_file']['tmp_name'], UPLOAD_DIR . $filename);
        $imageUrl = 'admin/assets/uploads/' . $filename;
    }

    $stmt = $pdo->prepare("INSERT INTO products (category_id, name, price, old_price, stock, rating, reviews, keywords, description, specs, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['category_id'], $_POST['name'], $_POST['price'], 
        $_POST['old_price'] ?: NULL, $_POST['stock'], $_POST['rating'], 
        $_POST['reviews'], $_POST['keywords'], $_POST['description'], 
        $_POST['specs'], $imageUrl
    ]);

    $productId = $pdo->lastInsertId();

    // Secondary Gallery Images Processing
    if (isset($_FILES['gallery_images'])) {
        $gStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");

        foreach ($_FILES['gallery_images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['gallery_images']['name'][$key], PATHINFO_EXTENSION);
                $filename = uniqid('gallery_') . '.' . $ext;
                
                if (move_uploaded_file($tmpName, UPLOAD_DIR . $filename)) {
                    $gStmt->execute([$productId, 'admin/assets/uploads/' . $filename]);
                }
            }
        }
    }

    header("Location: products.php");
    exit;
}

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div class="card" style="max-width: 600px;">
  <h2>Create Product Entry</h2>
  <form method="POST" enctype="multipart/form-data">
    <div class="form-group">
      <label>Product Name</label>
      <input type="text" name="name" required>
    </div>
    <div class="form-group">
      <label>Category</label>
      <select name="category_id" required>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Price (NPR)</label>
      <input type="number" step="0.01" name="price" required>
    </div>
    <div class="form-group">
      <label>Old Price (Optional)</label>
      <input type="number" step="0.01" name="old_price">
    </div>
    <div class="form-group">
      <label>Stock Qty</label>
      <input type="number" name="stock" required value="10">
    </div>
    <div class="form-group">
      <label>Rating (e.g. 4.5)</label>
      <input type="number" step="0.1" name="rating" value="4.5">
    </div>
    <div class="form-group">
      <label>Review Count</label>
      <input type="number" name="reviews" value="0">
    </div>
    <div class="form-group">
      <label>Keywords</label>
      <input type="text" name="keywords" placeholder="comma separated">
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description"></textarea>
    </div>
    <div class="form-group">
      <label>Specs</label>
      <input type="text" name="specs">
    </div>
    <div class="form-group">
      <label>Main Cover Image</label>
      <input type="file" name="image_file">
    </div>
    <div class="form-group">
      <label>Gallery Secondary Images (Multiple Selection)</label>
      <input type="file" name="gallery_images[]" multiple accept="image/*">
    </div>
    <div class="form-group">
      <label>...or Main External Image URL</label>
      <input type="url" name="image_url">
    </div>
    <button type="submit" class="btn-admin">Save Product</button>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>