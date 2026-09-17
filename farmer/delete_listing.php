<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("UPDATE listings SET status = 'removed' WHERE id = ? AND farmer_id = ?");
$stmt->execute([$id, $farmerId]);

flash('success', 'Listing removed.');
redirect('/farmer/listings.php');
