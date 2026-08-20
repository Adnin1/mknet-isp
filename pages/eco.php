<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Eco-Friendly Tracker';
include __DIR__ . '/../includes/header.php';

$rows = $pdo->query("SELECT month, energy_saved_kwh, carbon_reduced_kg FROM eco_stats ORDER BY id DESC")->fetchAll();
?>

<h2>Eco-Friendly Internet Tracker</h2>
<div class="grid">
<?php if ($rows): ?>
  <?php foreach ($rows as $r): ?>
    <div class="card">
      <h3><?=h($r['month'])?></h3>
      <p><b>Energy Saved:</b> <?=h($r['energy_saved_kwh'])?> kWh</p>
      <p><b>Carbon Reduced:</b> <?=h($r['carbon_reduced_kg'])?> kg</p>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card">No eco data found.</div>
<?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

