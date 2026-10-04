<?php
/** Aggregate marketplace and AI reports (FR-ADM.2, FR-ADM.3). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$from = $_GET['from'] ?? date('Y-m-01', strtotime('-5 months'));
$to = $_GET['to'] ?? date('Y-m-d');
if (!strtotime($from)) $from = date('Y-m-01');
if (!strtotime($to)) $to = date('Y-m-d');
$range = [$from . ' 00:00:00', $to . ' 23:59:59'];

$summary = $pdo->prepare(
    "SELECT COUNT(*) orders,
            SUM(status IN ('paid','completed')) paid_orders,
            SUM(status = 'cancelled') cancelled_orders,
            COALESCE(SUM(CASE WHEN status IN ('paid','completed') THEN total_price END), 0) revenue,
            COALESCE(SUM(CASE WHEN status IN ('paid','completed') THEN quantity_kg END), 0) kg_sold
     FROM orders WHERE created_at BETWEEN ? AND ?"
);
$summary->execute($range);
$summary = $summary->fetch();

$newListings = $pdo->prepare('SELECT COUNT(*) c FROM listings WHERE created_at BETWEEN ? AND ?');
$newListings->execute($range);
$newListings = $newListings->fetch()['c'];

$monthly = $pdo->prepare(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') month, COUNT(*) orders,
            COALESCE(SUM(CASE WHEN status IN ('paid','completed') THEN total_price END), 0) revenue
     FROM orders WHERE created_at BETWEEN ? AND ?
     GROUP BY month ORDER BY month"
);
$monthly->execute($range);
$monthly = $monthly->fetchAll();
$maxRevenue = max(array_merge([1], array_column($monthly, 'revenue')));

$byType = $pdo->prepare(
    "SELECT l.mushroom_type, SUM(o.quantity_kg) kg, SUM(o.total_price) revenue
     FROM orders o JOIN listings l ON l.id = o.listing_id
     WHERE o.status IN ('paid','completed') AND o.created_at BETWEEN ? AND ?
     GROUP BY l.mushroom_type ORDER BY revenue DESC"
);
$byType->execute($range);
$byType = $byType->fetchAll();
$maxType = max(array_merge([1], array_column($byType, 'revenue')));

$topFarmers = $pdo->prepare(
    "SELECT u.full_name, u.business_name, COUNT(o.id) orders, SUM(o.total_price) revenue
     FROM orders o JOIN users u ON u.id = o.farmer_id
     WHERE o.status IN ('paid','completed') AND o.created_at BETWEEN ? AND ?
     GROUP BY u.id ORDER BY revenue DESC LIMIT 5"
);
$topFarmers->execute($range);
$topFarmers = $topFarmers->fetchAll();

$aiStats = $pdo->prepare(
    "SELECT COALESCE(dt.name, 'Unknown') disease, COUNT(*) total, ROUND(AVG(d.confidence), 1) avg_conf,
            SUM(d.low_confidence_flag) flagged
     FROM diagnoses d LEFT JOIN disease_types dt ON dt.id = d.predicted_disease_id
     WHERE d.created_at BETWEEN ? AND ?
     GROUP BY disease ORDER BY total DESC"
);
$aiStats->execute($range);
$aiStats = $aiStats->fetchAll();

$payments = $pdo->prepare(
    "SELECT SUM(status = 'success') ok, SUM(status = 'failed') failed FROM payments WHERE created_at BETWEEN ? AND ?"
);
$payments->execute($range);
$payments = $payments->fetch();

$pageTitle = 'Reports';
include __DIR__ . '/../includes/header.php';
?>

<h1>📊 Platform Reports</h1>

<form method="get" class="card no-print" style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end;">
  <div><label>From</label><br><input type="date" name="from" value="<?= e($from) ?>" style="padding:8px;"></div>
  <div><label>To</label><br><input type="date" name="to" value="<?= e($to) ?>" style="padding:8px;"></div>
  <button class="btn" style="margin-top:0;">Apply</button>
  <button type="button" class="btn btn-outline" style="margin-top:0;" onclick="window.print()">🖨 Print</button>
</form>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int) $summary['orders'] ?></div><div class="label">Orders placed</div></div>
  <div class="stat-card"><div class="num"><?= (int) $summary['paid_orders'] ?></div><div class="label">Paid / completed</div></div>
  <div class="stat-card"><div class="num"><?= format_money($summary['revenue']) ?></div><div class="label">Revenue to farmers</div></div>
  <div class="stat-card"><div class="num"><?= number_format($summary['kg_sold'], 1) ?> kg</div><div class="label">Mushrooms sold</div></div>
  <div class="stat-card"><div class="num"><?= (int) $newListings ?></div><div class="label">New listings</div></div>
  <div class="stat-card"><div class="num"><?= (int) $payments['ok'] ?> / <?= (int) $payments['failed'] ?></div><div class="label">Payments OK / failed</div></div>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); align-items:start;">
  <div class="card">
    <h2 class="mt-0">Monthly revenue</h2>
    <?php if (!$monthly): ?><p class="muted">No orders in this period.</p><?php endif; ?>
    <?php foreach ($monthly as $m): ?>
      <div class="bar-row">
        <span class="bar-label"><?= e(date('M Y', strtotime($m['month'] . '-01'))) ?> (<?= (int) $m['orders'] ?>)</span>
        <span class="bar"><span style="width: <?= round($m['revenue'] / $maxRevenue * 100) ?>%"></span></span>
        <span class="bar-value"><?= format_money($m['revenue']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2 class="mt-0">Sales by mushroom type</h2>
    <?php if (!$byType): ?><p class="muted">No paid sales in this period.</p><?php endif; ?>
    <?php foreach ($byType as $t): ?>
      <div class="bar-row">
        <span class="bar-label"><?= e($t['mushroom_type']) ?></span>
        <span class="bar"><span style="width: <?= round($t['revenue'] / $maxType * 100) ?>%"></span></span>
        <span class="bar-value"><?= number_format($t['kg'], 1) ?> kg</span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<h2>Top farmers by revenue</h2>
<table class="data-table">
  <tr><th>Farmer</th><th>Farm</th><th>Paid orders</th><th>Revenue</th></tr>
  <?php foreach ($topFarmers as $f): ?>
    <tr><td><?= e($f['full_name']) ?></td><td><?= e($f['business_name'] ?: '-') ?></td><td><?= (int) $f['orders'] ?></td><td><?= format_money($f['revenue']) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$topFarmers): ?><tr><td colspan="4" class="muted">No data.</td></tr><?php endif; ?>
</table>

<h2>AI Snap &amp; Detect usage</h2>
<table class="data-table">
  <tr><th>Result</th><th>Diagnoses</th><th>Avg. confidence</th><th>Low-confidence flags</th></tr>
  <?php foreach ($aiStats as $a): ?>
    <tr><td><?= e($a['disease']) ?></td><td><?= (int) $a['total'] ?></td><td><?= e($a['avg_conf']) ?>%</td><td><?= (int) $a['flagged'] ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$aiStats): ?><tr><td colspan="4" class="muted">No diagnoses.</td></tr><?php endif; ?>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
