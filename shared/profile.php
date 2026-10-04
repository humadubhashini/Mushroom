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
            flash('error', t('profile.err_current'));
        } elseif (strlen($new) < 6) {
            flash('error', t('register.err_password'));
        } elseif ($new !== ($_POST['confirm_password'] ?? '')) {
            flash('error', t('register.err_match'));
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_BCRYPT), $userId]);
            flash('success', t('profile.password_changed'));
        }
        redirect('/shared/profile.php');
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $businessName = trim($_POST['business_name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $errors = [];
    if ($fullName === '') $errors[] = t('register.err_name');

    $imageError = null;
    $imageFile = handle_image_upload('profile_image', UPLOAD_PROFILES, $imageError);
    if ($imageError) $errors[] = $imageError;

    if (!$errors) {
        $pdo->prepare('UPDATE users SET full_name = ?, phone = ?, business_name = ?, address = ?, profile_image = ? WHERE id = ?')
            ->execute([$fullName, $phone ?: null, $businessName ?: null, $address ?: null, $imageFile ?: $profile['profile_image'], $userId]);
        $_SESSION['user']['full_name'] = $fullName;
        flash('success', t('profile.updated'));
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

$pageTitle = t('profile.title');
include __DIR__ . '/../includes/header.php';
?>

<h1><?= te('profile.title') ?></h1>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); align-items:start;">
  <div class="card">
    <div style="display:flex; gap:16px; align-items:center; margin-bottom:12px;">
      <img src="<?= $profile['profile_image'] ? BASE_URL . '/assets/uploads/profiles/' . e($profile['profile_image']) : AVATAR_PLACEHOLDER ?>" alt="Profile" style="width:96px; height:96px; border-radius:50%; object-fit:cover;">
      <div>
        <strong><?= e($profile['full_name']) ?></strong><br>
        <span class="badge badge-active"><?= te('role.' . $profile['role']) ?></span>
        <?php if ($rating && $rating['total']): ?>
          <br><span class="stars"><?= str_repeat('★', (int) round($rating['avg_rating'])) ?></span>
          <span class="muted"><?= number_format($rating['avg_rating'], 1) ?> (<?= te('profile.reviews', ['n' => (int) $rating['total']]) ?>)</span>
        <?php endif; ?>
      </div>
    </div>
    <form class="stacked" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="profile">
      <label><?= te('form.full_name') ?></label>
      <input type="text" name="full_name" value="<?= e($profile['full_name']) ?>" required>
      <label><?= te('profile.email_fixed') ?></label>
      <input type="email" value="<?= e($profile['email']) ?>" disabled>
      <label><?= te('form.phone') ?></label>
      <input type="tel" name="phone" value="<?= e($profile['phone']) ?>">
      <label><?= te($profile['role'] === 'buyer' ? 'profile.hotel_name' : ($profile['role'] === 'farmer' ? 'profile.farm_name' : 'profile.organisation')) ?></label>
      <input type="text" name="business_name" value="<?= e($profile['business_name']) ?>">
      <label><?= te('form.address') ?></label>
      <input type="text" name="address" value="<?= e($profile['address']) ?>">
      <label><?= te('profile.image') ?></label>
      <input type="file" name="profile_image" accept="image/*">
      <button type="submit" class="btn"><?= te('profile.save') ?></button>
    </form>
  </div>

  <div class="card">
    <h2 class="mt-0"><?= te('profile.change_password') ?></h2>
    <form class="stacked" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="password">
      <label><?= te('form.current_password') ?></label>
      <input type="password" name="current_password" required>
      <label><?= te('form.new_password') ?></label>
      <input type="password" name="new_password" required minlength="6">
      <label><?= te('form.confirm_password') ?></label>
      <input type="password" name="confirm_password" required minlength="6">
      <button type="submit" class="btn"><?= te('profile.change_password') ?></button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
