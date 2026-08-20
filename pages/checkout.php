<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Checkout — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();

// Tax rate: Bangladesh broadband/ISP bills have carried a reduced VAT
// (historically 5%) plus, since a Jan-2025 policy change, an additional
// 10% supplementary duty was reported for broadband specifically
// (total ~15.5%). Tax policy is not something this code can verify for
// you in real time - CONFIRM THE CURRENT RATE WITH YOUR ACCOUNTANT OR
// THE NATIONAL BOARD OF REVENUE (NBR) before launch and update the
// figure below. This is the one number in the whole project you should
// not trust blindly from generated code.
const TAX_RATE_PERCENT = 15.5;

if (empty($_SESSION['user_id'])) {
    $_SESSION['post_login_redirect'] = "/pages/checkout.php?" . http_build_query($_GET);
    header('Location: /pages/login.php?notice=' . urlencode('Please log in to continue checkout.'));
    exit;
}
if (!SessionManager::touch($pdo)) { header("Location: login.php"); exit; }
$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// ---- Resolve what's being purchased ----
$packageId = filter_input(INPUT_GET, 'package_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
$bundleId = filter_input(INPUT_GET, 'bundle_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'bundle_id', FILTER_VALIDATE_INT);
$addonIdsRaw = $_GET['addons'] ?? $_POST['addons'] ?? '';
$addonIds = array_filter(array_map('intval', explode(',', (string)$addonIdsRaw)));

if (!$packageId && !$bundleId) {
    header("Location: /pages/packages.php?error=" . urlencode('Please select a package first.'));
    exit;
}

$item = null;
$itemLabel = '';
if ($packageId) {
    $s = $pdo->prepare("SELECT * FROM packages WHERE id=? AND visible=1");
    $s->execute([$packageId]);
    $item = $s->fetch();
    $itemLabel = $item['name'] ?? '';
} elseif ($bundleId) {
    $s = $pdo->prepare("SELECT * FROM bundles WHERE id=? AND visible=1");
    $s->execute([$bundleId]);
    $item = $s->fetch();
    $itemLabel = $item['name'] ?? '';
}
if (!$item) {
    header("Location: /pages/packages.php?error=" . urlencode('That package could not be found.'));
    exit;
}

$baseAmount = (float)($item['price_bdt'] ?? $item['price'] ?? 0);

// Promo price (already-active discount on the package itself, if any)
$promoActive = false;
if (!empty($item['promo_price']) && !empty($item['promo_start']) && !empty($item['promo_end'])) {
    if (strtotime($item['promo_start']) <= time() && time() <= strtotime($item['promo_end'])) {
        $promoActive = true;
        $baseAmount = (float)$item['promo_price'];
    }
}

$selectedAddons = [];
$addonsTotal = 0.0;
if ($addonIds) {
    $in = implode(',', array_fill(0, count($addonIds), '?'));
    $s = $pdo->prepare("SELECT * FROM addons WHERE id IN ($in) AND visible=1");
    $s->execute($addonIds);
    $selectedAddons = $s->fetchAll();
    foreach ($selectedAddons as $a) $addonsTotal += (float)$a['price'];
}

$subtotal = $baseAmount + $addonsTotal;

$step = $_POST['step'] ?? 'review';
$error = '';

// ---- Coupon / Referral (stored in session as the checkout "draft") ----
if (!isset($_SESSION['checkout_draft']) || $_SESSION['checkout_draft']['item_key'] !== "{$packageId}-{$bundleId}-{$addonIdsRaw}") {
    $_SESSION['checkout_draft'] = ['item_key' => "{$packageId}-{$bundleId}-{$addonIdsRaw}", 'coupon_id' => null, 'coupon_discount' => 0, 'referral_discount' => 0, 'agreed_terms' => false];
}
$draft = &$_SESSION['checkout_draft'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
    $error = "Session expired, please try again.";
    $step = 'review';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {

    if (isset($_POST['apply_coupon'])) {
        $result = Coupon::validate($pdo, (string)($_POST['coupon_code'] ?? ''), $subtotal);
        if ($result['valid']) {
            $draft['coupon_id'] = $result['coupon']['id'];
            $draft['coupon_code'] = $result['coupon']['code'];
            $draft['coupon_discount'] = $result['discount'];
        } else {
            $error = $result['message'];
        }
        $step = 'review';
    }

    elseif (isset($_POST['apply_referral'])) {
        $code = strtoupper(trim((string)($_POST['referral_code'] ?? '')));
        $r = $pdo->prepare("SELECT id FROM users WHERE referral_code=? AND id<>?");
        $r->execute([$code, $userId]);
        if ($r->fetch()) {
            $draft['referral_code'] = $code;
            $draft['referral_discount'] = round($subtotal * 0.05, 2); // 5% checkout discount for using a referral code
        } else {
            $error = "Invalid referral code.";
        }
        $step = 'review';
    }

    elseif (isset($_POST['proceed_to_otp'])) {
        if (empty($_POST['agree_terms'])) {
            $error = "Please agree to the Terms & Conditions to continue.";
            $step = 'review';
        } else {
            $draft['agreed_terms'] = true;
            try {
                Otp::issue($pdo, $userId, 'email', 'checkout', $user['email']);
                $step = 'otp';
            } catch (RuntimeException $ex) {
                $error = $ex->getMessage();
                $step = 'review';
            }
        }
    }

    elseif (isset($_POST['resend_otp'])) {
        try {
            Otp::issue($pdo, $userId, 'email', 'checkout', $user['email']);
        } catch (RuntimeException $ex) {
            $error = $ex->getMessage();
        }
        $step = 'otp';
    }

    elseif (isset($_POST['verify_otp'])) {
        $code = trim((string)($_POST['otp_code'] ?? ''));
        if (Otp::verify($pdo, 'checkout', $user['email'], $code)) {
            $step = 'payment';
        } else {
            $error = "Invalid or expired code.";
            $step = 'otp';
        }
    }

    elseif (isset($_POST['place_order'])) {
        $gateway = $_POST['gateway'] ?? '';
        if (!isset(PaymentGatewayFactory::GATEWAYS[$gateway])) {
            $error = "Please select a valid payment method.";
            $step = 'payment';
        } else {
            $discount = (float)$draft['coupon_discount'] + (float)$draft['referral_discount'];
            $taxableAmount = max(0, $subtotal - $discount);
            $tax = round($taxableAmount * (TAX_RATE_PERCENT / 100), 2);
            $total = round($taxableAmount + $tax, 2);

            $meta = [
                'type' => $packageId ? 'package' : 'bundle',
                'package_id' => $packageId, 'bundle_id' => $bundleId,
                'addons' => array_map(fn($a) => ['id' => $a['id'], 'name' => $a['name'], 'price' => $a['price']], $selectedAddons),
                'subtotal' => $subtotal, 'coupon_code' => $draft['coupon_code'] ?? null, 'coupon_discount' => $draft['coupon_discount'],
                'referral_code' => $draft['referral_code'] ?? null, 'referral_discount' => $draft['referral_discount'],
                'tax_rate_percent' => TAX_RATE_PERCENT, 'tax_amount' => $tax,
            ];

            $ins = $pdo->prepare(
                "INSERT INTO invoices (user_id, package_id, bundle_id, amount, currency, status, meta, created_at) VALUES (?,?,?,?,?,?,?,NOW())"
            );
            $ins->execute([$userId, $packageId ?: null, $bundleId ?: null, $total, 'BDT', 'pending', json_encode($meta)]);
            $invoiceId = (int)$pdo->lastInsertId();

            foreach ($selectedAddons as $a) {
                $pdo->prepare("INSERT INTO user_addons (user_id, addon_id, status) VALUES (?,?, 'active')")->execute([$userId, $a['id']]);
            }

            // Recurring billing: (re)set this user's next billing date to one
            // month from now, so scripts/generate_recurring_invoices.php
            // picks them up automatically going forward.
            if ($packageId) {
                $pdo->prepare(
                    "INSERT INTO billing_cycles (user_id, package_id, last_billed_at, next_billing_at, auto_renew) VALUES (?,?, CURDATE(), CURDATE() + INTERVAL 1 MONTH, 1)
                     ON DUPLICATE KEY UPDATE package_id=VALUES(package_id), last_billed_at=CURDATE(), next_billing_at=CURDATE() + INTERVAL 1 MONTH, auto_renew=1"
                )->execute([$userId, $packageId]);
                $pdo->prepare("UPDATE users SET package_id=?, package_expires_at=CURDATE() + INTERVAL 1 MONTH WHERE id=?")->execute([$packageId, $userId]);
            }
            if (!empty($draft['coupon_id'])) Coupon::redeem($pdo, (int)$draft['coupon_id']);
            if (!empty($draft['referral_code'])) {
                $rf = $pdo->prepare("SELECT id, referrer_user_id FROM referrals WHERE referred_user_id=? AND status='pending' ORDER BY id DESC LIMIT 1");
                $rf->execute([$userId]);
                if ($refRow = $rf->fetch()) {
                    $pdo->prepare("UPDATE referrals SET status='completed', completed_at=NOW() WHERE id=?")->execute([$refRow['id']]);
                    Notifier::send($pdo, (int)$refRow['referrer_user_id'], 'Referral reward earned!', 'Your referral just made their first purchase. Your reward is on its way.', 'referral');
                }
            }

            unset($_SESSION['checkout_draft']);

            $txnId = PaymentGatewayFactory::recordAttempt($pdo, $invoiceId, $userId, $gateway, $total);
            $gatewayObj = PaymentGatewayFactory::make($gateway);
            try {
                $result = $gatewayObj->initiate([
                    'invoice_id' => $invoiceId,
                    'amount' => $total,
                    'currency' => 'BDT',
                    'tran_id' => 'MKNET-' . $invoiceId . '-' . time(),
                    'success_url' => 'http://' . $_SERVER['HTTP_HOST'] . "/pages/payment_callback.php?invoice_id=" . $invoiceId . '&gateway=' . $gateway,
                    'fail_url' => 'http://' . $_SERVER['HTTP_HOST'] . "/pages/payment_redirect.php?invoice_id=" . $invoiceId . '&failed=1',
                    'cancel_url' => 'http://' . $_SERVER['HTTP_HOST'] . "/pages/payment_redirect.php?invoice_id=" . $invoiceId,
                    'customer_name' => $user['full_name'] ?? $user['email'],
                    'customer_email' => $user['email'],
                    'customer_phone' => $user['phone'] ?? '',
                    'product_name' => $itemLabel,
                ]);
                header("Location: " . $result['url']);
                exit;
            } catch (RuntimeException $ex) {
                app_log('checkout', "Gateway initiate failed: " . $ex->getMessage());
                header("Location: /pages/payment_redirect.php?invoice_id={$invoiceId}&gateway_error=1");
                exit;
            }
        }
    }
}

$discount = (float)($draft['coupon_discount'] ?? 0) + (float)($draft['referral_discount'] ?? 0);
$taxableAmount = max(0, $subtotal - $discount);
$tax = round($taxableAmount * (TAX_RATE_PERCENT / 100), 2);
$total = round($taxableAmount + $tax, 2);

include __DIR__ . '/../includes/header.php';
?>
<main class="container">
<div class="card" style="max-width:560px;margin:24px auto;padding:20px;">
  <h2>Checkout</h2>
  <?php if ($error): ?><p style="color:#b00020;background:#ffe6e6;padding:10px;border-radius:6px;"><?= h($error) ?></p><?php endif; ?>

  <!-- Order Summary (always visible) -->
  <div style="background:rgba(0,0,0,0.03);padding:14px;border-radius:8px;margin-bottom:16px;">
    <h3 style="margin-top:0;">Order Summary</h3>
    <div style="display:flex;justify-content:space-between;"><span><?= h($itemLabel) ?></span><span><?= number_format($baseAmount, 2) ?> BDT</span></div>
    <?php foreach ($selectedAddons as $a): ?>
      <div style="display:flex;justify-content:space-between;color:#666;"><span>+ <?= h($a['name']) ?></span><span><?= number_format((float)$a['price'], 2) ?> BDT</span></div>
    <?php endforeach; ?>
    <hr>
    <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><span><?= number_format($subtotal, 2) ?> BDT</span></div>
    <?php if ($discount > 0): ?>
      <div style="display:flex;justify-content:space-between;color:#0a6;"><span>Discount<?= !empty($draft['coupon_code']) ? ' (' . h($draft['coupon_code']) . ')' : '' ?></span><span>-<?= number_format($discount, 2) ?> BDT</span></div>
    <?php endif; ?>
    <div style="display:flex;justify-content:space-between;"><span>VAT/Tax (<?= TAX_RATE_PERCENT ?>%)</span><span><?= number_format($tax, 2) ?> BDT</span></div>
    <hr>
    <div style="display:flex;justify-content:space-between;font-weight:bold;font-size:18px;"><span>Total</span><span><?= number_format($total, 2) ?> BDT</span></div>
  </div>

  <?php if ($step === 'review'): ?>
    <!-- Coupon -->
    <form method="post" style="margin-bottom:10px;display:flex;gap:6px;">
      <?= csrf_field() ?>
      <input type="hidden" name="package_id" value="<?= h($packageId) ?>">
      <input type="hidden" name="bundle_id" value="<?= h($bundleId) ?>">
      <input type="hidden" name="addons" value="<?= h($addonIdsRaw) ?>">
      <input type="text" name="coupon_code" placeholder="Coupon / Promo code" style="flex:1;padding:8px;" value="<?= h($draft['coupon_code'] ?? '') ?>">
      <button type="submit" name="apply_coupon" class="btn">Apply</button>
    </form>

    <!-- Referral -->
    <form method="post" style="margin-bottom:16px;display:flex;gap:6px;">
      <?= csrf_field() ?>
      <input type="hidden" name="package_id" value="<?= h($packageId) ?>">
      <input type="hidden" name="bundle_id" value="<?= h($bundleId) ?>">
      <input type="hidden" name="addons" value="<?= h($addonIdsRaw) ?>">
      <input type="text" name="referral_code" placeholder="Referral code (optional)" style="flex:1;padding:8px;" value="<?= h($draft['referral_code'] ?? '') ?>">
      <button type="submit" name="apply_referral" class="btn">Apply</button>
    </form>

    <!-- Terms + proceed -->
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="package_id" value="<?= h($packageId) ?>">
      <input type="hidden" name="bundle_id" value="<?= h($bundleId) ?>">
      <input type="hidden" name="addons" value="<?= h($addonIdsRaw) ?>">
      <label style="display:flex;align-items:start;gap:6px;margin-bottom:12px;">
        <input type="checkbox" name="agree_terms" required style="margin-top:4px;">
        <span>I agree to the <a href="/pages/terms.php" target="_blank">Terms & Conditions</a> and authorize this purchase.</span>
      </label>
      <button type="submit" name="proceed_to_otp" class="btn primary" style="width:100%;">Continue to Verification</button>
    </form>

  <?php elseif ($step === 'otp'): ?>
    <p>We've sent a 6-digit confirmation code to <b><?= h($user['email']) ?></b>.</p>
    <?php if (Services::isDevMode()): ?><p class="small-note">Dev mode: check <code>storage/logs/mail.log</code> for the code.</p><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="package_id" value="<?= h($packageId) ?>">
      <input type="hidden" name="bundle_id" value="<?= h($bundleId) ?>">
      <input type="hidden" name="addons" value="<?= h($addonIdsRaw) ?>">
      <input type="text" name="otp_code" placeholder="123456" required autofocus style="display:block;width:100%;margin:8px 0;padding:10px;font-size:20px;letter-spacing:4px;text-align:center;">
      <button type="submit" name="verify_otp" class="btn primary" style="width:100%;">Verify & Continue</button>
    </form>
    <form method="post" style="margin-top:8px;">
      <?= csrf_field() ?>
      <input type="hidden" name="package_id" value="<?= h($packageId) ?>">
      <input type="hidden" name="bundle_id" value="<?= h($bundleId) ?>">
      <input type="hidden" name="addons" value="<?= h($addonIdsRaw) ?>">
      <button type="submit" name="resend_otp" class="btn" style="width:100%;">Resend Code</button>
    </form>

  <?php elseif ($step === 'payment'): ?>
    <h3>Choose Payment Method</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="package_id" value="<?= h($packageId) ?>">
      <input type="hidden" name="bundle_id" value="<?= h($bundleId) ?>">
      <input type="hidden" name="addons" value="<?= h($addonIdsRaw) ?>">
      <?php foreach (PaymentGatewayFactory::GATEWAYS as $key => $g): ?>
        <label style="display:flex;align-items:center;gap:8px;padding:10px;border:1px solid #ddd;border-radius:6px;margin-bottom:8px;cursor:pointer;">
          <input type="radio" name="gateway" value="<?= h($key) ?>" required>
          <?= h($g['label']) ?>
        </label>
      <?php endforeach; ?>
      <button type="submit" name="place_order" class="btn primary" style="width:100%;margin-top:10px;">Place Order — <?= number_format($total, 2) ?> BDT</button>
    </form>
  <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
