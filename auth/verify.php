<?php
/** Account verification by one-time code (FR-AUTH.2). */
require_once __DIR__ . '/../includes/bootstrap.php';

$userId = (int) ($_SESSION['pending_verification'] ?? 0);
if (!$userId) {
    redirect('/auth/login.php');
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$pending = $stmt->fetch();
if (!$pending || $pending['is_verified']) {
    unset($_SESSION['pending_verification']);
    redirect('/auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'resend') {
        $code = issue_otp($pending['id'], $pending['email']);
        flash('success', t('otp.resent'));
        if (DEMO_MODE) {
            flash('info', t('otp.demo_code', ['code' => $code]));
        }
        redirect('/auth/verify.php');
    }

    $code = trim($_POST['otp'] ?? '');
    $expired = !$pending['otp_expires_at'] || strtotime($pending['otp_expires_at']) < time();

    if ($expired) {
        flash('error', t('otp.expired'));
    } elseif (!$pending['otp_code'] || !password_verify($code, $pending['otp_code'])) {
        flash('error', t('otp.incorrect'));
    } else {
        $pdo->prepare('UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?')
            ->execute([$pending['id']]);
        unset($_SESSION['pending_verification']);
        if ($pending['role'] === 'farmer' && !$pending['is_approved']) {
            // Figure 4.7, step 5: raise a verification request to the administrator.
            foreach ($pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll() as $admin) {
                notify($admin['id'], 'Farmer ' . $pending['full_name'] . ' (' . $pending['email'] . ') is waiting for approval.', '/admin/users.php?role=farmer');
            }
        }
        flash('success', t('otp.verified'));
        redirect('/auth/login.php');
    }
    redirect('/auth/verify.php');
}

$pageTitle = t('otp.title');
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0"><?= te('otp.title') ?></h1>
  <p class="muted"><?= te('otp.instructions', ['email' => $pending['email'], 'minutes' => OTP_TTL_MINUTES]) ?></p>
  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>
    <label><?= te('otp.code') ?></label>
    <input type="text" name="otp" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus>
    <button type="submit" class="btn"><?= te('otp.verify') ?></button>
  </form>
  <form method="post" action="">
    <?= csrf_field() ?>
    <button type="submit" name="action" value="resend" class="btn btn-outline btn-small" style="margin-top:14px;"><?= te('otp.resend') ?></button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
