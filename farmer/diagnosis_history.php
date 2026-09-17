<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];

$stmt = $pdo->prepare(
    'SELECT d.*, dt.name AS disease_name
     FROM diagnoses d
     LEFT JOIN disease_types dt ON dt.id = d.predicted_disease_id
     WHERE d.farmer_id = ?
     ORDER BY d.created_at DESC'
);
$stmt->execute([$farmerId]);
$diagnoses = $stmt->fetchAll();

$pageTitle = 'Diagnosis History';
include __DIR__ . '/../includes/header.php';
?>

<h1>My Diagnosis History</h1>

<?php if (!$diagnoses): ?>
  <p class="muted">No diagnoses yet. <a href="<?= BASE_URL ?>/farmer/diagnose.php">Try Snap &amp; Detect</a>.</p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($diagnoses as $d): ?>
      <div class="listing-card">
        <img src="<?= BASE_URL ?>/assets/uploads/diagnoses/<?= e($d['image']) ?>" alt="Diagnosis">
        <div class="body">
          <h3 style="margin:0 0 4px;"><?= e($d['disease_name'] ?? 'Unknown') ?></h3>
          <p class="muted">Confidence: <?= e($d['confidence']) ?>%<?= $d['low_confidence_flag'] ? ' &mdash; low confidence' : '' ?></p>
          <p class="muted"><?= format_date($d['created_at']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
