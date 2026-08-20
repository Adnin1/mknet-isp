<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Recharge / Renew — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!SessionManager::touch($pdo)) { header("Location: login.php"); exit; }

const TAX_RATE_PERCENT_RECHARGE = 15.5; // matches checkout.php's rate

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.*, p.name AS package_name, p.price_bdt, p.speed_mbps FROM users u LEFT JOIN packages p ON u.package_id=p.id WHERE u.id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// This feature is only for customers who already have a package (existing
// subscribers) - new customers must complete registration with a fixed
// package first. This is enforced here in code, not just hidden in the UI.
if (empty($user['package_id'])) {
    include __DIR__ . '/../includes/header.php';
    ?>
    <main class="container">
      <div class="card" style="max-width:480px;margin:40px auto;text-align:center;padding:24px;">
        <h2>No Active Package Yet</h2>
        <p>Recharge/renewal is available once you've subscribed to a package. Choose your first package to get started:</p>
        <p><a class="btn primary" href="/pages/packages.php">Browse Packages</a></p>
      </div>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <?php
    exit;
}

$plans = $pdo->query("SELECT * FROM duration_plans WHERE active=1 ORDER BY sort_order")->fetchAll();
$dailyRate = round((float)$user['price_bdt'] / 30, 4);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recharge'])) {
    if (!csrf_verify()) {
        $error = "Session expired, please try again.";
    } else {
        $planId = filter_input(INPUT_POST, 'plan_id', FILTER_VALIDATE_INT);
        $customDays = filter_input(INPUT_POST, 'custom_days', FILTER_VALIDATE_INT);

        $planStmt = $pdo->prepare("SELECT * FROM duration_plans WHERE id=? AND active=1");
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch();

        if (!$plan) {
            $error = "Please select a valid duration.";
        } else {
            $days = $plan['is_custom'] ? $customDays : (int)$plan['days'];
            if ($plan['is_custom'] && ($days < $plan['min_custom_days'] || $days > $plan['max_custom_days'])) {
                $error = "Custom duration must be between {$plan['min_custom_days']} and {$plan['max_custom_days']} days.";
            } elseif ($days < 1) {
                $error = "Please enter a valid number of days.";
            } else {
                $subtotal = round($dailyRate * $days, 2);
                $discount = round($subtotal * ((float)$plan['discount_percent'] / 100), 2);
                $afterDiscount = $subtotal - $discount;
                $tax = round($afterDiscount * (TAX_RATE_PERCENT_RECHARGE / 100), 2);
                $total = round($afterDiscount + $tax, 2);

                $meta = json_encode([
                    'type' => 'recharge',
                    'duration_days' => $days,
                    'plan_label' => $plan['label'],
                    'package_id' => $user['package_id'],
                    'subtotal' => $subtotal,
                    'discount_percent' => $plan['discount_percent'],
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                ]);

                $ins = $pdo->prepare("INSERT INTO invoices (user_id, package_id, amount, currency, status, meta, created_at) VALUES (?,?,?,?,'pending',?,NOW())");
                $ins->execute([$userId, $user['package_id'], $total, 'BDT', $meta]);
                $invoiceId = (int)$pdo->lastInsertId();

                header("Location: /pages/payment_redirect.php?invoice_id=" . $invoiceId);
                exit;
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<main class="container">
<div style="max-width:640px;margin:30px auto;">
  <h2>Recharge / Renew Connection</h2>
  <p>Current package: <b><?= h($user['package_name']) ?></b> (<?= h($user['speed_mbps']) ?> Mbps)
    <?php if ($user['package_expires_at']): ?> — expires <b><?= h($user['package_expires_at']) ?></b><?php endif; ?>
  </p>

  <?php if ($error): ?><p style="color:#b00020;"><?= h($error) ?></p><?php endif; ?>

  <form method="post" id="rechargeForm">
    <?= csrf_field() ?>
    <div class="card" style="padding:16px;margin-bottom:16px;">
      <h3>Choose Duration</h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;">
        <?php foreach ($plans as $p): ?>
          <label style="border:1px solid #ccc;border-radius:8px;padding:10px;text-align:center;cursor:pointer;">
            <input type="radio" name="plan_id" value="<?= (int)$p['id'] ?>" data-days="<?= (int)($p['days'] ?? 0) ?>" data-discount="<?= h($p['discount_percent']) ?>" data-custom="<?= $p['is_custom'] ?>" data-min="<?= (int)$p['min_custom_days'] ?>" data-max="<?= (int)$p['max_custom_days'] ?>" onchange="recalc()" required>
            <div><b><?= h($p['label']) ?></b></div>
            <?php if ($p['discount_percent'] > 0): ?><div class="small-note" style="color:#0a6;"><?= h($p['discount_percent']) ?>% off</div><?php endif; ?>
          </label>
        <?php endforeach; ?>
      </div>

      <div id="customDaysWrap" style="display:none;margin-top:10px;">
        <label>Number of days:</label>
        <input type="number" name="custom_days" id="customDaysInput" min="1" onchange="recalc()" oninput="recalc()" style="padding:8px;width:100px;">
      </div>
    </div>

    <div class="card" style="padding:16px;margin-bottom:16px;">
      <h3>Price Breakdown</h3>
      <table style="width:100%;">
        <tr><td>Daily rate</td><td style="text-align:right;"><?= number_format($dailyRate, 2) ?> BDT/day</td></tr>
        <tr><td>Duration</td><td style="text-align:right;" id="calcDays">—</td></tr>
        <tr><td>Subtotal</td><td style="text-align:right;" id="calcSubtotal">0.00 BDT</td></tr>
        <tr><td>Discount</td><td style="text-align:right;color:#0a6;" id="calcDiscount">-0.00 BDT</td></tr>
        <tr><td>Tax (<?= TAX_RATE_PERCENT_RECHARGE ?>%)</td><td style="text-align:right;" id="calcTax">0.00 BDT</td></tr>
        <tr style="font-weight:bold;font-size:1.1em;border-top:1px solid #ccc;"><td>Total Payable</td><td style="text-align:right;" id="calcTotal">0.00 BDT</td></tr>
      </table>
    </div>

    <button type="submit" name="recharge" class="btn primary" style="width:100%;">Proceed to Payment</button>
  </form>
</div>
</main>

<script>
const DAILY_RATE = <?= json_encode($dailyRate) ?>;
const TAX_RATE = <?= json_encode(TAX_RATE_PERCENT_RECHARGE) ?>;

function recalc() {
  const selected = document.querySelector('input[name="plan_id"]:checked');
  const customWrap = document.getElementById('customDaysWrap');
  if (!selected) return;

  const isCustom = selected.dataset.custom === '1';
  customWrap.style.display = isCustom ? 'block' : 'none';

  let days = isCustom ? (parseInt(document.getElementById('customDaysInput').value) || 0) : parseInt(selected.dataset.days);
  if (isCustom) {
    document.getElementById('customDaysInput').min = selected.dataset.min;
    document.getElementById('customDaysInput').max = selected.dataset.max;
  }

  const discountPct = parseFloat(selected.dataset.discount) || 0;
  const subtotal = DAILY_RATE * days;
  const discount = subtotal * (discountPct / 100);
  const afterDiscount = subtotal - discount;
  const tax = afterDiscount * (TAX_RATE / 100);
  const total = afterDiscount + tax;

  document.getElementById('calcDays').textContent = days > 0 ? days + ' day(s)' : '—';
  document.getElementById('calcSubtotal').textContent = subtotal.toFixed(2) + ' BDT';
  document.getElementById('calcDiscount').textContent = '-' + discount.toFixed(2) + ' BDT';
  document.getElementById('calcTax').textContent = tax.toFixed(2) + ' BDT';
  document.getElementById('calcTotal').textContent = total.toFixed(2) + ' BDT';
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
