<?php
/** Full details of a past Snap & Detect diagnosis. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');

$stmt = $pdo->prepare(
    'SELECT d.*, rd.name AS reviewed_name, rd.name_si AS reviewed_name_si, rd.name_ta AS reviewed_name_ta
     FROM diagnoses d LEFT JOIN disease_types rd ON rd.id = d.reviewed_disease_id
     WHERE d.id = ? AND d.farmer_id = ?'
);
$stmt->execute([(int) ($_GET['id'] ?? 0), current_user()['id']]);
$d = $stmt->fetch();
if (!$d) {
    redirect('/farmer/diagnosis_history.php');
}

// Show the expert-confirmed condition when there is one, otherwise the AI result.
$diseaseId = $d['reviewed_disease_id'] ?: $d['predicted_disease_id'];
$disease = null;
if ($diseaseId) {
    $q = $pdo->prepare('SELECT * FROM disease_types WHERE id = ?');
    $q->execute([$diseaseId]);
    $disease = $q->fetch();
}

$pageTitle = t('hist.title');
include __DIR__ . '/../includes/header.php';
?>

<a href="<?= BASE_URL ?>/farmer/diagnosis_history.php" class="muted">&larr; <?= te('hist.title') ?></a>

<div class="diagnosis-result <?= $d['low_confidence_flag'] && !$d['reviewed_by'] ? 'low-confidence' : '' ?>" style="margin-top:14px;">
  <div style="display:flex; gap:20px; flex-wrap:wrap;">
    <img src="<?= BASE_URL ?>/assets/uploads/diagnoses/<?= e($d['image']) ?>" alt="" style="width:220px; height:220px; object-fit:cover; border-radius:8px;">
    <div style="flex:1; min-width:240px;">
      <p class="muted" style="margin:0;"><?= format_date($d['created_at']) ?></p>
      <h2 class="mt-0" style="font-size:1.7rem;"><?= e($disease ? tr_field($disease, 'name') : '-') ?></h2>
      <p><?= te('diag.confidence') ?>: <strong><?= e($d['confidence']) ?>%</strong></p>
      <div class="confidence-bar"><div class="confidence-bar-fill" style="width: <?= e($d['confidence']) ?>%;"></div></div>
      <?php if ($d['reviewed_by']): ?>
        <div class="alert alert-info">👩‍🔬 <?= te('hist.expert') ?>:
          <strong><?= e(tr_field(['name' => $d['reviewed_name'], 'name_si' => $d['reviewed_name_si'], 'name_ta' => $d['reviewed_name_ta']], 'name')) ?></strong>
          <?php if ($d['expert_note']): ?><br><?= e($d['expert_note']) ?><?php endif; ?></div>
      <?php elseif ($d['low_confidence_flag']): ?>
        <p style="color:#b36b16;">⏳ <?= te('hist.waiting') ?></p>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($disease): ?>
    <div class="detail-grid">
      <div class="card"><h3>🔎 <?= te('diag.about') ?></h3><p><?= nl2br(e(tr_field($disease, 'description'))) ?></p></div>
      <div class="card"><h3>🩺 <?= te('diag.treatment') ?></h3><p><?= nl2br(e(tr_field($disease, 'treatment'))) ?></p></div>
      <div class="card"><h3>🧪 <?= te('diag.pesticide') ?></h3><p><?= nl2br(e(tr_field($disease, 'pesticide_recommendation'))) ?></p></div>
      <div class="card"><h3>🛡️ <?= te('diag.prevention') ?></h3><p><?= nl2br(e(tr_field($disease, 'prevention'))) ?></p></div>
    </div>
  <?php endif; ?>
  <div class="alert alert-info" style="margin:16px 0 0;">ℹ️ <?= te('diag.disclaimer') ?></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
