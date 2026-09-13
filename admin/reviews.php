<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';

// Handle Approve / Reject actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $status = ($_GET['action'] === 'approve') ? 'approved' : 'rejected';
    $stmt = $pdo->prepare("UPDATE reviews SET status = ? WHERE id = ?");
    $stmt->execute([$status, (int)$_GET['id']]);
    header("Location: reviews.php");
    exit;
}

// Fetch all reviews (Pending first)
$reviews = $pdo->query("SELECT r.*, p.name as product_name, u.name as user_name 
                        FROM reviews r 
                        JOIN products p ON r.product_id = p.id 
                        JOIN users u ON r.user_id = u.id 
                        ORDER BY FIELD(r.status, 'pending', 'approved', 'rejected'), r.id DESC")->fetchAll();
?>

<div class="card">
  <h2>Customer Reviews Queue</h2>
  <table>
    <thead>
      <tr>
        <th>Product</th>
        <th>Customer</th>
        <th>Rating</th>
        <th>Comment</th>
        <th>Date</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($reviews)): ?>
        <tr><td colspan="7">No customer reviews submitted yet.</td></tr>
      <?php else: ?>
        <?php foreach ($reviews as $r): ?>
          <tr>
            <td><strong><?= htmlspecialchars($r['product_name']) ?></strong></td>
            <td><?= htmlspecialchars($r['user_name']) ?></td>
            <td><span style="color:#e69b00; font-weight:bold;"><?= $r['rating'] ?> ★</span></td>
            <td style="max-width:300px;"><?= htmlspecialchars($r['comment']) ?></td>
            <td style="font-size:12px; color:#666;"><?= $r['created_at'] ?></td>
            <td>
              <span class="order-status" style="
                background: <?= $r['status'] === 'approved' ? '#eaf7f0' : ($r['status'] === 'rejected' ? '#fee2e2' : '#fef3c7') ?>;
                color: <?= $r['status'] === 'approved' ? '#087443' : ($r['status'] === 'rejected' ? '#dc2626' : '#d97706') ?>;
              ">
                <?= ucfirst($r['status']) ?>
              </span>
            </td>
            <td>
              <?php if ($r['status'] !== 'approved'): ?>
                <a href="reviews.php?action=approve&id=<?= $r['id'] ?>" class="btn-admin" style="padding:4px 8px; font-size:12px;">Approve</a>
              <?php endif; ?>
              <?php if ($r['status'] !== 'rejected'): ?>
                <a href="reviews.php?action=reject&id=<?= $r['id'] ?>" class="btn-admin btn-danger" style="padding:4px 8px; font-size:12px;">Reject</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>