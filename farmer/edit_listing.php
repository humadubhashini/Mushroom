<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM listings WHERE id = ? AND farmer_id = ?');
$stmt->execute([$id, $farmerId]);
$listing = $stmt->fetch();
if (!$listing) {
    flash('error', 'Listing not found.');
    redirect('/farmer/listings.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $type = trim($_POST['mushroom_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = $_POST['quantity_kg'] ?? '';
    $price = $_POST['price_per_kg'] ?? '';
    $harvestDate = $_POST['harvest_date'] ?: null;
    $status = $_POST['status'] ?? 'active';

    $errors = [];
    if ($type === '') $errors[] = 'Mushroom type is required.';
    if (!is_numeric($quantity) || $quantity <= 0) $errors[] = 'Quantity must be a positive number.';
    if (!is_numeric($price) || $price <= 0) $errors[] = 'Price must be a positive number.';
    if (!in_array($status, ['active', 'sold_out'], true)) $errors[] = 'Invalid status.';

    $imageError = null;
    $imageFile = handle_image_upload('image', UPLOAD_LISTINGS, $imageError);
    if ($imageError) $errors[] = $imageError;

    if (!$errors) {
        $image = $imageFile ?: $listing['image'];
        $stmt = $pdo->prepare(
            'UPDATE listings SET mushroom_type=?, description=?, quantity_kg=?, price_per_kg=?, harvest_date=?, status=?, image=?
             WHERE id = ? AND farmer_id = ?'
        );
        $stmt->execute([$type, $description ?: null, $quantity, $price, $harvestDate, $status, $image, $id, $farmerId]);
        flash('success', 'Listing updated.');
        redirect('/farmer/listings.php');
    } else {
        flash('error', implode(' ', $errors));
    }
}

$pageTitle = 'Edit Listing';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Edit Listing</h1>
  <form class="stacked" method="post" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <label>Mushroom Type</label>
    <input type="text" name="mushroom_type" value="<?= e($listing['mushroom_type']) ?>" required>

    <label>Description</label>
    <textarea name="description"><?= e($listing['description']) ?></textarea>

    <div class="form-row">
      <div>
        <label>Quantity (kg)</label>
        <input type="number" step="0.1" min="0.1" name="quantity_kg" value="<?= e($listing['quantity_kg']) ?>" required>
      </div>
      <div>
        <label>Price per kg (Rs.)</label>
        <input type="number" step="0.01" min="0.01" name="price_per_kg" value="<?= e($listing['price_per_kg']) ?>" required>
      </div>
    </div>

    <label>Harvest Date</label>
    <input type="date" name="harvest_date" value="<?= e($listing['harvest_date']) ?>">

    <label>Status</label>
    <select name="status">
      <option value="active" <?= $listing['status'] === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="sold_out" <?= $listing['status'] === 'sold_out' ? 'selected' : '' ?>>Sold Out</option>
    </select>

    <label>Replace Photo (optional)</label>
    <?php if ($listing['image']): ?>
      <img src="<?= BASE_URL ?>/assets/uploads/listings/<?= e($listing['image']) ?>" style="max-width:160px; display:block; margin-bottom:8px; border-radius:8px;">
    <?php endif; ?>
    <input type="file" name="image" accept="image/*">

    <button type="submit" class="btn">Save Changes</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
