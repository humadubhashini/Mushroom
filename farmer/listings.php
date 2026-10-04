<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];

$stmt = $pdo->prepare(
    "SELECT l.*, " . LISTING_TYPE_COLUMNS . " FROM listings l
     LEFT JOIN mushroom_types mt ON mt.id = l.mushroom_type_id
     WHERE l.farmer_id = ? AND l.status != 'removed' ORDER BY l.created_at DESC, l.id DESC"
);
$stmt->execute([$farmerId]);
$listings = $stmt->fetchAll();

$pageTitle = t('nav.my_listings');
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
  <h1><?= te('nav.my_listings') ?></h1>
  <a href="<?= BASE_URL ?>/farmer/add_listing.php" class="btn">+ <?= te('listing.new_title') ?></a>
</div>

<?php if (!$listings): ?>
  <p class="muted"><?= te('listing.none') ?></p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($listings as $l): ?>
      <div class="listing-card">
        <?= listing_picture($l) ?>
        <div class="body">
          <span class="badge badge-<?= e($l['status']) ?>"><?= e(status_label($l['status'])) ?></span>
          <h3 style="margin: 8px 0 4px;"><?= e(listing_title($l)) ?></h3>
          <p class="price"><?= format_money($l['price_per_kg']) ?> <?= te('common.per_kg') ?></p>
          <p class="muted"><?= te('common.available', ['qty' => $l['quantity_kg']]) ?><?= $l['location'] ? ' &middot; 📍 ' . e($l['location']) : '' ?></p>
          <a href="<?= BASE_URL ?>/farmer/edit_listing.php?id=<?= (int) $l['id'] ?>" class="btn btn-small btn-outline"><?= te('common.edit') ?></a>
          <form method="post" action="<?= BASE_URL ?>/farmer/delete_listing.php" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
            <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('<?= te('listing.confirm_remove') ?>')"><?= te('common.remove') ?></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
