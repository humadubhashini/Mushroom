<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');
$adminId = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'add_expert') {
        // Agricultural Expert / Content Contributor accounts (SRS 2.3) are created by the admin.
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$email]);
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            flash('error', 'Enter a name, valid email and a password of at least 6 characters.');
        } elseif ($exists->fetch()) {
            flash('error', 'An account with this email already exists.');
        } else {
            $pdo->prepare("INSERT INTO users (role, full_name, email, password_hash, business_name, is_verified) VALUES ('expert', ?, ?, ?, ?, 1)")
                ->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT), trim($_POST['organisation'] ?? '') ?: null]);
            log_admin_action('Created expert account ' . $email);
            flash('success', 'Expert account created.');
        }
        redirect('/admin/users.php');
    }

    $target = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $target->execute([$userId]);
    $target = $target->fetch();

    if (!$target || $userId === $adminId || $target['role'] === 'admin') {
        flash('error', 'You cannot modify this account.');
    } elseif ($action === 'suspend') {
        $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$userId]);
        $pdo->prepare("UPDATE listings SET status = 'removed' WHERE farmer_id = ? AND status = 'active'")->execute([$userId]);
        log_admin_action('Suspended user ' . $target['email']);
        flash('success', 'User suspended and their active listings hidden.');
    } elseif ($action === 'activate') {
        $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$userId]);
        log_admin_action('Activated user ' . $target['email']);
        flash('success', 'User activated.');
    } elseif ($action === 'remove') {
        $hasOrders = $pdo->prepare('SELECT COUNT(*) c FROM orders WHERE buyer_id = ? OR farmer_id = ?');
        $hasOrders->execute([$userId, $userId]);
        if ($hasOrders->fetch()['c'] > 0) {
            flash('error', 'This user has order/payment history, which must be kept. Suspend the account instead.');
        } else {
            $pdo->prepare('DELETE FROM reviews WHERE buyer_id = ? OR farmer_id = ?')->execute([$userId, $userId]);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            log_admin_action('Removed user ' . $target['email']);
            flash('success', 'User removed.');
        }
    }
    redirect('/admin/users.php');
}

$roleFilter = $_GET['role'] ?? '';
$sql = 'SELECT * FROM users WHERE 1=1';
$params = [];
if (in_array($roleFilter, ['farmer', 'buyer', 'admin', 'expert'], true)) {
    $sql .= ' AND role = ?';
    $params[] = $roleFilter;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>

<h1>Manage Users</h1>

<div style="margin-bottom:16px;">
  <a href="?role=" class="btn btn-small <?= $roleFilter === '' ? '' : 'btn-outline' ?>">All</a>
  <a href="?role=farmer" class="btn btn-small <?= $roleFilter === 'farmer' ? '' : 'btn-outline' ?>">Farmers</a>
  <a href="?role=buyer" class="btn btn-small <?= $roleFilter === 'buyer' ? '' : 'btn-outline' ?>">Buyers</a>
  <a href="?role=expert" class="btn btn-small <?= $roleFilter === 'expert' ? '' : 'btn-outline' ?>">Experts</a>
</div>

<table class="data-table">
  <tr><th>Name</th><th>Email</th><th>Role</th><th>Business</th><th>Status</th><th>Joined</th><th>Action</th></tr>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= e($u['full_name']) ?><?= $u['is_verified'] ? '' : ' <span class="badge badge-pending">Unverified</span>' ?></td>
      <td><?= e($u['email']) ?></td>
      <td><?= e(ucfirst($u['role'])) ?></td>
      <td><?= e($u['business_name'] ?: '-') ?></td>
      <td><span class="badge <?= $u['status'] === 'active' ? 'badge-active' : 'badge-cancelled' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
      <td><?= format_date($u['created_at']) ?></td>
      <td>
        <?php if ($u['id'] !== $adminId && $u['role'] !== 'admin'): ?>
          <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
            <?php if ($u['status'] === 'active'): ?>
              <button type="submit" name="action" value="suspend" class="btn btn-small btn-danger" onclick="return confirm('Suspend this user?')">Suspend</button>
            <?php else: ?>
              <button type="submit" name="action" value="activate" class="btn btn-small">Activate</button>
            <?php endif; ?>
            <button type="submit" name="action" value="remove" class="btn btn-small btn-outline" onclick="return confirm('Permanently remove this user?')">Remove</button>
          </form>
        <?php else: ?>
          <span class="muted">&mdash;</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<div class="card form-narrow" style="margin-top:24px;">
  <h2 class="mt-0">Add Agricultural Expert</h2>
  <p class="muted">Experts can manage Knowledge Hub tutorials, edit treatment recommendations and review AI diagnoses.</p>
  <form class="stacked" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_expert">
    <label>Full Name</label><input type="text" name="full_name" required>
    <label>Email</label><input type="email" name="email" required>
    <label>Organisation</label><input type="text" name="organisation" placeholder="e.g. Department of Agriculture">
    <label>Temporary Password</label><input type="password" name="password" required minlength="6">
    <button type="submit" class="btn">Create Expert Account</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
