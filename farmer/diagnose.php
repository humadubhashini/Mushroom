<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/ai_classifier.php';
require_role('farmer');
$farmerId = current_user()['id'];

$result = null;
$disease = null;
$analysisError = null;
$relatedTutorial = null;

// All disease rows, keyed by English name, for translated labels.
$diseaseRows = [];
foreach ($pdo->query('SELECT * FROM disease_types')->fetchAll() as $row) {
    $diseaseRows[$row['name']] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $imageError = null;
    $imageFile = handle_image_upload('image', UPLOAD_DIAGNOSES, $imageError);

    if ($imageError) {
        flash('error', $imageError);
    } elseif (!$imageFile) {
        flash('error', t('diag.choose_photo'));
    } else {
        $result = classify_mushroom_image(
            UPLOAD_DIAGNOSES . $imageFile,
            classify_parse_browser_features($_POST['browser_features'] ?? null)
        );

        if (isset($result['error'])) {
            $analysisError = $result['error'];
            @unlink(UPLOAD_DIAGNOSES . $imageFile);
            $result = null;
        } else {
            $lowConfidence = $result['confidence'] < AI_LOW_CONFIDENCE_THRESHOLD ? 1 : 0;

            $stmt = $pdo->prepare(
                'INSERT INTO diagnoses (farmer_id, image, predicted_disease_id, confidence, low_confidence_flag, model_version)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$farmerId, $imageFile, $result['disease_id'], $result['confidence'], $lowConfidence, $result['model'] ?? null]);
            $diagnosisId = $pdo->lastInsertId();

            $disease = $diseaseRows[$result['disease']] ?? null;

            // FR-NOT.2: tell the farmer the result is ready.
            notify($farmerId, 'notif.diagnosis_ready', '/farmer/diagnosis_view.php?id=' . $diagnosisId, ['disease' => $result['disease'], 'confidence' => $result['confidence']]);
            if ($lowConfidence) {
                foreach ($pdo->query("SELECT id FROM users WHERE role IN ('admin','expert') AND status = 'active'")->fetchAll() as $reviewer) {
                    notify($reviewer['id'], 'Low-confidence diagnosis #' . $diagnosisId . ' needs expert review.', '/admin/diagnoses.php?filter=unreviewed');
                }
            }
            $result['image'] = $imageFile;
            $result['low_confidence'] = $lowConfidence;

            // FR-EDU.4: suggest a related tutorial for the diagnosed disease.
            if ($result['disease_id']) {
                $t = $pdo->prepare('SELECT * FROM tutorials WHERE related_disease_id = ? LIMIT 1');
                $t->execute([$result['disease_id']]);
                $relatedTutorial = $t->fetch() ?: null;
            }
        }
    }
}

$pageTitle = t('nav.snap_detect');
include __DIR__ . '/../includes/header.php';
?>

<h1>📷 <?= te('diag.title') ?></h1>
<p class="muted"><?= te('diag.intro') ?></p>

<?php if ($analysisError === 'gd_missing'): ?>
  <div class="alert alert-error">
    <strong><?= te('diag.gd_title') ?></strong><br>
    <?= te('diag.gd_help') ?>
    <ol style="margin:8px 0 0;">
      <li><?= te('diag.gd_step1') ?></li>
      <li><?= te('diag.gd_step2') ?> <code>;extension=gd</code> &rarr; <code>extension=gd</code></li>
      <li><?= te('diag.gd_step3') ?></li>
    </ol>
  </div>
<?php elseif ($analysisError === 'unreadable'): ?>
  <div class="alert alert-error"><?= te('diag.unreadable') ?></div>
<?php endif; ?>

<div class="card form-narrow">
  <form class="stacked" method="post" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label><?= te('diag.step1') ?></label>
    <!-- capture="environment" opens the phone's rear camera directly (FR-AI.1) -->
    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" capture="environment" required id="diagImage">
    <input type="hidden" name="browser_features" id="browserFeatures" value="">
    <img id="diagPreview" alt="" style="display:none; max-width:100%; max-height:240px; margin-top:10px; border-radius:8px;">
    <button type="submit" class="btn">🔍 <?= te('diag.step2') ?></button>
  </form>
  <p class="muted" style="font-size:0.85rem; margin-bottom:0;">💡 <?= te('diag.tips') ?></p>
</div>

<?php if ($result): ?>
  <?php $topName = $disease ? tr_field($disease, 'name') : $result['disease']; ?>
  <div class="diagnosis-result <?= $result['low_confidence'] ? 'low-confidence' : '' ?>" style="margin-top:24px;" id="result">
    <div style="display:flex; gap:20px; flex-wrap:wrap;">
      <img src="<?= BASE_URL ?>/assets/uploads/diagnoses/<?= e($result['image']) ?>" alt="" style="width:220px; height:220px; object-fit:cover; border-radius:8px;">
      <div style="flex:1; min-width:260px;">
        <p class="muted" style="margin:0;"><?= te('diag.result') ?></p>
        <h2 class="mt-0" style="font-size:1.8rem; margin-bottom:6px;">
          <?= $result['disease'] === 'Healthy' ? '✅' : '⚠️' ?> <?= e($topName) ?>
        </h2>
        <p style="margin:0;"><?= te('diag.confidence') ?>: <strong><?= e($result['confidence']) ?>%</strong></p>
        <div class="confidence-bar"><div class="confidence-bar-fill" style="width: <?= e($result['confidence']) ?>%;"></div></div>

        <?php if ($result['low_confidence']): ?>
          <p style="color:#b36b16;"><strong><?= te('diag.low_title') ?></strong> <?= te('diag.low_text') ?></p>
        <?php endif; ?>

        <?php if (!empty($result['scores'])): ?>
          <h3 style="margin-bottom:4px;"><?= te('diag.all_results') ?></h3>
          <?php foreach ($result['scores'] as $name => $pct): ?>
            <div class="score-row <?= $name === $result['disease'] ? 'top' : '' ?>">
              <span class="name"><?= e(isset($diseaseRows[$name]) ? tr_field($diseaseRows[$name], 'name') : $name) ?></span>
              <span class="bar"><span style="width: <?= (float) $pct ?>%"></span></span>
              <span class="pct"><?= e($pct) ?>%</span>
            </div>
          <?php endforeach; ?>
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
    <?php else: ?>
      <?php // Table C.3 exception: no treatment record -> general hygiene advice and contact an expert. ?>
      <div class="detail-grid">
        <div class="card"><h3>🛡️ <?= te('diag.prevention') ?></h3><p><?= nl2br(e(tr_field($diseaseRows['Healthy'] ?? [], 'prevention'))) ?></p></div>
      </div>
    <?php endif; ?>

    <?php // Section 4.7.3 / Table 4.9: a diagnosis is advice only. ?>
    <div class="alert alert-info" style="margin:16px 0 0;">ℹ️ <?= te('diag.disclaimer') ?></div>

    <?php if ($relatedTutorial): ?>
      <p style="margin-top:16px;"><a href="<?= BASE_URL ?>/knowledge/view.php?id=<?= (int) $relatedTutorial['id'] ?>" class="btn btn-outline">📺 <?= te('diag.watch') ?>: <?= e(tr_field($relatedTutorial, 'title')) ?></a></p>
    <?php endif; ?>
    <p class="muted" style="font-size:0.8rem; margin-bottom:0;"><?= te('diag.model') ?>: <?= e($result['model']) ?></p>
  </div>
  <script>document.getElementById('result').scrollIntoView({behavior: 'smooth'});</script>
<?php endif; ?>

<p style="margin-top:24px;"><a href="<?= BASE_URL ?>/farmer/diagnosis_history.php"><?= te('diag.history_link') ?> &rarr;</a></p>

<script src="<?= BASE_URL ?>/assets/js/snap-detect.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
