<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'My Dashboard — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!SessionManager::touch($pdo)) { header("Location: login.php"); exit; }

$user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Users who registered before the referral feature existed won't have a
// code yet - generate one now rather than showing a blank/N-A field.
if (empty($user['referral_code'])) {
    $newCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    $pdo->prepare("UPDATE users SET referral_code=? WHERE id=?")->execute([$newCode, $user_id]);
    $user['referral_code'] = $newCode;
}

// Current package details
$currentPackage = null;
if (!empty($user['package_id'])) {
    $p = $pdo->prepare("SELECT * FROM packages WHERE id=?");
    $p->execute([$user['package_id']]);
    $currentPackage = $p->fetch();
}
$remainingDays = null;
if (!empty($user['package_expires_at'])) {
    $remainingDays = (int)ceil((strtotime($user['package_expires_at']) - time()) / 86400);
}

// Current/outstanding bill (most recent pending invoice)
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id=? AND status='pending' ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$currentBill = $stmt->fetch();

// Real due-date data (from the billing table, which is where admin-issued
// bills with actual due dates live) for the "Bill Due" countdown.
$stmt = $pdo->prepare("SELECT * FROM billing WHERE user_id=? AND status='pending' ORDER BY due_date ASC LIMIT 1");
$stmt->execute([$user_id]);
$nextDueBill = $stmt->fetch();
$daysUntilDue = $nextDueBill ? (int)ceil((strtotime($nextDueBill['due_date']) - time()) / 86400) : null;

// Payment history (invoices - the modern flow) merged with legacy billing rows
$stmt = $pdo->prepare("SELECT id, amount, status, created_at, 'invoice' as src FROM invoices WHERE user_id=? ORDER BY id DESC LIMIT 10");
$stmt->execute([$user_id]);
$paymentHistory = $stmt->fetchAll();

// Active addons
$stmt = $pdo->prepare(
    "SELECT ua.id as ua_id, a.* FROM user_addons ua JOIN addons a ON ua.addon_id = a.id
     WHERE ua.user_id=? AND ua.status='active' ORDER BY ua.added_at DESC"
);
$stmt->execute([$user_id]);
$activeAddons = $stmt->fetchAll();

$availableAddons = $pdo->query("SELECT * FROM addons WHERE visible=1")->fetchAll();

// Packages for upgrade/downgrade picker
$allPackages = $pdo->query("SELECT * FROM packages WHERE visible=1 ORDER BY speed_mbps ASC")->fetchAll();

// Notifications
$notifications = Notifier::listForUser($pdo, $user_id, 10);
$unreadCount = Notifier::unreadCount($pdo, $user_id);

// Usage history
$stmt = $pdo->prepare("SELECT * FROM usage_history WHERE user_id=? ORDER BY date DESC LIMIT 30");
$stmt->execute([$user_id]);
$latestDowntime = isset($rows[0]['downtime_seconds']) ? (int)$rows[0]['downtime_seconds'] : 0;
$latestProblems = isset($rows[0]['problems_count']) ? (int)$rows[0]['problems_count'] : 0;

$notice = $_GET['notice'] ?? '';
$error = $_GET['error'] ?? '';

// [AI Enhancement] Data for the new profile header strip
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

$lastLoginStmt = $pdo->prepare("SELECT created_at, ip_address FROM login_history WHERE user_id=? AND status='success' ORDER BY id DESC LIMIT 1 OFFSET 1");
$lastLoginStmt->execute([$user_id]);
$lastLogin = $lastLoginStmt->fetch(); // OFFSET 1 skips the current login, showing the previous one

$openTicketCount = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id=? AND status NOT IN ('resolved','closed')");
$openTicketCount->execute([$user_id]);
$openTicketCount = (int)$openTicketCount->fetchColumn();
?>

<!-- Floating Toolbar -->
<div class="toolbar">
  <button id="darkModeToggle">Toggle Dark Mode</button>
  <button id="toggleAll">Expand All</button>
  <div class="badge">
    <button id="alertsButton">System Alerts</button>
    <span id="alertsBadge">0</span>
  </div>
</div>

<div style="max-width:1000px;margin:0 auto;padding:0 12px;">

<?php if ($notice): ?><p style="color:#0a6;background:#e6ffed;padding:10px;border-radius:6px;"><?= h($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p style="color:#b00020;background:#ffe6e6;padding:10px;border-radius:6px;"><?= h($error) ?></p><?php endif; ?>

<!-- Profile header strip -->
<div class="card" style="padding:16px;margin:16px 0;display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <div>
    <h2 style="margin:0;">👋 <?= h($greeting) ?>, <?= h($user['full_name'] ?? $user['email']) ?></h2>
    <div class="small-note">Customer ID: #<?= str_pad((string)$user['id'], 6, '0', STR_PAD_LEFT) ?> &nbsp;·&nbsp; <?= date('l, F j, Y') ?></div>
  </div>
  <div style="text-align:right;font-size:14px;">
    <div>Account: <?= $user['account_status'] === 'paused' ? '⏸️ <b style="color:#c90;">Paused</b>' : '🟢 <b style="color:#0a6;">Active</b>' ?></div>
    <?php if ($lastLogin): ?><div class="small-note">Last login: <?= h($lastLogin['created_at']) ?> from <?= h($lastLogin['ip_address']) ?></div><?php endif; ?>
  </div>
</div>

<!-- Quick Actions -->
<div class="card" style="padding:16px;margin-bottom:16px;">
  <h3 style="margin-top:0;">Quick Actions</h3>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;text-align:center;">
    <a href="/pages/recharge.php" class="btn" style="display:block;">🔋 Recharge</a>
    <a href="/pages/paybill.php" class="btn" style="display:block;">💳 Pay Bill</a>
    <a href="/pages/tickets.php" class="btn" style="display:block;">🎫 Support Ticket<?= $openTicketCount ? " ($openTicketCount)" : '' ?></a>
    <a href="/pages/packages.php" class="btn" style="display:block;">📦 Package Change</a>
    <a href="#referralPanel" class="btn" style="display:block;">🎁 Referral</a>
    <a href="/pages/store.php" class="btn" style="display:block;">🛒 Store</a>
    <a href="/pages/speedtest.php" class="btn" style="display:block;">⚡ Speed Test</a>
  </div>
</div>

<!-- Summary cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin:16px 0;">
  <div class="card" style="padding:14px;">
    <div class="small-note">Current Package</div>
    <div style="font-size:20px;font-weight:bold;"><?= $currentPackage ? h($currentPackage['name']) . ' (' . h($currentPackage['speed_mbps']) . ' Mbps)' : 'No package' ?></div>
    <div>Status: <?= $user['account_status'] === 'paused' ? '⏸️ Paused' : '✅ Active' ?></div>
  </div>
  <div class="card" style="padding:14px;">
    <div class="small-note">Expiry Date</div>
    <div style="font-size:20px;font-weight:bold;"><?= h($user['package_expires_at'] ?? 'N/A') ?></div>
    <div><?= $remainingDays !== null ? ($remainingDays >= 0 ? "$remainingDays day(s) remaining" : 'Expired') : '' ?></div>
  </div>
  <div class="card" style="padding:14px;">
    <div class="small-note">Current Bill</div>
    <div style="font-size:20px;font-weight:bold;">
      <?= $currentBill ? number_format((float)$currentBill['amount'], 2) . ' BDT' : 'No dues' ?>
    </div>
    <?php if ($nextDueBill && $daysUntilDue !== null): ?>
      <div style="color:<?= $daysUntilDue < 0 ? '#b00020' : ($daysUntilDue <= 3 ? '#c90' : 'inherit') ?>;">
        <?= $daysUntilDue < 0 ? abs($daysUntilDue) . ' day(s) overdue' : ($daysUntilDue === 0 ? 'Due today' : "Due in $daysUntilDue day(s)") ?>
        (<?= h($nextDueBill['due_date']) ?>)
      </div>
    <?php endif; ?>
    <?php if ($currentBill): ?><a class="btn small" href="/pages/payment_redirect.php?invoice_id=<?= h($currentBill['id']) ?>">Pay Now</a><?php endif; ?>
  </div>
</div>

<!-- Notifications -->
<button class="collapsible">Notifications <?= $unreadCount ? "($unreadCount new)" : '' ?></button>
<div class="content" id="notifPanel">
  <?php if (empty($notifications)): ?>
    <p>No notifications yet.</p>
  <?php else: ?>
    <ul style="list-style:none;padding:0;">
      <?php foreach ($notifications as $n): ?>
      <li style="padding:8px;border-bottom:1px solid #eee;<?= $n['is_read'] ? 'opacity:0.6;' : '' ?>">
        <b><?= h($n['title']) ?></b><br>
        <span><?= h($n['message']) ?></span><br>
        <span class="small-note"><?= h($n['created_at']) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<!-- Refer a Friend -->
<button class="collapsible">🎁 Refer a Friend</button>
<div class="content" id="referralPanel">
  <p>Share your referral code — you'll earn a reward when they sign up and make their first purchase.</p>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <code style="background:#f4f4f4;padding:8px 14px;border-radius:6px;font-size:18px;font-weight:bold;"><?= h($user['referral_code'] ?? 'N/A') ?></code>
    <input type="text" readonly id="referralLink" value="https://<?= h($_SERVER['HTTP_HOST'] ?? 'yourdomain.com') ?>/mknet/pages/register.php?ref=<?= h($user['referral_code'] ?? '') ?>" style="flex:1;min-width:200px;padding:8px;">
    <button type="button" class="btn small" onclick="navigator.clipboard.writeText(document.getElementById('referralLink').value); this.textContent='Copied!'; setTimeout(()=>this.textContent='Copy Link', 1500);">Copy Link</button>
  </div>
</div>

<!-- Package management -->
<button class="collapsible">Manage Package</button>
<div class="content" id="packagePanel">
  <form method="post" action="package_action.php" style="margin-bottom:10px;">
    <?= csrf_field() ?>
    <select name="package_id" required>
      <option value="">Select a package...</option>
      <?php foreach ($allPackages as $p): ?>
        <option value="<?= h($p['id']) ?>" <?= (int)$p['id'] === (int)$user['package_id'] ? 'selected disabled' : '' ?>>
          <?= h($p['name']) ?> — <?= h($p['speed_mbps']) ?> Mbps — <?= h($p['price_bdt']) ?> BDT/mo
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" name="change_package" class="btn primary" onclick="return confirm('Change your package? This will generate a new invoice.')">Upgrade / Downgrade</button>
  </form>

  <form method="post" action="package_action.php" style="display:inline;">
    <?= csrf_field() ?>
    <?php if ($user['account_status'] === 'paused'): ?>
      <button type="submit" name="resume_connection" class="btn primary">▶️ Resume Connection</button>
    <?php else: ?>
      <button type="submit" name="pause_connection" class="btn" onclick="return confirm('Pause your connection?')">⏸️ Pause Connection</button>
    <?php endif; ?>
  </form>
</div>

<!-- Addons -->
<button class="collapsible">Active Addons</button>
<div class="content" id="addonsPanel">
  <?php if (empty($activeAddons)): ?>
    <p>No active addons.</p>
  <?php else: ?>
    <ul>
      <?php foreach ($activeAddons as $a): ?>
        <li><?= h($a['name']) ?> — <?= h($a['price']) ?> BDT (<?= h($a['type']) ?>)</li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <?php if (!empty($availableAddons)): ?>
    <p class="small-note">Browse more addons on the <a href="packages.php">Packages page</a>.</p>
  <?php endif; ?>
</div>

<!-- Usage History -->
<button class="collapsible">Usage History</button>
<div class="content" id="usagePanel">
  <?php if (empty($rows)): ?>
    <p>No usage data recorded yet.</p>
  <?php else: ?>
  <canvas id="usageChart"></canvas>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
  new Chart(document.getElementById('usageChart'),{
    type:'line',
    data:{
      labels: <?= json_encode(array_column($rows,'date')) ?>,
      datasets:[
        {label:'Download (GB)',data:<?= json_encode(array_column($rows,'download_gb')) ?>,borderColor:'blue',fill:false},
        {label:'Upload (GB)',data:<?= json_encode(array_column($rows,'upload_gb')) ?>,borderColor:'green',fill:false}
      ]
    }
  });
  </script>
  <?php endif; ?>
</div>

<!-- Payment History -->
<button class="collapsible">Payment History</button>
<div class="content" id="billingPanel">
  <table style="width:100%;">
    <tr><th>Date</th><th>Amount</th><th>Status</th><th>Invoice</th></tr>
    <?php if (empty($paymentHistory)): ?>
      <tr><td colspan="4">No billing history yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($paymentHistory as $b): ?>
    <tr>
      <td><?= h($b['created_at']) ?></td>
      <td><?= h($b['amount']) ?> BDT</td>
      <td><?= h($b['status']) ?></td>
      <td><a href="/pages/invoice_view.php?id=<?= (int)$b['id'] ?>" target="_blank">View / Download</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<!-- Account -->
<button class="collapsible">Account Settings</button>
<div class="content" id="accountPanel">
  <p><a class="btn" href="profile.php">👤 Update Profile</a></p>
  <p><a class="btn" href="account_security.php">🔒 Change Password & Security (2FA, Sessions, Login History)</a></p>
</div>

<button class="collapsible">Support</button>
<div class="content" id="supportPanel">
  <p><a href="/pages/tickets.php">🎫 My Support Tickets</a></p>
  <p><a href="/pages/contact.php">Open Support Ticket (general contact form)</a></p>
  <?php
  $supportSettings = $pdo->query("SELECT support_phone, messenger_link, telegram_link FROM settings WHERE id=1")->fetch();
  $waNumber = preg_replace('/[^0-9]/', '', (string)($supportSettings['support_phone'] ?? ''));
  ?>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
    <?php if ($waNumber): ?><a class="btn small" href="https://wa.me/<?= h($waNumber) ?>" target="_blank" rel="noopener">💬 WhatsApp</a><?php endif; ?>
    <?php if (!empty($supportSettings['messenger_link'])): ?><a class="btn small" href="<?= h($supportSettings['messenger_link']) ?>" target="_blank" rel="noopener">📘 Messenger</a><?php endif; ?>
    <?php if (!empty($supportSettings['telegram_link'])): ?><a class="btn small" href="<?= h($supportSettings['telegram_link']) ?>" target="_blank" rel="noopener">✈️ Telegram</a><?php endif; ?>
  </div>
</div>

<button class="collapsible">System Alerts</button>
<div class="content" id="alertsPanel">
  <ul>
    <li>Last downtime: <?= h($latestDowntime) ?> sec</li>
    <li>Problems reported: <?= h($latestProblems) ?> times</li>
  </ul>
</div>

</div>

<!-- Scripts -->
<script>
const darkBtn = document.getElementById('darkModeToggle');
darkBtn.addEventListener('click', function(){
  document.body.classList.toggle('dark-mode');
  let theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
  localStorage.setItem('theme', theme);
  fetch("/api/save_theme.php", {
    method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({theme: theme})
  }).catch(function(err){ console.error('Theme save failed:', err); });
});
<?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark'): ?>
  document.body.classList.add('dark-mode');
<?php endif; ?>
if (localStorage.getItem('theme') === 'dark') { document.body.classList.add('dark-mode'); }

var coll = document.getElementsByClassName("collapsible");
for (var i = 0; i < coll.length; i++) {
  coll[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var content = this.nextElementSibling;
    if (content.style.display === "block") {
      content.style.display = "none"; localStorage.removeItem("openPanel");
    } else {
      content.style.display = "block"; localStorage.setItem("openPanel", content.id);
    }
  });
}
var savedPanel = localStorage.getItem("openPanel");
if (savedPanel) {
  var panel = document.getElementById(savedPanel);
  if (panel) { panel.style.display = "block"; panel.previousElementSibling.classList.add("active"); }
}

document.getElementById("toggleAll").addEventListener("click", function(){
  var coll = document.getElementsByClassName("collapsible");
  var contents = document.getElementsByClassName("content");
  var expand = this.textContent === "Expand All";
  for (var i = 0; i < coll.length; i++) {
    if (expand) { coll[i].classList.add("active"); contents[i].style.display = "block"; }
    else { coll[i].classList.remove("active"); contents[i].style.display = "none"; }
  }
  this.textContent = expand ? "Collapse All" : "Expand All";
});

var problemsCount = <?= (int)$latestProblems ?>;
var badge = document.getElementById("alertsBadge");
badge.textContent = problemsCount;
function refreshBadge(){
  fetch("/api/alerts_api.php").then(res => res.json()).then(data => {
    let newCount = data.problems_count;
    if (newCount > problemsCount) { badge.classList.add("flash"); setTimeout(() => badge.classList.remove("flash"), 1000); }
    problemsCount = newCount; badge.textContent = newCount;
  }).catch(err => console.error("Badge update error:", err));
}
setInterval(refreshBadge, 30000);
refreshBadge();
document.getElementById("alertsButton").addEventListener("click", function(){
  document.getElementById("alertsPanel").scrollIntoView({behavior:"smooth"});
});
</script>
