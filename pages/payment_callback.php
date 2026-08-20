<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

try {
    $invoiceId = filter_input(INPUT_GET, 'invoice_id', FILTER_VALIDATE_INT);
    if (!$invoiceId) throw new Exception("Missing invoice_id.");

    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id=? LIMIT 1");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
    if (!$invoice) throw new Exception("Invoice not found.");

    // Which gateway is this callback from? Falls back to reading it off
    // the most recent payment_transactions row for this invoice if the
    // URL didn't carry it (some gateways only pass their own params).
    $gatewayName = $_GET['gateway'] ?? '';
    $gatewayName = preg_replace('/_sim$/', '', $gatewayName) ?: null;
    if (!$gatewayName) {
        $tx = $pdo->prepare("SELECT gateway FROM payment_transactions WHERE invoice_id=? ORDER BY id DESC LIMIT 1");
        $tx->execute([$invoiceId]);
        $gatewayName = $tx->fetchColumn() ?: 'manual';
    }

    // IMPORTANT: for real (non-simulated) gateways, the adapter
    // re-validates server-side (e.g. SSLCommerz's Validation API) rather
    // than trusting the raw query string, since success_url/callback
    // params can be spoofed by anyone who guesses/reuses the URL.
    $adapter = PaymentGatewayFactory::make($gatewayName);
    $result = $adapter->handleCallback($_GET);

    $newStatus = $result['success'] ? 'paid' : 'failed';
    $pdo->prepare("UPDATE invoices SET status=? WHERE id=?")->execute([$newStatus, $invoiceId]);

    $tx = $pdo->prepare("SELECT id FROM payment_transactions WHERE invoice_id=? ORDER BY id DESC LIMIT 1");
    $tx->execute([$invoiceId]);
    if ($txnId = $tx->fetchColumn()) {
        PaymentGatewayFactory::recordResult($pdo, (int)$txnId, $result['success'], $result['gateway_txn_id'], $result['raw']);
    }

    if ($result['success'] && $invoice['user_id']) {
        Notifier::send($pdo, (int)$invoice['user_id'], 'Payment received', 'Your payment of ' . number_format((float)$invoice['amount'], 2) . ' BDT has been confirmed. Thank you!', 'success', "/pages/invoice_view.php?id=" . $invoiceId);

        // Recharge/renewal invoices extend the user's package_expires_at by
        // the purchased duration rather than replacing their package.
        $meta = json_decode((string)$invoice['meta'], true) ?: [];
        if (($meta['type'] ?? '') === 'recharge' && !empty($meta['duration_days'])) {
            $days = (int)$meta['duration_days'];
            $u = $pdo->prepare("SELECT package_expires_at FROM users WHERE id=?");
            $u->execute([$invoice['user_id']]);
            $currentExpiry = $u->fetchColumn();
            // Extend from the current expiry if it's still in the future
            // (renewing early doesn't lose remaining paid days); otherwise
            // extend from today.
            $base = ($currentExpiry && strtotime($currentExpiry) > time()) ? $currentExpiry : date('Y-m-d');
            $pdo->prepare("UPDATE users SET package_expires_at = ? + INTERVAL ? DAY, account_status='active' WHERE id=?")
                ->execute([$base, $days, $invoice['user_id']]);
            app_log('recharge', "User {$invoice['user_id']} recharged {$days} day(s), new expiry base {$base}");
        }
    }

    include __DIR__ . '/../includes/header.php';
    ?>
    <main class="container">
    <div class="card" style="max-width:480px;margin:40px auto;text-align:center;padding:24px;">
      <?php if ($result['success']): ?>
        <h2 style="color:#0a6;">✅ Payment Successful</h2>
        <p>Invoice #<?= h($invoiceId) ?> has been paid.</p>
        <?php if ($invoice['user_id']): ?><p><a class="btn primary" href="/pages/invoice_view.php?id=<?= h($invoiceId) ?>">View Invoice</a></p><?php endif; ?>
        <p><a href="/pages/dashboard.php">Back to Dashboard</a></p>
      <?php else: ?>
        <h2 style="color:#b00020;">Payment Not Confirmed</h2>
        <p>We couldn't confirm this payment. If money was deducted, please contact support with your invoice number.</p>
        <p><a class="btn" href="/pages/payment_redirect.php?invoice_id=<?= h($invoiceId) ?>">Try Again</a></p>
      <?php endif; ?>
    </div>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <?php
    exit;

} catch (Throwable $ex) {
    app_log('payment_callback', $ex->getMessage());
    http_response_code(200);
    include __DIR__ . '/../includes/header.php';
    echo '<main class="container"><div class="card" style="max-width:480px;margin:40px auto;">';
    echo '<h2>Callback Error</h2><p>' . h($ex->getMessage()) . '</p>';
    echo '<p><a href="/pages/dashboard.php">Back to Dashboard</a></p></div></main>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}
