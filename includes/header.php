<?php
/** Shared page header/navigation. Expects $pageTitle to be set by the including page. */
$user = current_user();
$lang = current_lang();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? t('app.name')) ?> | <?= te('app.name') ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="lang-<?= e($lang) ?>">
<div class="lang-bar">
  <div class="container">
    🌐
    <?php foreach (LANGUAGES as $code => $label): ?>
      <a href="<?= e(lang_url($code)) ?>" class="<?= $code === $lang ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?= BASE_URL ?>/index.php">🍄 <?= te('app.name') ?></a>
    <nav class="main-nav">
      <a href="<?= BASE_URL ?>/mushrooms/index.php"><?= te('nav.mushrooms') ?></a>
      <a href="<?= BASE_URL ?>/knowledge/index.php"><?= te('nav.knowledge') ?></a>
      <?php if (!$user): ?>
        <a href="<?= BASE_URL ?>/buyer/marketplace.php"><?= te('nav.marketplace') ?></a>
        <a href="<?= BASE_URL ?>/help.php"><?= te('nav.help') ?></a>
        <a href="<?= BASE_URL ?>/contact.php"><?= te('nav.contact') ?></a>
        <a href="<?= BASE_URL ?>/auth/login.php"><?= te('nav.login') ?></a>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn-nav"><?= te('nav.register') ?></a>
      <?php else: ?>
        <?php if ($user['role'] === 'farmer'): ?>
          <a href="<?= BASE_URL ?>/farmer/dashboard.php"><?= te('nav.dashboard') ?></a>
          <a href="<?= BASE_URL ?>/farmer/listings.php"><?= te('nav.my_listings') ?></a>
          <a href="<?= BASE_URL ?>/farmer/diagnose.php"><?= te('nav.snap_detect') ?></a>
          <a href="<?= BASE_URL ?>/farmer/orders.php"><?= te('nav.orders') ?></a>
          <a href="<?= BASE_URL ?>/shared/transactions.php"><?= te('nav.transactions') ?></a>
        <?php elseif ($user['role'] === 'buyer'): ?>
          <a href="<?= BASE_URL ?>/buyer/dashboard.php"><?= te('nav.dashboard') ?></a>
          <a href="<?= BASE_URL ?>/buyer/marketplace.php"><?= te('nav.marketplace') ?></a>
          <a href="<?= BASE_URL ?>/buyer/orders.php"><?= te('nav.my_orders') ?></a>
          <a href="<?= BASE_URL ?>/shared/transactions.php"><?= te('nav.transactions') ?></a>
        <?php elseif ($user['role'] === 'admin'): ?>
          <a href="<?= BASE_URL ?>/admin/dashboard.php"><?= te('nav.admin') ?></a>
        <?php elseif ($user['role'] === 'expert'): ?>
          <a href="<?= BASE_URL ?>/admin/dashboard.php"><?= te('nav.expert') ?></a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/contact.php"><?= te('nav.contact') ?></a>
        <?php $unread = unread_notification_count(); ?>
        <a href="<?= BASE_URL ?>/shared/notifications.php" title="<?= te('nav.notifications') ?>">🔔<?php if ($unread): ?><span class="notif-count"><?= $unread ?></span><?php endif; ?></a>
        <a href="<?= BASE_URL ?>/shared/profile.php"><?= te('nav.profile') ?></a>
        <a href="<?= BASE_URL ?>/auth/logout.php"><?= te('nav.logout') ?> (<?= e($user['full_name']) ?>)</a>
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
