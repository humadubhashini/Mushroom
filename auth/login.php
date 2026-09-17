<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('/index.php');
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
    } else {
        login_user($user);
        clear_old();
        redirect(match ($user['role']) {
            'farmer' => '/farmer/dashboard.php',
            'buyer' => '/buyer/dashboard.php',
            'admin' => '/admin/dashboard.php',
        });
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
  <p class="muted">Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php">Register here</a></p>
  <p class="muted">Admin demo login: admin@mushroom.lk / Admin@123</p>
</div>

<?php clear_old(); include __DIR__ . '/../includes/footer.php'; ?>
