<?php
/** Feedback, suggestions and questions sent by farmers and buyers from the Contact page. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');
ensure_feedback_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM feedback WHERE id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if ($item && ($_POST['action'] ?? '') === 'reply') {
        $reply = trim($_POST['admin_reply'] ?? '');
        if ($reply === '') {
            flash('error', 'Please write a reply.');
        } else {
            $pdo->prepare("UPDATE feedback SET admin_reply = ?, status = 'replied', replied_at = NOW() WHERE id = ?")->execute([$reply, $id]);
            if ($item['user_id']) {
                notify($item['user_id'], 'notif.feedback_reply', '/contact.php#feedback', ['subject' => $item['subject']]);
            }
            log_admin_action('Replied to feedback #' . $id);
            flash('success', 'Reply sent. The user has been notified.');
        }
    } elseif ($item && ($_POST['action'] ?? '') === 'read' && $item['status'] === 'new') {
        $pdo->prepare("UPDATE feedback SET status = 'read' WHERE id = ?")->execute([$id]);
    }
    redirect('/admin/feedback.php?filter=' . urlencode($_POST['filter'] ?? ''));
}

$filter = $_GET['filter'] ?? '';
$sql = "SELECT f.*, u.full_name, u.email, u.phone, u.role, u.business_name
        FROM feedback f LEFT JOIN users u ON u.id = f.user_id";
if (in_array($filter, ['new', 'read', 'replied'], true)) {
    $sql .= ' WHERE f.status = ' . $pdo->quote($filter);
}
$sql .= ' ORDER BY f.created_at DESC, f.id DESC';
$items = $pdo->query($sql)->fetchAll();
$counts = $pdo->query("SELECT status, COUNT(*) c FROM feedback GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Feedback & Suggestions';
include __DIR__ . '/../includes/header.php';
?>

<h1>💬 Feedback &amp; Suggestions</h1>
<p class="muted">Messages sent by farmers and buyers from the Contact page. Replies are shown to the user and trigger a notification.</p>

<div style="margin-bottom:16px;">
  <a href="?" class="btn btn-small <?= $filter === '' ? '' : 'btn-outline' ?>">All</a>
  <a href="?filter=new" class="btn btn-small <?= $filter === 'new' ? '' : 'btn-outline' ?>">New (<?= (int) ($counts['new'] ?? 0) ?>)</a>
  <a href="?filter=read" class="btn btn-small <?= $filter === 'read' ? '' : 'btn-outline' ?>">Read (<?= (int) ($counts['read'] ?? 0) ?>)</a>
  <a href="?filter=replied" class="btn btn-small <?= $filter === 'replied' ? '' : 'btn-outline' ?>">Replied (<?= (int) ($counts['replied'] ?? 0) ?>)</a>
</div>

<?php if (!$items): ?>
  <p class="muted">No messages yet.</p>
<?php endif; ?>

<?php foreach ($items as $f): ?>
  <div class="card" style="margin-bottom:14px; <?= $f['status'] === 'new' ? 'border-left:4px solid var(--green);' : '' ?>">
    <span class="badge <?= $f['status'] === 'replied' ? 'badge-completed' : ($f['status'] === 'new' ? 'badge-pending' : 'badge-confirmed') ?>"><?= e(ucfirst($f['status'])) ?></span>
    <span class="badge badge-active"><?= e(ucfirst($f['type'])) ?></span>
    <strong style="margin-left:6px;"><?= e($f['subject']) ?></strong>
    <p class="muted" style="margin:6px 0;">
      <?= e($f['full_name'] ?? 'Deleted user') ?><?= $f['role'] ? ' (' . e(ucfirst($f['role'])) . ')' : '' ?>
      <?= $f['business_name'] ? ' &middot; ' . e($f['business_name']) : '' ?>
      <?= $f['email'] ? ' &middot; <a href="mailto:' . e($f['email']) . '">' . e($f['email']) . '</a>' : '' ?>
      <?= $f['phone'] ? ' &middot; <a href="' . e(tel_link($f['phone'])) . '">' . e($f['phone']) . '</a>' : '' ?>
      &middot; <?= e(date('d M Y, h:i A', strtotime($f['created_at']))) ?>
    </p>
    <p><?= nl2br(e($f['message'])) ?></p>

    <?php if ($f['admin_reply']): ?>
      <div class="alert alert-info"><strong>Your reply (<?= format_date($f['replied_at']) ?>):</strong><br><?= nl2br(e($f['admin_reply'])) ?></div>
    <?php endif; ?>

    <form class="stacked" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
      <input type="hidden" name="filter" value="<?= e($filter) ?>">
      <textarea name="admin_reply" rows="2" placeholder="<?= $f['admin_reply'] ? 'Update your reply' : 'Write a reply to this user' ?>"></textarea>
      <div>
        <button type="submit" name="action" value="reply" class="btn btn-small">Send Reply</button>
        <?php if ($f['status'] === 'new'): ?>
          <button type="submit" name="action" value="read" class="btn btn-small btn-outline">Mark as Read</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
