<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <h1>Direct Market Access for Sri Lankan Mushroom Farmers</h1>
  <p>Sell straight to hotels and restaurants without middlemen, and diagnose crop diseases instantly with AI-powered "Snap &amp; Detect".</p>
  <a href="<?= BASE_URL ?>/buyer/marketplace.php" class="btn">Browse Marketplace</a>
  <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline">Join as a Farmer</a>
</section>

<div class="feature-grid">
  <div class="card">
    <h3>🛒 Direct B2B Marketplace</h3>
    <p class="muted">Farmers list their harvest, hotels and restaurants order in bulk directly &mdash; no middlemen, better margins for growers.</p>
  </div>
  <div class="card">
    <h3>🤖 AI Disease Diagnosis</h3>
    <p class="muted">Snap a photo of a sick mushroom and get an instant AI diagnosis with a confidence score and treatment guidance.</p>
  </div>
  <div class="card">
    <h3>💳 Secure Payments</h3>
    <p class="muted">Transparent, secure digital payments between farmers and buyers, with a full transaction history.</p>
  </div>
  <div class="card">
    <h3>🎓 Knowledge Hub</h3>
    <p class="muted">Expert video tutorials covering house preparation, spawning, disease prevention, and harvesting.</p>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
