<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$categoryId = (int) ($_GET['category'] ?? 0);
$search = trim($_GET['q'] ?? '');

$categories = $pdo->query('SELECT * FROM tutorial_categories ORDER BY name')->fetchAll();

$sql = 'SELECT t.*, c.name AS category_name FROM tutorials t LEFT JOIN tutorial_categories c ON c.id = t.category_id WHERE 1=1';
$params = [];
if ($categoryId) {
    $sql .= ' AND t.category_id = ?';
    $params[] = $categoryId;
}
if ($search !== '') {
    $sql .= ' AND (t.title LIKE ? OR t.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$sql .= ' ORDER BY t.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tutorials = $stmt->fetchAll();

$pageTitle = 'Knowledge Hub';
include __DIR__ . '/../includes/header.php';
?>

<h1>🎓 Knowledge Hub</h1>
<p class="muted">Expert video tutorials to guide you from house preparation to harvest.</p>

<form method="get" action="" style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:20px;">
  <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search tutorials..." style="padding:8px; flex:1; min-width:200px;">
  <select name="category" style="padding:8px;">
    <option value="0">All Categories</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn" style="margin-top:0;">Filter</button>
</form>

<?php if (!$tutorials): ?>
  <p class="muted">No tutorials found.</p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($tutorials as $t): ?>
      <div class="tutorial-card">
        <img src="<?= $t['thumbnail'] ? BASE_URL . '/assets/uploads/tutorials/' . e($t['thumbnail']) : 'https://placehold.co/400x220?text=Tutorial' ?>" alt="<?= e($t['title']) ?>">
        <div class="body">
          <span class="badge badge-active"><?= e($t['category_name'] ?? 'General') ?></span>
          <h3 style="margin: 8px 0 4px;"><?= e($t['title']) ?></h3>
          <p class="muted"><?= e(mb_strimwidth($t['description'] ?? '', 0, 90, '...')) ?></p>
          <a href="<?= BASE_URL ?>/knowledge/view.php?id=<?= (int) $t['id'] ?>" class="btn btn-small">Watch</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
