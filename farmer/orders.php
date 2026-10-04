<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND farmer_id = ?');
    $stmt->execute([$orderId, $farmerId]);
    $order = $stmt->fetch();

    if ($order) {
        if ($action === 'confirm' && $order['status'] === 'pending') {
            $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?")->execute([$orderId]);
            notify($order['buyer_id'], 'Order #' . $orderId . ' was confirmed by the farmer. Please complete the payment.', '/buyer/payment.php?order_id=' . $orderId);
            flash('success', 'Order confirmed. The buyer can now proceed to payment.');
        } elseif ($action === 'complete' && $order['status'] === 'paid') {
            $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?")->execute([$orderId]);
            notify($order['buyer_id'], 'Order #' . $orderId . ' has been marked as completed. Please rate the farmer.', '/buyer/review.php?order_id=' . $orderId);
            flash('success', 'Order marked as completed.');
        } elseif ($action === 'cancel' && in_array($order['status'], ['pending', 'confirmed'], true)) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
            // Return the reserved quantity to the listing.
            $pdo->prepare("UPDATE listings SET quantity_kg = quantity_kg + ?, status = IF(status = 'sold_out', 'active', status) WHERE id = ?")
                ->execute([$order['quantity_kg'], $order['listing_id']]);
            $pdo->commit();
            notify($order['buyer_id'], 'Order #' . $orderId . ' was cancelled by the farmer.', '/buyer/orders.php');
            flash('success', 'Order cancelled.');
        }
    }
    redirect('/farmer/orders.php');
}

$stmt = $pdo->prepare(
    "SELECT o.*, l.mushroom_type, u.full_name AS buyer_name, u.business_name AS buyer_business
     FROM orders o
     JOIN listings l ON l.id = o.listing_id
     JOIN users u ON u.id = o.buyer_id
     WHERE o.farmer_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->execute([$farmerId]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
include __DIR__ . '/../includes/header.php';
?>

<h1>Orders Received</h1>

<?php if (!$orders): ?>
  <p class="muted">No orders received yet.</p>
<?php else: ?>
  <table class="data-table">
    <tr><th>Buyer</th><th>Mushroom</th><th>Qty (kg)</th><th>Total</th><th>Delivery</th><th>Status</th><th>Action</th></tr>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= e($o['buyer_name']) ?><?= $o['buyer_business'] ? ' (' . e($o['buyer_business']) . ')' : '' ?></td>
        <td><?= e($o['mushroom_type']) ?></td>
        <td><?= e($o['quantity_kg']) ?></td>
        <td><?= format_money($o['total_price']) ?></td>
        <td><?= format_date($o['delivery_date']) ?></td>
        <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
        <td>
          <?php if ($o['status'] === 'pending'): ?>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
              <button type="submit" name="action" value="confirm" class="btn btn-small">Confirm</button>
            </form>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
              <button type="submit" name="action" value="cancel" class="btn btn-small btn-danger" onclick="return confirm('Cancel this order?')">Cancel</button>
            </form>
          <?php elseif ($o['status'] === 'paid'): ?>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
              <button type="submit" name="action" value="complete" class="btn btn-small">Mark Completed</button>
            </form>
          <?php endif; ?>
          <?php if (in_array($o['status'], ['paid', 'completed'], true)): ?>
            <a href="<?= BASE_URL ?>/shared/receipt.php?order_id=<?= (int) $o['id'] ?>" class="btn btn-small btn-outline">Receipt</a>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/shared/dispute.php?order_id=<?= (int) $o['id'] ?>" class="btn btn-small btn-outline" title="Report a problem">⚠</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
