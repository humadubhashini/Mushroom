<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(role_home(current_user()['role']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $_SESSION['old'] = ['email' => $email];

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        flash('error', 'Invalid email or password.');
    } elseif ($user['status'] === 'suspended') {
        flash('error', 'This account has been suspended. Please contact support.');
    } elseif (!$user['is_verified']) {
        // FR-AUTH.2: unverified accounts must complete OTP verification first.
        $code = issue_otp($user['id'], $user['email']);
        $_SESSION['pending_verification'] = (int) $user['id'];
        flash('info', 'Please verify your account first. A new code was sent to ' . $user['email']
            . (DEMO_MODE ? ' (demo code: ' . $code . ')' : '') . '.');
        redirect('/auth/verify.php');
    } else {
        login_user($user);
        clear_old();
        redirect(role_home($user['role']));
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Log In</h1>
  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>
    <label>Email Address</label>
    <input type="email" name="email" value="<?= old('email') ?>" required autofocus>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit" class="btn">Log In</button>
  </form>
  <p class="muted"><a href="<?= BASE_URL ?>/auth/forgot_password.php">Forgot your password?</a></p>
  <p class="muted">Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php">Register here</a></p>
  <details class="muted">
    <summary>Demo accounts</summary>
    <p>Admin: admin@mushroom.lk / Admin@123<br>
    Farmer: farmer@mushroom.lk / Farmer@123<br>
    Buyer: buyer@mushroom.lk / Buyer@123<br>
    Expert: expert@mushroom.lk / Expert@123</p>
  </details>
</div>

<?php clear_old(); include __DIR__ . '/../includes/footer.php'; ?>
