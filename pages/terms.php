<?php
require_once __DIR__ . '/../config.php';
include __DIR__ . '/../includes/header.php';
?>
<main class="container">
<div class="card" style="max-width:720px;margin:24px auto;padding:24px;">
  <h1>Terms & Conditions</h1>
  <p class="small-note">Last updated: <?= date('F Y') ?></p>

  <h3>1. Service Agreement</h3>
  <p>By subscribing to an MK Net internet package, you agree to pay the listed subscription fee on a recurring basis according to your chosen billing cycle. Speeds shown are "up to" maximum speeds and may vary based on network conditions.</p>

  <h3>2. Billing & Payments</h3>
  <p>Invoices are generated at the start of each billing cycle. Payment is due within the timeframe stated on your invoice. Failure to pay may result in service suspension.</p>

  <h3>3. Cancellation & Pausing</h3>
  <p>You may pause your connection at any time from your dashboard. Paused accounts are not billed for the duration of the pause. You may cancel your subscription by contacting support.</p>

  <h3>4. Refunds</h3>
  <p>Refunds for undelivered service are considered on a case-by-case basis. Please contact support with your invoice number.</p>

  <h3>5. Fair Use</h3>
  <p>Package data caps and fair-use policies, where applicable, are listed on the package details page.</p>

  <h3>6. Changes to These Terms</h3>
  <p>MK Net may update these terms from time to time. Continued use of the service after changes constitutes acceptance of the updated terms.</p>

  <p style="margin-top:24px;"><a href="/pages/contact.php">Questions? Contact support →</a></p>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
