<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('buyer');
$buyerId = current_user()['id'];

$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ? AND status = 'completed'");
$stmt->execute([$orderId, $buyerId]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', 'This order cannot be reviewed.');
    redirect('/buyer/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        flash('error', 'Please select a rating between 1 and 5.');
    } else {
        $pdo->prepare('INSERT INTO reviews (order_id, buyer_id, farmer_id, rating, comment) VALUES (?, ?, ?, ?, ?)')
            ->execute([$order['id'], $buyerId, $order['farmer_id'], $rating, $comment ?: null]);
        notify($order['farmer_id'], 'You received a ' . $rating . '-star review for order #' . $order['id'] . '.', '/shared/profile.php');
        flash('success', 'Thank you for your feedback!');
        redirect('/buyer/orders.php');
    }
}

$pageTitle = 'Rate Farmer';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Rate This Transaction</h1>
  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">

    <label>Rating</label>
    <select name="rating" required>
      <option value="">-- Select --</option>
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <option value="<?= $i ?>"><?= str_repeat('★', $i) ?> (<?= $i ?>/5)</option>
      <?php endfor; ?>
    </select>

    <label>Comment (optional)</label>
    <textarea name="comment" placeholder="Share your experience with this farmer"></textarea>

    <button type="submit" class="btn">Submit Review</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
