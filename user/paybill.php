<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// The Bkash/Nagad SDKs were never installed (no vendor/ folder, no
// composer.json) - requiring vendor/autoload.php unconditionally used to
// crash this entire page with a fatal error the moment anyone opened it.
// We now load it only if it actually exists, and simply hide the
// "Pay Online" gateway option when the SDKs aren't present, so manual
// transaction-ID submission (which doesn't need them) still works.
$gatewaysAvailable = false;
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $gatewaysAvailable = class_exists('Xenon\\NagadApi\\Base') && class_exists('Bkash\\Payment\\BkashPayment');
}

if(!isset($_SESSION['user_id'])){
    include __DIR__ . '/../includes/header.php';
    echo '<main class="container"><div class="card" style="max-width:500px;margin:40px auto;text-align:center;padding:24px;">';
    echo '<h2>💳 Online Bill Portal</h2><p>Please log in to your account to view and pay your internet bills.</p>';
    echo '<p><a class="btn primary" href="/pages/login.php">Log In to Account</a> <a class="btn" href="/pages/packages.php">View Packages</a></p></div></main>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Fetch user bills
$stmt = $pdo->prepare("SELECT * FROM billing WHERE user_id=? ORDER BY id DESC");
$stmt->execute([$user_id]);
$bills = $stmt->fetchAll();

$message = "";

// Handle manual transaction submission
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['transaction_id'])){
  if (!csrf_verify()) {
    $message = "Session expired, please try again.";
  } else {
    $bill_id = filter_input(INPUT_POST, 'bill_id', FILTER_VALIDATE_INT);
    $trx = trim((string)($_POST['transaction_id'] ?? ''));

    if (!$bill_id || $trx === '') {
      $message = "Please provide a valid transaction ID.";
    } else {
      $stmt = $pdo->prepare("UPDATE billing SET status='paid', paid_date=NOW(), transaction_id=? WHERE id=? AND user_id=?");
      if($stmt->execute([$trx, $bill_id, $user_id])){
        $message = "Payment submitted successfully! Admin will verify.";
        // refresh list
        $stmt = $pdo->prepare("SELECT * FROM billing WHERE user_id=? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        $bills = $stmt->fetchAll();
      } else {
        $message = "Error submitting payment.";
      }
    }
  }
}

// Handle gateway integration (only reachable if SDKs are actually installed)
if($gatewaysAvailable && $_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['gateway'])){
  if (!csrf_verify()) {
    $message = "Session expired, please try again.";
  } else {
    $bill_id = filter_input(INPUT_POST, 'bill_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $gateway = $_POST['gateway'] ?? '';

    if ($gateway === 'nagad') {
      $nagad = new \Xenon\NagadApi\Base();
      $response = $nagad->initiatePayment([
        'amount' => $amount,
        'orderId' => $bill_id,
        'merchantCallbackURL' => 'http://localhost/mknet/user/payment_callback.php'
      ]);
      header("Location: ".$response['payment_url']);
      exit;
    }

    if ($gateway === 'bkash') {
      $bkash = new \Bkash\Payment\BkashPayment();
      $response = $bkash->createPayment($amount, $bill_id);
      header("Location: ".$response['payment_url']);
      exit;
    }
  }
}
?>
<link rel="stylesheet" href="../admin/style.css">

<div class="main">
  <h2>My Bills</h2>
  <?php if(!empty($message)): ?>
    <p style="color:green;"><?= h($message) ?></p>
  <?php endif; ?>

  <table>
    <tr><th>ID</th><th>Amount</th><th>Status</th><th>Due Date</th><th>Paid Date</th><th>Transaction</th><th>Action</th></tr>
    <?php if (empty($bills)): ?>
      <tr><td colspan="7">No bills yet.</td></tr>
    <?php endif; ?>
    <?php foreach($bills as $b): ?>
    <tr>
      <td><?= h($b['id']) ?></td>
      <td><?= h($b['amount']) ?></td>
      <td>
        <?php if($b['status']=='pending'): ?>
          <span class="badge badge-low">Pending</span>
        <?php else: ?>
          <span class="badge badge-high">Paid</span>
        <?php endif; ?>
      </td>
      <td><?= h($b['due_date']) ?></td>
      <td><?= h($b['paid_date']) ?></td>
      <td><?= h($b['transaction_id'] ?? 'N/A') ?></td>
      <td>
        <?php if($b['status']=='pending'): ?>
          <!-- Manual Payment -->
          <form method="post" style="margin-bottom:10px;">
            <?= csrf_field() ?>
            <input type="hidden" name="bill_id" value="<?= h($b['id']) ?>">
            <input type="text" name="transaction_id" placeholder="Bkash/Nagad TrxID" required>
            <button type="submit">Submit Payment</button>
          </form>

          <?php if ($gatewaysAvailable): ?>
          <!-- Gateway Integration -->
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="bill_id" value="<?= h($b['id']) ?>">
            <input type="hidden" name="amount" value="<?= h($b['amount']) ?>">
            <select name="gateway" required>
              <option value="">Select Gateway</option>
              <option value="bkash">Bkash</option>
              <option value="nagad">Nagad</option>
            </select>
            <button type="submit">Pay Online</button>
          </form>
          <?php else: ?>
          <p class="small-note">Online gateway payment isn't configured yet — please pay via Bkash/Nagad manually and submit the transaction ID above.</p>
          <?php endif; ?>
        <?php else: ?>
          <a href="invoice.php?id=<?= h($b['id']) ?>">Download Invoice</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
