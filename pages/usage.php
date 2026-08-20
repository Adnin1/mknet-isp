<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
$pageTitle = 'My Usage Insights — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();

// [Fixed] This page used to show EVERY customer's usage rows to EVERY
// visitor (a privacy bug) and repeated the same handful of accounts over
// and over because nothing filtered by the logged-in user. It now shows
// only your own usage, like the rest of Self Care.
if (!SessionManager::touch($pdo)) {
    header("Location: /pages/login.php?notice=" . urlencode('Please log in to view your usage insights.'));
    exit;
}
$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT email FROM users WHERE id=?");
$stmt->execute([$userId]);
$myEmail = (string)$stmt->fetchColumn();

$rows = [];
try {
    $stmt = $pdo->prepare("SELECT user_email, month, data_used_gb, peak_hours, devices FROM usage_stats WHERE user_email = ? ORDER BY month ASC");
    $stmt->execute([$myEmail]);
    $rows = $stmt->fetchAll();
} catch (Throwable $e) { $rows = []; }

$months = array_column($rows, 'month');
$dataUsed = array_column($rows, 'data_used_gb');

include __DIR__ . '/../includes/header.php';
?>
<h2>My Usage Insights</h2>
<p class="small-note">Personalized to your account (<?= h($myEmail) ?>) only.</p>

<?php if (empty($rows)): ?>
  <div class="card" style="padding:20px;max-width:520px;">
    <p>No usage data has been recorded for your account yet. Check back after your first billing cycle, or see the "Usage History" panel on your <a href="/pages/dashboard.php">Dashboard</a>.</p>
  </div>
<?php else: ?>
<div style="max-width:800px;margin:auto;">
  <canvas id="usageChart"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('usageChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [{
      label: 'Data Used (GB)',
      data: <?= json_encode($dataUsed) ?>,
      backgroundColor: 'rgba(46,125,255,0.7)'
    }]
  },
  options: { responsive: true, plugins: { title: { display: true, text: 'Your Monthly Data Usage' } } }
});
</script>

<div class="grid">
  <?php foreach ($rows as $r): ?>
    <div class="card">
      <h3><?= h($r['month']) ?></h3>
      <p><b>Data Used:</b> <?= h($r['data_used_gb']) ?> GB</p>
      <p><b>Peak Hours:</b> <?= h($r['peak_hours']) ?></p>
      <p><b>Devices:</b> <?= h($r['devices']) ?></p>
      <p><b>Suggestion:</b> <?= ((float)$r['data_used_gb'] > 100) ? 'Upgrade to Premium' : 'Current package sufficient' ?></p>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
