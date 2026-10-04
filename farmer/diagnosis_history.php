<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];

$stmt = $pdo->prepare(
    'SELECT d.*, dt.name AS disease_name, dt.name_si, dt.name_ta,
            rd.name AS reviewed_name, rd.name_si AS reviewed_name_si, rd.name_ta AS reviewed_name_ta
     FROM diagnoses d
     LEFT JOIN disease_types dt ON dt.id = d.predicted_disease_id
     LEFT JOIN disease_types rd ON rd.id = d.reviewed_disease_id
     WHERE d.farmer_id = ?
     ORDER BY d.created_at DESC, d.id DESC'
);
$stmt->execute([$farmerId]);
$diagnoses = $stmt->fetchAll();

$pageTitle = t('hist.title');
include __DIR__ . '/../includes/header.php';
?>

<h1><?= te('hist.title') ?></h1>

<?php if (!$diagnoses): ?>
  <p class="muted"><?= te('hist.none') ?> <a href="<?= BASE_URL ?>/farmer/diagnose.php"><?= te('nav.snap_detect') ?></a></p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($diagnoses as $d): ?>
      <a class="listing-card" href="<?= BASE_URL ?>/farmer/diagnosis_view.php?id=<?= (int) $d['id'] ?>" style="text-decoration:none; color:inherit;">
        <img src="<?= BASE_URL ?>/assets/uploads/diagnoses/<?= e($d['image']) ?>" alt="">
        <div class="body">
          <h3 style="margin:0 0 4px;"><?= e($d['disease_name'] ? tr_field(['name' => $d['disease_name'], 'name_si' => $d['name_si'], 'name_ta' => $d['name_ta']], 'name') : '-') ?></h3>
          <p class="muted"><?= te('diag.confidence') ?>: <?= e($d['confidence']) ?>%<?= $d['low_confidence_flag'] ? ' &mdash; ' . te('hist.low') : '' ?></p>
          <p class="muted"><?= format_date($d['created_at']) ?></p>
          <?php if ($d['reviewed_by']): ?>
            <div class="alert alert-info" style="margin:8px 0 0;">👩‍🔬 <?= te('hist.expert') ?>:
              <strong><?= e(tr_field(['name' => $d['reviewed_name'], 'name_si' => $d['reviewed_name_si'], 'name_ta' => $d['reviewed_name_ta']], 'name')) ?></strong>
              <?php if ($d['expert_note']): ?><br><?= e($d['expert_note']) ?><?php endif; ?></div>
          <?php elseif ($d['low_confidence_flag']): ?>
            <p class="muted" style="font-size:0.85rem;">⏳ <?= te('hist.waiting') ?></p>
          <?php endif; ?>
          <span class="btn btn-small btn-outline"><?= te('hist.details') ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
