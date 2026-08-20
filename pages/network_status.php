<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Network Status & POP Uptime';
include __DIR__ . '/../includes/header.php';

try {
  $incidents = $pdo->query("SELECT * FROM network_incidents ORDER BY id ASC")->fetchAll();
} catch (Throwable $e) {
  $incidents = [];
}
?>

<div class="mk-container" style="padding-top: 30px; padding-bottom: 60px;">

  <!-- Hero Header -->
  <div style="background: linear-gradient(135deg, rgba(30,41,59,0.9), rgba(15,23,42,0.95)); padding: 40px 30px; border-radius: 16px; border: 1px solid rgba(16,185,129,0.3); color: white; text-align: center; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <span style="display:inline-block; padding:4px 12px; background:rgba(16,185,129,0.15); border:1px solid #10b981; border-radius:20px; color:#10b981; font-size:0.85em; font-weight:600; text-transform:uppercase; margin-bottom:12px;">🟢 All Core Systems Operational</span>
    <h1 style="font-size: 2.4em; font-weight: 800; margin-bottom: 10px;">Network Transparency & Live Uptime</h1>
    <p style="color: #94a3b8; max-width: 650px; margin: 0 auto 20px;">Real-time POP status, fiber cut incident tracker, and 99.99% guaranteed SLA uptime metrics.</p>
  </div>

  <!-- Real-Time Metrics Row -->
  <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px;">
    <div style="flex:1; min-width:220px; background:#1e293b; padding:20px; border-radius:14px; border:1px solid rgba(255,255,255,0.08); text-align:center;">
      <div style="font-size:2.2em; font-weight:800; color:#10b981;">99.99%</div>
      <div style="color:#94a3b8; font-size:0.9em; margin-top:4px;">Guaranteed Uptime SLA</div>
    </div>
    <div style="flex:1; min-width:220px; background:#1e293b; padding:20px; border-radius:14px; border:1px solid rgba(255,255,255,0.08); text-align:center;">
      <div style="font-size:2.2em; font-weight:800; color:#38bdf8;">200+ Gbps</div>
      <div style="color:#94a3b8; font-size:0.9em; margin-top:4px;">BDIX & International Capacity</div>
    </div>
    <div style="flex:1; min-width:220px; background:#1e293b; padding:20px; border-radius:14px; border:1px solid rgba(255,255,255,0.08); text-align:center;">
      <div style="font-size:2.2em; font-weight:800; color:#fbbf24;">&lt; 2 ms</div>
      <div style="color:#94a3b8; font-size:0.9em; margin-top:4px;">BDIX Local Latency</div>
    </div>
    <div style="flex:1; min-width:220px; background:#1e293b; padding:20px; border-radius:14px; border:1px solid rgba(255,255,255,0.08); text-align:center;">
      <div style="font-size:2.2em; font-weight:800; color:#6366f1;">24/7/365</div>
      <div style="color:#94a3b8; font-size:0.9em; margin-top:4px;">NOC Line Monitoring</div>
    </div>
  </div>

  <!-- POP Operational Status Directory -->
  <div style="background: #1e293b; border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px;">
    <h3 style="color:#f8fafc; margin-top:0; margin-bottom:20px;">📡 Regional POP Hub Operational Status</h3>
    
    <div style="display:flex; flex-direction:column; gap:15px;">
      <?php if (!empty($incidents)): ?>
        <?php foreach ($incidents as $inc): ?>
          <div style="display:flex; justify-content:space-between; align-items:center; background:#0f172a; padding:16px 20px; border-radius:12px; border:1px solid #334155; flex-wrap:wrap; gap:10px;">
            <div>
              <h4 style="margin:0; color:#f8fafc; font-size:1.1em;"><?= h($inc['pop_name']) ?></h4>
              <span style="color:#94a3b8; font-size:0.85em;">Coverage Region: <?= h($inc['region']) ?></span>
            </div>
            <div style="text-align:right;">
              <?php if ($inc['status'] === 'operational'): ?>
                <span style="padding:4px 12px; background:rgba(16,185,129,0.15); border:1px solid #10b981; border-radius:14px; color:#10b981; font-weight:600; font-size:0.85em;">Operational 🟢</span>
              <?php else: ?>
                <span style="padding:4px 12px; background:rgba(239,68,68,0.15); border:1px solid #ef4444; border-radius:14px; color:#ef4444; font-weight:600; font-size:0.85em;">Maintenance 🟠</span>
              <?php endif; ?>
              <div style="color:#64748b; font-size:0.8em; margin-top:4px;"><?= h($inc['estimated_recovery']) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
