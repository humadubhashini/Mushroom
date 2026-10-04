<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/ai_classifier.php';
require_role('farmer');
$farmerId = current_user()['id'];

$result = null;
$disease = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $imageError = null;
    $imageFile = handle_image_upload('image', UPLOAD_DIAGNOSES, $imageError);

    if ($imageError) {
        flash('error', $imageError);
    } elseif (!$imageFile) {
        flash('error', 'Please choose a photo of the mushroom to diagnose.');
    } else {
        $result = classify_mushroom_image(UPLOAD_DIAGNOSES . $imageFile);
        $lowConfidence = $result['confidence'] < AI_LOW_CONFIDENCE_THRESHOLD ? 1 : 0;

        $stmt = $pdo->prepare(
            'INSERT INTO diagnoses (farmer_id, image, predicted_disease_id, confidence, low_confidence_flag, model_version)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$farmerId, $imageFile, $result['disease_id'], $result['confidence'], $lowConfidence, $result['model'] ?? null]);
        $diagnosisId = $pdo->lastInsertId();

        if ($result['disease_id']) {
            $stmt = $pdo->prepare('SELECT * FROM disease_types WHERE id = ?');
            $stmt->execute([$result['disease_id']]);
            $disease = $stmt->fetch();
        }
        notify($farmerId, 'Your Snap & Detect result is ready: ' . $result['disease'] . ' (' . $result['confidence'] . '% confidence).', '/farmer/diagnosis_history.php');
        if ($lowConfidence) {
            foreach ($pdo->query("SELECT id FROM users WHERE role IN ('admin','expert') AND status = 'active'")->fetchAll() as $reviewer) {
                notify($reviewer['id'], 'Low-confidence diagnosis #' . $diagnosisId . ' needs expert review.', '/admin/diagnoses.php?filter=unreviewed');
            }
        }
        $result['image'] = $imageFile;
        $result['low_confidence'] = $lowConfidence;

        // FR-EDU.4: suggest a related tutorial for the diagnosed disease, if any.
        $relatedTutorial = null;
        if ($result['disease_id']) {
            $t = $pdo->prepare('SELECT * FROM tutorials WHERE related_disease_id = ? LIMIT 1');
            $t->execute([$result['disease_id']]);
            $relatedTutorial = $t->fetch() ?: null;
        }
    }
}

$pageTitle = 'Snap & Detect';
include __DIR__ . '/../includes/header.php';
?>

<h1>📷 Snap &amp; Detect &mdash; AI Disease Diagnosis</h1>
<p class="muted">Upload a clear, well-lit photo of the mushroom or growing bed. The AI model will identify the most likely condition and suggest treatment.
Accuracy may be reduced in low-light growing houses (see SRS Section 5.7).</p>

<div class="card form-narrow">
  <form class="stacked" method="post" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Step 1 &mdash; Take or choose a photo of the mushroom / growing bed</label>
    <!-- capture="environment" opens the phone's rear camera directly (FR-AI.1) -->
    <input type="file" name="image" accept="image/*" capture="environment" required id="diagImage">
    <img id="diagPreview" alt="" style="display:none; max-width:100%; max-height:240px; margin-top:10px; border-radius:8px;">
    <script>
      document.getElementById('diagImage').addEventListener('change', function () {
        var img = document.getElementById('diagPreview');
        if (this.files && this.files[0]) { img.src = URL.createObjectURL(this.files[0]); img.style.display = 'block'; }
      });
    </script>
    <button type="submit" class="btn">Step 2 &mdash; 🔍 Diagnose Now</button>
  </form>
</div>

<?php if ($result): ?>
  <div class="diagnosis-result <?= $result['low_confidence'] ? 'low-confidence' : '' ?>">
    <div style="display:flex; gap:20px; flex-wrap:wrap;">
      <img src="<?= BASE_URL ?>/assets/uploads/diagnoses/<?= e($result['image']) ?>" alt="Uploaded mushroom" style="width:200px; height:200px; object-fit:cover; border-radius:8px;">
      <div style="flex:1; min-width:240px;">
        <h2 class="mt-0">Diagnosis: <?= e($result['disease']) ?></h2>
        <p>Confidence: <strong><?= e($result['confidence']) ?>%</strong></p>
        <div class="confidence-bar"><div class="confidence-bar-fill" style="width: <?= e($result['confidence']) ?>%;"></div></div>
        <?php if ($result['low_confidence']): ?>
          <p style="color:#d9822b;"><strong>⚠ Low confidence result.</strong> This case has been sent to our agricultural experts for review &mdash; you'll get a notification with their advice. You can also consult your local agriculture extension officer.</p>
        <?php endif; ?>

        <?php if ($disease): ?>
          <h3>Recommended Treatment</h3>
          <p><?= e($disease['treatment']) ?></p>
          <h3>Pesticide / Remedy Suggestion</h3>
          <p><?= e($disease['pesticide_recommendation']) ?></p>
        <?php endif; ?>

        <?php if (!empty($relatedTutorial)): ?>
          <p><a href="<?= BASE_URL ?>/knowledge/view.php?id=<?= (int) $relatedTutorial['id'] ?>" class="btn btn-small btn-outline">📺 Watch: <?= e($relatedTutorial['title']) ?></a></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<p style="margin-top:24px;"><a href="<?= BASE_URL ?>/farmer/diagnosis_history.php">View my diagnosis history &rarr;</a></p>

<?php include __DIR__ . '/../includes/footer.php'; ?>
