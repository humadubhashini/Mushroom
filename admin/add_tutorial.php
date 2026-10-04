<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin', 'expert']);

$categories = $pdo->query('SELECT * FROM tutorial_categories ORDER BY name')->fetchAll();
$diseases = $pdo->query('SELECT * FROM disease_types ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $videoUrl = trim($_POST['video_url'] ?? '');
    $categoryId = ($_POST['category_id'] ?? '') ?: null;
    $relatedDiseaseId = ($_POST['related_disease_id'] ?? '') ?: null;

    $errors = [];
    if ($title === '') $errors[] = 'Title is required.';
    if (!filter_var($videoUrl, FILTER_VALIDATE_URL)) $errors[] = 'A valid video URL (embed link) is required.';

    $thumbError = null;
    $thumbFile = handle_image_upload('thumbnail', UPLOAD_TUTORIALS, $thumbError);
    if ($thumbError) $errors[] = $thumbError;

    if (!$errors) {
        $pdo->prepare(
            'INSERT INTO tutorials (category_id, title, description, video_url, thumbnail, related_disease_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$categoryId, $title, $description ?: null, $videoUrl, $thumbFile, $relatedDiseaseId]);
        log_admin_action('Added tutorial: ' . $title);
        flash('success', 'Tutorial added.');
        redirect('/admin/tutorials.php');
    } else {
        flash('error', implode(' ', $errors));
    }
}

$pageTitle = 'Add Tutorial';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Add Tutorial</h1>
  <form class="stacked" method="post" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <label>Title</label>
    <input type="text" name="title" required>

    <label>Description</label>
    <textarea name="description"></textarea>

    <label>Video Embed URL</label>
    <input type="text" name="video_url" placeholder="https://www.youtube.com/embed/..." required>

    <label>Category</label>
    <select name="category_id">
      <option value="">-- None --</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Related Disease (for Snap &amp; Detect suggestions)</label>
    <select name="related_disease_id">
      <option value="">-- None --</option>
      <?php foreach ($diseases as $d): ?>
        <option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Thumbnail Image</label>
    <input type="file" name="thumbnail" accept="image/*">

    <button type="submit" class="btn">Add Tutorial</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
