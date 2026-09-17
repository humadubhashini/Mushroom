<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT t.*, c.name AS category_name FROM tutorials t LEFT JOIN tutorial_categories c ON c.id = t.category_id WHERE t.id = ?'
);
$stmt->execute([$id]);
$tutorial = $stmt->fetch();

if (!$tutorial) {
    flash('error', 'Tutorial not found.');
    redirect('/knowledge/index.php');
}

$pageTitle = $tutorial['title'];
include __DIR__ . '/../includes/header.php';
?>

<a href="<?= BASE_URL ?>/knowledge/index.php" class="muted">&larr; Back to Knowledge Hub</a>

<div class="card" style="margin-top:14px;">
  <span class="badge badge-active"><?= e($tutorial['category_name'] ?? 'General') ?></span>
  <h1><?= e($tutorial['title']) ?></h1>
  <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:8px; background:#000;">
    <iframe src="<?= e($tutorial['video_url']) ?>" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allowfullscreen></iframe>
  </div>
  <p style="margin-top:16px;"><?= nl2br(e($tutorial['description'])) ?></p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
