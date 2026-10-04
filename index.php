<?php
require_once __DIR__ . '/includes/bootstrap.php';

$featured = $pdo->query('SELECT * FROM mushroom_types WHERE is_active = 1 ORDER BY sort_order LIMIT 4')->fetchAll();

$pageTitle = t('home.title');
include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <h1><?= te('home.hero_title') ?></h1>
  <p><?= te('home.hero_text') ?></p>
  <a href="<?= BASE_URL ?>/mushrooms/index.php" class="btn"><?= te('home.see_mushrooms') ?></a>
  <a href="<?= BASE_URL ?>/buyer/marketplace.php" class="btn"><?= te('home.browse') ?></a>
  <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline" style="color:#fff; border-color:#fff;"><?= te('home.join') ?></a>
</section>

<div class="feature-grid">
  <div class="card">
    <h3>🛒 <?= te('home.f1_title') ?></h3>
    <p class="muted"><?= te('home.f1_text') ?></p>
  </div>
  <div class="card">
    <h3>🤖 <?= te('home.f2_title') ?></h3>
    <p class="muted"><?= te('home.f2_text') ?></p>
  </div>
  <div class="card">
    <h3>💳 <?= te('home.f3_title') ?></h3>
    <p class="muted"><?= te('home.f3_text') ?></p>
  </div>
  <div class="card">
    <h3>🎓 <?= te('home.f4_title') ?></h3>
    <p class="muted"><?= te('home.f4_text') ?></p>
  </div>
</div>

<div style="display:flex; justify-content:space-between; align-items:baseline; flex-wrap:wrap;">
  <h2>🍄 <?= te('nav.mushrooms') ?></h2>
  <a href="<?= BASE_URL ?>/mushrooms/index.php"><?= te('home.all_mushrooms') ?> &rarr;</a>
</div>
<div class="mushroom-grid">
  <?php foreach ($featured as $m): ?>
    <a class="mushroom-card" href="<?= BASE_URL ?>/mushrooms/view.php?id=<?= (int) $m['id'] ?>">
      <div class="pic"><?= mushroom_picture($m, 120) ?></div>
      <div class="body">
        <h3><?= e(tr_field($m, 'name')) ?></h3>
        <span class="price-tag"><?= format_money($m['price_per_kg']) ?> <?= te('common.per_kg') ?></span>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
