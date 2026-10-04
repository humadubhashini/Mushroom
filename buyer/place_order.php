<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('buyer');
$buyerId = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/buyer/marketplace.php');
}
verify_csrf();

$listingId = (int) ($_POST['listing_id'] ?? 0);
$quantity = $_POST['quantity_kg'] ?? '';
$deliveryDate = ($_POST['delivery_date'] ?? '') ?: null;

$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND status = 'active'");
$stmt->execute([$listingId]);
$listing = $stmt->fetch();

if (!$listing) {
    flash('error', t('market.unavailable'));
    redirect('/buyer/marketplace.php');
}
if (!is_numeric($quantity) || $quantity <= 0 || $quantity > $listing['quantity_kg']) {
    flash('error', t('order.bad_qty'));
    redirect('/buyer/listing_view.php?id=' . $listingId);
}

$totalPrice = round($quantity * $listing['price_per_kg'], 2);

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO orders (listing_id, buyer_id, farmer_id, quantity_kg, total_price, delivery_date, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$listingId, $buyerId, $listing['farmer_id'], $quantity, $totalPrice, $deliveryDate, 'pending']);
    $orderId = $pdo->lastInsertId();

    $remaining = $listing['quantity_kg'] - $quantity;
    $newStatus = $remaining <= 0 ? 'sold_out' : 'active';
    $pdo->prepare('UPDATE listings SET quantity_kg = ?, status = ? WHERE id = ?')
        ->execute([max(0, $remaining), $newStatus, $listingId]);

    notify($listing['farmer_id'], 'notif.new_order', '/farmer/orders.php',
        ['order' => $orderId, 'qty' => $quantity, 'name' => $listing['mushroom_type'], 'buyer' => current_user()['full_name']]);

    $pdo->commit();
    flash('success', t('order.placed'));
    redirect('/buyer/orders.php');
} catch (Exception $e) {
    $pdo->rollBack();
    flash('error', t('order.failed'));
    redirect('/buyer/listing_view.php?id=' . $listingId);
}
