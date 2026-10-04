<?php
/** Raise a dispute about an order or payment (FR-ADM.4). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['farmer', 'buyer']);
$user = current_user();
$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT o.*, l.mushroom_type FROM orders o JOIN listings l ON l.id = o.listing_id
     WHERE o.id = ? AND (o.buyer_id = ? OR o.farmer_id = ?)'
);
$stmt->execute([$orderId, $user['id'], $user['id']]);
$order = $stmt->fetch();
$back = $user['role'] === 'farmer' ? '/farmer/orders.php' : '/buyer/orders.php';

if (!$order) {
    flash('error', 'Order not found.');
    redirect($back);
}

$existing = $pdo->prepare('SELECT * FROM disputes WHERE order_id = ? ORDER BY created_at DESC');
$existing->execute([$orderId]);
$existing = $existing->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $reason = trim($_POST['reason'] ?? '');
    if (strlen($reason) < 10) {
        flash('error', 'Please describe the problem (at least 10 characters).');
        redirect('/shared/dispute.php?order_id=' . $orderId);
    }
    $pdo->prepare('INSERT INTO disputes (order_id, raised_by, reason) VALUES (?, ?, ?)')
        ->execute([$orderId, $user['id'], $reason]);

    $otherParty = $user['role'] === 'farmer' ? $order['buyer_id'] : $order['farmer_id'];
    notify($otherParty, 'A dispute was raised on order #' . $orderId . '. An administrator will review it.', $user['role'] === 'farmer' ? '/buyer/orders.php' : '/farmer/orders.php');
    foreach ($pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll() as $admin) {
        notify($admin['id'], 'New dispute on order #' . $orderId, '/admin/disputes.php');
    }
    flash('success', 'Your dispute has been submitted to the administrator.');
    redirect($back);
}

$pageTitle = 'Report a Problem';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Report a Problem</h1>
  <p class="muted">Order #<?= (int) $order['id'] ?> &mdash; <?= e($order['mushroom_type']) ?>, <?= e($order['quantity_kg']) ?> kg, <?= format_money($order['total_price']) ?> (<?= e(ucfirst($order['status'])) ?>)</p>

  <?php foreach ($existing as $d): ?>
    <div class="alert <?= $d['status'] === 'open' ? 'alert-info' : 'alert-success' ?>">
      <strong><?= e(ucfirst($d['status'])) ?>:</strong> <?= e($d['reason']) ?>
      <?php if ($d['admin_response']): ?><br><em>Admin: <?= e($d['admin_response']) ?></em><?php endif; ?>
    </div>
  <?php endforeach; ?>

  <form class="stacked" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
    <label>Describe the issue (quality, quantity, payment, delivery...)</label>
    <textarea name="reason" required minlength="10"></textarea>
    <button type="submit" class="btn">Submit Dispute</button>
  </form>
  <p><a href="<?= BASE_URL . $back ?>">&larr; Back to orders</a></p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
