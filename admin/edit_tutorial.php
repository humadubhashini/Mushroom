<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM tutorials WHERE id = ?');
$stmt->execute([$id]);
$tutorial = $stmt->fetch();
if (!$tutorial) {
    flash('error', 'Tutorial not found.');
    redirect('/admin/tutorials.php');
}

$categories = $pdo->query('SELECT * FROM tutorial_categories ORDER BY name')->fetchAll();
$diseases = $pdo->query('SELECT * FROM disease_types ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $videoUrl = trim($_POST['video_url'] ?? '');
    $categoryId = $_POST['category_id'] ?: null;
    $relatedDiseaseId = $_POST['related_disease_id'] ?: null;

    $errors = [];
    if ($title === '') $errors[] = 'Title is required.';
    if (!filter_var($videoUrl, FILTER_VALIDATE_URL)) $errors[] = 'A valid video URL is required.';

    $thumbError = null;
    $thumbFile = handle_image_upload('thumbnail', UPLOAD_TUTORIALS, $thumbError);
    if ($thumbError) $errors[] = $thumbError;

    if (!$errors) {
        $thumbnail = $thumbFile ?: $tutorial['thumbnail'];
        $pdo->prepare(
            'UPDATE tutorials SET category_id=?, title=?, description=?, video_url=?, thumbnail=?, related_disease_id=? WHERE id=?'
        )->execute([$categoryId, $title, $description ?: null, $videoUrl, $thumbnail, $relatedDiseaseId, $id]);
        flash('success', 'Tutorial updated.');
        redirect('/admin/tutorials.php');
    } else {
        flash('error', implode(' ', $errors));
    }
}

$pageTitle = 'Edit Tutorial';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Edit Tutorial</h1>
  <form class="stacked" method="post" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <label>Title</label>
    <input type="text" name="title" value="<?= e($tutorial['title']) ?>" required>

    <label>Description</label>
    <textarea name="description"><?= e($tutorial['description']) ?></textarea>

    <label>Video Embed URL</label>
    <input type="text" name="video_url" value="<?= e($tutorial['video_url']) ?>" required>

    <label>Category</label>
    <select name="category_id">
      <option value="">-- None --</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $tutorial['category_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Related Disease</label>
    <select name="related_disease_id">
      <option value="">-- None --</option>
      <?php foreach ($diseases as $d): ?>
        <option value="<?= (int) $d['id'] ?>" <?= $tutorial['related_disease_id'] == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Replace Thumbnail (optional)</label>
    <?php if ($tutorial['thumbnail']): ?>
      <img src="<?= BASE_URL ?>/assets/uploads/tutorials/<?= e($tutorial['thumbnail']) ?>" style="max-width:160px; display:block; margin-bottom:8px; border-radius:8px;">
    <?php endif; ?>
    <input type="file" name="thumbnail" accept="image/*">

    <button type="submit" class="btn">Save Changes</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
