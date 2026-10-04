<?php
/** AI diagnostic monitoring and expert review of flagged cases (FR-ADM.3, FR-AI.6). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin', 'expert']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['diagnosis_id'] ?? 0);
    $reviewedId = (int) ($_POST['reviewed_disease_id'] ?? 0) ?: null;
    $note = trim($_POST['expert_note'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM diagnoses WHERE id = ?');
    $stmt->execute([$id]);
    if ($diag = $stmt->fetch()) {
        $pdo->prepare('UPDATE diagnoses SET reviewed_disease_id = ?, expert_note = ?, reviewed_by = ? WHERE id = ?')
            ->execute([$reviewedId, $note ?: null, current_user()['id'], $id]);
        notify($diag['farmer_id'], 'An agricultural expert reviewed your diagnosis #' . $id . '. Tap to see their advice.', '/farmer/diagnosis_history.php');
        log_admin_action('Reviewed AI diagnosis #' . $id);
        flash('success', 'Review saved and the farmer has been notified.');
    }
    redirect('/admin/diagnoses.php?' . http_build_query(['filter' => $_POST['filter'] ?? '']));
}

$filter = $_GET['filter'] ?? '';
$sql = "SELECT d.*, dt.name AS disease_name, rd.name AS reviewed_name, u.full_name AS farmer_name
        FROM diagnoses d
        LEFT JOIN disease_types dt ON dt.id = d.predicted_disease_id
        LEFT JOIN disease_types rd ON rd.id = d.reviewed_disease_id
        JOIN users u ON u.id = d.farmer_id
        WHERE 1=1";
if ($filter === 'low_confidence') {
    $sql .= ' AND d.low_confidence_flag = 1';
} elseif ($filter === 'unreviewed') {
    $sql .= ' AND d.low_confidence_flag = 1 AND d.reviewed_by IS NULL';
}
$sql .= ' ORDER BY d.created_at DESC LIMIT 100';
$diagnoses = $pdo->query($sql)->fetchAll();
$diseases = $pdo->query('SELECT id, name FROM disease_types ORDER BY id')->fetchAll();

$pageTitle = 'AI Diagnosis Log';
include __DIR__ . '/../includes/header.php';
?>

<h1>🤖 AI Diagnosis Log</h1>
<p class="muted">Monitor Snap &amp; Detect usage and review low-confidence cases. Expert-confirmed labels can be exported to retrain the CNN model.</p>

<div style="margin-bottom:16px;">
  <a href="?" class="btn btn-small <?= $filter === '' ? '' : 'btn-outline' ?>">All</a>
  <a href="?filter=low_confidence" class="btn btn-small <?= $filter === 'low_confidence' ? '' : 'btn-outline' ?>">Low Confidence</a>
  <a href="?filter=unreviewed" class="btn btn-small <?= $filter === 'unreviewed' ? '' : 'btn-outline' ?>">Awaiting Expert Review</a>
</div>

<table class="data-table">
  <tr><th>Image</th><th>Farmer</th><th>AI Result</th><th>Confidence</th><th>Date</th><th>Expert Review</th></tr>
  <?php foreach ($diagnoses as $d): ?>
    <tr>
      <td><a href="<?= BASE_URL ?>/assets/uploads/diagnoses/<?= e($d['image']) ?>" target="_blank"><img src="<?= BASE_URL ?>/assets/uploads/diagnoses/<?= e($d['image']) ?>" alt="" style="width:64px; height:64px; object-fit:cover; border-radius:6px;"></a></td>
      <td><?= e($d['farmer_name']) ?></td>
      <td><?= e($d['disease_name'] ?? 'Unknown') ?><br><small class="muted"><?= e($d['model_version'] ?? '') ?></small></td>
      <td><?= e($d['confidence']) ?>%
        <?= $d['low_confidence_flag'] ? '<br><span class="badge badge-pending">Low confidence</span>' : '' ?></td>
      <td><?= format_date($d['created_at']) ?></td>
      <td>
        <?php if ($d['reviewed_by']): ?>
          <strong><?= e($d['reviewed_name'] ?? '-') ?></strong><br><small><?= e($d['expert_note'] ?? '') ?></small>
        <?php else: ?>
          <form method="post" style="display:flex; flex-direction:column; gap:6px; min-width:200px;">
            <?= csrf_field() ?>
            <input type="hidden" name="diagnosis_id" value="<?= (int) $d['id'] ?>">
            <input type="hidden" name="filter" value="<?= e($filter) ?>">
            <select name="reviewed_disease_id">
              <?php foreach ($diseases as $ds): ?>
                <option value="<?= (int) $ds['id'] ?>" <?= $ds['id'] == $d['predicted_disease_id'] ? 'selected' : '' ?>><?= e($ds['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" name="expert_note" placeholder="Advice for the farmer">
            <button type="submit" class="btn btn-small">Save review</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$diagnoses): ?><tr><td colspan="6" class="muted">No diagnoses yet.</td></tr><?php endif; ?>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
