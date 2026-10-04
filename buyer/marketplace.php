<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$search = trim($_GET['q'] ?? '');
$typeId = (int) ($_GET['type'] ?? 0);
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$location = trim($_GET['location'] ?? '');
$minQty = $_GET['min_qty'] ?? '';

$types = $pdo->query('SELECT * FROM mushroom_types WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

// Fair ranking (SRS 6.2): results are ordered by listing date only, never by seller.
$sql = "SELECT l.*, u.full_name AS farmer_name, u.address AS farmer_address, " . LISTING_TYPE_COLUMNS . ",
               (SELECT AVG(r.rating) FROM reviews r WHERE r.farmer_id = l.farmer_id) AS farmer_rating
        FROM listings l
        JOIN users u ON u.id = l.farmer_id
        LEFT JOIN mushroom_types mt ON mt.id = l.mushroom_type_id
        WHERE l.status = 'active' AND u.status = 'active'";
$params = [];

if ($typeId) {
    $sql .= ' AND l.mushroom_type_id = ?';
    $params[] = $typeId;
}
if ($search !== '') {
    $sql .= ' AND (l.mushroom_type LIKE ? OR mt.name_si LIKE ? OR mt.name_ta LIKE ?)';
    array_push($params, '%' . $search . '%', '%' . $search . '%', '%' . $search . '%');
}
if ($location !== '') {
    $sql .= ' AND (l.location LIKE ? OR u.address LIKE ?)';
    array_push($params, '%' . $location . '%', '%' . $location . '%');
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

$pageTitle = t('nav.marketplace');
include __DIR__ . '/../includes/header.php';
?>

<h1><?= te('nav.marketplace') ?></h1>

<form class="card filter-form" method="get" action="">
  <div>
    <label><?= te('mush.type') ?></label>
    <select name="type">
      <option value="0"><?= te('market.all_types') ?></option>
      <?php foreach ($types as $tp): ?>
        <option value="<?= (int) $tp['id'] ?>" <?= $typeId === (int) $tp['id'] ? 'selected' : '' ?>><?= e(tr_field($tp, 'name')) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label><?= te('common.search') ?></label>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= te('market.search_ph') ?>">
  </div>
  <div>
    <label><?= te('common.location') ?></label>
    <input type="text" name="location" value="<?= e($location) ?>" placeholder="<?= te('market.location_ph') ?>">
  </div>
  <div>
    <label><?= te('market.min_qty') ?></label>
    <input type="number" name="min_qty" value="<?= e($minQty) ?>">
  </div>
  <div>
    <label><?= te('market.min_price') ?></label>
    <input type="number" name="min_price" value="<?= e($minPrice) ?>">
  </div>
  <div>
    <label><?= te('market.max_price') ?></label>
    <input type="number" name="max_price" value="<?= e($maxPrice) ?>">
  </div>
  <div class="filter-actions">
    <button type="submit" class="btn"><?= te('common.search') ?></button>
    <a href="?" class="btn btn-outline"><?= te('common.clear') ?></a>
  </div>
</form>

<?php if (!$listings): ?>
  <p class="muted"><?= te('market.none') ?></p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($listings as $l): ?>
      <div class="listing-card">
        <?= listing_picture($l) ?>
        <div class="body">
          <h3 style="margin: 0 0 4px;"><?= e(listing_title($l)) ?></h3>
          <p class="muted"><?= te('common.by', ['name' => $l['farmer_name']]) ?>
            <?php if ($l['farmer_rating']): ?><span class="stars">★</span> <?= number_format($l['farmer_rating'], 1) ?><?php endif; ?>
            <?php if ($l['location'] ?: $l['farmer_address']): ?><br>📍 <?= e($l['location'] ?: $l['farmer_address']) ?><?php endif; ?></p>
          <p class="price"><?= format_money($l['price_per_kg']) ?> <?= te('common.per_kg') ?></p>
          <p class="muted"><?= te('common.available', ['qty' => $l['quantity_kg']]) ?></p>
          <a href="<?= BASE_URL ?>/buyer/listing_view.php?id=<?= (int) $l['id'] ?>" class="btn btn-small"><?= te('market.view_order') ?></a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
