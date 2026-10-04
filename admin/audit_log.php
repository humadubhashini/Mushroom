<?php
/** Audit trail of administrative actions (NFR-SEC.5). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$logs = $pdo->query(
    'SELECT a.*, u.full_name, u.role FROM admin_logs a JOIN users u ON u.id = a.admin_id
     ORDER BY a.created_at DESC, a.id DESC LIMIT 200'
)->fetchAll();

$pageTitle = 'Audit Log';
include __DIR__ . '/../includes/header.php';
?>

<h1>📝 Audit Log</h1>
<p class="muted">Most recent 200 administrative and expert actions.</p>

<table class="data-table">
  <tr><th>Date &amp; Time</th><th>By</th><th>Action</th></tr>
  <?php foreach ($logs as $l): ?>
    <tr>
      <td><?= e(date('d M Y, h:i A', strtotime($l['created_at']))) ?></td>
      <td><?= e($l['full_name']) ?> (<?= e($l['role']) ?>)</td>
      <td><?= e($l['action']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$logs): ?><tr><td colspan="3" class="muted">No actions logged yet.</td></tr><?php endif; ?>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
