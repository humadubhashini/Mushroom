<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('buyer');
$buyerId = current_user()['id'];

$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT o.*, l.mushroom_type, u.full_name AS farmer_name
     FROM orders o JOIN listings l ON l.id = o.listing_id JOIN users u ON u.id = o.farmer_id
     WHERE o.id = ? AND o.buyer_id = ?"
);
$stmt->execute([$orderId, $buyerId]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'Order not found.');
    redirect('/buyer/orders.php');
}
if ($order['status'] !== 'confirmed') {
    flash('error', 'This order is not awaiting payment.');
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
        notify($buyerId, 'Payment for order #' . $order['id'] . ' failed: ' . $gateway['message'] . ' You were not charged.', '/buyer/payment.php?order_id=' . $order['id']);
        $errors[] = 'Payment failed: ' . $gateway['message'] . ' You have not been charged - please try again or use another method.';
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

            notify($buyerId, 'Payment of ' . format_money($order['total_price']) . ' for order #' . $order['id'] . ' was successful.', '/shared/receipt.php?order_id=' . $order['id']);
            notify($order['farmer_id'], 'Payment of ' . format_money($order['total_price']) . ' received for order #' . $order['id'] . '.', '/shared/receipt.php?order_id=' . $order['id']);

            flash('success', 'Payment successful! Reference: ' . $gateway['reference']);
            redirect('/shared/receipt.php?order_id=' . $order['id']);
        } catch (Exception $e) {
            $pdo->rollBack();
            flash('error', 'This order has already been paid or could not be processed.');
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
        if (trim($input['card_name'] ?? '') === '') $errors[] = 'Cardholder name is required.';
        if (!preg_match('/^\d{16}$/', $cardNumber)) $errors[] = 'Card number must be 16 digits.';
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', trim($input['expiry'] ?? ''))) $errors[] = 'Expiry must be in MM/YY format.';
        if (!preg_match('/^\d{3,4}$/', trim($input['cvv'] ?? ''))) $errors[] = 'CVV must be 3-4 digits.';
        if ($errors) return ['errors' => $errors, 'approved' => false, 'reference' => '', 'message' => ''];

        $reference = 'CARD-' . substr($cardNumber, -4) . '-' . strtoupper(bin2hex(random_bytes(3)));
        if ($cardNumber === '4000000000000002') {
            return ['errors' => [], 'approved' => false, 'reference' => $reference, 'message' => 'Card declined by issuing bank.'];
        }
    } elseif ($method === 'bank') {
        if (trim($input['bank_name'] ?? '') === '') $errors[] = 'Please select your bank.';
        if (!preg_match('/^\d{6,16}$/', trim($input['account_no'] ?? ''))) $errors[] = 'Enter a valid account number.';
    } elseif ($method === 'wallet') {
        if (!preg_match('/^07\d{8}$/', trim($input['wallet_phone'] ?? ''))) $errors[] = 'Enter a valid mobile number (07XXXXXXXX).';
    } else {
        $errors[] = 'Please choose a payment method.';
    }

    return ['errors' => $errors, 'approved' => !$errors, 'reference' => $reference, 'message' => 'Approved'];
}

$pageTitle = 'Secure Payment';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Secure Payment</h1>
  <p class="muted">Order: <?= e($order['mushroom_type']) ?> (<?= e($order['quantity_kg']) ?> kg) from <?= e($order['farmer_name']) ?></p>
  <p><strong>Amount Due: <?= format_money($order['total_price']) ?></strong></p>

  <?php if ($errors): ?>
    <div class="alert alert-error"><?= e(implode(' ', $errors)) ?></div>
  <?php endif; ?>

  <form class="stacked" method="post" action="" id="payForm">
    <?= csrf_field() ?>
    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">

    <label>Payment Method</label>
    <select name="method" id="payMethod">
      <option value="card">💳 Credit / Debit Card</option>
      <option value="bank">🏦 Online Bank Transfer</option>
      <option value="wallet">📱 Mobile Wallet (eZ Cash / mCash)</option>
    </select>

    <div data-method="card">
      <label>Cardholder Name</label>
      <input type="text" name="card_name">
      <label>Card Number</label>
      <input type="text" name="card_number" placeholder="16-digit card number" maxlength="19" inputmode="numeric">
      <div class="form-row">
        <div>
          <label>Expiry (MM/YY)</label>
          <input type="text" name="expiry" placeholder="MM/YY" maxlength="5">
        </div>
        <div>
          <label>CVV</label>
          <input type="password" name="cvv" maxlength="4" inputmode="numeric" autocomplete="off">
        </div>
      </div>
    </div>

    <div data-method="bank" hidden>
      <label>Bank</label>
      <select name="bank_name">
        <option value="">-- Select bank --</option>
        <option>Bank of Ceylon</option><option>People's Bank</option><option>Commercial Bank</option>
        <option>Sampath Bank</option><option>Hatton National Bank</option><option>NSB</option>
      </select>
      <label>Account Number</label>
      <input type="text" name="account_no" inputmode="numeric">
    </div>

    <div data-method="wallet" hidden>
      <label>Mobile Number</label>
      <input type="tel" name="wallet_phone" placeholder="07XXXXXXXX">
    </div>

    <button type="submit" class="btn" onclick="this.disabled=true; this.form.submit();">Pay <?= format_money($order['total_price']) ?></button>
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
  <p class="muted" style="margin-top:14px;">🔒 Simulated payment gateway for demonstration. No real card data is transmitted or stored &mdash; only a masked reference is kept. Test decline card: 4000 0000 0000 0002.</p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
