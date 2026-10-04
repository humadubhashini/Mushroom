<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $listingId = (int) ($_POST['listing_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT farmer_id, mushroom_type FROM listings WHERE id = ?');
    $stmt->execute([$listingId]);
    if ($listing = $stmt->fetch()) {
        $pdo->prepare("UPDATE listings SET status = 'removed' WHERE id = ?")->execute([$listingId]);
        log_admin_action('Removed listing #' . $listingId . ' (' . $listing['mushroom_type'] . ')');
        notify($listing['farmer_id'], 'notif.listing_removed', '/farmer/listings.php', ['name' => $listing['mushroom_type']]);
    }
    flash('success', 'Listing removed.');
    redirect('/admin/listings.php');
}

$stmt = $pdo->query(
    "SELECT l.*, u.full_name AS farmer_name FROM listings l JOIN users u ON u.id = l.farmer_id
     WHERE l.status != 'removed' ORDER BY l.created_at DESC"
);
$listings = $stmt->fetchAll();

$pageTitle = 'Manage Listings';
include __DIR__ . '/../includes/header.php';
?>

<h1>Manage Listings</h1>

<table class="data-table">
  <tr><th>Farmer</th><th>Mushroom</th><th>Qty (kg)</th><th>Price/kg</th><th>Status</th><th>Listed</th><th>Action</th></tr>
  <?php foreach ($listings as $l): ?>
    <tr>
      <td><?= e($l['farmer_name']) ?></td>
      <td><?= e($l['mushroom_type']) ?></td>
      <td><?= e($l['quantity_kg']) ?></td>
      <td><?= format_money($l['price_per_kg']) ?></td>
      <td><span class="badge badge-<?= e($l['status']) ?>"><?= e(ucfirst(str_replace('_',' ',$l['status']))) ?></span></td>
      <td><?= format_date($l['created_at']) ?></td>
      <td>
        <form method="post" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="listing_id" value="<?= (int) $l['id'] ?>">
          <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Remove this listing for policy violation?')">Remove</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
