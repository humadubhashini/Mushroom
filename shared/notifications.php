<?php
/** In-app notification centre (FR-MKT.5, FR-NOT.1 - FR-NOT.3). */
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$userId = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$userId]);
    redirect('/shared/notifications.php');
}

// Opening a notification marks it read and follows its link.
if (isset($_GET['open'])) {
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE id = ? AND user_id = ?');
    $stmt->execute([(int) $_GET['open'], $userId]);
    if ($n = $stmt->fetch()) {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([$n['id']]);
        redirect($n['link'] ?: '/shared/notifications.php');
    }
    redirect('/shared/notifications.php');
}

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 100');
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$pageTitle = t('nav.notifications');
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
  <h1>🔔 <?= te('nav.notifications') ?></h1>
  <?php if ($notifications): ?>
    <form method="post"><?= csrf_field() ?><button class="btn btn-small btn-outline" type="submit"><?= te('notif.mark_all') ?></button></form>
  <?php endif; ?>
</div>

<?php if (!$notifications): ?>
  <p class="muted"><?= te('notif.none') ?></p>
<?php else: ?>
  <ul class="notif-list card" style="padding:0;">
    <?php foreach ($notifications as $n): ?>
      <li class="<?= $n['is_read'] ? '' : 'unread' ?>">
        <a href="?open=<?= (int) $n['id'] ?>" style="color:inherit; text-decoration:none;"><?= e(notification_text($n['message'])) ?></a><br>
        <span class="time"><?= e(date('d M Y, h:i A', strtotime($n['created_at']))) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
