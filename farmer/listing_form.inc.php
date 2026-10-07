<?php
/**
 * Shared create/edit listing logic for farmer/add_listing.php and
 * farmer/edit_listing.php. Expects $pdo, $farmerId and $listing (null for new).
 */
// UC-03 pre-condition: the farmer's account must be approved by an administrator.
if (!farmer_is_approved($farmerId)) {
    flash('error', t('farmer.pending_approval'));
    redirect('/farmer/dashboard.php');
}

$types = $pdo->query('SELECT * FROM mushroom_types WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
$typeById = array_column($types, null, 'id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $typeId = (int) ($_POST['mushroom_type_id'] ?? 0);
    $otherType = trim($_POST['mushroom_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = $_POST['quantity_kg'] ?? '';
    $price = $_POST['price_per_kg'] ?? '';
    $harvestDate = ($_POST['harvest_date'] ?? '') ?: null;
    $location = trim($_POST['location'] ?? '');
    $status = $listing ? ($_POST['status'] ?? 'active') : 'active';

    // Catalogue type -> store its English name too; "other" -> the typed name.
    $typeName = isset($typeById[$typeId]) ? $typeById[$typeId]['name'] : $otherType;
    if (!isset($typeById[$typeId])) {
        $typeId = null;
    }

    $errors = [];
    if ($typeName === '') $errors[] = t('listing.err_type');
    if (!is_numeric($quantity) || $quantity <= 0) $errors[] = t('listing.err_qty');
    if (!is_numeric($price) || $price <= 0) $errors[] = t('listing.err_price');
    if (!in_array($status, ['active', 'sold_out'], true)) $errors[] = t('listing.err_status');
    // Table C.1: the harvest date cannot be in the future.
    if ($harvestDate && (!strtotime($harvestDate) || $harvestDate > date('Y-m-d'))) $errors[] = t('common.err_date');

    // Figure 4.7, step 11 (checkDuplicates): one active listing per mushroom type per farmer.
    $dup = $pdo->prepare(
        "SELECT id FROM listings WHERE farmer_id = ? AND status = 'active' AND id != ?
         AND (mushroom_type_id = ? OR (mushroom_type_id IS NULL AND LOWER(mushroom_type) = LOWER(?)))"
    );
    $dup->execute([$farmerId, $listing['id'] ?? 0, $typeId ?? 0, $typeName]);
    if ($typeName !== '' && $dup->fetch()) $errors[] = t('listing.err_duplicate');

    $imageError = null;
    $imageFile = handle_image_upload('image', UPLOAD_LISTINGS, $imageError);
    if ($imageError) $errors[] = $imageError;

    if (!$errors) {
        if ($listing) {
            $pdo->prepare(
                'UPDATE listings SET mushroom_type_id=?, mushroom_type=?, description=?, quantity_kg=?, price_per_kg=?, harvest_date=?, location=?, status=?, image=?
                 WHERE id = ? AND farmer_id = ?'
            )->execute([$typeId, $typeName, $description ?: null, $quantity, $price, $harvestDate, $location ?: null, $status,
                        $imageFile ?: $listing['image'], $listing['id'], $farmerId]);
            flash('success', t('listing.updated'));
        } else {
            $pdo->prepare(
                'INSERT INTO listings (farmer_id, mushroom_type_id, mushroom_type, description, quantity_kg, price_per_kg, harvest_date, location, image)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([$farmerId, $typeId, $typeName, $description ?: null, $quantity, $price, $harvestDate, $location ?: null, $imageFile]);
            flash('success', t('listing.created'));
        }
        redirect('/farmer/listings.php');
    }
    flash('error', implode(' ', $errors));
    redirect($listing ? '/farmer/edit_listing.php?id=' . $listing['id'] : '/farmer/add_listing.php');
}

if (!$listing) {
    $addr = $pdo->prepare('SELECT address FROM users WHERE id = ?');
    $addr->execute([$farmerId]);
    $defaults = ['mushroom_type_id' => null, 'mushroom_type' => '', 'description' => '', 'quantity_kg' => '', 'price_per_kg' => '',
                 'harvest_date' => date('Y-m-d'), 'location' => $addr->fetch()['address'] ?? '', 'status' => 'active'];
}
$f = $listing ?: $defaults;

$pageTitle = $listing ? t('listing.edit_title') : t('listing.new_title');
include __DIR__ . '/../includes/header.php';
?>

<div class="card form-narrow">
  <h1 class="mt-0"><?= e($pageTitle) ?></h1>
  <form class="stacked" method="post" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <label><?= te('mush.type') ?></label>
    <select name="mushroom_type_id" id="typeSelect">
      <?php foreach ($types as $tp): ?>
        <option value="<?= (int) $tp['id'] ?>" data-price="<?= e($tp['price_per_kg']) ?>" <?= (int) $f['mushroom_type_id'] === (int) $tp['id'] ? 'selected' : '' ?>>
          <?= e(tr_field($tp, 'name')) ?> (<?= te('listing.ref_price') ?>: <?= format_money($tp['price_per_kg']) ?>)
        </option>
      <?php endforeach; ?>
      <option value="0" <?= $listing && !$f['mushroom_type_id'] ? 'selected' : '' ?>><?= te('mush.other_type') ?></option>
    </select>
    <input type="text" name="mushroom_type" id="otherType" value="<?= e($f['mushroom_type_id'] ? '' : $f['mushroom_type']) ?>" placeholder="<?= te('listing.other_ph') ?>" style="margin-top:8px;">

    <label><?= te('listing.description') ?></label>
    <textarea name="description" placeholder="<?= te('listing.description_ph') ?>"><?= e($f['description']) ?></textarea>

    <div class="form-row">
      <div>
        <label><?= te('listing.quantity') ?></label>
        <input type="number" step="0.1" min="0.1" name="quantity_kg" value="<?= e($f['quantity_kg']) ?>" required>
      </div>
      <div>
        <label><?= te('listing.price') ?></label>
        <input type="number" step="0.01" min="0.01" name="price_per_kg" id="priceInput" value="<?= e($f['price_per_kg']) ?>" required>
      </div>
    </div>

    <label><?= te('listing.harvest_date') ?></label>
    <input type="date" name="harvest_date" value="<?= e($f['harvest_date']) ?>" max="<?= date('Y-m-d') ?>">

    <label><?= te('listing.location') ?></label>
    <input type="text" name="location" value="<?= e($f['location']) ?>" placeholder="<?= te('market.location_ph') ?>">

    <?php if ($listing): ?>
      <label><?= te('common.status') ?></label>
      <select name="status">
        <option value="active" <?= $f['status'] === 'active' ? 'selected' : '' ?>><?= te('status.active') ?></option>
        <option value="sold_out" <?= $f['status'] === 'sold_out' ? 'selected' : '' ?>><?= te('status.sold_out') ?></option>
      </select>
    <?php endif; ?>

    <label><?= $listing ? te('listing.replace_photo') : te('listing.photo') ?></label>
    <input type="file" name="image" accept="image/*">

    <button type="submit" class="btn"><?= $listing ? te('common.save') : te('listing.create') ?></button>
  </form>
</div>
<script>
  (function () {
    var select = document.getElementById('typeSelect'), other = document.getElementById('otherType'), price = document.getElementById('priceInput');
    function update(fillPrice) {
      var isOther = select.value === '0';
      other.style.display = isOther ? '' : 'none';
      other.required = isOther;
      var opt = select.options[select.selectedIndex];
      if (fillPrice && !isOther && !price.value) price.value = opt.getAttribute('data-price');
    }
    select.addEventListener('change', function () { price.value = ''; update(true); });
    update(<?= $listing ? 'false' : 'true' ?>);
  })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
