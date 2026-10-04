<?php
/** Review and update treatment / pesticide recommendations (FR-REC.3). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin', 'expert']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $pesticide = trim($_POST['pesticide_recommendation'] ?? '');

    if ($treatment === '' || $pesticide === '') {
        flash('error', 'Treatment and pesticide recommendation cannot be empty.');
    } else {
        $pdo->prepare('UPDATE disease_types SET description = ?, treatment = ?, pesticide_recommendation = ? WHERE id = ?')
            ->execute([$description, $treatment, $pesticide, $id]);
        log_admin_action('Updated treatment content for disease #' . $id);
        flash('success', 'Treatment recommendation updated.');
    }
    redirect('/admin/diseases.php');
}

$diseases = $pdo->query('SELECT * FROM disease_types ORDER BY id')->fetchAll();

$pageTitle = 'Treatment Content';
include __DIR__ . '/../includes/header.php';
?>

<h1>💊 Treatment &amp; Pesticide Recommendations</h1>
<p class="muted">These recommendations are shown to farmers immediately after a Snap &amp; Detect diagnosis. Agricultural experts should validate them.</p>

<?php foreach ($diseases as $d): ?>
  <div class="card" style="margin-bottom:16px;">
    <h2 class="mt-0"><?= e($d['name']) ?></h2>
    <form class="stacked" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
      <label>Description / Symptoms</label>
      <textarea name="description"><?= e($d['description']) ?></textarea>
      <label>Recommended Treatment Actions</label>
      <textarea name="treatment" required><?= e($d['treatment']) ?></textarea>
      <label>Pesticide / Remedy &amp; Usage Guidance</label>
      <textarea name="pesticide_recommendation" required><?= e($d['pesticide_recommendation']) ?></textarea>
      <button type="submit" class="btn btn-small">Save</button>
    </form>
  </div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
