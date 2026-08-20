<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Speed & Stability Analyzer';
include __DIR__ . '/../includes/header.php';

$results = [];
$recommendation = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Simulate test values (replace with real checks later)
  $latency = rand(20, 150);   // ms
  $jitter  = rand(5, 50);     // ms
  $loss    = rand(0, 5);      // %

  $results = [
    'Latency' => $latency . " ms",
    'Jitter'  => $jitter . " ms",
    'Packet Loss' => $loss . "%"
  ];

  // Recommendations
  if ($latency > 100 || $jitter > 30 || $loss > 2) {
    $recommendation = "⚠️ Your connection shows instability. Consider upgrading to Turbo package for smoother gaming/streaming.";
  } else {
    $recommendation = "✅ Your connection is stable. Current package is sufficient.";
  }
}
?>

<h2>Speed & Stability Analyzer</h2>
<form method="post">
  <button class="btn" type="submit">Run Test</button>
</form>

<?php if ($results): ?>
  <div class="grid">
    <?php foreach ($results as $metric => $value): ?>
      <div class="card">
        <h3><?=h($metric)?></h3>
        <p><?=$value?></p>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="card" style="background:#222;color:#fff">
    <h3>Recommendation</h3>
    <p><?=$recommendation?></p>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
