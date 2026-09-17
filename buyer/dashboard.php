<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('buyer');
$buyerId = current_user()['id'];

$totalOrders = $pdo->prepare('SELECT COUNT(*) c FROM orders WHERE buyer_id = ?');
$totalOrders->execute([$buyerId]);
$totalOrders = $totalOrders->fetch()['c'];

$pendingPayments = $pdo->prepare("SELECT COUNT(*) c FROM orders WHERE buyer_id = ? AND status = 'confirmed'");
$pendingPayments->execute([$buyerId]);
$pendingPayments = $pendingPayments->fetch()['c'];

$totalSpent = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) s FROM orders WHERE buyer_id = ? AND status IN ('paid','completed')");
$totalSpent->execute([$buyerId]);
$totalSpent = $totalSpent->fetch()['s'];

$recentOrders = $pdo->prepare(
    "SELECT o.*, l.mushroom_type, u.full_name AS farmer_name
     FROM orders o
     JOIN listings l ON l.id = o.listing_id
     JOIN users u ON u.id = o.farmer_id
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC LIMIT 5"
);
$recentOrders->execute([$buyerId]);
$recentOrders = $recentOrders->fetchAll();

$pageTitle = 'Buyer Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<h1>Welcome back, <?= e(current_user()['full_name']) ?></h1>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int) $totalOrders ?></div><div class="label">Total Orders</div></div>
  <div class="stat-card"><div class="num"><?= (int) $pendingPayments ?></div><div class="label">Awaiting Payment</div></div>
  <div class="stat-card"><div class="num"><?= format_money($totalSpent) ?></div><div class="label">Total Spent</div></div>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); margin-bottom: 24px;">
  <a href="<?= BASE_URL ?>/buyer/marketplace.php" class="btn">Browse Marketplace</a>
  <a href="<?= BASE_URL ?>/buyer/orders.php" class="btn btn-outline">My Orders</a>
</div>

<h2>Recent Orders</h2>
<?php if (!$recentOrders): ?>
  <p class="muted">No orders yet.</p>
<?php else: ?>
  <table class="data-table">
    <tr><th>Farmer</th><th>Mushroom</th><th>Qty (kg)</th><th>Total</th><th>Status</th><th>Date</th></tr>
    <?php foreach ($recentOrders as $o): ?>
      <tr>
        <td><?= e($o['farmer_name']) ?></td>
        <td><?= e($o['mushroom_type']) ?></td>
        <td><?= e($o['quantity_kg']) ?></td>
        <td><?= format_money($o['total_price']) ?></td>
        <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
        <td><?= format_date($o['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
