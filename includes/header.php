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
    <a class="brand" href="<?= BASE_URL ?>/index.php">🍄 <?= e(APP_NAME) ?></a>
    <nav class="main-nav">
      <a href="<?= BASE_URL ?>/knowledge/index.php"><?= e(t('nav.knowledge')) ?></a>
      <?php if (!$user): ?>
        <a href="<?= BASE_URL ?>/buyer/marketplace.php"><?= e(t('nav.marketplace')) ?></a>
        <a href="<?= BASE_URL ?>/help.php"><?= e(t('nav.help')) ?></a>
        <a href="<?= BASE_URL ?>/auth/login.php"><?= e(t('nav.login')) ?></a>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn-nav"><?= e(t('nav.register')) ?></a>
      <?php else: ?>
        <?php if ($user['role'] === 'farmer'): ?>
          <a href="<?= BASE_URL ?>/farmer/dashboard.php"><?= e(t('nav.dashboard')) ?></a>
          <a href="<?= BASE_URL ?>/farmer/listings.php"><?= e(t('nav.my_listings')) ?></a>
          <a href="<?= BASE_URL ?>/farmer/diagnose.php"><?= e(t('nav.snap_detect')) ?></a>
          <a href="<?= BASE_URL ?>/farmer/orders.php"><?= e(t('nav.orders')) ?></a>
          <a href="<?= BASE_URL ?>/shared/transactions.php"><?= e(t('nav.transactions')) ?></a>
        <?php elseif ($user['role'] === 'buyer'): ?>
          <a href="<?= BASE_URL ?>/buyer/dashboard.php"><?= e(t('nav.dashboard')) ?></a>
          <a href="<?= BASE_URL ?>/buyer/marketplace.php"><?= e(t('nav.marketplace')) ?></a>
          <a href="<?= BASE_URL ?>/buyer/orders.php"><?= e(t('nav.my_orders')) ?></a>
          <a href="<?= BASE_URL ?>/shared/transactions.php"><?= e(t('nav.transactions')) ?></a>
        <?php elseif ($user['role'] === 'admin'): ?>
          <a href="<?= BASE_URL ?>/admin/dashboard.php"><?= e(t('nav.admin')) ?></a>
        <?php elseif ($user['role'] === 'expert'): ?>
          <a href="<?= BASE_URL ?>/admin/dashboard.php"><?= e(t('nav.expert')) ?></a>
        <?php endif; ?>
        <?php $unread = unread_notification_count(); ?>
        <a href="<?= BASE_URL ?>/shared/notifications.php" title="<?= e(t('nav.notifications')) ?>">🔔<?php if ($unread): ?><span class="notif-count"><?= $unread ?></span><?php endif; ?></a>
        <a href="<?= BASE_URL ?>/shared/profile.php"><?= e(t('nav.profile')) ?></a>
        <a href="<?= BASE_URL ?>/auth/logout.php"><?= e(t('nav.logout')) ?> (<?= e($user['full_name']) ?>)</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container page-main">
  <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = flash('info')): ?>
    <div class="alert alert-info"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
    <div class="alert alert-error"><?= e($msg) ?></div>
  <?php endif; ?>
