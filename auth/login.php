<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $_SESSION['old'] = ['email' => $email];

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        flash('error', t('login.invalid'));
    } elseif ($user['status'] === 'suspended') {
        flash('error', t('login.suspended'));
    } elseif (!$user['is_verified']) {
        // FR-AUTH.2: unverified accounts must complete OTP verification first.
        $code = issue_otp($user['id'], $user['email']);
        $_SESSION['pending_verification'] = (int) $user['id'];
        flash('info', t('login.verify_first', ['email' => $user['email']]) . (DEMO_MODE ? ' ' . t('otp.demo_code', ['code' => $code]) : ''));
        redirect('/auth/verify.php');
    } else {
        // Logging in replaces any account that was already signed in on this
        // browser, so you can switch between farmer / buyer / admin accounts.
        login_user($user);
        clear_old();
        redirect(role_home($user['role']));
    }
    redirect('/auth/login.php');
}

$current = current_user();
$pageTitle = t('login.title');
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0"><?= te('login.title') ?></h1>
  <?php if ($current): ?>
    <div class="alert alert-info">
      <?= te('login.already', ['name' => $current['full_name'], 'role' => t('role.' . $current['role'])]) ?>
      <a href="<?= BASE_URL . role_home($current['role']) ?>"><?= te('login.go_dashboard') ?></a>
    </div>
  <?php endif; ?>
  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>
    <label><?= te('form.email') ?></label>
    <input type="email" name="email" value="<?= old('email') ?>" required autofocus>

    <label><?= te('form.password') ?></label>
    <input type="password" name="password" required>

    <button type="submit" class="btn"><?= te('login.title') ?></button>
  </form>
  <p class="muted"><a href="<?= BASE_URL ?>/auth/forgot_password.php"><?= te('login.forgot') ?></a></p>
  <p class="muted"><?= te('login.no_account') ?> <a href="<?= BASE_URL ?>/auth/register.php"><?= te('login.register_here') ?></a></p>
  <details class="muted" open>
    <summary><?= te('login.demo_accounts') ?></summary>
    <p><?= te('role.admin') ?>: admin@mushroom.lk / Admin@123<br>
    <?= te('role.farmer') ?>: farmer@mushroom.lk / Farmer@123<br>
    <?= te('role.buyer') ?>: buyer@mushroom.lk / Buyer@123<br>
    <?= te('role.expert') ?>: expert@mushroom.lk / Expert@123</p>
  </details>
</div>

<?php clear_old(); include __DIR__ . '/../includes/footer.php'; ?>
