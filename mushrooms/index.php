<?php
/** "Our Mushrooms" - catalogue of the mushroom varieties sold on the platform. */
require_once __DIR__ . '/../includes/bootstrap.php';

$types = $pdo->query(
    "SELECT m.*,
            (SELECT COUNT(*) FROM listings l WHERE l.mushroom_type_id = m.id AND l.status = 'active') AS listing_count,
            (SELECT MIN(l.price_per_kg) FROM listings l WHERE l.mushroom_type_id = m.id AND l.status = 'active') AS lowest_price
     FROM mushroom_types m WHERE m.is_active = 1 ORDER BY m.sort_order, m.name"
)->fetchAll();

$pageTitle = t('mush.title');
include __DIR__ . '/../includes/header.php';
?>

<h1>🍄 <?= te('mush.title') ?></h1>
<p class="muted"><?= te('mush.intro') ?></p>

<div class="mushroom-grid">
  <?php foreach ($types as $m): ?>
    <a class="mushroom-card" href="<?= BASE_URL ?>/mushrooms/view.php?id=<?= (int) $m['id'] ?>">
      <div class="pic"><?= mushroom_picture($m, 130) ?></div>
      <div class="body">
        <h3><?= e(tr_field($m, 'name')) ?></h3>
        <div class="sci"><?= e($m['scientific_name']) ?></div>
        <span class="price-tag"><?= format_money($m['price_per_kg']) ?> <?= te('common.per_kg') ?></span>
        <p class="muted" style="margin:8px 0 0; font-size:0.88rem;">
          <?= $m['listing_count'] ? te('mush.farmers_selling', ['n' => (int) $m['listing_count']]) : te('mush.none_selling') ?>
        </p>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<p class="muted" style="margin-top:20px; font-size:0.88rem;">* <?= te('mush.price_note') ?></p>

<?php include __DIR__ . '/../includes/footer.php'; ?>
