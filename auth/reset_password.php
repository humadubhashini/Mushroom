<?php
/** Forgotten password - step 2: choose a new password (FR-AUTH.4). */
require_once __DIR__ . '/../includes/bootstrap.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$user = null;
if ($token !== '') {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_expires_at > NOW()');
    $stmt->execute([hash('sha256', $token)]);
    $user = $stmt->fetch();
}

if (!$user) {
    flash('error', 'This password reset link is invalid or has expired.');
    redirect('/auth/forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        flash('error', 'Password must be at least 6 characters.');
    } elseif ($password !== $confirm) {
        flash('error', 'Passwords do not match.');
    } else {
        // Completing a reset via the emailed link also proves email ownership.
        $pdo->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL, is_verified = 1 WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_BCRYPT), $user['id']]);
        flash('success', 'Your password has been reset. Please log in.');
        redirect('/auth/login.php');
    }
    redirect('/auth/reset_password.php?token=' . urlencode($token));
}

$pageTitle = 'Reset Password';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Choose a New Password</h1>
  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label>New Password</label>
    <input type="password" name="password" required minlength="6">
    <label>Confirm New Password</label>
    <input type="password" name="confirm_password" required minlength="6">
    <button type="submit" class="btn">Reset Password</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
