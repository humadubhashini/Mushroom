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

// Convert normal YouTube links to embeddable ones; anything else is shown as a link.
$embedUrl = null;
$url = $tutorial['video_url'];
if (preg_match('~youtube\.com/embed/[\w-]+~', $url)) {
    $embedUrl = $url;
} elseif (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{11})~', $url, $m)) {
    $embedUrl = 'https://www.youtube.com/embed/' . $m[1];
} elseif (preg_match('~\.(mp4|webm)$~i', $url)) {
    $embedUrl = 'video';
}

$related = $pdo->prepare('SELECT id, title FROM tutorials WHERE category_id = ? AND id != ? LIMIT 4');
$related->execute([$tutorial['category_id'], $tutorial['id']]);
$related = $related->fetchAll();

$pageTitle = $tutorial['title'];
include __DIR__ . '/../includes/header.php';
?>

<a href="<?= BASE_URL ?>/knowledge/index.php" class="muted">&larr; Back to Knowledge Hub</a>

<div class="card" style="margin-top:14px;">
  <span class="badge badge-active"><?= e($tutorial['category_name'] ?? 'General') ?></span>
  <h1><?= e($tutorial['title']) ?></h1>
  <?php if ($embedUrl === 'video'): ?>
    <video src="<?= e($url) ?>" controls style="width:100%; border-radius:8px; background:#000;"></video>
  <?php elseif ($embedUrl): ?>
    <div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; border-radius:8px; background:#000;">
      <iframe src="<?= e($embedUrl) ?>" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allowfullscreen></iframe>
    </div>
  <?php else: ?>
    <a href="<?= e($url) ?>" target="_blank" rel="noopener" class="btn">▶ Watch video</a>
  <?php endif; ?>
  <p style="margin-top:16px;"><?= nl2br(e($tutorial['description'])) ?></p>
</div>

<?php if ($related): ?>
  <h2>More in <?= e($tutorial['category_name'] ?? 'this category') ?></h2>
  <ul>
    <?php foreach ($related as $r): ?>
      <li><a href="<?= BASE_URL ?>/knowledge/view.php?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
