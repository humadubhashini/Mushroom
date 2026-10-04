<?php
/** Contact details and the feedback / suggestions form for farmers and buyers. */
require_once __DIR__ . '/includes/bootstrap.php';
ensure_feedback_table();

$user = current_user();
$canSend = $user && in_array($user['role'], ['farmer', 'buyer'], true);
$types = ['suggestion', 'question', 'complaint', 'other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!$canSend) {
        flash('error', t('contact.login_needed'));
        redirect('/contact.php');
    }
    $type = in_array($_POST['type'] ?? '', $types, true) ? $_POST['type'] : 'suggestion';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '' || mb_strlen($message) < 10) {
        flash('error', t('contact.err_fields'));
    } else {
        $pdo->prepare('INSERT INTO feedback (user_id, type, subject, message) VALUES (?, ?, ?, ?)')
            ->execute([$user['id'], $type, mb_substr($subject, 0, 200), $message]);
        foreach ($pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll() as $admin) {
            notify($admin['id'], 'New ' . $type . ' from ' . $user['full_name'] . ': ' . mb_substr($subject, 0, 80), '/admin/feedback.php');
        }
        flash('success', t('contact.sent'));
    }
    redirect('/contact.php#feedback');
}

$myMessages = [];
if ($canSend) {
    $stmt = $pdo->prepare('SELECT * FROM feedback WHERE user_id = ? ORDER BY created_at DESC, id DESC');
    $stmt->execute([$user['id']]);
    $myMessages = $stmt->fetchAll();
}

$pageTitle = t('contact.title');
include __DIR__ . '/includes/header.php';
?>

<h1>📞 <?= te('contact.title') ?></h1>
<p class="muted"><?= te('contact.intro') ?></p>

<div class="contact-grid">
  <a class="contact-card" href="<?= e(tel_link(CONTACT_PHONE)) ?>">
    <span class="icon">📞</span>
    <span class="label"><?= te('contact.phone') ?></span>
    <strong><?= e(CONTACT_PHONE) ?></strong>
  </a>
  <a class="contact-card" href="mailto:<?= e(CONTACT_EMAIL) ?>">
    <span class="icon">✉️</span>
    <span class="label"><?= te('contact.email') ?></span>
    <strong><?= e(CONTACT_EMAIL) ?></strong>
  </a>
  <a class="contact-card facebook" href="<?= e(CONTACT_FACEBOOK_URL) ?>" target="_blank" rel="noopener">
    <span class="icon"><?= FACEBOOK_ICON ?></span>
    <span class="label"><?= te('contact.facebook') ?></span>
    <strong><?= e(CONTACT_FACEBOOK_NAME) ?></strong>
  </a>
  <div class="contact-card">
    <span class="icon">🕘</span>
    <span class="label"><?= te('contact.hours') ?></span>
    <strong><?= te('contact.hours_value') ?></strong>
  </div>
</div>

<h2 id="feedback">💬 <?= te('contact.feedback_title') ?></h2>
<p class="muted"><?= te('contact.feedback_intro') ?></p>

<?php if ($canSend): ?>
  <div class="card form-narrow" style="margin-left:0;">
    <form class="stacked" method="post" action="">
      <?= csrf_field() ?>
      <label><?= te('contact.type') ?></label>
      <select name="type">
        <?php foreach ($types as $tp): ?>
          <option value="<?= $tp ?>"><?= te('contact.type_' . $tp) ?></option>
        <?php endforeach; ?>
      </select>
      <label><?= te('contact.subject') ?></label>
      <input type="text" name="subject" maxlength="200" required>
      <label><?= te('contact.message') ?></label>
      <textarea name="message" rows="5" required minlength="10" placeholder="<?= te('contact.message_ph') ?>"></textarea>
      <button type="submit" class="btn"><?= te('contact.send') ?></button>
    </form>
  </div>

  <?php if ($myMessages): ?>
    <h3><?= te('contact.my_messages') ?></h3>
    <?php foreach ($myMessages as $m): ?>
      <div class="card" style="margin-bottom:12px;">
        <span class="badge badge-<?= $m['status'] === 'replied' ? 'completed' : 'pending' ?>"><?= te('contact.status_' . $m['status']) ?></span>
        <span class="badge badge-active"><?= te('contact.type_' . $m['type']) ?></span>
        <strong style="margin-left:6px;"><?= e($m['subject']) ?></strong>
        <span class="muted"> &middot; <?= format_date($m['created_at']) ?></span>
        <p style="margin:8px 0;"><?= nl2br(e($m['message'])) ?></p>
        <?php if ($m['admin_reply']): ?>
          <div class="alert alert-info" style="margin:0;"><strong><?= te('contact.reply_from') ?>:</strong><br><?= nl2br(e($m['admin_reply'])) ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
<?php elseif ($user): ?>
  <p class="muted"><?= te('contact.admin_note') ?> <a href="<?= BASE_URL ?>/admin/feedback.php"><?= te('contact.view_feedback') ?></a></p>
<?php else: ?>
  <div class="alert alert-info">
    <?= te('contact.login_needed') ?>
    <a href="<?= BASE_URL ?>/auth/login.php"><?= te('nav.login') ?></a> /
    <a href="<?= BASE_URL ?>/auth/register.php"><?= te('nav.register') ?></a>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
