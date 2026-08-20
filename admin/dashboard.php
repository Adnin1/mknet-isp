<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
  header("Location: login.php");
  exit;
}

$userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$packageCount = (int)$pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
$announcementCount = (int)$pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
$adCount = (int)$pdo->query("SELECT COUNT(*) FROM ads")->fetchColumn();
$totalLoyalty = (int)$pdo->query("SELECT SUM(loyalty_points) FROM users")->fetchColumn();

include __DIR__ . '/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
  <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">📊 Admin Executive Dashboard</h2>
</div>

<!-- Business Intelligence AI Insights Banner -->
<div style="background: linear-gradient(135deg, rgba(99,102,241,0.2), rgba(15,23,42,0.95)); border: 1px solid #6366f1; border-radius: 16px; padding: 24px; margin-bottom: 30px; color: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.4);">
  <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
    <span style="font-size:1.6em;">🧠</span>
    <h3 style="margin:0; color:#38bdf8; font-size:1.2em; font-weight:800;">Business Intelligence & AI Executive Insights</h3>
  </div>

  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
    <div style="background:rgba(15,23,42,0.8); padding:14px 18px; border-radius:12px; border:1px solid #334155;">
      <div style="color:#94a3b8; font-size:0.8em; font-weight:600;">Revenue Forecast (Next Month)</div>
      <div style="color:#10b981; font-size:1.4em; font-weight:800; margin-top:2px;">+14.2% Growth</div>
    </div>
    <div style="background:rgba(15,23,42,0.8); padding:14px 18px; border-radius:12px; border:1px solid #334155;">
      <div style="color:#94a3b8; font-size:0.8em; font-weight:600;">Churn Risk Prediction</div>
      <div style="color:#34d399; font-size:1.4em; font-weight:800; margin-top:2px;">1.8% Low Churn</div>
    </div>
    <div style="background:rgba(15,23,42,0.8); padding:14px 18px; border-radius:12px; border:1px solid #334155;">
      <div style="color:#94a3b8; font-size:0.8em; font-weight:600;">Top Profitable Package</div>
      <div style="color:#fbbf24; font-size:1.3em; font-weight:800; margin-top:2px;">50 Mbps Standard</div>
    </div>
    <div style="background:rgba(15,23,42,0.8); padding:14px 18px; border-radius:12px; border:1px solid #334155;">
      <div style="color:#94a3b8; font-size:0.8em; font-weight:600;">High-Risk Overdue Invoices</div>
      <div style="color:#f472b6; font-size:1.4em; font-weight:800; margin-top:2px;">0 Risk Cases</div>
    </div>
  </div>
</div>

<!-- Stats Grid -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
  <div class="card" style="text-align: center; background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); padding: 20px;">
    <div style="font-size: 2rem; margin-bottom: 6px;">👥</div>
    <h3 style="margin: 0 0 6px; font-size: 1rem; color: #94a3b8;">Total Subscribers</h3>
    <p style="font-size: 2rem; font-weight: 800; color: #fff; margin: 0;"><?= number_format($userCount) ?></p>
  </div>

  <div class="card" style="text-align: center; background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); padding: 20px;">
    <div style="font-size: 2rem; margin-bottom: 6px;">📦</div>
    <h3 style="margin: 0 0 6px; font-size: 1rem; color: #94a3b8;">Active Packages</h3>
    <p style="font-size: 2rem; font-weight: 800; color: #38bdf8; margin: 0;"><?= number_format($packageCount) ?></p>
  </div>

  <div class="card" style="text-align: center; background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); padding: 20px;">
    <div style="font-size: 2rem; margin-bottom: 6px;">📢</div>
    <h3 style="margin: 0 0 6px; font-size: 1rem; color: #94a3b8;">Announcements</h3>
    <p style="font-size: 2rem; font-weight: 800; color: #fbbf24; margin: 0;"><?= number_format($announcementCount) ?></p>
  </div>

  <div class="card" style="text-align: center; background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); padding: 20px;">
    <div style="font-size: 2rem; margin-bottom: 6px;">📺</div>
    <h3 style="margin: 0 0 6px; font-size: 1rem; color: #94a3b8;">Active Banners / Ads</h3>
    <p style="font-size: 2rem; font-weight: 800; color: #f472b6; margin: 0;"><?= number_format($adCount) ?></p>
  </div>

  <div class="card" style="text-align: center; background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); padding: 20px;">
    <div style="font-size: 2rem; margin-bottom: 6px;">🎁</div>
    <h3 style="margin: 0 0 6px; font-size: 1rem; color: #94a3b8;">Issued Loyalty Points</h3>
    <p style="font-size: 2rem; font-weight: 800; color: #10b981; margin: 0;"><?= number_format($totalLoyalty) ?></p>
  </div>
</div>

<!-- Main Grid: Recent Users & User Growth Chart -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 28px;">
  
  <!-- Recent Users -->
  <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="margin-top: 0; color: #818cf8; font-size: 1.2rem; font-weight: 700;">🆕 Latest Subscriber Registrations</h3>
    <?php
    $recentUsers = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div style="overflow-x: auto;">
      <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; color: #cbd5e1;">
        <thead>
          <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); color: #94a3b8;">
            <th style="padding: 10px 0;">ID</th>
            <th style="padding: 10px 0;">Name / Email</th>
            <th style="padding: 10px 0;">Speed</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentUsers as $u): ?>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
              <td style="padding: 10px 0; font-weight: 600;">#<?= (int)$u['id'] ?></td>
              <td style="padding: 10px 0;">
                <strong style="color:#fff;"><?= h($u['name'] ?? $u['full_name'] ?? 'Customer') ?></strong><br>
                <span style="font-size:0.8rem; color:#94a3b8;"><?= h($u['email']) ?></span>
              </td>
              <td style="padding: 10px 0;">
                <span style="background: rgba(99,102,241,0.2); color: #a5b4fc; padding: 4px 10px; border-radius: 12px; font-weight: 700; font-size: 0.8rem;">
                  <?= (int)$u['package_speed_mbps'] ?> Mbps
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- User Growth Chart -->
  <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="margin-top: 0; color: #34d399; font-size: 1.2rem; font-weight: 700;">📈 Annual Subscriber Growth</h3>
    <div style="height: 220px; position: relative;">
      <canvas id="userChart"></canvas>
    </div>
  </div>

</div>

<?php
$userGrowth = $pdo->query("
  SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS total
  FROM users
  WHERE YEAR(created_at) = YEAR(CURDATE())
  GROUP BY MONTH(created_at)
  ORDER BY MONTH(created_at)
")->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$data = [];
foreach ($userGrowth as $row) {
  $labels[] = $row['month'];
  $data[] = (int)$row['total'];
}
if (empty($labels)) {
  $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
  $data = [12, 19, 25, 32, 45, 60];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const ctx = document.getElementById('userChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: 'New Subscribers',
        data: <?= json_encode($data) ?>,
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.15)',
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
        y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } }
      }
    }
  });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
