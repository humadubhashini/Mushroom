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
    "SELECT o.*, l.mushroom_type, u.full_name AS buyer_name, " . LISTING_TYPE_COLUMNS . "
     FROM orders o
     JOIN listings l ON l.id = o.listing_id
     LEFT JOIN mushroom_types mt ON mt.id = l.mushroom_type_id
     JOIN users u ON u.id = o.buyer_id
     WHERE o.farmer_id = ?
     ORDER BY o.created_at DESC LIMIT 5"
);
$recentOrders->execute([$farmerId]);
$recentOrders = $recentOrders->fetchAll();

// FR-EDU.4: recommend tutorials based on the farmer's most recent diagnosis.
$lastDiag = $pdo->prepare('SELECT d.predicted_disease_id, dt.name, dt.name_si, dt.name_ta FROM diagnoses d LEFT JOIN disease_types dt ON dt.id = d.predicted_disease_id
                           WHERE d.farmer_id = ? ORDER BY d.created_at DESC, d.id DESC LIMIT 1');
$lastDiag->execute([$farmerId]);
$lastDiag = $lastDiag->fetch();
$recommended = [];
if ($lastDiag && $lastDiag['predicted_disease_id']) {
    $rec = $pdo->prepare('SELECT id, title, title_si, title_ta FROM tutorials WHERE related_disease_id = ? LIMIT 3');
    $rec->execute([$lastDiag['predicted_disease_id']]);
    $recommended = $rec->fetchAll();
}

$pageTitle = t('farmer.dashboard');
include __DIR__ . '/../includes/header.php';
?>

<h1><?= te('dash.welcome', ['name' => current_user()['full_name']]) ?></h1>

<?php if (!farmer_is_approved($farmerId)): ?>
  <div class="alert alert-info">⏳ <?= te('farmer.pending_approval') ?></div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int) $listingCount ?></div><div class="label"><?= te('farmer.active_listings') ?></div></div>
  <div class="stat-card"><div class="num"><?= (int) $pendingOrders ?></div><div class="label"><?= te('farmer.pending_orders') ?></div></div>
  <div class="stat-card"><div class="num"><?= format_money($totalEarnings) ?></div><div class="label"><?= te('farmer.total_earnings') ?></div></div>
  <div class="stat-card"><div class="num"><?= (int) $diagnosisCount ?></div><div class="label"><?= te('farmer.diagnoses_run') ?></div></div>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); margin-bottom: 24px;">
  <a href="<?= BASE_URL ?>/farmer/add_listing.php" class="btn">+ <?= te('listing.new_title') ?></a>
  <a href="<?= BASE_URL ?>/farmer/diagnose.php" class="btn btn-secondary">📷 <?= te('nav.snap_detect') ?></a>
  <a href="<?= BASE_URL ?>/farmer/orders.php" class="btn btn-outline"><?= te('farmer.view_orders') ?></a>
  <a href="<?= BASE_URL ?>/knowledge/index.php" class="btn btn-outline"><?= te('nav.knowledge') ?></a>
</div>

<?php if ($recommended): ?>
  <div class="card" style="margin-bottom:24px;">
    <h2 class="mt-0">🎓 <?= te('farmer.recommended') ?></h2>
    <p class="muted"><?= te('farmer.based_on', ['disease' => tr_field($lastDiag, 'name')]) ?></p>
    <ul>
      <?php foreach ($recommended as $r): ?>
        <li><a href="<?= BASE_URL ?>/knowledge/view.php?id=<?= (int) $r['id'] ?>"><?= e(tr_field($r, 'title')) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<h2><?= te('dash.recent_orders') ?></h2>
<?php if (!$recentOrders): ?>
  <p class="muted"><?= te('dash.no_orders') ?></p>
<?php else: ?>
  <table class="data-table">
    <tr><th><?= te('common.buyer') ?></th><th><?= te('common.mushroom') ?></th><th><?= te('common.qty_kg') ?></th><th><?= te('common.total') ?></th><th><?= te('common.status') ?></th><th><?= te('common.date') ?></th></tr>
    <?php foreach ($recentOrders as $o): ?>
      <tr>
        <td><?= e($o['buyer_name']) ?></td>
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
