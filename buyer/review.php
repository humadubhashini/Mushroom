<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('buyer');
$buyerId = current_user()['id'];

$orderId = (int) ($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ? AND status = 'completed'");
$stmt->execute([$orderId, $buyerId]);
$order = $stmt->fetch();

if (!$order) {
    flash('error', t('review.cannot'));
    redirect('/buyer/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        flash('error', t('review.err_rating'));
    } else {
        $pdo->prepare('INSERT INTO reviews (order_id, buyer_id, farmer_id, rating, comment) VALUES (?, ?, ?, ?, ?)')
            ->execute([$order['id'], $buyerId, $order['farmer_id'], $rating, $comment ?: null]);
        notify($order['farmer_id'], 'notif.review', '/shared/profile.php', ['stars' => $rating, 'order' => $order['id']]);
        flash('success', t('review.thanks'));
        redirect('/buyer/orders.php');
    }
}

$pageTitle = t('review.title');
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0"><?= te('review.title') ?></h1>
  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">

    <label><?= te('review.rating') ?></label>
    <select name="rating" required>
      <option value=""><?= te('register.select_role') ?></option>
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <option value="<?= $i ?>"><?= str_repeat('★', $i) ?> (<?= $i ?>/5)</option>
      <?php endfor; ?>
    </select>

    <label><?= te('review.comment') ?></label>
    <textarea name="comment" placeholder="<?= te('review.comment_ph') ?>"></textarea>

    <button type="submit" class="btn"><?= te('review.submit') ?></button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
