<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
$pageTitle = 'Internet Packages & Custom Plans — MK Net ISP';
include __DIR__ . '/../includes/header.php';

// Helpers
function money($v){ return number_format((float)$v, 2); }

// Category selected via ?category=residential|business|enterprise
$allowedCategories = ['residential','business','enterprise'];
$categoryInput = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$activeCategory = in_array($categoryInput, $allowedCategories, true) ? $categoryInput : '';

// Build query
$where = ["visible = 1"];
$params = [];
if ($activeCategory !== '') { $where[] = "category = ?"; $params[] = $activeCategory; }
$whereSql = implode(' AND ', $where);

try {
  $sql = "SELECT * FROM packages WHERE {$whereSql} ORDER BY price_bdt ASC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
  $rows = [];
}

// Load addons
try {
  $addons = $pdo->query("SELECT * FROM addons ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
  $addons = [];
}

// Load active duration plans
try {
  $durationPlans = $pdo->query("SELECT * FROM duration_plans WHERE active=1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $ex) {
  $durationPlans = [];
}

$isLoggedIn = !empty($_SESSION['user_id'] ?? null);
$hasActivePackage = false;
if ($isLoggedIn) {
  try {
    $chk = $pdo->prepare("SELECT package_id FROM users WHERE id=?");
    $chk->execute([(int)$_SESSION['user_id']]);
    $hasActivePackage = (bool)$chk->fetchColumn();
  } catch (Exception $ex) { $hasActivePackage = false; }
}
?>

<div class="packages-hero-container" style="background: linear-gradient(135deg, #0f172a, #1e1b4b); padding: 50px 20px; color: #fff; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); position: relative; overflow: hidden;">
  <div style="position: absolute; inset: 0; background: radial-gradient(circle at 50% 30%, rgba(99,102,241,0.15), transparent 70%); pointer-events: none;"></div>
  
  <div class="container" style="max-width: 1100px; margin: 0 auto; position: relative; z-index: 2;">
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 10px; background: linear-gradient(to right, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Internet Packages & Custom Duration Plans</h1>
    <p style="font-size: 1.1rem; color: #94a3b8; max-width: 700px; margin: 0 auto 25px;">Ultra-fast fiber optic internet plans tailored for home, gamer, business, and enterprise users.</p>

    <!-- Category Tabs: All / Residential / Business / Enterprise -->
    <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; flex-wrap: wrap;">
      <a href="packages.php" class="btn" style="padding: 10px 22px; border-radius: 30px; font-weight: 600; text-decoration: none; <?= $activeCategory==='' ? 'background:#6366f1; color:#fff;' : 'background:rgba(255,255,255,0.08); color:#cbd5e1;' ?>">All Packages</a>
      <a href="packages.php?category=residential" class="btn" style="padding: 10px 22px; border-radius: 30px; font-weight: 600; text-decoration: none; <?= $activeCategory==='residential' ? 'background:#6366f1; color:#fff;' : 'background:rgba(255,255,255,0.08); color:#cbd5e1;' ?>">🏠 Residential</a>
      <a href="packages.php?category=business" class="btn" style="padding: 10px 22px; border-radius: 30px; font-weight: 600; text-decoration: none; <?= $activeCategory==='business' ? 'background:#6366f1; color:#fff;' : 'background:rgba(255,255,255,0.08); color:#cbd5e1;' ?>">💼 Business</a>
      <a href="packages.php?category=enterprise" class="btn" style="padding: 10px 22px; border-radius: 30px; font-weight: 600; text-decoration: none; <?= $activeCategory==='enterprise' ? 'background:#6366f1; color:#fff;' : 'background:rgba(255,255,255,0.08); color:#cbd5e1;' ?>">🏢 Enterprise</a>
    </div>
  </div>
</div>

<?php
$isLoggedIn = !empty($_SESSION['user_id'] ?? null);
$isRegularUser = false;

if ($isLoggedIn) {
  try {
    $userStmt = $pdo->prepare("SELECT is_regular FROM users WHERE id=?");
    $userStmt->execute([(int)$_SESSION['user_id']]);
    $userFlag = (int)($userStmt->fetchColumn() ?: 0);

    $invStmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE user_id=? AND status='paid'");
    $invStmt->execute([(int)$_SESSION['user_id']]);
    $paidInvoiceCount = (int)$invStmt->fetchColumn();

    if ($userFlag === 1 || $paidInvoiceCount > 0) {
      $isRegularUser = true;
    }
  } catch (Exception $ex) { $isRegularUser = false; }
}
?>

<div class="container" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">

  <!-- Interactive Package Customizer Engine (Regular vs New Customer Rules) -->
  <div class="card" style="background: rgba(30,41,59,0.8); backdrop-filter: blur(14px); border: 1px solid rgba(99,102,241,0.3); border-radius: 20px; padding: 32px; margin-bottom: 45px; color: #fff; box-shadow: 0 15px 40px rgba(0,0,0,0.5);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 16px;">
      <div>
        <h2 style="margin: 0; color: #fff; font-size: 1.6rem; font-weight: 700;">🎛️ Interactive Custom Duration & Plan Builder</h2>
        <p style="color: #94a3b8; margin: 4px 0 0; font-size: 0.95rem;">
          <?php if ($isRegularUser): ?>
            <span style="color:#34d399; font-weight:700;">⭐ Regular Customer Status Active:</span> You can freely select custom durations (1, 3, 7, 15, or 30 days) with pro-rated daily rates!
          <?php else: ?>
            <span style="color:#f59e0b; font-weight:700;">🆕 New Customer Notice:</span> New connections start with standard 30-day packages. Short-term passes (1, 3, 7, 15 days) unlock automatically on your first renewal!
          <?php endif; ?>
        </p>
      </div>
      <span style="background: <?= $isRegularUser ? 'rgba(16,185,129,0.2)' : 'rgba(245,158,11,0.2)' ?>; color: <?= $isRegularUser ? '#34d399' : '#fbbf24' ?>; font-weight: 700; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem;">
        <?= $isRegularUser ? '🔓 Custom Duration Unlocked' : '🔒 30-Day Initial Activation' ?>
      </span>
    </div>

    <form method="get" action="/pages/checkout.php" id="customBuilderForm">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 24px;">
        
        <!-- Step 1: Select Package / Bandwidth -->
        <div>
          <label style="display: block; color: #818cf8; font-weight: 700; margin-bottom: 10px;">1. Choose Base Package / Bandwidth</label>
          <select name="package_id" id="builderPackage" required style="width: 100%; padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.2); color: #fff; font-weight: 600;">
            <?php foreach ($rows as $p): 
              $pPrice = (float)($p['price_bdt'] > 0 ? $p['price_bdt'] : ($p['price'] > 0 ? $p['price'] : 999));
            ?>
              <option value="<?= (int)$p['id'] ?>" data-price="<?= $pPrice ?>" data-name="<?= h($p['name']) ?>">
                <?= h($p['name']) ?> (<?= (int)$p['speed_mbps'] ?> Mbps) — <?= number_format($pPrice, 0) ?> BDT/mo
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Step 2: Choose Duration (1, 3, 7, 15, 30 Days or Custom) -->
        <div>
          <label style="display: block; color: #818cf8; font-weight: 700; margin-bottom: 10px;">2. Select Duration</label>
          <select name="duration_days" id="builderDuration" style="width: 100%; padding: 12px; border-radius: 10px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.2); color: #fff; font-weight: 600;">
            <option value="30" data-discount="0">30 Days (Standard 1 Month)</option>
            <?php if ($isRegularUser): ?>
              <option value="1" data-discount="0">1 Day (Emergency Pass)</option>
              <option value="3" data-discount="0">3 Days Pass</option>
              <option value="7" data-discount="2">7 Days (Weekly Top-Up)</option>
              <option value="15" data-discount="3">15 Days (Half-Month)</option>
              <option value="60" data-discount="7">60 Days (2 Months)</option>
              <option value="90" data-discount="10">90 Days (Quarterly)</option>
            <?php else: ?>
              <option value="1" disabled style="color:#64748b;">1 Day Pass (Locked for New Users)</option>
              <option value="3" disabled style="color:#64748b;">3 Days Pass (Locked for New Users)</option>
              <option value="7" disabled style="color:#64748b;">7 Days Pass (Locked for New Users)</option>
              <option value="15" disabled style="color:#64748b;">15 Days Pass (Locked for New Users)</option>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <!-- Real-Time Price Calculation Banner -->
      <div style="background: rgba(15,23,42,0.7); border: 1px solid rgba(99,102,241,0.2); border-radius: 14px; padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
          <span style="color: #94a3b8; font-size: 0.9rem; display: block;">Total Calculated Price:</span>
          <span id="calculatedPriceDisplay" style="font-size: 2.2rem; font-weight: 800; color: #10b981;">999.00</span>
          <span style="color: #34d399; font-weight: 700; font-size: 1.1rem;"> BDT</span>
          <span id="durationLabelDisplay" style="color: #94a3b8; font-size: 0.85rem; margin-left: 8px;">(30 Days)</span>
        </div>

        <button type="submit" class="btn" style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 14px 28px; border-radius: 10px; font-weight: 700; font-size: 1rem; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(16,185,129,0.4);">
          Proceed to Order →
        </button>
      </div>
    </form>
  </div>

  <h2 style="color: #fff; font-size: 1.8rem; font-weight: 700; margin-bottom: 24px;">Standard Monthly Internet Packages</h2>

  <!-- Packages Grid -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 28px;">
    <?php if (empty($rows)): ?>
      <div class="card" style="grid-column: 1/-1; padding: 40px; text-align: center; color: #94a3b8;">
        No packages found in this category.
      </div>
    <?php else: foreach ($rows as $p):
      $pkgPrice = (float)($p['price_bdt'] > 0 ? $p['price_bdt'] : ($p['price'] > 0 ? $p['price'] : 999));
      $features = !empty($p['features']) ? (json_decode($p['features'], true) ?: []) : ['Unlimited Data', 'BDIX Speed Boost', '24/7 Support'];
    ?>
      <div class="card package-card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 28px; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.3s ease, border-color 0.3s ease; position: relative;">
        
        <?php if (!empty($p['badge']) && $p['badge'] !== 'none'): ?>
          <span style="position: absolute; top: 16px; right: 16px; background: linear-gradient(135deg, #ec4899, #8b5cf6); color: #fff; font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; text-transform: uppercase;">
            <?= h($p['badge']) ?>
          </span>
        <?php endif; ?>

        <div>
          <div style="font-size: 0.85rem; color: #818cf8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
            <?= h(strtoupper($p['category'] ?? 'residential')) ?>
          </div>
          <h3 style="font-size: 1.6rem; color: #fff; font-weight: 700; margin-bottom: 8px;"><?= h($p['name']) ?></h3>
          <p style="color: #94a3b8; font-size: 0.95rem; margin-bottom: 20px; line-height: 1.5;"><?= h($p['description']) ?></p>

          <div style="background: rgba(15,23,42,0.6); padding: 16px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
              <div>
                <span style="font-size: 2.2rem; font-weight: 800; color: #fff;"><?= number_format($pkgPrice, 0) ?></span>
                <span style="color: #818cf8; font-weight: 700; font-size: 1.1rem;"> BDT</span>
                <span style="color: #94a3b8; font-size: 0.85rem;"> / month</span>
              </div>
              <span style="background: rgba(99,102,241,0.2); color: #a5b4fc; font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 0.9rem;">
                ⚡ <?= (int)$p['speed_mbps'] ?> Mbps
              </span>
            </div>
          </div>

          <!-- Feature List -->
          <ul style="list-style: none; padding: 0; margin: 0 0 24px; color: #e2e8f0; font-size: 0.95rem;">
            <?php foreach ($features as $feat): ?>
              <li style="margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <span style="color: #10b981; font-weight: 800;">✓</span> <?= h($feat) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div>
          <!-- Subscribe & Compare Buttons -->
          <div style="display:flex; gap:8px; margin-bottom:10px;">
            <button onclick="toggleWishlist(<?= (int)$p['id'] ?>, '<?= h($p['name']) ?>')" id="fav-btn-<?= (int)$p['id'] ?>" style="flex:1; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:10px; border-radius:8px; font-weight:600; cursor:pointer;">
              ♡ Wishlist
            </button>
            <label style="flex:1; background:rgba(99,102,241,0.15); border:1px solid #6366f1; color:#a5b4fc; padding:10px; border-radius:8px; font-weight:600; cursor:pointer; text-align:center; font-size:0.85em;">
              <input type="checkbox" class="compare-chk" value="<?= (int)$p['id'] ?>" data-name="<?= h($p['name']) ?>" data-speed="<?= (int)$p['speed_mbps'] ?>" data-price="<?= money($p['price_bdt']) ?>" onchange="updateCompareBar()"> Compare
            </label>
          </div>
          <a href="/pages/checkout.php?package_id=<?= (int)$p['id'] ?>" class="btn" style="display: block; width: 100%; text-align: center; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 14px; border-radius: 10px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 14px rgba(99,102,241,0.3);">
            Subscribe Now (30 Days) →
          </a>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

</div>

<!-- Floating Compare Action Bar -->
<div id="compareBar" style="display:none; position:fixed; bottom:20px; right:20px; background:#1e293b; border:1px solid #6366f1; border-radius:14px; padding:16px 24px; box-shadow:0 10px 30px rgba(0,0,0,0.6); z-index:999; color:#fff; align-items:center; gap:20px;">
  <div>
    <strong id="compareCount">0 Packages Selected</strong>
    <div style="font-size:0.8em; color:#94a3b8;">Compare speed, price & features side-by-side</div>
  </div>
  <button onclick="openCompareModal()" style="background:linear-gradient(135deg, #10b981, #059669); color:#fff; padding:10px 20px; border:none; border-radius:8px; font-weight:700; cursor:pointer;">
    Compare Side-by-Side 📊
  </button>
</div>

<!-- Real-time Custom Plan Builder Calculator Script -->
<script>
(function() {
  const pkgSelect = document.getElementById('builderPackage');
  const durSelect = document.getElementById('builderDuration');
  const priceDisplay = document.getElementById('calculatedPriceDisplay');
  const labelDisplay = document.getElementById('durationLabelDisplay');

  function calculate() {
    if (!pkgSelect || !durSelect || !priceDisplay) return;
    const selectedPkg = pkgSelect.options[pkgSelect.selectedIndex];
    const selectedDur = durSelect.options[durSelect.selectedIndex];

    const monthlyPrice = parseFloat(selectedPkg.dataset.price || 999);
    const days = parseInt(selectedDur.value || 30);
    const discount = parseFloat(selectedDur.dataset.discount || 0);

    const dailyRate = monthlyPrice / 30;
    let rawTotal = dailyRate * days;
    if (discount > 0) {
      rawTotal = rawTotal * (1 - (discount / 100));
    }

    priceDisplay.textContent = rawTotal.toFixed(2);
    labelDisplay.textContent = `(${days} Day${days > 1 ? 's' : ''}${discount > 0 ? ' - ' + discount + '% discount' : ''})`;
  }

  if (pkgSelect) pkgSelect.addEventListener('change', calculate);
  if (durSelect) durSelect.addEventListener('change', calculate);
  calculate();
})();

function toggleWishlist(id, name) {
  var favs = JSON.parse(localStorage.getItem('mknet_fav_packages') || '[]');
  var btn = document.getElementById('fav-btn-' + id);
  if (favs.includes(id)) {
    favs = favs.filter(x => x !== id);
    btn.innerHTML = '♡ Wishlist';
    btn.style.background = 'rgba(255,255,255,0.08)';
  } else {
    favs.push(id);
    btn.innerHTML = '❤️ Saved';
    btn.style.background = 'rgba(239,68,68,0.2)';
  }
  localStorage.setItem('mknet_fav_packages', JSON.stringify(favs));
}

function updateCompareBar() {
  var checked = document.querySelectorAll('.compare-chk:checked');
  var bar = document.getElementById('compareBar');
  var count = document.getElementById('compareCount');
  if (checked.length > 0) {
    bar.style.display = 'flex';
    count.textContent = checked.length + ' Package' + (checked.length > 1 ? 's' : '') + ' Selected';
  } else {
    bar.style.display = 'none';
  }
}

function openCompareModal() {
  var checked = document.querySelectorAll('.compare-chk:checked');
  if (checked.length === 0) return;
  var msg = '📊 PACKAGE COMPARISON:\n\n';
  checked.forEach(function(c) {
    msg += '• ' + c.dataset.name + ' (' + c.dataset.speed + ' Mbps) — ' + c.dataset.price + ' BDT/mo\n';
  });
  alert(msg);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
