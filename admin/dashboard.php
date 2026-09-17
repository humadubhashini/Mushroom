<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$farmerCount = $pdo->query("SELECT COUNT(*) c FROM users WHERE role = 'farmer'")->fetch()['c'];
$buyerCount = $pdo->query("SELECT COUNT(*) c FROM users WHERE role = 'buyer'")->fetch()['c'];
$listingCount = $pdo->query("SELECT COUNT(*) c FROM listings WHERE status != 'removed'")->fetch()['c'];
$orderCount = $pdo->query('SELECT COUNT(*) c FROM orders')->fetch()['c'];
$revenue = $pdo->query("SELECT COALESCE(SUM(total_price),0) s FROM orders WHERE status IN ('paid','completed')")->fetch()['s'];
$diagnosisCount = $pdo->query('SELECT COUNT(*) c FROM diagnoses')->fetch()['c'];
$lowConfidenceCount = $pdo->query('SELECT COUNT(*) c FROM diagnoses WHERE low_confidence_flag = 1')->fetch()['c'];

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<h1>Admin Dashboard</h1>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int) $farmerCount ?></div><div class="label">Farmers</div></div>
  <div class="stat-card"><div class="num"><?= (int) $buyerCount ?></div><div class="label">Buyers</div></div>
  <div class="stat-card"><div class="num"><?= (int) $listingCount ?></div><div class="label">Active Listings</div></div>
  <div class="stat-card"><div class="num"><?= (int) $orderCount ?></div><div class="label">Total Orders</div></div>
  <div class="stat-card"><div class="num"><?= format_money($revenue) ?></div><div class="label">Platform GMV</div></div>
  <div class="stat-card"><div class="num"><?= (int) $diagnosisCount ?></div><div class="label">AI Diagnoses (<?= (int) $lowConfidenceCount ?> low-confidence)</div></div>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(200px,1fr));">
  <a href="<?= BASE_URL ?>/admin/users.php" class="btn">Manage Users</a>
  <a href="<?= BASE_URL ?>/admin/listings.php" class="btn btn-outline">Manage Listings</a>
  <a href="<?= BASE_URL ?>/admin/tutorials.php" class="btn btn-outline">Manage Tutorials</a>
  <a href="<?= BASE_URL ?>/admin/diagnoses.php" class="btn btn-outline">AI Diagnosis Log</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
