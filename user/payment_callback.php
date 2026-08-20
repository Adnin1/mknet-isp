<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
  header("Location: /pages/login.php");
  exit;
}

// Check if callback contains orderId (bill ID)
if(isset($_GET['orderId'])){
  $bill_id = filter_input(INPUT_GET, 'orderId', FILTER_VALIDATE_INT);
  $trx = $_GET['paymentRefId'] ?? $_GET['trxID'] ?? null; // Nagad or Bkash reference

  if($trx && $bill_id){
    // Update bill as paid
    $stmt = $pdo->prepare("UPDATE billing SET status='paid', paid_date=NOW(), transaction_id=? WHERE id=? AND user_id=?");
    $stmt->execute([$trx, $bill_id, $_SESSION['user_id']]);

    $message = "Payment successful! Transaction ID: ".htmlspecialchars((string)$trx, ENT_QUOTES, 'UTF-8');
  } else {
    $message = "Payment failed or invalid response.";
  }
} else {
  $message = "Invalid callback request.";
}
?>
<link rel="stylesheet" href="../admin/style.css">

<div class="main">
  <h2>Payment Result</h2>
  <p><?= $message ?></p>
  <a href="paybill.php">Back to My Bills</a>
</div>
