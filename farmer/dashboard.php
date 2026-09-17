<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];

$listingCount = $pdo->prepare("SELECT COUNT(*) c FROM listings WHERE farmer_id = ? AND status != 'removed'");
$listingCount->execute([$farmerId]);
$listingCount = $listingCount->fetch()['c'];

$pendingOrders = $pdo->prepare("SELECT COUNT(*) c FROM orders WHERE farmer_id = ? AND status = 'pending'");
$pendingOrders->execute([$farmerId]);
$pendingOrders = $pendingOrders->fetch()['c'];

$totalEarnings = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) s FROM orders WHERE farmer_id = ? AND status IN ('paid','completed')");
$totalEarnings->execute([$farmerId]);
$totalEarnings = $totalEarnings->fetch()['s'];

$diagnosisCount = $pdo->prepare('SELECT COUNT(*) c FROM diagnoses WHERE farmer_id = ?');
$diagnosisCount->execute([$farmerId]);
$diagnosisCount = $diagnosisCount->fetch()['c'];

$recentOrders = $pdo->prepare(
    "SELECT o.*, l.mushroom_type, u.full_name AS buyer_name
     FROM orders o
     JOIN listings l ON l.id = o.listing_id
     JOIN users u ON u.id = o.buyer_id
     WHERE o.farmer_id = ?
     ORDER BY o.created_at DESC LIMIT 5"
);
$recentOrders->execute([$farmerId]);
$recentOrders = $recentOrders->fetchAll();

$pageTitle = 'Farmer Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<h1>Welcome back, <?= e(current_user()['full_name']) ?></h1>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int) $listingCount ?></div><div class="label">Active Listings</div></div>
  <div class="stat-card"><div class="num"><?= (int) $pendingOrders ?></div><div class="label">Pending Orders</div></div>
  <div class="stat-card"><div class="num"><?= format_money($totalEarnings) ?></div><div class="label">Total Earnings</div></div>
  <div class="stat-card"><div class="num"><?= (int) $diagnosisCount ?></div><div class="label">AI Diagnoses Run</div></div>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); margin-bottom: 24px;">
  <a href="<?= BASE_URL ?>/farmer/add_listing.php" class="btn">+ New Listing</a>
  <a href="<?= BASE_URL ?>/farmer/diagnose.php" class="btn btn-secondary">📷 Snap &amp; Detect</a>
  <a href="<?= BASE_URL ?>/farmer/orders.php" class="btn btn-outline">View Orders</a>
  <a href="<?= BASE_URL ?>/knowledge/index.php" class="btn btn-outline">Knowledge Hub</a>
</div>

<h2>Recent Orders</h2>
<?php if (!$recentOrders): ?>
  <p class="muted">No orders yet.</p>
<?php else: ?>
  <table class="data-table">
    <tr><th>Buyer</th><th>Mushroom</th><th>Qty (kg)</th><th>Total</th><th>Status</th><th>Date</th></tr>
    <?php foreach ($recentOrders as $o): ?>
      <tr>
        <td><?= e($o['buyer_name']) ?></td>
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
