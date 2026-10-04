<?php
/** Manage the mushroom variety catalogue: prices, translations and photos. */
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin', 'expert']);

$fields = ['name', 'name_si', 'name_ta', 'scientific_name', 'description', 'description_si', 'description_ta',
           'growing_info', 'growing_info_si', 'growing_info_ta', 'uses', 'uses_si', 'uses_ta'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    $price = $_POST['price_per_kg'] ?? '';
    $color = preg_match('/^#[0-9a-f]{6}$/i', $_POST['color'] ?? '') ? $_POST['color'] : '#c9b79c';
    $active = !empty($_POST['is_active']) ? 1 : 0;
    $sort = (int) ($_POST['sort_order'] ?? 0);

    $imageError = null;
    $image = handle_image_upload('image', UPLOAD_MUSHROOMS, $imageError);

    if ($data['name'] === '' || !is_numeric($price) || $price <= 0) {
        flash('error', 'English name and a positive price per kg are required.');
    } elseif ($imageError) {
        flash('error', $imageError);
    } else {
        $cols = $fields;
        $values = array_values($data);
        $cols[] = 'price_per_kg'; $values[] = $price;
        $cols[] = 'color'; $values[] = $color;
        $cols[] = 'is_active'; $values[] = $active;
        $cols[] = 'sort_order'; $values[] = $sort;
        if ($image) {
            $cols[] = 'image'; $values[] = $image;
        }
        if ($id) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", $cols));
            $values[] = $id;
            $pdo->prepare("UPDATE mushroom_types SET $set WHERE id = ?")->execute($values);
            log_admin_action('Updated mushroom type #' . $id . ' (' . $data['name'] . ', ' . $price . '/kg)');
        } else {
            $pdo->prepare('INSERT INTO mushroom_types (' . implode(', ', $cols) . ') VALUES (' . rtrim(str_repeat('?, ', count($cols)), ', ') . ')')
                ->execute($values);
            log_admin_action('Added mushroom type ' . $data['name']);
        }
        flash('success', 'Mushroom type saved.');
        redirect('/admin/mushroom_types.php');
    }
    redirect('/admin/mushroom_types.php' . ($id ? '?id=' . $id : '?new=1'));
}

$editing = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM mushroom_types WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $editing = $stmt->fetch() ?: null;
} elseif (isset($_GET['new'])) {
    $editing = array_fill_keys($fields, '') + ['id' => 0, 'price_per_kg' => '', 'color' => '#c9b79c', 'is_active' => 1, 'sort_order' => 0, 'image' => null];
}

$types = $pdo->query('SELECT * FROM mushroom_types ORDER BY sort_order, name')->fetchAll();

$pageTitle = 'Mushroom Types';
include __DIR__ . '/../includes/header.php';
?>

<h1>🍄 Mushroom Types &amp; Prices</h1>
<p class="muted">These appear on the public "Our Mushrooms" pages. Prices are reference prices per 1 kg; farmers set their own listing prices.</p>

<?php if ($editing !== null): ?>
  <div class="card">
    <h2 class="mt-0"><?= $editing['id'] ? 'Edit: ' . e($editing['name']) : 'Add Mushroom Type' ?></h2>
    <form class="stacked" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
      <div class="form-row">
        <div><label>Name (English)</label><input type="text" name="name" value="<?= e($editing['name']) ?>" required></div>
        <div><label>Name (සිංහල)</label><input type="text" name="name_si" value="<?= e($editing['name_si']) ?>"></div>
        <div><label>Name (தமிழ்)</label><input type="text" name="name_ta" value="<?= e($editing['name_ta']) ?>"></div>
      </div>
      <div class="form-row">
        <div><label>Scientific name</label><input type="text" name="scientific_name" value="<?= e($editing['scientific_name']) ?>"></div>
        <div><label>Price per 1 kg (Rs.)</label><input type="number" step="0.01" min="1" name="price_per_kg" value="<?= e($editing['price_per_kg']) ?>" required></div>
        <div><label>Display order</label><input type="number" name="sort_order" value="<?= (int) $editing['sort_order'] ?>"></div>
      </div>
      <?php foreach (['description' => 'Description', 'growing_info' => 'How it is grown', 'uses' => 'Uses in cooking'] as $f => $label): ?>
        <div class="form-row">
          <div><label><?= $label ?> (English)</label><textarea name="<?= $f ?>"><?= e($editing[$f]) ?></textarea></div>
          <div><label><?= $label ?> (සිංහල)</label><textarea name="<?= $f ?>_si"><?= e($editing[$f . '_si']) ?></textarea></div>
          <div><label><?= $label ?> (தமிழ்)</label><textarea name="<?= $f ?>_ta"><?= e($editing[$f . '_ta']) ?></textarea></div>
        </div>
      <?php endforeach; ?>
      <div class="form-row">
        <div><label>Photo (optional - replaces the drawing)</label><input type="file" name="image" accept="image/*"></div>
        <div><label>Drawing colour</label><input type="color" name="color" value="<?= e($editing['color']) ?>"></div>
        <div><label><input type="checkbox" name="is_active" value="1" <?= $editing['is_active'] ? 'checked' : '' ?>> Show on website</label></div>
      </div>
      <div>
        <button type="submit" class="btn">Save</button>
        <a href="<?= BASE_URL ?>/admin/mushroom_types.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
<?php else: ?>
  <a href="?new=1" class="btn" style="margin-bottom:16px;">+ Add Mushroom Type</a>
<?php endif; ?>

<table class="data-table" style="margin-top:16px;">
  <tr><th></th><th>Name</th><th>සිංහල / தமிழ்</th><th>Price / kg</th><th>Shown</th><th></th></tr>
  <?php foreach ($types as $m): ?>
    <tr>
      <td style="width:60px;"><div style="width:48px; height:48px; overflow:hidden; border-radius:6px;"><?= mushroom_picture($m, 48) ?></div></td>
      <td><?= e($m['name']) ?><br><small class="muted"><em><?= e($m['scientific_name']) ?></em></small></td>
      <td><?= e($m['name_si']) ?><br><?= e($m['name_ta']) ?></td>
      <td><?= format_money($m['price_per_kg']) ?></td>
      <td><?= $m['is_active'] ? 'Yes' : 'No' ?></td>
      <td><a href="?id=<?= (int) $m['id'] ?>" class="btn btn-small btn-outline">Edit</a></td>
    </tr>
  <?php endforeach; ?>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
