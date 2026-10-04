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
        flash('success', 'A new verification code has been sent.');
        if (DEMO_MODE) {
            flash('info', 'Demo mode: your verification code is ' . $code);
        }
        redirect('/auth/verify.php');
    }

    $code = trim($_POST['otp'] ?? '');
    $expired = !$pending['otp_expires_at'] || strtotime($pending['otp_expires_at']) < time();

    if ($expired) {
        flash('error', 'This code has expired. Please request a new one.');
    } elseif (!$pending['otp_code'] || !password_verify($code, $pending['otp_code'])) {
        flash('error', 'Incorrect verification code.');
    } else {
        $pdo->prepare('UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?')
            ->execute([$pending['id']]);
        unset($_SESSION['pending_verification']);
        flash('success', 'Your account is verified. You can now log in.');
        redirect('/auth/login.php');
    }
    redirect('/auth/verify.php');
}

$pageTitle = 'Verify Account';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Verify Your Account</h1>
  <p class="muted">Enter the 6-digit code sent to <strong><?= e($pending['email']) ?></strong>. The code expires in <?= OTP_TTL_MINUTES ?> minutes.</p>
  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>
    <label>Verification Code</label>
    <input type="text" name="otp" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus>
    <button type="submit" class="btn">Verify</button>
  </form>
  <form method="post" action="">
    <?= csrf_field() ?>
    <button type="submit" name="action" value="resend" class="btn btn-outline btn-small" style="margin-top:14px;">Resend code</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
