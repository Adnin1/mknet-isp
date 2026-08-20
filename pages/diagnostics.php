<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Internet Diagnostics';
include __DIR__ . '/../includes/header.php';

// Run simple checks
$results = [];

// Check database connection
try {
  $pdo->query("SELECT 1");
  $results['Database'] = "✅ Database connected";
} catch (Exception $e) {
  $results['Database'] = "❌ Database connection failed";
}

// Check DNS resolution (try to resolve google.com)
$dnsCheck = gethostbyname("google.com");
if ($dnsCheck && $dnsCheck !== "google.com") {
  $results['DNS'] = "✅ DNS resolution working";
} else {
  $results['DNS'] = "❌ DNS resolution failed";
}

// Check internet reachability (ping 8.8.8.8)
$pingResult = null;
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
  $pingResult = exec("ping -n 1 8.8.8.8");
} else {
  $pingResult = exec("ping -c 1 8.8.8.8");
}
if (strpos($pingResult, "TTL") !== false) {
  $results['Internet'] = "✅ Internet reachable";
} else {
  $results['Internet'] = "❌ Internet not reachable";
}
?>

<h2>Internet Diagnostics</h2>
<div class="grid">
  <?php foreach ($results as $test => $status): ?>
    <div class="card">
      <h3><?=h($test)?></h3>
      <p><?=$status?></p>
    </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
