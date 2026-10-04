<?php
/** Profile management for every role (FR-AUTH.5) + change password. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_login();
$userId = current_user()['id'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'profile';

    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($current, $profile['password_hash'])) {
            flash('error', 'Your current password is incorrect.');
        } elseif (strlen($new) < 6) {
            flash('error', 'New password must be at least 6 characters.');
        } elseif ($new !== ($_POST['confirm_password'] ?? '')) {
            flash('error', 'New passwords do not match.');
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_BCRYPT), $userId]);
            flash('success', 'Password changed successfully.');
        }
        redirect('/shared/profile.php');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $businessName = trim($_POST['business_name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $errors = [];
    if ($fullName === '') $errors[] = 'Full name is required.';

    $imageError = null;
    $imageFile = handle_image_upload('profile_image', UPLOAD_PROFILES, $imageError);
    if ($imageError) $errors[] = $imageError;

    if (!$errors) {
        $pdo->prepare('UPDATE users SET full_name = ?, phone = ?, business_name = ?, address = ?, profile_image = ? WHERE id = ?')
            ->execute([$fullName, $phone ?: null, $businessName ?: null, $address ?: null, $imageFile ?: $profile['profile_image'], $userId]);
        $_SESSION['user']['full_name'] = $fullName;
        flash('success', 'Profile updated.');
    } else {
        flash('error', implode(' ', $errors));
    }
    redirect('/shared/profile.php');
}

$rating = null;
if ($profile['role'] === 'farmer') {
    $r = $pdo->prepare('SELECT AVG(rating) avg_rating, COUNT(*) total FROM reviews WHERE farmer_id = ?');
    $r->execute([$userId]);
    $rating = $r->fetch();
}

$pageTitle = 'My Profile';
include __DIR__ . '/../includes/header.php';
?>

<h1>My Profile</h1>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); align-items:start;">
  <div class="card">
    <div style="display:flex; gap:16px; align-items:center; margin-bottom:12px;">
      <img src="<?= $profile['profile_image'] ? BASE_URL . '/assets/uploads/profiles/' . e($profile['profile_image']) : 'https://placehold.co/96x96?text=%F0%9F%91%A4' ?>" alt="Profile" style="width:96px; height:96px; border-radius:50%; object-fit:cover;">
      <div>
        <strong><?= e($profile['full_name']) ?></strong><br>
        <span class="badge badge-active"><?= e(ucfirst($profile['role'])) ?></span>
        <?php if ($rating && $rating['total']): ?>
          <br><span class="stars"><?= str_repeat('★', (int) round($rating['avg_rating'])) ?></span>
          <span class="muted"><?= number_format($rating['avg_rating'], 1) ?> (<?= (int) $rating['total'] ?> reviews)</span>
        <?php endif; ?>
      </div>
    </div>
    <form class="stacked" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="profile">
      <label>Full Name</label>
      <input type="text" name="full_name" value="<?= e($profile['full_name']) ?>" required>
      <label>Email (cannot be changed)</label>
      <input type="email" value="<?= e($profile['email']) ?>" disabled>
      <label>Phone</label>
      <input type="tel" name="phone" value="<?= e($profile['phone']) ?>">
      <label><?= $profile['role'] === 'buyer' ? 'Hotel / Restaurant Name' : ($profile['role'] === 'farmer' ? 'Farm Name' : 'Organisation') ?></label>
      <input type="text" name="business_name" value="<?= e($profile['business_name']) ?>">
      <label>Address</label>
      <input type="text" name="address" value="<?= e($profile['address']) ?>">
      <label>Profile Image</label>
      <input type="file" name="profile_image" accept="image/*">
      <button type="submit" class="btn">Save Profile</button>
    </form>
  </div>

  <div class="card">
    <h2 class="mt-0">Change Password</h2>
    <form class="stacked" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="password">
      <label>Current Password</label>
      <input type="password" name="current_password" required>
      <label>New Password</label>
      <input type="password" name="new_password" required minlength="6">
      <label>Confirm New Password</label>
      <input type="password" name="confirm_password" required minlength="6">
      <button type="submit" class="btn">Change Password</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
