<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Interactive Coverage Map & Expansion';
include __DIR__ . '/../includes/header.php';

try {
  $rows = [];
  if (!empty($_GET['district']) || !empty($_GET['upazila'])) {
    $district = trim($_GET['district'] ?? '');
    $upazila = trim($_GET['upazila'] ?? '');
    $stmt = $pdo->prepare("SELECT * FROM coverage_areas WHERE district LIKE ? AND upazila LIKE ? ORDER BY district");
    $stmt->execute(["%$district%", "%$upazila%"]);
    $rows = $stmt->fetchAll();
  } else {
    $rows = $pdo->query("SELECT * FROM coverage_areas ORDER BY district")->fetchAll();
  }
} catch (Exception $e) {
  $rows = [];
}
?>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<div class="mk-container" style="padding-top: 30px; padding-bottom: 60px;">

  <!-- Hero Header -->
  <div style="background: linear-gradient(135deg, rgba(30,41,59,0.9), rgba(15,23,42,0.95)), url("/assets/images/coverage.jpg") center/cover; padding: 40px 30px; border-radius: 16px; border: 1px solid rgba(99,102,241,0.3); color: white; text-align: center; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <span style="display:inline-block; padding:4px 12px; background:rgba(56,189,248,0.15); border:1px solid #38bdf8; border-radius:20px; color:#38bdf8; font-size:0.85em; font-weight:600; text-transform:uppercase; margin-bottom:12px;">🗺️ OpenStreetMap Coverage Network</span>
    <h1 style="font-size: 2.4em; font-weight: 800; margin-bottom: 10px;">Interactive Fiber Coverage Map</h1>
    <p style="color: #94a3b8; max-width: 650px; margin: 0 auto 20px;">Search your district, pin your exact location on the map, and check real-time fiber optic signal availability.</p>
  </div>

  <!-- OpenStreetMap Leaflet Canvas -->
  <div style="background: #1e293b; border-radius: 16px; padding: 15px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.4);">
    <div id="mknetCoverageMap" style="height: 450px; width: 100%; border-radius: 12px; z-index: 1;"></div>
  </div>

  <!-- Search & Filter Card -->
  <div style="background: #1e293b; border-radius: 16px; padding: 25px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px; max-width: 800px; margin-left: auto; margin-right: auto;">
    <h3 style="margin-top:0; color:#f8fafc; font-size:1.2em; margin-bottom:15px;">🔎 Search Coverage by District & Thana</h3>
    <form method="get" action="" style="display: flex; gap: 15px; flex-wrap: wrap;">
      <div style="flex: 1; min-width: 200px;">
        <label style="color:#94a3b8; font-size:0.85em; display:block; margin-bottom:5px;">District</label>
        <input class="input" type="text" name="district" placeholder="e.g. Dhaka, Bogra, Sylhet" value="<?= h($_GET['district'] ?? '') ?>" style="width:100%; padding:10px 14px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff;">
      </div>
      <div style="flex: 1; min-width: 200px;">
        <label style="color:#94a3b8; font-size:0.85em; display:block; margin-bottom:5px;">Upazila / Thana</label>
        <input class="input" type="text" name="upazila" placeholder="e.g. Gulshan, Sadar" value="<?= h($_GET['upazila'] ?? '') ?>" style="width:100%; padding:10px 14px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff;">
      </div>
      <div style="display:flex; align-items:flex-end;">
        <button class="btn" style="background:linear-gradient(135deg, #6366f1, #4f46e5); color:#fff; padding:10px 24px; border:none; border-radius:8px; font-weight:600; cursor:pointer;">Filter Locations</button>
      </div>
    </form>
  </div>

  <!-- Results Table -->
  <div style="background: #1e293b; border-radius: 16px; padding: 20px; border: 1px solid rgba(255,255,255,0.1); overflow-x: auto;">
    <h3 style="color:#f8fafc; margin-top:0; margin-bottom:15px;">Network Status Directory</h3>
    <table style="width:100%; border-collapse:collapse; text-align:left; color:#f8fafc; font-size:0.9em;">
      <thead>
        <tr style="background:#0f172a; border-bottom:2px solid #334155;">
          <th style="padding:12px;">District</th>
          <th style="padding:12px;">Upazila / Thana</th>
          <th style="padding:12px;">Coverage Status</th>
          <th style="padding:12px;">Active Subscribers</th>
          <th style="padding:12px;">Optical Sectors</th>
          <th style="padding:12px;">Expansion Note</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($rows)): ?>
          <?php foreach ($rows as $r): ?>
            <tr style="border-bottom:1px solid #334155;">
              <td style="padding:12px; font-weight:600;"><?= h($r['district']) ?></td>
              <td style="padding:12px;"><?= h($r['upazila']) ?></td>
              <td style="padding:12px;">
                <?php if (strtolower($r['status'] ?? '') === 'available'): ?>
                  <span style="display:inline-block; padding:3px 10px; background:rgba(16,185,129,0.15); border:1px solid #10b981; border-radius:12px; color:#10b981; font-weight:600; font-size:0.85em;">Live 🟢</span>
                <?php else: ?>
                  <span style="display:inline-block; padding:3px 10px; background:rgba(239,68,68,0.15); border:1px solid #ef4444; border-radius:12px; color:#ef4444; font-weight:600; font-size:0.85em;">Expansion 🟠</span>
                <?php endif; ?>
              </td>
              <td style="padding:12px;"><?= (int)($r['customers'] ?? 0) ?></td>
              <td style="padding:12px;"><?= (int)($r['sectors'] ?? 1) ?> Sectors</td>
              <td style="padding:12px; color:#94a3b8;"><?= h($r['present_status'] ?? 'Gigabit Ready') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" style="padding:20px; text-align:center; color:#94a3b8;">No coverage records found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  // Initialize Leaflet Map centered on Bangladesh
  var map = L.map('mknetCoverageMap').setView([23.8103, 90.4125], 7);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors | MK Net Fiber Network'
  }).addTo(map);

  // Key Coverage Hub Markers
  var locations = [
    { name: "Dhaka HQ POP (Gulshan 1)", lat: 23.7788, lng: 90.4172, status: "Active 🟢", speed: "100 Gbps" },
    { name: "Dhaka North POP (Uttara Sector 7)", lat: 23.8759, lng: 90.3795, status: "Active 🟢", speed: "40 Gbps" },
    { name: "Bogra Central Hub (Sadar)", lat: 24.8481, lng: 89.3730, status: "Active 🟢", speed: "20 Gbps" },
    { name: "Chittagong Port POP (Agrabad)", lat: 22.3240, lng: 91.8130, status: "Active 🟢", speed: "40 Gbps" },
    { name: "Sylhet Metro POP (Zindabazar)", lat: 24.8949, lng: 91.8687, status: "Active 🟢", speed: "20 Gbps" },
    { name: "Rajshahi North POP (Shaheb Bazar)", lat: 24.3745, lng: 88.6042, status: "Active 🟢", speed: "20 Gbps" }
  ];

  locations.forEach(function(loc) {
    var marker = L.marker([loc.lat, loc.lng]).addTo(map);
    marker.bindPopup("<div style='font-family:sans-serif; color:#0f172a;'><b>" + loc.name + "</b><br>Status: " + loc.status + "<br>Capacity: " + loc.speed + "</div>");
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
