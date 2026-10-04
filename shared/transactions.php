<?php
/** Transaction history for farmers and buyers (FR-PAY.4). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['farmer', 'buyer']);
$user = current_user();
$column = $user['role'] === 'farmer' ? 'o.farmer_id' : 'o.buyer_id';

$stmt = $pdo->prepare(
    "SELECT p.*, o.quantity_kg, l.mushroom_type,
            bu.full_name AS buyer_name, fu.full_name AS farmer_name
     FROM payments p
     JOIN orders o ON o.id = p.order_id
     JOIN listings l ON l.id = o.listing_id
     JOIN users bu ON bu.id = o.buyer_id
     JOIN users fu ON fu.id = o.farmer_id
     WHERE $column = ?
     ORDER BY p.created_at DESC, p.id DESC"
);
$stmt->execute([$user['id']]);
$payments = $stmt->fetchAll();

$total = 0;
foreach ($payments as $p) {
    if ($p['status'] === 'success') $total += $p['amount'];
}

$pageTitle = 'Transactions';
include __DIR__ . '/../includes/header.php';
?>

<h1>💳 Transaction History</h1>
<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= format_money($total) ?></div><div class="label"><?= $user['role'] === 'farmer' ? 'Total Received' : 'Total Paid' ?></div></div>
  <div class="stat-card"><div class="num"><?= count($payments) ?></div><div class="label">Transactions</div></div>
</div>

<?php if (!$payments): ?>
  <p class="muted">No transactions yet.</p>
<?php else: ?>
  <table class="data-table">
    <tr><th>Date</th><th>Order</th><th><?= $user['role'] === 'farmer' ? 'Buyer' : 'Farmer' ?></th><th>Mushroom</th><th>Amount</th><th>Reference</th><th>Status</th><th></th></tr>
    <?php foreach ($payments as $p): ?>
      <tr>
        <td><?= format_date($p['created_at']) ?></td>
        <td>#<?= (int) $p['order_id'] ?></td>
        <td><?= e($user['role'] === 'farmer' ? $p['buyer_name'] : $p['farmer_name']) ?></td>
        <td><?= e($p['mushroom_type']) ?></td>
        <td><?= format_money($p['amount']) ?></td>
        <td><?= e($p['gateway_reference'] ?: '-') ?></td>
        <td><span class="badge badge-<?= $p['status'] === 'success' ? 'completed' : e($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span>
          <?php if ($p['failure_reason']): ?><br><small class="muted"><?= e($p['failure_reason']) ?></small><?php endif; ?></td>
        <td><?php if ($p['status'] === 'success'): ?><a class="btn btn-small btn-outline" href="<?= BASE_URL ?>/shared/receipt.php?order_id=<?= (int) $p['order_id'] ?>">Receipt</a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
