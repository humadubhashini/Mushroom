<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/farmer/listings.php');
}
verify_csrf();
$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare("UPDATE listings SET status = 'removed' WHERE id = ? AND farmer_id = ?");
$stmt->execute([$id, $farmerId]);

flash('success', t('listing.removed'));
redirect('/farmer/listings.php');
