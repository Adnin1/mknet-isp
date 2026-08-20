<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$invoice_id = filter_input(INPUT_GET, 'invoice_id', FILTER_VALIDATE_INT);
if (!$invoice_id) { die("Invalid invoice."); }

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id=? LIMIT 1");
$stmt->execute([$invoice_id]);
$inv = $stmt->fetch();
if (!$inv) { die("Invoice not found."); }

// If logged in, this invoice must belong to them.
if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] !== (int)$inv['user_id']) {
    die("This invoice does not belong to your account.");
}

$error = $_GET['gateway_error'] ?? false;
$manualMode = isset($_GET['manual']) || (($_POST['gateway'] ?? '') === 'manual');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $gateway = $_POST['gateway'] ?? '';

    if ($gateway === 'manual' && isset($_POST['transaction_id'])) {
        $trx = trim((string)$_POST['transaction_id']);
        if ($trx !== '') {
            $pdo->prepare("UPDATE invoices SET status='pending' WHERE id=?")->execute([$invoice_id]); // stays pending until admin verifies
            PaymentGatewayFactory::recordAttempt($pdo, $invoice_id, $inv['user_id'], 'manual', (float)$inv['amount']);
            $meta = json_decode((string)($inv['meta'] ?? ''), true) ?: [];
            $meta['manual_transaction_id'] = $trx;
            $pdo->prepare("UPDATE invoices SET meta=? WHERE id=?")->execute([json_encode($meta), $invoice_id]);
            if ($inv['user_id']) {
                Notifier::send($pdo, (int)$inv['user_id'], 'Payment submitted', "Your manual payment (Trx: " . h($trx) . ") for invoice #{$invoice_id} has been submitted and is awaiting admin verification.", 'info');
            }
            header("Location: /pages/payment_redirect.php?invoice_id={$invoice_id}&manual_submitted=1");
            exit;
        }
    } elseif (isset(PaymentGatewayFactory::GATEWAYS[$gateway])) {
        $txnId = PaymentGatewayFactory::recordAttempt($pdo, $invoice_id, $inv['user_id'], $gateway, (float)$inv['amount']);
        $adapter = PaymentGatewayFactory::make($gateway);
        try {
            $result = $adapter->initiate([
                'invoice_id' => $invoice_id,
                'amount' => (float)$inv['amount'],
                'currency' => $inv['currency'] ?? 'BDT',
                'tran_id' => 'MKNET-' . $invoice_id . '-' . time(),
                'success_url' => 'http://' . $_SERVER['HTTP_HOST'] . "/pages/payment_callback.php?invoice_id=" . $invoice_id . '&gateway=' . $gateway,
                'fail_url' => 'http://' . $_SERVER['HTTP_HOST'] . "/pages/payment_redirect.php?invoice_id=" . $invoice_id . '&gateway_error=1',
                'cancel_url' => 'http://' . $_SERVER['HTTP_HOST'] . "/pages/payment_redirect.php?invoice_id=" . $invoice_id,
            ]);
            header("Location: " . $result['url']);
            exit;
        } catch (RuntimeException $ex) {
            app_log('payment_redirect', $ex->getMessage());
            $error = true;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<main class="container">
  <div class="card" style="max-width:480px;margin:30px auto;padding:20px;">
    <h2>Complete Payment</h2>
    <p>Invoice #<?= h($invoice_id) ?> — Amount: <b><?= h(number_format((float)$inv['amount'],2)) ?> <?= h($inv['currency']) ?></b></p>
    <p>Status: <?= h(strtoupper($inv['status'])) ?></p>

    <?php if ($error): ?>
      <p style="color:#b00020;">The payment gateway could not be reached. Please try again or use manual transfer.</p>
    <?php endif; ?>

    <?php if (isset($_GET['manual_submitted'])): ?>
      <p style="color:#0a6;">✅ Your transaction ID has been submitted. An admin will verify and confirm your payment shortly.</p>
    <?php elseif ($inv['status'] === 'paid'): ?>
      <p style="color:#0a6;">✅ This invoice is already paid. <a href="/pages/invoice_view.php?id=<?= h($invoice_id) ?>">View invoice</a></p>
    <?php elseif ($manualMode): ?>
      <h3>Manual Transfer</h3>
      <p>Send <b><?= h(number_format((float)$inv['amount'],2)) ?> BDT</b> to our bKash/Nagad/Rocket merchant number, then submit your transaction ID below.</p>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="gateway" value="manual">
        <input type="text" name="transaction_id" placeholder="Transaction ID" required style="display:block;width:100%;margin:8px 0;padding:8px;">
        <button type="submit" class="btn primary" style="width:100%;">Submit for Verification</button>
      </form>
      <p style="margin-top:10px;"><a href="?invoice_id=<?= h($invoice_id) ?>">← Choose a different payment method</a></p>
    <?php else: ?>
      <h3>Choose Payment Method</h3>
      <form method="post">
        <?= csrf_field() ?>
        <?php foreach (PaymentGatewayFactory::GATEWAYS as $key => $g): ?>
          <label style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:8px;cursor:pointer;">
            <input type="radio" name="gateway" value="<?= h($key) ?>" required>
            <?= h($g['label']) ?>
          </label>
        <?php endforeach; ?>
        <button type="submit" class="btn primary" style="width:100%;">Continue</button>
      </form>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
