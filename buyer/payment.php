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
     * This demo processor validates card-shape input locally and never
     * stores raw card data (only a masked reference is kept), matching
     * NFR-SEC.3. To go live, swap this block for a real gateway SDK call
     * (e.g. PayHere/Stripe) while keeping the same success/failure branch
     * and the payments table untouched.
     */
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $cardName = trim($_POST['card_name'] ?? '');
    $expiry = trim($_POST['expiry'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');

    if ($cardName === '') $errors[] = 'Cardholder name is required.';
    if (!preg_match('/^\d{16}$/', $cardNumber)) $errors[] = 'Card number must be 16 digits.';
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) $errors[] = 'Expiry must be in MM/YY format.';
    if (!preg_match('/^\d{3,4}$/', $cvv)) $errors[] = 'CVV must be 3-4 digits.';

    if (!$errors) {
        $maskedRef = 'VISA-' . substr($cardNumber, -4) . '-' . strtoupper(bin2hex(random_bytes(3)));

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO payments (order_id, amount, method, gateway_reference, status, paid_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            )->execute([$order['id'], $order['total_price'], 'card', $maskedRef, 'success']);

            $pdo->prepare("UPDATE orders SET status = 'paid' WHERE id = ?")->execute([$order['id']]);
            $pdo->commit();

            flash('success', 'Payment successful! Reference: ' . $maskedRef);
            redirect('/buyer/orders.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Payment could not be processed. Please try again.';
        }
    }
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

  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">

    <label>Cardholder Name</label>
    <input type="text" name="card_name" required>

    <label>Card Number</label>
    <input type="text" name="card_number" placeholder="16-digit card number" maxlength="19" required>

    <div class="form-row">
      <div>
        <label>Expiry (MM/YY)</label>
        <input type="text" name="expiry" placeholder="MM/YY" maxlength="5" required>
      </div>
      <div>
        <label>CVV</label>
        <input type="text" name="cvv" maxlength="4" required>
      </div>
    </div>

    <button type="submit" class="btn">Pay <?= format_money($order['total_price']) ?></button>
  </form>
  <p class="muted" style="margin-top:14px;">🔒 This is a simulated payment gateway for demonstration purposes. No real card data is transmitted or stored.</p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
