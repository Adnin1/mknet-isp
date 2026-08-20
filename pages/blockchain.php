<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Blockchain Billing';
include __DIR__ . '/../includes/header.php';

$rows = $pdo->query("SELECT user_email, package, amount, tx_hash, status, created_at FROM blockchain_payments ORDER BY id DESC")->fetchAll();
?>

<h2>Smart Contract Billing</h2>
<div class="grid">
<?php foreach ($rows as $r): ?>
  <div class="card">
    <p><b>User:</b> <?=$r['user_email']?></p>
    <p><b>Package:</b> <?=$r['package']?></p>
    <p><b>Amount:</b> <?=$r['amount']?> BDT</p>
    <p><b>Transaction ID:</b> <?=$r['tx_hash']?></p>
    <p><b>Status:</b> <?=$r['status']?></p>
    <p class="tiny">Date: <?=$r['created_at']?></p>
  </div>
<?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
