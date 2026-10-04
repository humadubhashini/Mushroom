<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$categoryId = (int) ($_GET['category'] ?? 0);
$search = trim($_GET['q'] ?? '');

$categories = $pdo->query('SELECT * FROM tutorial_categories ORDER BY id')->fetchAll();

$sql = 'SELECT t.*, c.name AS category_name, c.name_si AS category_name_si, c.name_ta AS category_name_ta FROM tutorials t LEFT JOIN tutorial_categories c ON c.id = t.category_id WHERE 1=1';
$params = [];
if ($categoryId) {
    $sql .= ' AND t.category_id = ?';
    $params[] = $categoryId;
}
if ($search !== '') {
    $sql .= ' AND (t.title LIKE ? OR t.description LIKE ? OR t.title_si LIKE ? OR t.title_ta LIKE ?)';
    array_push($params, '%' . $search . '%', '%' . $search . '%', '%' . $search . '%', '%' . $search . '%');
}
$sql .= ' ORDER BY t.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tutorials = $stmt->fetchAll();

$pageTitle = t('nav.knowledge');
include __DIR__ . '/../includes/header.php';
?>

<h1>🎓 <?= te('nav.knowledge') ?></h1>
<p class="muted"><?= te('kh.intro') ?></p>

<form method="get" action="" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;">
  <input type="text" name="q" value="<?= e($search) ?>" placeholder="<?= te('kh.search_ph') ?>" style="padding:8px; flex:1; min-width:200px;">
  <select name="category" style="padding:8px;">
    <option value="0"><?= te('kh.all_categories') ?></option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>><?= e(tr_field($c, 'name')) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn" style="margin-top:0;"><?= te('common.search') ?></button>
</form>

<?php if (!$tutorials): ?>
  <p class="muted"><?= te('kh.none') ?></p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($tutorials as $t): ?>
      <div class="tutorial-card">
        <?php if ($t['thumbnail']): ?>
          <img src="<?= BASE_URL ?>/assets/uploads/tutorials/<?= e($t['thumbnail']) ?>" alt="">
        <?php else: ?>
          <div class="pic-fallback" style="height:160px; min-height:0; font-size:3rem;">🎬</div>
        <?php endif; ?>
        <div class="body">
          <span class="badge badge-active"><?= e($t['category_name'] ? tr_field($t, 'category_name') : t('kh.general')) ?></span>
          <h3 style="margin: 8px 0 4px;"><?= e(tr_field($t, 'title')) ?></h3>
          <p class="muted"><?= e(mb_strimwidth(tr_field($t, 'description'), 0, 110, '...')) ?></p>
          <a href="<?= BASE_URL ?>/knowledge/view.php?id=<?= (int) $t['id'] ?>" class="btn btn-small"><?= te('diag.watch') ?></a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
