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
    "SELECT o.*, l.mushroom_type, u.full_name AS farmer_name, " . LISTING_TYPE_COLUMNS . "
     FROM orders o
     JOIN listings l ON l.id = o.listing_id
     LEFT JOIN mushroom_types mt ON mt.id = l.mushroom_type_id
     JOIN users u ON u.id = o.farmer_id
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC, o.id DESC LIMIT 5"
);
$recentOrders->execute([$buyerId]);
$recentOrders = $recentOrders->fetchAll();

$pageTitle = t('buyer.dashboard');
include __DIR__ . '/../includes/header.php';
?>

<h1><?= te('dash.welcome', ['name' => current_user()['full_name']]) ?></h1>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int) $totalOrders ?></div><div class="label"><?= te('buyer.total_orders') ?></div></div>
  <div class="stat-card"><div class="num"><?= (int) $pendingPayments ?></div><div class="label"><?= te('buyer.awaiting_payment') ?></div></div>
  <div class="stat-card"><div class="num"><?= format_money($totalSpent) ?></div><div class="label"><?= te('buyer.total_spent') ?></div></div>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); margin-bottom: 24px;">
  <a href="<?= BASE_URL ?>/buyer/marketplace.php" class="btn"><?= te('home.browse') ?></a>
  <a href="<?= BASE_URL ?>/mushrooms/index.php" class="btn btn-outline"><?= te('home.see_mushrooms') ?></a>
  <a href="<?= BASE_URL ?>/buyer/orders.php" class="btn btn-outline"><?= te('nav.my_orders') ?></a>
</div>

<h2><?= te('dash.recent_orders') ?></h2>
<?php if (!$recentOrders): ?>
  <p class="muted"><?= te('dash.no_orders') ?></p>
<?php else: ?>
  <table class="data-table">
    <tr><th><?= te('common.farmer') ?></th><th><?= te('common.mushroom') ?></th><th><?= te('common.qty_kg') ?></th><th><?= te('common.total') ?></th><th><?= te('common.status') ?></th><th><?= te('common.date') ?></th></tr>
    <?php foreach ($recentOrders as $o): ?>
      <tr>
        <td><?= e($o['farmer_name']) ?></td>
        <td><?= e(listing_title($o)) ?></td>
        <td><?= e($o['quantity_kg']) ?></td>
        <td><?= format_money($o['total_price']) ?></td>
        <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
        <td><?= format_date($o['created_at']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
