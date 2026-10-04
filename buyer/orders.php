<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('buyer');
$buyerId = current_user()['id'];

$stmt = $pdo->prepare(
    "SELECT o.*, l.mushroom_type, u.full_name AS farmer_name, " . LISTING_TYPE_COLUMNS . ",
            (SELECT COUNT(*) FROM reviews r WHERE r.order_id = o.id) AS has_review
     FROM orders o
     JOIN listings l ON l.id = o.listing_id
     LEFT JOIN mushroom_types mt ON mt.id = l.mushroom_type_id
     JOIN users u ON u.id = o.farmer_id
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC, o.id DESC"
);
$stmt->execute([$buyerId]);
$orders = $stmt->fetchAll();

$pageTitle = t('nav.my_orders');
include __DIR__ . '/../includes/header.php';
?>

<h1><?= te('nav.my_orders') ?></h1>

<?php if (!$orders): ?>
  <p class="muted"><?= te('buyer.no_orders') ?> <a href="<?= BASE_URL ?>/buyer/marketplace.php"><?= te('home.browse') ?></a></p>
<?php else: ?>
  <table class="data-table">
    <tr><th>#</th><th><?= te('common.farmer') ?></th><th><?= te('common.mushroom') ?></th><th><?= te('common.qty_kg') ?></th><th><?= te('common.total') ?></th><th><?= te('common.status') ?></th><th><?= te('common.date') ?></th><th><?= te('common.action') ?></th></tr>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= (int) $o['id'] ?></td>
        <td><?= e($o['farmer_name']) ?></td>
        <td><?= e(listing_title($o)) ?></td>
        <td><?= e($o['quantity_kg']) ?></td>
        <td><?= format_money($o['total_price']) ?></td>
        <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
        <td><?= format_date($o['created_at']) ?></td>
        <td>
          <?php if ($o['status'] === 'pending'): ?>
            <span class="muted" style="font-size:0.85rem;"><?= te('buyer.waiting_farmer') ?></span>
          <?php elseif ($o['status'] === 'confirmed'): ?>
            <a href="<?= BASE_URL ?>/buyer/payment.php?order_id=<?= (int) $o['id'] ?>" class="btn btn-small"><?= te('buyer.pay_now') ?></a>
          <?php elseif ($o['status'] === 'completed' && !$o['has_review']): ?>
            <a href="<?= BASE_URL ?>/buyer/review.php?order_id=<?= (int) $o['id'] ?>" class="btn btn-small btn-outline"><?= te('buyer.rate') ?></a>
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
