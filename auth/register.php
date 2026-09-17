<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('/index.php');
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
    if (!in_array($role, ['farmer', 'buyer'], true)) {
        $errors[] = 'Please select whether you are registering as a Farmer or a Buyer.';
    }
    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO users (role, full_name, email, phone, password_hash, business_name, address, is_verified)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
        );
        // is_verified = 1 for the demo environment; in production this would start at 0
        // pending an email/OTP verification step (FR-AUTH.2).
        $stmt->execute([
            $role, $fullName, $email, $phone,
            password_hash($password, PASSWORD_BCRYPT),
            $businessName ?: null, $address ?: null,
        ]);
        clear_old();
        flash('success', 'Registration successful! You can now log in.');
        redirect('/auth/login.php');
    } else {
        flash('error', implode(' ', $errors));
    }
}

$pageTitle = 'Register';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Create an Account</h1>
  <form class="stacked" method="post" action="">
    <?= csrf_field() ?>

    <label>I am registering as</label>
    <select name="role" required>
      <option value="">-- Select role --</option>
      <option value="farmer" <?= old('role') === 'farmer' ? 'selected' : '' ?>>Farmer (I grow mushrooms)</option>
      <option value="buyer" <?= old('role') === 'buyer' ? 'selected' : '' ?>>Buyer (Hotel / Restaurant)</option>
    </select>

    <label>Full Name</label>
    <input type="text" name="full_name" value="<?= old('fullName') ?>" required>

    <label>Email Address</label>
    <input type="email" name="email" value="<?= old('email') ?>" required>

    <label>Phone Number</label>
    <input type="tel" name="phone" value="<?= old('phone') ?>">

    <label>Farm Name / Business Name (optional)</label>
    <input type="text" name="business_name" value="<?= old('businessName') ?>">

    <label>Address</label>
    <input type="text" name="address" value="<?= old('address') ?>">

    <div class="form-row">
      <div>
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <div>
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required>
      </div>
    </div>

    <button type="submit" class="btn">Register</button>
  </form>
  <p class="muted">Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Log in</a></p>
</div>

<?php clear_old(); include __DIR__ . '/../includes/footer.php'; ?>
