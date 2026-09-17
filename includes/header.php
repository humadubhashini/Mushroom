<?php
/** Shared page header/navigation. Expects $pageTitle to be set by the including page. */
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Mushroom Direct') ?> | Mushroom Direct</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?= BASE_URL ?>/index.php">🍄 Mushroom Direct</a>
    <nav class="main-nav">
      <a href="<?= BASE_URL ?>/knowledge/index.php">Knowledge Hub</a>
      <?php if (!$user): ?>
        <a href="<?= BASE_URL ?>/buyer/marketplace.php">Marketplace</a>
        <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn-nav">Register</a>
      <?php elseif ($user['role'] === 'farmer'): ?>
        <a href="<?= BASE_URL ?>/farmer/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/farmer/listings.php">My Listings</a>
        <a href="<?= BASE_URL ?>/farmer/diagnose.php">Snap &amp; Detect</a>
        <a href="<?= BASE_URL ?>/farmer/orders.php">Orders</a>
        <a href="<?= BASE_URL ?>/auth/logout.php">Logout (<?= e($user['full_name']) ?>)</a>
      <?php elseif ($user['role'] === 'buyer'): ?>
        <a href="<?= BASE_URL ?>/buyer/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/buyer/marketplace.php">Marketplace</a>
        <a href="<?= BASE_URL ?>/buyer/orders.php">My Orders</a>
        <a href="<?= BASE_URL ?>/auth/logout.php">Logout (<?= e($user['full_name']) ?>)</a>
      <?php elseif ($user['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>/admin/dashboard.php">Admin Panel</a>
        <a href="<?= BASE_URL ?>/auth/logout.php">Logout (<?= e($user['full_name']) ?>)</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container page-main">
  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
    <div class="alert alert-error"><?= e($msg) ?></div>
  <?php endif; ?>
