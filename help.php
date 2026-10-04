<?php
/** In-app quick-start guides for farmers, buyers and administrators (SRS 2.6). */
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Help & Quick-Start Guide';
include __DIR__ . '/includes/header.php';
?>

<h1>❓ Quick-Start Guide</h1>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); align-items:start;">
  <div class="card">
    <h2 class="mt-0">👨‍🌾 For Farmers</h2>
    <h3>Sell your harvest</h3>
    <ol>
      <li><a href="<?= BASE_URL ?>/auth/register.php">Register</a> as a <strong>Farmer</strong> and enter the 6-digit code to verify.</li>
      <li>Go to <strong>My Listings &rarr; + New Listing</strong>. Add the mushroom type, quantity, price per kg, harvest date and a photo.</li>
      <li>When a hotel orders, you get a 🔔 notification. Open <strong>Orders</strong> and press <strong>Confirm</strong>.</li>
      <li>After the buyer pays, deliver the mushrooms and press <strong>Mark Completed</strong>.</li>
      <li>See all payments in <strong>Transactions</strong> and download receipts.</li>
    </ol>
    <h3>📷 Snap &amp; Detect (3 steps)</h3>
    <ol>
      <li>Tap <strong>Snap &amp; Detect</strong> in the menu.</li>
      <li>Take a clear photo in good light (or choose one from your gallery).</li>
      <li>Tap <strong>Diagnose Now</strong> &mdash; you get the disease, a confidence score, treatment and a related video.</li>
    </ol>
    <p class="muted">If confidence is low, an agricultural expert will review your photo and send advice.</p>
  </div>

  <div class="card">
    <h2 class="mt-0">🏨 For Hotels &amp; Restaurants</h2>
    <ol>
      <li><a href="<?= BASE_URL ?>/auth/register.php">Register</a> as a <strong>Buyer</strong> and verify your account.</li>
      <li>Open the <strong>Marketplace</strong>. Filter by mushroom type, location, quantity or price.</li>
      <li>Open a listing, enter the quantity and preferred delivery date, then <strong>Place Order</strong>.</li>
      <li>When the farmer confirms, click <strong>Pay Now</strong> and pay by card, bank transfer or mobile wallet.</li>
      <li>Print the digital receipt, and after delivery <strong>Rate the Farmer</strong>.</li>
      <li>Problem with an order? Click ⚠ in <strong>My Orders</strong> to raise a dispute.</li>
    </ol>
  </div>

  <div class="card">
    <h2 class="mt-0">🛠 For Administrators &amp; Experts</h2>
    <ul>
      <li><strong>Manage Users</strong>: suspend, activate or remove accounts; create expert accounts.</li>
      <li><strong>Manage Listings</strong>: remove listings that break platform policy.</li>
      <li><strong>Reports</strong>: orders, revenue, sales by mushroom type, AI usage.</li>
      <li><strong>Disputes</strong>: respond to farmer/buyer complaints.</li>
      <li><strong>Tutorials</strong> &amp; <strong>Treatment Content</strong>: keep the Knowledge Hub and recommendations up to date.</li>
      <li><strong>AI Diagnosis Log</strong>: review low-confidence cases.</li>
      <li><strong>Audit Log</strong>: every admin action is recorded.</li>
    </ul>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
