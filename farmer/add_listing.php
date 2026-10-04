<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];
$addr = $pdo->prepare('SELECT address FROM users WHERE id = ?');
$addr->execute([$farmerId]);
$farmerAddress = $addr->fetch()['address'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $type = trim($_POST['mushroom_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = $_POST['quantity_kg'] ?? '';
    $price = $_POST['price_per_kg'] ?? '';
    $harvestDate = $_POST['harvest_date'] ?: null;
    $location = trim($_POST['location'] ?? '');

    $errors = [];
    if ($type === '') $errors[] = 'Mushroom type is required.';
    if (!is_numeric($quantity) || $quantity <= 0) $errors[] = 'Quantity must be a positive number.';
    if (!is_numeric($price) || $price <= 0) $errors[] = 'Price must be a positive number.';

    $imageError = null;
    $imageFile = handle_image_upload('image', UPLOAD_LISTINGS, $imageError);
    if ($imageError) $errors[] = $imageError;

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO listings (farmer_id, mushroom_type, description, quantity_kg, price_per_kg, harvest_date, location, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$farmerId, $type, $description ?: null, $quantity, $price, $harvestDate, $location ?: null, $imageFile]);
        flash('success', 'Listing created successfully.');
        redirect('/farmer/listings.php');
    } else {
        flash('error', implode(' ', $errors));
    }
}

$pageTitle = 'New Listing';
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0">Create a New Listing</h1>
  <form class="stacked" method="post" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <label>Mushroom Type</label>
    <input type="text" name="mushroom_type" placeholder="e.g. Oyster Mushroom" required>

    <label>Description</label>
    <textarea name="description" placeholder="Growing method, quality notes, etc."></textarea>

    <div class="form-row">
      <div>
        <label>Quantity (kg)</label>
        <input type="number" step="0.1" min="0.1" name="quantity_kg" required>
      </div>
      <div>
        <label>Price per kg (Rs.)</label>
        <input type="number" step="0.01" min="0.01" name="price_per_kg" required>
      </div>
    </div>

    <label>Harvest Date</label>
    <input type="date" name="harvest_date">

    <label>Location (District / Town)</label>
    <input type="text" name="location" placeholder="e.g. Kurunegala" value="<?= e($farmerAddress) ?>">

    <label>Photo</label>
    <input type="file" name="image" accept="image/*">

    <button type="submit" class="btn">Create Listing</button>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
