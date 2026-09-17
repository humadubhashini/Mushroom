<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('admin');

$id = (int) ($_GET['id'] ?? 0);
$pdo->prepare('DELETE FROM tutorials WHERE id = ?')->execute([$id]);
flash('success', 'Tutorial deleted.');
redirect('/admin/tutorials.php');
