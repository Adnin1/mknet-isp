<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }

$message = '';
$messageType = '';

// Handle Manual Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = "Session expired, please try again.";
        $messageType = "error";
    } else {
        if (isset($_POST['approve_payment'])) {
            $trxId = (int)$_POST['trx_id'];
            
            $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE id = ?");
            $stmt->execute([$trxId]);
            $trx = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($trx) {
                // Update transaction status
                $pdo->prepare("UPDATE payment_transactions SET status = 'approved' WHERE id = ?")->execute([$trxId]);

                // Update invoice status
                $pdo->prepare("UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE id = ?")->execute([$trx['invoice_id']]);

                // Fetch invoice duration and user id
                $invStmt = $pdo->prepare("SELECT user_id, duration_days FROM invoices WHERE id = ?");
                $invStmt->execute([$trx['invoice_id']]);
                $inv = $invStmt->fetch(PDO::FETCH_ASSOC);

                if ($inv) {
                    $days = (int)($inv['duration_days'] ?: 30);
                    $pdo->prepare("UPDATE users SET account_status = 'active', package_expires_at = DATE_ADD(IF(package_expires_at IS NULL OR package_expires_at < NOW(), NOW(), package_expires_at), INTERVAL ? DAY) WHERE id = ?")
                        ->execute([$days, $inv['user_id']]);
                }

                $message = "Payment ID #$trxId verified & approved successfully! User subscription extended.";
                $messageType = "success";
            }
        }

        if (isset($_POST['reject_payment'])) {
            $trxId = (int)$_POST['trx_id'];
            $pdo->prepare("UPDATE payment_transactions SET status = 'rejected' WHERE id = ?")->execute([$trxId]);
            $message = "Payment ID #$trxId marked as rejected.";
            $messageType = "error";
        }
    }
}

// Fetch pending transactions safely
$invCols = $pdo->query("SHOW COLUMNS FROM invoices")->fetchAll(PDO::FETCH_COLUMN);
$durationCol = in_array('duration_days', $invCols) ? 'i.duration_days' : '30 AS duration_days';

$pendingTransactions = $pdo->query(
    "SELECT pt.*, u.name as user_name, u.email as user_email, u.phone as user_phone, i.amount as inv_amount, {$durationCol}
     FROM payment_transactions pt
     JOIN users u ON pt.user_id = u.id
     JOIN invoices i ON pt.invoice_id = i.id
     WHERE pt.status = 'pending'
     ORDER BY pt.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent verified transactions
$recentTransactions = $pdo->query(
    "SELECT pt.*, u.name as user_name, u.email as user_email
     FROM payment_transactions pt
     JOIN users u ON pt.user_id = u.id
     WHERE pt.status != 'pending'
     ORDER BY pt.id DESC LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="main-content" style="padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">✅ Verify Manual Mobile Payments (bKash / Nagad / Rocket)</h2>
    </div>

    <?php if ($message): ?>
        <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: <?= $messageType==='error'?'#fca5a5':'#6ee7b7' ?>;">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <!-- Pending Verification Table -->
    <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff; margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #fbbf24;">⏳ Pending Payment Verification Requests</h3>
        
        <?php if (empty($pendingTransactions)): ?>
            <p style="color: #94a3b8; font-size: 0.95rem;">No pending manual payment verification requests at the moment. All clear! 🎉</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; color: #e2e8f0; font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; text-transform: uppercase;">
                            <th style="padding: 12px;">ID</th>
                            <th style="padding: 12px;">Customer</th>
                            <th style="padding: 12px;">Gateway</th>
                            <th style="padding: 12px;">Trx ID</th>
                            <th style="padding: 12px;">Sender Number</th>
                            <th style="padding: 12px;">Amount</th>
                            <th style="padding: 12px;">Duration</th>
                            <th style="padding: 12px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingTransactions as $p): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 12px; font-weight: 600;">#TRX-<?= (int)$p['id'] ?></td>
                                <td style="padding: 12px;">
                                    <strong style="color: #fff;"><?= h($p['user_name']) ?></strong><br>
                                    <span style="color: #94a3b8; font-size: 0.8rem;"><?= h($p['user_email']) ?></span>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="background: rgba(99,102,241,0.2); color: #a5b4fc; padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">
                                        <?= h($p['gateway']) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; font-family: monospace; font-size: 1rem; color: #34d399; font-weight: 700;"><?= h($p['trx_id']) ?></td>
                                <td style="padding: 12px; color: #cbd5e1;"><?= h($p['sender_number'] ?: 'N/A') ?></td>
                                <td style="padding: 12px; font-weight: 800; color: #fff;"><?= number_format((float)$p['amount'], 2) ?> BDT</td>
                                <td style="padding: 12px; color: #94a3b8;"><?= (int)($p['duration_days'] ?: 30) ?> Days</td>
                                <td style="padding: 12px; text-align: right;">
                                    <form method="post" style="display: inline-flex; gap: 6px;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="trx_id" value="<?= (int)$p['id'] ?>">
                                        <button type="submit" name="approve_payment" style="background: #10b981; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">Approve ✅</button>
                                        <button type="submit" name="reject_payment" onclick="return confirm('Reject this payment?');" style="background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 8px 14px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">Reject ❌</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent History Table -->
    <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
        <h3 style="margin-top: 0; color: #38bdf8;">📜 Recent Verified / Processed Payments</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; color: #e2e8f0; font-size: 0.88rem;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8;">
                        <th style="padding: 10px;">Trx ID</th>
                        <th style="padding: 10px;">Customer</th>
                        <th style="padding: 10px;">Gateway</th>
                        <th style="padding: 10px;">Transaction Code</th>
                        <th style="padding: 10px;">Amount</th>
                        <th style="padding: 10px;">Status</th>
                        <th style="padding: 10px;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $r): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 10px;">#TRX-<?= (int)$r['id'] ?></td>
                            <td style="padding: 10px; color: #fff; font-weight: 600;"><?= h($r['user_name']) ?></td>
                            <td style="padding: 10px; text-transform: uppercase; color: #818cf8; font-weight: 700;"><?= h($r['gateway']) ?></td>
                            <td style="padding: 10px; font-family: monospace; color: #34d399;"><?= h($r['trx_id']) ?></td>
                            <td style="padding: 10px; font-weight: 700;"><?= number_format((float)$r['amount'], 2) ?> BDT</td>
                            <td style="padding: 10px;">
                                <span style="background: <?= $r['status']==='approved'?'rgba(16,185,129,0.2)':'rgba(239,68,68,0.2)' ?>; color: <?= $r['status']==='approved'?'#34d399':'#fca5a5' ?>; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                    <?= h($r['status']) ?>
                                </span>
                            </td>
                            <td style="padding: 10px; color: #94a3b8;"><?= h($r['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
