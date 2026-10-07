<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('buyer');
$buyerId = current_user()['id'];

$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT o.*, l.mushroom_type, u.full_name AS farmer_name, " . LISTING_TYPE_COLUMNS . "
     FROM orders o JOIN listings l ON l.id = o.listing_id JOIN users u ON u.id = o.farmer_id
     LEFT JOIN mushroom_types mt ON mt.id = l.mushroom_type_id
     WHERE o.id = ? AND o.buyer_id = ?"
);
$stmt->execute([$orderId, $buyerId]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', t('order.not_found'));
    redirect('/buyer/orders.php');
}
if ($order['status'] !== 'confirmed') {
    flash('error', t('pay.not_awaiting'));
    redirect('/buyer/orders.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    /**
     * Simulated secure payment gateway (FR-PAY.1 - FR-PAY.5).
     *
     * This demo processor validates input locally and never stores raw card
     * data (only a masked reference is kept), matching NFR-SEC.3. To go live,
     * swap gateway_charge() for a real gateway SDK call (e.g. PayHere) while
     * keeping the same success/failure branch and the payments table.
     *
     * Test cards: any 16-digit number succeeds, except 4000 0000 0000 0002
     * which is declined (to demonstrate NFR-REL.2 failure handling).
     */
    $method = $_POST['method'] ?? 'card';
    $gateway = gateway_charge($method, $_POST);

    if ($gateway['errors']) {
        $errors = $gateway['errors'];
    } elseif (!$gateway['approved']) {
        $pdo->prepare(
            "INSERT INTO payments (order_id, amount, method, gateway_reference, status, failure_reason)
             VALUES (?, ?, ?, ?, 'failed', ?)"
        )->execute([$order['id'], $order['total_price'], $method, $gateway['reference'], $gateway['message']]);
        notify($buyerId, 'notif.payment_failed', '/buyer/payment.php?order_id=' . $order['id'], ['order' => $order['id']]);
        $errors[] = t('pay.failed', ['reason' => t($gateway['message'])]);
    } else {
        $pdo->beginTransaction();
        try {
            // Only move confirmed -> paid once, so a double-submit can never charge twice (NFR-REL.2).
            $update = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE id = ? AND status = 'confirmed'");
            $update->execute([$order['id']]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Order already paid.');
            }
            $pdo->prepare(
                "INSERT INTO payments (order_id, amount, method, gateway_reference, status, paid_at)
                 VALUES (?, ?, ?, ?, 'success', NOW())"
            )->execute([$order['id'], $order['total_price'], $method, $gateway['reference']]);
            $pdo->commit();

            notify($buyerId, 'notif.payment_success', '/shared/receipt.php?order_id=' . $order['id'], ['amount' => format_money($order['total_price']), 'order' => $order['id']]);
            notify($order['farmer_id'], 'notif.payment_received', '/shared/receipt.php?order_id=' . $order['id'], ['amount' => format_money($order['total_price']), 'order' => $order['id']]);

            // Back to the order list with the masked reference (Figure 5.5);
            // the digital receipt is available from the Receipt button.
            flash('success', t('pay.success', ['ref' => $gateway['reference']]));
            redirect('/buyer/orders.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', t('pay.already'));
            redirect('/buyer/orders.php');
        }
    }
}

/**
 * Stand-in for the third-party gateway API. Returns
 * ['errors' => [], 'approved' => bool, 'reference' => string, 'message' => string].
 */
function gateway_charge(string $method, array $input): array {
    $errors = [];
    $reference = strtoupper(substr($method, 0, 4)) . '-' . strtoupper(bin2hex(random_bytes(4)));

    if ($method === 'card') {
        $cardNumber = preg_replace('/\D+/', '', $input['card_number'] ?? '');
        if (trim($input['card_name'] ?? '') === '') $errors[] = t('pay.err_name');
        if (!preg_match('/^\d{16}$/', $cardNumber)) $errors[] = t('pay.err_card');
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', trim($input['expiry'] ?? ''))) $errors[] = t('pay.err_expiry');
        if (!preg_match('/^\d{3,4}$/', trim($input['cvv'] ?? ''))) $errors[] = t('pay.err_cvv');
        if ($errors) return ['errors' => $errors, 'approved' => false, 'reference' => '', 'message' => ''];

        $brand = match ($cardNumber[0]) { '4' => 'VISA', '5' => 'MASTER', '3' => 'AMEX', default => 'CARD' };
        $reference = $brand . '-' . substr($cardNumber, -4) . '-' . strtoupper(bin2hex(random_bytes(3)));
        if ($cardNumber === '4000000000000002') {
            return ['errors' => [], 'approved' => false, 'reference' => $reference, 'message' => 'pay.declined'];
        }
    } elseif ($method === 'bank') {
        if (trim($input['bank_name'] ?? '') === '') $errors[] = t('pay.err_bank');
        if (!preg_match('/^\d{6,16}$/', trim($input['account_no'] ?? ''))) $errors[] = t('pay.err_account');
    } elseif ($method === 'wallet') {
        if (!preg_match('/^07\d{8}$/', trim($input['wallet_phone'] ?? ''))) $errors[] = t('pay.err_mobile');
    } else {
        $errors[] = t('pay.err_method');
    }

    return ['errors' => $errors, 'approved' => !$errors, 'reference' => $reference, 'message' => 'Approved'];
}

$pageTitle = t('pay.title');
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0"><?= te('pay.title') ?></h1>
  <p class="muted"><?= te('pay.order_line', ['name' => listing_title($order), 'qty' => $order['quantity_kg'], 'farmer' => $order['farmer_name']]) ?></p>
  <p><strong><?= te('pay.amount_due') ?>: <?= format_money($order['total_price']) ?></strong></p>

  <?php if ($errors): ?>
    <div class="alert alert-error"><?= e(implode(' ', $errors)) ?></div>
  <?php endif; ?>

  <form class="stacked" method="post" action="" id="payForm">
    <?= csrf_field() ?>
    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">

    <label><?= te('pay.method') ?></label>
    <select name="method" id="payMethod">
      <option value="card">💳 <?= te('pay.card') ?></option>
      <option value="bank">🏦 <?= te('pay.bank') ?></option>
      <option value="wallet">📱 <?= te('pay.wallet') ?></option>
    </select>

    <div data-method="card">
      <label><?= te('pay.card_name') ?></label>
      <input type="text" name="card_name">
      <label><?= te('pay.card_number') ?></label>
      <input type="text" name="card_number" placeholder="<?= te('pay.card_ph') ?>" maxlength="19" inputmode="numeric">
      <div class="form-row">
        <div>
          <label><?= te('pay.expiry') ?></label>
          <input type="text" name="expiry" placeholder="MM/YY" maxlength="5">
        </div>
        <div>
          <label>CVV</label>
          <input type="password" name="cvv" maxlength="4" inputmode="numeric" autocomplete="off">
        </div>
      </div>
    </div>

    <div data-method="bank" hidden>
      <label><?= te('pay.bank_name') ?></label>
      <select name="bank_name">
        <option value=""><?= te('pay.select_bank') ?></option>
        <option>Bank of Ceylon</option><option>People's Bank</option><option>Commercial Bank</option>
        <option>Sampath Bank</option><option>Hatton National Bank</option><option>NSB</option>
      </select>
      <label><?= te('pay.account') ?></label>
      <input type="text" name="account_no" inputmode="numeric">
    </div>

    <div data-method="wallet" hidden>
      <label><?= te('pay.mobile') ?></label>
      <input type="tel" name="wallet_phone" placeholder="07XXXXXXXX">
    </div>

    <button type="submit" class="btn" onclick="this.disabled=true; this.form.submit();"><?= te('pay.pay') ?> <?= format_money($order['total_price']) ?></button>
  </form>
  <script>
    (function () {
      var select = document.getElementById('payMethod');
      function show() {
        document.querySelectorAll('#payForm [data-method]').forEach(function (el) {
          el.hidden = el.getAttribute('data-method') !== select.value;
        });
      }
      select.addEventListener('change', show);
      show();
    })();
  </script>
  <p class="muted" style="margin-top:14px;">🔒 <?= te('pay.note') ?></p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
