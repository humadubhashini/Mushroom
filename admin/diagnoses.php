<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$filter = $_GET['filter'] ?? '';
$sql = "SELECT d.*, dt.name AS disease_name, u.full_name AS farmer_name
        FROM diagnoses d
        LEFT JOIN disease_types dt ON dt.id = d.predicted_disease_id
        JOIN users u ON u.id = d.farmer_id
        WHERE 1=1";
$params = [];
if ($filter === 'low_confidence') {
    $sql .= ' AND d.low_confidence_flag = 1';
}
$sql .= ' ORDER BY d.created_at DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$diagnoses = $stmt->fetchAll();

$pageTitle = 'AI Diagnosis Log';
include __DIR__ . '/../includes/header.php';
?>

<h1>AI Diagnosis Log</h1>
<p class="muted">Monitor Snap &amp; Detect usage and review low-confidence cases that may need expert follow-up (FR-ADM.3).</p>

<div style="margin-bottom:16px;">
  <a href="?" class="btn btn-small <?= $filter === '' ? '' : 'btn-outline' ?>">All</a>
  <a href="?filter=low_confidence" class="btn btn-small <?= $filter === 'low_confidence' ? '' : 'btn-outline' ?>">Low Confidence Only</a>
</div>

<table class="data-table">
  <tr><th>Farmer</th><th>Diagnosis</th><th>Confidence</th><th>Flag</th><th>Date</th></tr>
  <?php foreach ($diagnoses as $d): ?>
    <tr>
      <td><?= e($d['farmer_name']) ?></td>
      <td><?= e($d['disease_name'] ?? 'Unknown') ?></td>
      <td><?= e($d['confidence']) ?>%</td>
      <td><?= $d['low_confidence_flag'] ? '<span class="badge badge-pending">Low confidence</span>' : '<span class="badge badge-completed">OK</span>' ?></td>
      <td><?= format_date($d['created_at']) ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
