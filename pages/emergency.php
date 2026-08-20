<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Emergency Mode';
include __DIR__ . '/../includes/header.php';

$rows = $pdo->query("SELECT area, active, speed, services FROM emergency_status")->fetchAll();
?>

<h2>Emergency Internet Mode</h2>
<div class="grid">
<?php foreach ($rows as $r): ?>
  <div class="card">
    <h3><?= h($r['area']) ?></h3>
    <p><b>Status:</b> <?= $r['active'] ? 'Active' : 'Inactive' ?></p>
    <p><b>Speed:</b> <?= h($r['speed']) ?></p>
    <p><b>Services Allowed:</b> <?= h($r['services']) ?></p>
  </div>
<?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
