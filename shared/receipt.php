<?php
/** Printable digital receipt / invoice for a paid order (FR-PAY.3). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$user = current_user();
$orderId = (int) ($_GET['order_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT o.*, l.mushroom_type, l.price_per_kg, " . LISTING_TYPE_COLUMNS . ",
            bu.full_name AS buyer_name, bu.business_name AS buyer_business, bu.address AS buyer_address, bu.email AS buyer_email,
            fu.full_name AS farmer_name, fu.business_name AS farmer_business, fu.address AS farmer_address, fu.phone AS farmer_phone,
            p.gateway_reference, p.paid_at, p.method, p.amount
     FROM orders o
     JOIN listings l ON l.id = o.listing_id
     LEFT JOIN mushroom_types mt ON mt.id = l.mushroom_type_id
     JOIN users bu ON bu.id = o.buyer_id
     JOIN users fu ON fu.id = o.farmer_id
     JOIN payments p ON p.order_id = o.id AND p.status = 'success'
     WHERE o.id = ?"
);
$stmt->execute([$orderId]);
$r = $stmt->fetch();

$allowed = $r && ($user['role'] === 'admin' || $r['buyer_id'] == $user['id'] || $r['farmer_id'] == $user['id']);
if (!$allowed) {
    flash('error', t('receipt.not_found'));
    redirect(role_home($user['role']));
}

$pageTitle = t('common.receipt') . ' #' . $orderId;
include __DIR__ . '/../includes/header.php';
?>

<div class="card receipt">
  <div style="display:flex; justify-content:space-between; flex-wrap:wrap;">
    <div>
      <h1 class="mt-0">🍄 <?= e(APP_NAME) ?></h1>
      <p class="muted"><?= te('receipt.title') ?></p>
    </div>
    <div style="text-align:right;">
      <strong><?= te('receipt.invoice') ?> #INV-<?= str_pad((string) $r['id'], 6, '0', STR_PAD_LEFT) ?></strong><br>
      <span class="muted"><?= te('status.paid') ?>: <?= e(date('d M Y, h:i A', strtotime($r['paid_at']))) ?></span><br>
      <span class="badge badge-completed"><?= te('receipt.paid_badge') ?></span>
    </div>
  </div>

  <div class="form-row" style="margin:20px 0;">
    <div>
      <strong><?= te('receipt.seller') ?></strong><br>
      <?= e($r['farmer_name']) ?><br>
      <?= e($r['farmer_business'] ?: '') ?><br>
      <span class="muted"><?= e($r['farmer_address'] ?: '') ?> <?= e($r['farmer_phone'] ?: '') ?></span>
    </div>
    <div>
      <strong><?= te('common.buyer') ?></strong><br>
      <?= e($r['buyer_name']) ?><br>
      <?= e($r['buyer_business'] ?: '') ?><br>
      <span class="muted"><?= e($r['buyer_address'] ?: '') ?> <?= e($r['buyer_email']) ?></span>
    </div>
  </div>

  <table>
    <tr><td><?= e(listing_title($r)) ?> &times; <?= e($r['quantity_kg']) ?> kg @ <?= format_money($r['price_per_kg']) ?></td><td><?= format_money($r['total_price']) ?></td></tr>
    <tr><td><?= te('listing.delivery_date') ?></td><td><?= format_date($r['delivery_date']) ?></td></tr>
    <tr><td><?= te('pay.method') ?></td><td><?= te('pay.' . $r['method']) ?></td></tr>
    <tr><td><?= te('receipt.reference') ?></td><td><?= e($r['gateway_reference']) ?></td></tr>
    <tr><td><strong><?= te('receipt.total_paid') ?></strong></td><td><strong><?= format_money($r['amount']) ?></strong></td></tr>
  </table>

  <p class="muted" style="margin-top:16px;"><?= te('receipt.note') ?></p>
  <button class="btn no-print" onclick="window.print()">🖨 <?= te('common.print') ?></button>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
