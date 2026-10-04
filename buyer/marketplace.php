<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$search = trim($_GET['q'] ?? '');
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$location = trim($_GET['location'] ?? '');
$minQty = $_GET['min_qty'] ?? '';

// Fair ranking (SRS 6.2): results are ordered by listing date only, never by seller.
$sql = "SELECT l.*, u.full_name AS farmer_name, u.address AS farmer_address,
               (SELECT AVG(r.rating) FROM reviews r WHERE r.farmer_id = l.farmer_id) AS farmer_rating
        FROM listings l JOIN users u ON u.id = l.farmer_id
        WHERE l.status = 'active' AND u.status = 'active'";
$params = [];

if ($search !== '') {
    $sql .= ' AND l.mushroom_type LIKE ?';
    $params[] = '%' . $search . '%';
}
if ($location !== '') {
    $sql .= ' AND (l.location LIKE ? OR u.address LIKE ?)';
    $params[] = '%' . $location . '%';
    $params[] = '%' . $location . '%';
}
if (is_numeric($minQty)) {
    $sql .= ' AND l.quantity_kg >= ?';
    $params[] = $minQty;
}
if (is_numeric($minPrice)) {
    $sql .= ' AND l.price_per_kg >= ?';
    $params[] = $minPrice;
}
if (is_numeric($maxPrice)) {
    $sql .= ' AND l.price_per_kg <= ?';
    $params[] = $maxPrice;
}
$sql .= ' ORDER BY l.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$pageTitle = 'Marketplace';
include __DIR__ . '/../includes/header.php';
?>

<h1>Marketplace</h1>

<form class="card" method="get" action="" style="display:flex; gap:14px; flex-wrap:wrap; align-items:flex-end;">
  <div>
    <label style="font-weight:600; font-size:0.9rem;">Mushroom Type</label><br>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="e.g. Oyster" style="padding:8px;">
  </div>
  <div>
    <label style="font-weight:600; font-size:0.9rem;">Location</label><br>
    <input type="text" name="location" value="<?= e($location) ?>" placeholder="e.g. Gampaha" style="padding:8px; width:140px;">
  </div>
  <div>
    <label style="font-weight:600; font-size:0.9rem;">Min Quantity (kg)</label><br>
    <input type="number" name="min_qty" value="<?= e($minQty) ?>" style="padding:8px; width:110px;">
  </div>
  <div>
    <label style="font-weight:600; font-size:0.9rem;">Min Price (Rs./kg)</label><br>
    <input type="number" name="min_price" value="<?= e($minPrice) ?>" style="padding:8px; width:120px;">
  </div>
  <div>
    <label style="font-weight:600; font-size:0.9rem;">Max Price (Rs./kg)</label><br>
    <input type="number" name="max_price" value="<?= e($maxPrice) ?>" style="padding:8px; width:120px;">
  </div>
  <button type="submit" class="btn" style="margin-top:0;">Search</button>
  <a href="?" class="btn btn-outline" style="margin-top:0;">Clear</a>
</form>

<?php if (!$listings): ?>
  <p class="muted">No listings match your search.</p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($listings as $l): ?>
      <div class="listing-card">
        <img src="<?= $l['image'] ? BASE_URL . '/assets/uploads/listings/' . e($l['image']) : 'https://placehold.co/400x260?text=Mushroom' ?>" alt="<?= e($l['mushroom_type']) ?>">
        <div class="body">
          <h3 style="margin: 0 0 4px;"><?= e($l['mushroom_type']) ?></h3>
          <p class="muted">by <?= e($l['farmer_name']) ?>
            <?php if ($l['farmer_rating']): ?><span class="stars">★</span> <?= number_format($l['farmer_rating'], 1) ?><?php endif; ?>
            <?php if ($l['location'] ?: $l['farmer_address']): ?><br>📍 <?= e($l['location'] ?: $l['farmer_address']) ?><?php endif; ?></p>
          <p class="price"><?= format_money($l['price_per_kg']) ?> / kg</p>
          <p class="muted">Available: <?= e($l['quantity_kg']) ?> kg</p>
          <a href="<?= BASE_URL ?>/buyer/listing_view.php?id=<?= (int) $l['id'] ?>" class="btn btn-small">View &amp; Order</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
