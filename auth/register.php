<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect(role_home(current_user()['role']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $role = $_POST['role'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $businessName = trim($_POST['business_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $_SESSION['old'] = compact('role', 'fullName', 'email', 'phone', 'businessName', 'address');

    $errors = [];
    if (!in_array($role, ['farmer', 'buyer'], true)) $errors[] = t('register.err_role');
    if ($fullName === '') $errors[] = t('register.err_name');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('register.err_email');
    if (strlen($password) < 6) $errors[] = t('register.err_password');
    if ($password !== $confirm) $errors[] = t('register.err_match');

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = t('register.err_exists');
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (role, full_name, email, phone, password_hash, business_name, address, is_verified)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)'
        );
        $stmt->execute([
            $role, $fullName, $email, $phone,
            password_hash($password, PASSWORD_BCRYPT),
            $businessName ?: null, $address ?: null,
        ]);
        $userId = (int) $pdo->lastInsertId();
        clear_old();

        // FR-AUTH.2: the account stays unverified until the OTP is entered.
        $code = issue_otp($userId, $email);
        $_SESSION['pending_verification'] = $userId;
        flash('success', t('register.success', ['email' => $email]));
        if (DEMO_MODE) {
            flash('info', t('otp.demo_code', ['code' => $code]));
        }
        redirect('/auth/verify.php');
    }
    flash('error', implode(' ', $errors));
    redirect('/auth/register.php');
}

$pageTitle = t('register.title');
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0"><?= te('register.title') ?></h1>
  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>

    <label><?= te('register.as') ?></label>
    <select name="role" required>
      <option value=""><?= te('register.select_role') ?></option>
      <option value="farmer" <?= old('role') === 'farmer' ? 'selected' : '' ?>><?= te('register.role_farmer') ?></option>
      <option value="buyer" <?= old('role') === 'buyer' ? 'selected' : '' ?>><?= te('register.role_buyer') ?></option>
    </select>

    <label><?= te('form.full_name') ?></label>
    <input type="text" name="full_name" value="<?= old('fullName') ?>" required>

    <label><?= te('form.email') ?></label>
    <input type="email" name="email" value="<?= old('email') ?>" required>

    <label><?= te('form.phone') ?></label>
    <input type="tel" name="phone" value="<?= old('phone') ?>">

    <label><?= te('register.business') ?></label>
    <input type="text" name="business_name" value="<?= old('businessName') ?>">

    <label><?= te('form.address') ?></label>
    <input type="text" name="address" value="<?= old('address') ?>">

    <div class="form-row">
      <div>
        <label><?= te('form.password') ?></label>
        <input type="password" name="password" required>
      </div>
      <div>
        <label><?= te('form.confirm_password') ?></label>
        <input type="password" name="confirm_password" required>
      </div>
    </div>

    <button type="submit" class="btn"><?= te('register.button') ?></button>
  </form>
  <p class="muted"><?= te('register.have_account') ?> <a href="<?= BASE_URL ?>/auth/login.php"><?= te('nav.login') ?></a></p>
</div>

<?php clear_old(); include __DIR__ . '/../includes/footer.php'; ?>
