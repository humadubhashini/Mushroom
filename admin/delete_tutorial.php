<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role(['admin', 'expert']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/tutorials.php');
}
verify_csrf();
$id = (int) ($_POST['id'] ?? 0);
$pdo->prepare('DELETE FROM tutorials WHERE id = ?')->execute([$id]);
log_admin_action('Deleted tutorial #' . $id);
flash('success', 'Tutorial deleted.');
redirect('/admin/tutorials.php');
