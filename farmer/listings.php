<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];

$stmt = $pdo->prepare("SELECT * FROM listings WHERE farmer_id = ? AND status != 'removed' ORDER BY created_at DESC");
$stmt->execute([$farmerId]);
$listings = $stmt->fetchAll();

$pageTitle = 'My Listings';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center;">
  <h1>My Listings</h1>
  <a href="<?= BASE_URL ?>/farmer/add_listing.php" class="btn">+ New Listing</a>
</div>

<?php if (!$listings): ?>
  <p class="muted">You have no listings yet. Create one to start selling directly to hotels and restaurants.</p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($listings as $l): ?>
      <div class="listing-card">
        <img src="<?= $l['image'] ? BASE_URL . '/assets/uploads/listings/' . e($l['image']) : 'https://placehold.co/400x260?text=Mushroom' ?>" alt="<?= e($l['mushroom_type']) ?>">
        <div class="body">
          <span class="badge badge-<?= e($l['status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $l['status']))) ?></span>
          <h3 style="margin: 8px 0 4px;"><?= e($l['mushroom_type']) ?></h3>
          <p class="price"><?= format_money($l['price_per_kg']) ?> / kg</p>
          <p class="muted">Available: <?= e($l['quantity_kg']) ?> kg<?= $l['location'] ? ' &middot; 📍 ' . e($l['location']) : '' ?></p>
          <a href="<?= BASE_URL ?>/farmer/edit_listing.php?id=<?= (int) $l['id'] ?>" class="btn btn-small btn-outline">Edit</a>
          <form method="post" action="<?= BASE_URL ?>/farmer/delete_listing.php" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
            <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Remove this listing?')">Remove</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
