<?php
require_once __DIR__ . '/../config.php';
session_start();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || !isset($_SESSION['user_id'])) {
    include __DIR__ . '/../includes/header.php';
    echo '<main class="container"><div class="card" style="max-width:500px;margin:40px auto;text-align:center;padding:24px;">';
    echo '<h2>📄 Invoice Viewer</h2><p>Please log in or select a valid invoice from your dashboard.</p>';
    echo '<p><a class="btn primary" href="/pages/login.php">Go to Login</a> <a class="btn" href="/pages/paybill.php">View Bills</a></p></div></main>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}
$stmt = $pdo->prepare("SELECT b.*, u.email FROM billing b JOIN users u ON b.user_id=u.id WHERE b.id=? AND b.user_id=?");
$stmt->execute([$id, $_SESSION['user_id']]);
$bill = $stmt->fetch();

if(!$bill){
    include __DIR__ . '/../includes/header.php';
    echo '<main class="container"><div class="card" style="max-width:500px;margin:40px auto;text-align:center;padding:24px;">';
    echo '<h2>Invoice Not Found</h2><p>The requested invoice could not be found.</p>';
    echo '<p><a href="/pages/paybill.php">Back to Bills</a></p></div></main>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}
?>
<link rel="stylesheet" href="../admin/style.css">

<div class="main">
  <h2>Invoice #<?= h($bill['id']) ?></h2>
  <p>User: <?= h($bill['email']) ?></p>
  <p>Amount: <?= h($bill['amount']) ?> BDT</p>
  <p>Status: <?= h($bill['status']) ?></p>
  <p>Due Date: <?= h($bill['due_date']) ?></p>
  <p>Paid Date: <?= h($bill['paid_date']) ?></p>
  <p>Transaction ID: <?= h($bill['transaction_id'] ?? 'N/A') ?></p>
</div>
