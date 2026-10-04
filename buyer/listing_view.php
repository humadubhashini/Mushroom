<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT l.*, u.full_name AS farmer_name, u.address AS farmer_address, u.phone AS farmer_phone, " . LISTING_TYPE_COLUMNS . "
     FROM listings l JOIN users u ON u.id = l.farmer_id
     LEFT JOIN mushroom_types mt ON mt.id = l.mushroom_type_id
     WHERE l.id = ? AND l.status = 'active'"
);
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) {
    flash('error', t('market.unavailable'));
    redirect('/buyer/marketplace.php');
}

// Supplier reviews (FR-MKT.7)
$reviews = $pdo->prepare(
    'SELECT r.*, u.full_name AS buyer_name, u.business_name FROM reviews r JOIN users u ON u.id = r.buyer_id
     WHERE r.farmer_id = ? ORDER BY r.created_at DESC LIMIT 5'
);
$reviews->execute([$listing['farmer_id']]);
$reviews = $reviews->fetchAll();
$avgRating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : null;

$pageTitle = listing_title($listing);
include __DIR__ . '/../includes/header.php';
?>

<div class="card">
  <div style="display:flex; gap:24px; flex-wrap:wrap;">
    <div style="width:320px; max-width:100%; border-radius:8px; overflow:hidden;" class="listing-photo"><?= listing_picture($listing, 200) ?></div>
    <div style="flex:1; min-width:260px;">
      <h1 class="mt-0"><?= e(listing_title($listing)) ?></h1>
      <p class="price" style="font-size:1.4rem;"><?= format_money($listing['price_per_kg']) ?> <?= te('common.per_kg') ?></p>
      <p class="muted"><?= te('common.sold_by') ?>: <strong><?= e($listing['farmer_name']) ?></strong>, <?= e($listing['location'] ?: ($listing['farmer_address'] ?: 'Sri Lanka')) ?>
        <?php if ($avgRating): ?><br><span class="stars"><?= str_repeat('★', (int) round($avgRating)) ?></span> <?= number_format($avgRating, 1) ?>/5<?php endif; ?></p>
      <?php if ($listing['harvest_date']): ?><p class="muted"><?= te('listing.harvested') ?>: <?= format_date($listing['harvest_date']) ?></p><?php endif; ?>
      <p><?= nl2br(e($listing['description'] ?: t('listing.no_description'))) ?></p>
      <p class="muted"><?= te('common.available', ['qty' => $listing['quantity_kg']]) ?></p>
      <?php if ($listing['mushroom_type_id']): ?>
        <p><a href="<?= BASE_URL ?>/mushrooms/view.php?id=<?= (int) $listing['mushroom_type_id'] ?>"><?= te('listing.about_type') ?> &rarr;</a></p>
      <?php endif; ?>

      <?php if (is_logged_in() && current_user()['role'] === 'buyer'): ?>
        <form class="stacked" method="post" action="<?= BASE_URL ?>/buyer/place_order.php">
          <?= csrf_field() ?>
          <input type="hidden" name="listing_id" value="<?= (int) $listing['id'] ?>">
          <label><?= te('listing.qty_to_order') ?></label>
          <input type="number" step="0.1" min="0.1" max="<?= e($listing['quantity_kg']) ?>" name="quantity_kg" required id="orderQty">
          <p class="muted" id="orderTotal" style="margin:4px 0 0;"></p>
          <label><?= te('listing.delivery_date') ?></label>
          <input type="date" name="delivery_date" min="<?= date('Y-m-d') ?>">
          <button type="submit" class="btn"><?= te('listing.place_order') ?></button>
        </form>
        <script>
          document.getElementById('orderQty').addEventListener('input', function () {
            var total = (parseFloat(this.value) || 0) * <?= (float) $listing['price_per_kg'] ?>;
            document.getElementById('orderTotal').textContent = total ? '<?= te('common.total') ?>: Rs. ' + total.toLocaleString('en-LK', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '';
          });
        </script>
      <?php elseif (!is_logged_in()): ?>
        <p><a href="<?= BASE_URL ?>/auth/login.php" class="btn"><?= te('listing.login_to_order') ?></a></p>
      <?php else: ?>
        <p class="muted"><?= te('listing.buyers_only') ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($reviews): ?>
  <h2><?= te('listing.reviews') ?></h2>
  <?php foreach ($reviews as $r): ?>
    <div class="card" style="margin-bottom:10px;">
      <span class="stars"><?= str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']) ?></span>
      <strong><?= e($r['business_name'] ?: $r['buyer_name']) ?></strong>
      <span class="muted"> &middot; <?= format_date($r['created_at']) ?></span>
      <?php if ($r['comment']): ?><p style="margin:6px 0 0;"><?= e($r['comment']) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
