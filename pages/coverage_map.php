<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Coverage Map — MK Net';
include __DIR__ . '/../includes/header.php';

$rows = $pdo->query(
    "SELECT district, upazila, status, latitude, longitude, customers, homes, offices, present_status, updates
     FROM coverage_areas WHERE latitude IS NOT NULL AND longitude IS NOT NULL"
)->fetchAll();
?>
<main class="container">
<h2>Coverage Map</h2>
<p>Green = live &amp; connected, orange = planned/coming soon. Use the search box to check coverage near your address.</p>

<div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;">
  <input type="text" id="addressSearch" placeholder="Enter your address or area..." style="flex:1;min-width:220px;padding:10px;">
  <button type="button" class="btn primary" onclick="checkMyCoverage()">📍 Check My Coverage</button>
</div>
<div id="coverageResult" style="display:none;margin-bottom:10px;"></div>

<div id="map" style="height:520px;width:100%;border-radius:8px;"></div>

<div style="display:flex;gap:16px;margin-top:10px;flex-wrap:wrap;font-size:14px;">
  <span>🟢 Covered</span>
  <span>🟠 Planned</span>
  <span>🔵 Coming Soon</span>
  <span>⚪ Other</span>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
var map = L.map('map').setView([23.7, 90.4], 7); // Center Bangladesh
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors',
  maxZoom: 18,
}).addTo(map);

var statusColor = {
  'covered': '#0a6', 'planned': '#e67e00', 'coming soon': '#0668c9'
};
var coverageAreas = [];

<?php foreach ($rows as $r):
  $statusKey = strtolower(trim((string)$r['status']));
  $color = $statusKey === 'covered' ? '#0a6' : ($statusKey === 'planned' ? '#e67e00' : ($statusKey === 'coming soon' ? '#0668c9' : '#888'));
?>
(function(){
  var lat = <?= (float)$r['latitude'] ?>, lng = <?= (float)$r['longitude'] ?>;
  coverageAreas.push({lat: lat, lng: lng, district: <?= json_encode($r['district']) ?>, status: <?= json_encode($r['status']) ?>});

  var marker = L.circleMarker([lat, lng], {
    radius: 9, color: <?= json_encode($color) ?>, fillColor: <?= json_encode($color) ?>, fillOpacity: 0.8, weight: 2
  }).addTo(map);

  <?php if ($statusKey === 'covered'): ?>
  // Approximate service radius for a live POP - purely illustrative, not a
  // precise engineering coverage boundary (that would need real fiber/tower
  // reach data your NOC team maintains, not something this map can infer).
  L.circle([lat, lng], { radius: 1500, color: <?= json_encode($color) ?>, fillOpacity: 0.06, weight: 1 }).addTo(map);
  <?php endif; ?>

  marker.bindPopup(
    "<b><?= h($r['district']) ?> (<?= h($r['upazila']) ?>)</b><br>" +
    "Status: <?= h($r['status'] ?: 'Unknown') ?><br>" +
    <?php if ($r['customers']): ?>"Customers connected: <?= (int)$r['customers'] ?><br>" +<?php endif; ?>
    <?php if ($r['present_status']): ?>"<?= h($r['present_status']) ?><br>" +<?php endif; ?>
    <?php if ($r['updates']): ?>"<i><?= h($r['updates']) ?></i>" +<?php endif; ?>
    ""
  );
})();
<?php endforeach; ?>

function checkMyCoverage() {
  var query = document.getElementById('addressSearch').value.trim();
  var resultBox = document.getElementById('coverageResult');
  if (!query) return;

  resultBox.style.display = 'block';
  resultBox.innerHTML = '<p>Searching…</p>';

  // Free geocoding via OpenStreetMap's Nominatim - no API key required.
  fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=bd&q=' + encodeURIComponent(query))
    .then(function(r) { return r.json(); })
    .then(function(results) {
      if (!results.length) {
        resultBox.innerHTML = '<p style="color:#b00020;">Could not find that address. Try a nearby landmark or area name.</p>';
        return;
      }
      var lat = parseFloat(results[0].lat), lng = parseFloat(results[0].lon);
      map.setView([lat, lng], 12);
      L.marker([lat, lng]).addTo(map).bindPopup('📍 ' + query).openPopup();

      // Find nearest coverage area (simple haversine distance)
      var nearest = null, nearestDist = Infinity;
      coverageAreas.forEach(function(a) {
        var d = haversine(lat, lng, a.lat, a.lng);
        if (d < nearestDist) { nearestDist = d; nearest = a; }
      });

      if (nearest && nearestDist <= 5) {
        resultBox.innerHTML = '<p style="color:#0a6;">✅ Good news — ' + nearest.district + ' coverage area is about ' + nearestDist.toFixed(1) + ' km away (' + nearest.status + '). <a href="/pages/availability.php">Check exact availability →</a></p>';
      } else if (nearest) {
        resultBox.innerHTML = '<p style="color:#c90;">The nearest coverage area (' + nearest.district + ') is ' + nearestDist.toFixed(1) + ' km away. We may not be live in your exact area yet — <a href="/pages/contact.php">contact us</a> to request coverage.</p>';
      } else {
        resultBox.innerHTML = '<p>No coverage data available yet for this region.</p>';
      }
    })
    .catch(function() {
      resultBox.innerHTML = '<p style="color:#b00020;">Search failed. Please try again.</p>';
    });
}

function haversine(lat1, lon1, lat2, lon2) {
  var R = 6371;
  var dLat = (lat2 - lat1) * Math.PI / 180;
  var dLon = (lon2 - lon1) * Math.PI / 180;
  var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
          Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
          Math.sin(dLon/2) * Math.sin(dLon/2);
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}
</script>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
