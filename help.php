<?php
/** In-app quick-start guides for farmers, buyers and administrators (SRS 2.6). */
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = t('footer.help');
include __DIR__ . '/includes/header.php';

$sections = [
    ['👨‍🌾 ' . t('help.farmers'), [
        [t('help.sell'), 'ol', ['help.f1', 'help.f2', 'help.f3', 'help.f4', 'help.f5']],
        ['📷 ' . t('help.snap'), 'ol', ['help.s1', 'help.s2', 'help.s3']],
    ], 'help.snap_note'],
    ['🏨 ' . t('help.buyers'), [
        [null, 'ol', ['help.b1', 'help.b2', 'help.b3', 'help.b4', 'help.b5', 'help.b6']],
    ], null],
    ['🛠 ' . t('help.admins'), [
        [null, 'ul', ['help.a1', 'help.a2', 'help.a3', 'help.a4', 'help.a5', 'help.a6', 'help.a7']],
    ], null],
];
?>

<h1>❓ <?= te('footer.help') ?></h1>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); align-items:start;">
  <?php foreach ($sections as [$title, $blocks, $note]): ?>
    <div class="card">
      <h2 class="mt-0"><?= e($title) ?></h2>
      <?php foreach ($blocks as [$heading, $tag, $items]): ?>
        <?php if ($heading): ?><h3><?= e($heading) ?></h3><?php endif; ?>
        <<?= $tag ?>>
          <?php foreach ($items as $key): ?><li><?= te($key) ?></li><?php endforeach; ?>
        </<?= $tag ?>>
      <?php endforeach; ?>
      <?php if ($note): ?><p class="muted"><?= te($note) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<p><a href="<?= BASE_URL ?>/auth/register.php" class="btn"><?= te('nav.register') ?></a></p>

<?php include __DIR__ . '/includes/footer.php'; ?>
