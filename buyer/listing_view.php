<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT l.*, u.full_name AS farmer_name, u.address AS farmer_address, u.phone AS farmer_phone
     FROM listings l JOIN users u ON u.id = l.farmer_id
     WHERE l.id = ? AND l.status = 'active'"
);
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) {
    flash('error', 'This listing is no longer available.');
    redirect('/buyer/marketplace.php');
}

$pageTitle = $listing['mushroom_type'];
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
  <div style="display:flex; gap:24px; flex-wrap:wrap;">
    <img src="<?= $listing['image'] ? BASE_URL . '/assets/uploads/listings/' . e($listing['image']) : 'https://placehold.co/400x300?text=Mushroom' ?>" style="width:320px; max-width:100%; border-radius:8px; object-fit:cover;">
    <div style="flex:1; min-width:260px;">
      <h1 class="mt-0"><?= e($listing['mushroom_type']) ?></h1>
      <p class="price" style="font-size:1.4rem;"><?= format_money($listing['price_per_kg']) ?> / kg</p>
      <p class="muted">Sold by <strong><?= e($listing['farmer_name']) ?></strong>, <?= e($listing['farmer_address'] ?: 'Sri Lanka') ?></p>
      <p><?= nl2br(e($listing['description'] ?: 'No additional description provided.')) ?></p>
      <p class="muted">Available quantity: <?= e($listing['quantity_kg']) ?> kg</p>
      <p class="muted">Harvest date: <?= format_date($listing['harvest_date']) ?></p>

      <?php if (is_logged_in() && current_user()['role'] === 'buyer'): ?>
        <form class="stacked" method="post" action="<?= BASE_URL ?>/buyer/place_order.php">
          <?= csrf_field() ?>
          <input type="hidden" name="listing_id" value="<?= (int) $listing['id'] ?>">
          <label>Quantity to Order (kg)</label>
          <input type="number" step="0.1" min="0.1" max="<?= e($listing['quantity_kg']) ?>" name="quantity_kg" required>
          <label>Preferred Delivery Date</label>
          <input type="date" name="delivery_date" min="<?= date('Y-m-d') ?>">
          <button type="submit" class="btn">Place Order</button>
        </form>
      <?php elseif (!is_logged_in()): ?>
        <p><a href="<?= BASE_URL ?>/auth/login.php" class="btn">Log in as a buyer to order</a></p>
      <?php else: ?>
        <p class="muted">Only registered buyers (hotels/restaurants) can place orders.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
