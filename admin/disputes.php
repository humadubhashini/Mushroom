<?php
/** Dispute-resolution workflow (FR-ADM.4). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['dispute_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $response = trim($_POST['admin_response'] ?? '');

    $stmt = $pdo->prepare(
        "SELECT d.*, o.buyer_id, o.farmer_id, o.status AS order_status, o.listing_id, o.quantity_kg
         FROM disputes d JOIN orders o ON o.id = d.order_id WHERE d.id = ? AND d.status = 'open'"
    );
    $stmt->execute([$id]);
    $dispute = $stmt->fetch();

    if (!$dispute || !in_array($decision, ['resolved', 'rejected'], true) || $response === '') {
        flash('error', 'Please write a response and choose a decision.');
    } else {
        $pdo->prepare('UPDATE disputes SET status = ?, admin_response = ?, resolved_at = NOW() WHERE id = ?')
            ->execute([$decision, $response, $id]);

        // Optionally cancel an unpaid order as part of the resolution.
        if (!empty($_POST['cancel_order']) && in_array($dispute['order_status'], ['pending', 'confirmed'], true)) {
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$dispute['order_id']]);
            $pdo->prepare("UPDATE listings SET quantity_kg = quantity_kg + ?, status = IF(status = 'sold_out', 'active', status) WHERE id = ?")
                ->execute([$dispute['quantity_kg'], $dispute['listing_id']]);
        }

        $vars = ['order' => $dispute['order_id'], 'response' => $response];
        $key = $decision === 'resolved' ? 'notif.dispute_resolved' : 'notif.dispute_rejected';
        notify($dispute['buyer_id'], $key, '/shared/dispute.php?order_id=' . $dispute['order_id'], $vars);
        notify($dispute['farmer_id'], $key, '/shared/dispute.php?order_id=' . $dispute['order_id'], $vars);
        log_admin_action('Marked dispute #' . $id . ' (order #' . $dispute['order_id'] . ') as ' . $decision);
        flash('success', 'Dispute updated and both parties notified.');
    }
    redirect('/admin/disputes.php');
}

$filter = ($_GET['status'] ?? 'open') === 'all' ? 'all' : 'open';
$sql = "SELECT d.*, o.total_price, o.status AS order_status, l.mushroom_type,
               ru.full_name AS raised_by_name, ru.role AS raised_by_role,
               bu.full_name AS buyer_name, fu.full_name AS farmer_name
        FROM disputes d
        JOIN orders o ON o.id = d.order_id
        JOIN listings l ON l.id = o.listing_id
        JOIN users ru ON ru.id = d.raised_by
        JOIN users bu ON bu.id = o.buyer_id
        JOIN users fu ON fu.id = o.farmer_id"
     . ($filter === 'open' ? " WHERE d.status = 'open'" : '')
     . ' ORDER BY d.created_at DESC';
$disputes = $pdo->query($sql)->fetchAll();

$pageTitle = 'Disputes';
include __DIR__ . '/../includes/header.php';
?>

<h1>⚖ Disputes</h1>
<div style="margin-bottom:16px;">
  <a href="?status=open" class="btn btn-small <?= $filter === 'open' ? '' : 'btn-outline' ?>">Open</a>
  <a href="?status=all" class="btn btn-small <?= $filter === 'all' ? '' : 'btn-outline' ?>">All</a>
</div>

<?php if (!$disputes): ?>
  <p class="muted">No disputes to show.</p>
<?php endif; ?>

<?php foreach ($disputes as $d): ?>
  <div class="card" style="margin-bottom:14px;">
    <strong>Order #<?= (int) $d['order_id'] ?></strong> &mdash; <?= e($d['mushroom_type']) ?>, <?= format_money($d['total_price']) ?>
    <span class="badge badge-<?= e($d['order_status']) ?>"><?= e(ucfirst($d['order_status'])) ?></span>
    <span class="badge <?= $d['status'] === 'open' ? 'badge-pending' : 'badge-completed' ?>"><?= e(ucfirst($d['status'])) ?></span>
    <p class="muted">Buyer: <?= e($d['buyer_name']) ?> &middot; Farmer: <?= e($d['farmer_name']) ?> &middot;
      Raised by <?= e($d['raised_by_name']) ?> (<?= e($d['raised_by_role']) ?>) on <?= format_date($d['created_at']) ?></p>
    <p><?= nl2br(e($d['reason'])) ?></p>
    <?php if ($d['status'] === 'open'): ?>
      <form class="stacked" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="dispute_id" value="<?= (int) $d['id'] ?>">
        <label>Response to both parties</label>
        <textarea name="admin_response" required></textarea>
        <?php if (in_array($d['order_status'], ['pending', 'confirmed'], true)): ?>
          <label><input type="checkbox" name="cancel_order" value="1"> Cancel this unpaid order</label>
        <?php endif; ?>
        <div>
          <button type="submit" name="decision" value="resolved" class="btn btn-small">Mark Resolved</button>
          <button type="submit" name="decision" value="rejected" class="btn btn-small btn-danger">Reject</button>
        </div>
      </form>
    <?php else: ?>
      <p><em>Admin response: <?= e($d['admin_response']) ?></em></p>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
