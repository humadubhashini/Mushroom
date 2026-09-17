<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');
$adminId = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($userId === $adminId) {
        flash('error', 'You cannot modify your own admin account here.');
    } else {
        if ($action === 'suspend') {
            $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$userId]);
            flash('success', 'User suspended.');
        } elseif ($action === 'activate') {
            $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$userId]);
            flash('success', 'User activated.');
        }
    }
    redirect('/admin/users.php');
}

$roleFilter = $_GET['role'] ?? '';
$sql = 'SELECT * FROM users WHERE 1=1';
$params = [];
if (in_array($roleFilter, ['farmer', 'buyer', 'admin'], true)) {
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
</div>

<table class="data-table">
  <tr><th>Name</th><th>Email</th><th>Role</th><th>Business</th><th>Status</th><th>Joined</th><th>Action</th></tr>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= e($u['full_name']) ?></td>
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
          </form>
        <?php else: ?>
          <span class="muted">&mdash;</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
