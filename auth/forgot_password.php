<?php
/** Forgotten password - step 1: request a reset link (FR-AUTH.4). */
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $demoLink = null;
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE users SET reset_token = ?, reset_expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?')
            ->execute([hash('sha256', $token), $user['id']]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $link = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL . '/auth/reset_password.php?token=' . $token;
        send_email($user['email'], APP_NAME . ' password reset', "Reset your password using this link (valid for 30 minutes):\n$link");
        $demoLink = $link;
    }

    // Same message whether or not the email exists, so accounts cannot be enumerated.
    flash('success', 'If an account exists for that email, a password reset link has been sent.');
    if (DEMO_MODE && $demoLink) {
        $_SESSION['demo_reset_link'] = $demoLink;
    }
    redirect('/auth/forgot_password.php');
}

$demoLink = $_SESSION['demo_reset_link'] ?? null;
unset($_SESSION['demo_reset_link']);

$pageTitle = 'Forgot Password';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Forgot Password</h1>
  <?php if ($demoLink): ?>
    <div class="alert alert-info">Demo mode: <a href="<?= e($demoLink) ?>">click here to reset your password</a></div>
  <?php endif; ?>
  <p class="muted">Enter your registered email address and we'll send you a link to reset your password.</p>
  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>
    <label>Email Address</label>
    <input type="email" name="email" required autofocus>
    <button type="submit" class="btn">Send Reset Link</button>
  </form>
  <p class="muted"><a href="<?= BASE_URL ?>/auth/login.php">&larr; Back to login</a></p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
