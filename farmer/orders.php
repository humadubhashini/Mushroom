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
            notify($order['buyer_id'], 'notif.order_confirmed', '/buyer/payment.php?order_id=' . $orderId, ['order' => $orderId]);
            flash('success', t('forders.confirmed'));
        } elseif ($action === 'complete' && $order['status'] === 'paid') {
            $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?")->execute([$orderId]);
            notify($order['buyer_id'], 'notif.order_completed', '/buyer/review.php?order_id=' . $orderId, ['order' => $orderId]);
            flash('success', t('forders.completed'));
        } elseif ($action === 'cancel' && in_array($order['status'], ['pending', 'confirmed'], true)) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$orderId]);
            // Return the reserved quantity to the listing.
            $pdo->prepare("UPDATE listings SET quantity_kg = quantity_kg + ?, status = IF(status = 'sold_out', 'active', status) WHERE id = ?")
                ->execute([$order['quantity_kg'], $order['listing_id']]);
            $pdo->commit();
            notify($order['buyer_id'], 'notif.order_cancelled', '/buyer/orders.php', ['order' => $orderId]);
            flash('success', t('forders.cancelled'));
        }
    }
    redirect('/farmer/orders.php');
}

$stmt = $pdo->prepare(
    "SELECT o.*, l.mushroom_type, u.full_name AS buyer_name, u.business_name AS buyer_business, " . LISTING_TYPE_COLUMNS . "
     FROM orders o
     JOIN listings l ON l.id = o.listing_id
     LEFT JOIN mushroom_types mt ON mt.id = l.mushroom_type_id
     JOIN users u ON u.id = o.buyer_id
     WHERE o.farmer_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->execute([$farmerId]);
$orders = $stmt->fetchAll();

$pageTitle = t('forders.title');
include __DIR__ . '/../includes/header.php';
?>

<h1><?= te('forders.title') ?></h1>

<?php if (!$orders): ?>
  <p class="muted"><?= te('forders.none') ?></p>
<?php else: ?>
  <table class="data-table">
    <tr><th>#</th><th><?= te('common.buyer') ?></th><th><?= te('common.mushroom') ?></th><th><?= te('common.qty_kg') ?></th><th><?= te('common.total') ?></th><th><?= te('common.delivery') ?></th><th><?= te('common.status') ?></th><th><?= te('common.action') ?></th></tr>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= (int) $o['id'] ?></td>
        <td><?= e($o['buyer_name']) ?><?= $o['buyer_business'] ? ' (' . e($o['buyer_business']) . ')' : '' ?></td>
        <td><?= e(listing_title($o)) ?></td>
        <td><?= e($o['quantity_kg']) ?></td>
        <td><?= format_money($o['total_price']) ?></td>
        <td><?= format_date($o['delivery_date']) ?></td>
        <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
        <td>
          <?php if ($o['status'] === 'pending'): ?>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
              <button type="submit" name="action" value="confirm" class="btn btn-small"><?= te('common.confirm') ?></button>
            </form>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
              <button type="submit" name="action" value="cancel" class="btn btn-small btn-danger" onclick="return confirm('<?= te('forders.confirm_cancel') ?>')"><?= te('common.cancel') ?></button>
            </form>
          <?php elseif ($o['status'] === 'paid'): ?>
            <form method="post" style="display:inline">
              <?= csrf_field() ?>
              <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
              <button type="submit" name="action" value="complete" class="btn btn-small"><?= te('forders.mark_completed') ?></button>
            </form>
          <?php endif; ?>
          <?php if (in_array($o['status'], ['paid', 'completed'], true)): ?>
            <a href="<?= BASE_URL ?>/shared/receipt.php?order_id=<?= (int) $o['id'] ?>" class="btn btn-small btn-outline"><?= te('common.receipt') ?></a>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>/shared/dispute.php?order_id=<?= (int) $o['id'] ?>" class="btn btn-small btn-outline" title="<?= te('common.report_problem') ?>">⚠</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
