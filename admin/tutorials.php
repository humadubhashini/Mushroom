<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin', 'expert']);

$stmt = $pdo->query(
    'SELECT t.*, c.name AS category_name FROM tutorials t LEFT JOIN tutorial_categories c ON c.id = t.category_id
     ORDER BY t.created_at DESC'
);
$tutorials = $stmt->fetchAll();

$pageTitle = 'Manage Tutorials';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center;">
  <h1>Manage Knowledge Hub Tutorials</h1>
  <a href="<?= BASE_URL ?>/admin/add_tutorial.php" class="btn">+ Add Tutorial</a>
</div>

<table class="data-table">
  <tr><th>Title</th><th>Category</th><th>Added</th><th>Action</th></tr>
  <?php foreach ($tutorials as $t): ?>
    <tr>
      <td><?= e($t['title']) ?></td>
      <td><?= e($t['category_name'] ?? '-') ?></td>
      <td><?= format_date($t['created_at']) ?></td>
      <td>
        <a href="<?= BASE_URL ?>/admin/edit_tutorial.php?id=<?= (int) $t['id'] ?>" class="btn btn-small btn-outline">Edit</a>
        <form method="post" action="<?= BASE_URL ?>/admin/delete_tutorial.php" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
          <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete this tutorial?')">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
