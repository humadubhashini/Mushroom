<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('buyer');
$buyerId = current_user()['id'];

$stmt = $pdo->prepare(
    "SELECT o.*, l.mushroom_type, u.full_name AS farmer_name,
            (SELECT COUNT(*) FROM reviews r WHERE r.order_id = o.id) AS has_review
     FROM orders o
     JOIN listings l ON l.id = o.listing_id
     JOIN users u ON u.id = o.farmer_id
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->execute([$buyerId]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
include __DIR__ . '/../includes/header.php';
?>

<h1>My Orders</h1>

<?php if (!$orders): ?>
  <p class="muted">You haven't placed any orders yet. <a href="<?= BASE_URL ?>/buyer/marketplace.php">Browse the marketplace</a>.</p>
<?php else: ?>
  <table class="data-table">
    <tr><th>Farmer</th><th>Mushroom</th><th>Qty (kg)</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= e($o['farmer_name']) ?></td>
        <td><?= e($o['mushroom_type']) ?></td>
        <td><?= e($o['quantity_kg']) ?></td>
        <td><?= format_money($o['total_price']) ?></td>
        <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
        <td><?= format_date($o['created_at']) ?></td>
        <td>
          <?php if ($o['status'] === 'confirmed'): ?>
            <a href="<?= BASE_URL ?>/buyer/payment.php?order_id=<?= (int) $o['id'] ?>" class="btn btn-small">Pay Now</a>
          <?php elseif ($o['status'] === 'completed' && !$o['has_review']): ?>
            <a href="<?= BASE_URL ?>/buyer/review.php?order_id=<?= (int) $o['id'] ?>" class="btn btn-small btn-outline">Rate Farmer</a>
          <?php else: ?>
            <span class="muted">&mdash;</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
