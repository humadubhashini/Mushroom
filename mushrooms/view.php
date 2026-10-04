<?php
/** Details of one mushroom variety, its reference price per kg, and current farmer listings. */
require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM mushroom_types WHERE id = ? AND is_active = 1');
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) {
    flash('error', t('mush.not_found'));
    redirect('/mushrooms/index.php');
}

$listings = $pdo->prepare(
    "SELECT l.*, u.full_name AS farmer_name, u.address AS farmer_address
     FROM listings l JOIN users u ON u.id = l.farmer_id
     WHERE l.mushroom_type_id = ? AND l.status = 'active' AND u.status = 'active'
     ORDER BY l.price_per_kg ASC"
);
$listings->execute([$id]);
$listings = $listings->fetchAll();

$others = $pdo->prepare('SELECT * FROM mushroom_types WHERE id != ? AND is_active = 1 ORDER BY sort_order LIMIT 4');
$others->execute([$id]);
$others = $others->fetchAll();

$pageTitle = tr_field($m, 'name');
include __DIR__ . '/../includes/header.php';
?>

<a href="<?= BASE_URL ?>/mushrooms/index.php" class="muted">&larr; <?= te('mush.back') ?></a>

<div class="card" style="margin-top:14px;">
  <div class="mushroom-hero">
    <div class="pic"><?= mushroom_picture($m, 220) ?></div>
    <div style="flex:1; min-width:260px;">
      <h1 class="mt-0" style="margin-bottom:2px;"><?= e(tr_field($m, 'name')) ?></h1>
      <?php if (current_lang() !== 'en'): ?><div class="muted"><?= e($m['name']) ?></div><?php endif; ?>
      <div class="sci" style="font-style:italic; color:var(--muted);"><?= e($m['scientific_name']) ?></div>

      <p style="font-size:1.6rem; font-weight:700; color:var(--green-dark); margin:14px 0 0;">
        <?= format_money($m['price_per_kg']) ?> <span style="font-size:1rem; font-weight:normal;"><?= te('common.per_kg') ?></span>
      </p>
      <p class="muted" style="margin-top:0;"><?= te('common.price_per_kg') ?> &mdash; <?= te('mush.reference_price') ?></p>

      <p><?= nl2br(e(tr_field($m, 'description'))) ?></p>

      <?php if ($listings): ?>
        <a href="#buy" class="btn"><?= te('mush.buy_now') ?></a>
      <?php endif; ?>
    </div>
  </div>

  <div class="detail-grid">
    <div class="card">
      <h3>🌱 <?= te('mush.growing') ?></h3>
      <p><?= nl2br(e(tr_field($m, 'growing_info'))) ?></p>
    </div>
    <div class="card">
      <h3>🍳 <?= te('mush.uses') ?></h3>
      <p><?= nl2br(e(tr_field($m, 'uses'))) ?></p>
    </div>
  </div>
</div>

<h2 id="buy"><?= te('mush.available_from') ?></h2>
<?php if (!$listings): ?>
  <p class="muted"><?= te('mush.no_listings') ?></p>
<?php else: ?>
  <table class="data-table">
    <tr><th><?= te('common.farmer') ?></th><th><?= te('common.location') ?></th><th><?= te('common.price_per_kg') ?></th><th><?= te('mush.stock') ?></th><th></th></tr>
    <?php foreach ($listings as $l): ?>
      <tr>
        <td><?= e($l['farmer_name']) ?></td>
        <td><?= e($l['location'] ?: ($l['farmer_address'] ?: '-')) ?></td>
        <td><strong><?= format_money($l['price_per_kg']) ?></strong></td>
        <td><?= e($l['quantity_kg']) ?> kg</td>
        <td><a href="<?= BASE_URL ?>/buyer/listing_view.php?id=<?= (int) $l['id'] ?>" class="btn btn-small"><?= te('mush.order') ?></a></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php if ($others): ?>
  <h2><?= te('mush.other') ?></h2>
  <div class="mushroom-grid">
    <?php foreach ($others as $o): ?>
      <a class="mushroom-card" href="<?= BASE_URL ?>/mushrooms/view.php?id=<?= (int) $o['id'] ?>">
        <div class="pic"><?= mushroom_picture($o, 110) ?></div>
        <div class="body">
          <h3><?= e(tr_field($o, 'name')) ?></h3>
          <span class="price-tag"><?= format_money($o['price_per_kg']) ?> <?= te('common.per_kg') ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
