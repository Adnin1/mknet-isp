<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
$pageTitle = 'Pay Bill — MK Net ISP';
if (session_status() === PHP_SESSION_NONE) session_start();

$packageId = filter_input(INPUT_GET, 'package_id', FILTER_VALIDATE_INT);
$bundleId  = filter_input(INPUT_GET, 'bundle_id', FILTER_VALIDATE_INT);

$user = null;
$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([(int)$userId]);
    $user = $stmt->fetch();
}

// Quick search by lookup input (Phone / Email / Account ID) for guests or logged in
$lookupQuery = trim($_GET['lookup'] ?? '');
$foundUser = null;
$pendingInvoices = [];
$message = '';
$messageType = '';

if ($lookupQuery !== '') {
    $lookupStmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ? OR id = ? LIMIT 1");
    $lookupStmt->execute([$lookupQuery, $lookupQuery, is_numeric($lookupQuery) ? (int)$lookupQuery : 0]);
    $foundUser = $lookupStmt->fetch();

    if ($foundUser) {
        $invStmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC");
        $invStmt->execute([(int)$foundUser['id']]);
        $pendingInvoices = $invStmt->fetchAll();
    } else {
        $message = 'No account found matching "' . h($lookupQuery) . '". Please check your Phone or Email.';
        $messageType = 'error';
    }
} elseif ($user) {
    $foundUser = $user;
    $invStmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC");
    $invStmt->execute([(int)$user['id']]);
    $pendingInvoices = $invStmt->fetchAll();
}

// Handle Direct Quick Pay submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_pay') {
    $payUserId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $payAmount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $gateway   = trim($_POST['gateway'] ?? 'bkash');
    $phoneNo   = trim($_POST['phone_number'] ?? '');

    if ($payAmount && $payAmount > 0) {
        // Create an invoice if none exists or pay directly
        $invInsert = $pdo->prepare("INSERT INTO invoices (user_id, amount, currency, status, meta, created_at) VALUES (?, ?, 'BDT', 'pending', ?, NOW())");
        $meta = json_encode(['gateway' => $gateway, 'phone' => $phoneNo, 'type' => 'quick_pay']);
        $invInsert->execute([(int)($payUserId ?: ($user['id'] ?? 0)), $payAmount, $meta]);
        $newInvoiceId = (int)$pdo->lastInsertId();

        header("Location: /pages/payment_redirect.php?invoice_id=" . $newInvoiceId . "&gateway=" . urlencode($gateway));
        exit;
    } else {
        $message = 'Please enter a valid bill amount.';
        $messageType = 'error';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="paybill-page container" style="max-width: 900px; margin: 40px auto; padding: 0 20px;">
    
    <!-- Header Banner -->
    <div class="card" style="background: linear-gradient(135deg, rgba(30,41,59,0.9), rgba(15,23,42,0.9)); border: 1px solid rgba(99,102,241,0.3); border-radius: 16px; padding: 32px; text-align: center; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <div style="font-size: 42px; margin-bottom: 10px;">💳</div>
        <h1 style="font-size: 2.2rem; font-weight: 700; color: #fff; margin-bottom: 8px;">Online Bill Payment</h1>
        <p style="color: #94a3b8; font-size: 1.05rem; max-width: 600px; margin: 0 auto;">
            Pay your MK Net ISP monthly internet bill instantly via bKash, Nagad, Rocket, Upay, or Bank Card with 0% extra fee.
        </p>
    </div>

    <?php if ($message): ?>
        <div style="padding: 16px 20px; border-radius: 10px; margin-bottom: 24px; background: <?= $messageType==='error'?'rgba(239,68,68,0.15)':'rgba(16,185,129,0.15)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: <?= $messageType==='error'?'#fca5a5':'#6ee7b7' ?>;">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <!-- Quick Account Lookup Form -->
    <div class="card" style="background: rgba(30,41,59,0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; margin-bottom: 30px;">
        <h3 style="color: #fff; font-size: 1.2rem; margin-bottom: 16px;">🔍 Find Bill by Phone or Customer ID</h3>
        <form method="get" action="/pages/paybill.php" style="display: flex; gap: 12px; flex-wrap: wrap;">
            <input type="text" name="lookup" value="<?= h($lookupQuery) ?>" placeholder="Enter Mobile Number, Email, or Customer ID..." required class="input" style="flex: 1; min-width: 260px; padding: 12px 16px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
            <button type="submit" class="btn" style="background: #6366f1; color: #fff; padding: 12px 24px; font-weight: 600; border-radius: 8px;">Search Account</button>
        </form>
    </div>

    <!-- Account & Invoice Details -->
    <?php if ($foundUser): ?>
        <div class="card" style="background: rgba(30,41,59,0.8); border: 1px solid rgba(99,102,241,0.2); border-radius: 16px; padding: 24px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 16px; margin-bottom: 20px;">
                <div>
                    <h3 style="color: #fff; margin: 0; font-size: 1.3rem;"><?= h($foundUser['name'] ?? $foundUser['full_name'] ?? 'Valued Customer') ?></h3>
                    <p style="color: #94a3b8; margin: 4px 0 0; font-size: 0.9rem;">Customer ID: #<?= (int)$foundUser['id'] ?> | Phone: <?= h($foundUser['phone'] ?? 'N/A') ?></p>
                </div>
                <span style="background: rgba(16,185,129,0.2); color: #34d399; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">Account Active</span>
            </div>

            <h4 style="color: #e2e8f0; font-size: 1.1rem; margin-bottom: 16px;">Pending Invoices</h4>

            <?php if (!empty($pendingInvoices)): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; color: #e2e8f0;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; font-size: 0.9rem;">
                                <th style="padding: 12px;">Invoice #</th>
                                <th style="padding: 12px;">Amount</th>
                                <th style="padding: 12px;">Date</th>
                                <th style="padding: 12px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingInvoices as $inv): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                    <td style="padding: 14px 12px; font-weight: 600;">#INV-<?= (int)$inv['id'] ?></td>
                                    <td style="padding: 14px 12px; font-weight: 700; color: #60a5fa;"><?= number_format((float)$inv['amount'], 2) ?> BDT</td>
                                    <td style="padding: 14px 12px; color: #94a3b8; font-size: 0.9rem;"><?= date('M d, Y', strtotime($inv['created_at'])) ?></td>
                                    <td style="padding: 14px 12px; text-align: right;">
                                        <a href="/pages/payment_redirect.php?invoice_id=<?= (int)$inv['id'] ?>" class="btn" style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 8px 18px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Pay Now</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); border-radius: 12px; padding: 20px; text-align: center; color: #34d399;">
                    <div style="font-size: 28px; margin-bottom: 6px;">🎉</div>
                    <strong>No Unpaid Bills Found!</strong>
                    <p style="color: #94a3b8; margin: 4px 0 0; font-size: 0.9rem;">Your internet bill is fully paid up to date. You can also advance recharge below if desired.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Express Custom Bill Pay Form -->
    <div class="card" style="background: rgba(30,41,59,0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 28px;">
        <h3 style="color: #fff; font-size: 1.3rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <span>⚡ Express Bill Recharge / Payment</span>
        </h3>

        <form method="post" action="/pages/paybill.php">
            <input type="hidden" name="action" value="quick_pay">
            <input type="hidden" name="user_id" value="<?= (int)($foundUser['id'] ?? ($user['id'] ?? 0)) ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; color: #94a3b8; margin-bottom: 8px; font-size: 0.9rem;">Customer Phone Number</label>
                    <input type="text" name="phone_number" value="<?= h($foundUser['phone'] ?? ($user['phone'] ?? '')) ?>" placeholder="017XXXXXXXX" required class="input" style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                </div>

                <div>
                    <label style="display: block; color: #94a3b8; margin-bottom: 8px; font-size: 0.9rem;">Bill Amount (BDT)</label>
                    <input type="number" name="amount" value="<?= $packageId ? 999 : 1499 ?>" min="10" step="10" required class="input" style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff; font-weight: 700;">
                </div>
            </div>

            <label style="display: block; color: #94a3b8; margin-bottom: 12px; font-size: 0.9rem;">Select Payment Method</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 14px; margin-bottom: 28px;">
                <label style="border: 2px solid #e11d48; border-radius: 10px; padding: 14px; text-align: center; cursor: pointer; background: rgba(225,29,72,0.1); color: #fff; font-weight: 600;">
                    <input type="radio" name="gateway" value="bkash" checked style="margin-right: 6px;"> bKash
                </label>
                <label style="border: 2px solid #f97316; border-radius: 10px; padding: 14px; text-align: center; cursor: pointer; background: rgba(249,115,22,0.1); color: #fff; font-weight: 600;">
                    <input type="radio" name="gateway" value="nagad" style="margin-right: 6px;"> Nagad
                </label>
                <label style="border: 2px solid #8b5cf6; border-radius: 10px; padding: 14px; text-align: center; cursor: pointer; background: rgba(139,92,246,0.1); color: #fff; font-weight: 600;">
                    <input type="radio" name="gateway" value="rocket" style="margin-right: 6px;"> Rocket
                </label>
                <label style="border: 2px solid #06b6d4; border-radius: 10px; padding: 14px; text-align: center; cursor: pointer; background: rgba(6,182,212,0.1); color: #fff; font-weight: 600;">
                    <input type="radio" name="gateway" value="card" style="margin-right: 6px;"> Card / Banking
                </label>
            </div>

            <button type="submit" class="btn" style="width: 100%; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 16px; border-radius: 10px; font-weight: 700; font-size: 1.1rem; cursor: pointer; box-shadow: 0 4px 15px rgba(99,102,241,0.4);">
                Proceed to Secure Payment →
            </button>
        </form>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
