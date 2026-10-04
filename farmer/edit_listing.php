<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];

$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND farmer_id = ? AND status != 'removed'");
$stmt->execute([(int) ($_GET['id'] ?? 0), $farmerId]);
$listing = $stmt->fetch();
if (!$listing) {
    flash('error', t('listing.not_found'));
    redirect('/farmer/listings.php');
}

require __DIR__ . '/listing_form.inc.php';
