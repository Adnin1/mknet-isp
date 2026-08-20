<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'MK Net ISP — High Speed Broadband Internet';

// Admin-controlled homepage background falling back to site default
try {
    $bgRow = $pdo->prepare("SELECT image_path FROM site_content WHERE content_key='homepage_background'");
    $bgRow->execute();
    $heroBackground = $bgRow->fetchColumn() ?: '';
} catch (Exception $ex) {
    $heroBackground = '';
}

try {
    $slides = $pdo->query("SELECT * FROM carousel_slides WHERE active=1 ORDER BY sort_order")->fetchAll();
} catch (Exception $ex) {
    $slides = [];
}

try {
    $featuredPkgs = $pdo->query("SELECT * FROM packages WHERE visible=1 ORDER BY speed_mbps ASC LIMIT 3")->fetchAll();
} catch (Exception $ex) {
    $featuredPkgs = [];
}

include __DIR__ . '/includes/header.php';
?>

<!-- 3D Interactive Futuristic Hero Section -->
<div class="hero-3d-wrapper" style="position: relative; border-radius: 24px; overflow: hidden; margin-bottom: 40px; background: linear-gradient(135deg, #090d16 0%, #1e1b4b 50%, #0f172a 100%); padding: 90px 24px; color: white; text-align: center; border: 1px solid rgba(99,102,241,0.25); box-shadow: 0 25px 60px rgba(0,0,0,0.7);">
  
  <!-- Interactive 3D Canvas Background Animation -->
  <canvas id="hero3dCanvas" style="position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; opacity: 0.7; z-index: 1;"></canvas>

  <?php if ($heroBackground): ?>
    <div style="position: absolute; inset: 0; background-image: url('<?= h($heroBackground) ?>'); background-size: cover; background-position: center; opacity: 0.2; z-index: 0;"></div>
  <?php endif; ?>

  <div style="position: relative; z-index: 2; max-width: 950px; margin: 0 auto;">
    <span style="display: inline-flex; align-items:center; gap:8px; background: rgba(99,102,241,0.2); border: 1px solid rgba(129,140,248,0.4); color: #a5b4fc; font-weight: 800; font-size: 0.85rem; padding: 8px 20px; border-radius: 30px; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 20px; box-shadow: 0 0 20px rgba(99,102,241,0.3);">
      <i class="fa-solid fa-bolt" style="color:#f59e0b;"></i> Next-Gen 100% Fiber Optic Broadband
    </span>

    <h1 style="font-size: 3.4rem; font-weight: 900; line-height: 1.15; margin-bottom: 20px; background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 50%, #818cf8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; letter-spacing:-1px;">
      Ultra-Fast Fiber Internet & Custom Day Top-Up Passes
    </h1>

    <p style="font-size: 1.25rem; color: #94a3b8; max-width: 720px; margin: 0 auto 36px; line-height: 1.6;">
      Dedicated Gigabit bandwidth, BDIX speed boost, 24/7 priority support, and total freedom to choose 1-day, 7-day, 15-day or 30-day custom packages.
    </p>

    <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
      <a class="btn" href="/pages/packages.php" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 16px 36px; border-radius: 14px; font-weight: 800; font-size: 1.1rem; text-decoration: none; box-shadow: 0 4px 25px rgba(99,102,241,0.5); display:inline-flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-cube"></i> Explore Packages & Custom Plans →
      </a>
      <a class="btn" href="/pages/paybill.php" style="background: rgba(16,185,129,0.15); border: 1px solid #10b981; color: #34d399; padding: 16px 36px; border-radius: 14px; font-weight: 800; font-size: 1.1rem; text-decoration: none; display:inline-flex; align-items:center; gap:10px;">
        <i class="fa-solid fa-credit-card"></i> Instant Bill Payment
      </a>
    </div>
  </div>
</div>

<!-- Live Network Stats Counter Bar -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 45px;">
  <div style="background: rgba(15,23,42,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); padding: 22px; border-radius: 16px; text-align: center;">
    <div style="font-size: 2.2rem; font-weight: 900; color: #818cf8; font-family:'Outfit',sans-serif;">50,000+</div>
    <div style="color: #94a3b8; font-size: 0.9rem; font-weight: 600; margin-top: 4px;">Active Subscribers</div>
  </div>
  <div style="background: rgba(15,23,42,0.7); backdrop-filter: blur(129px); border: 1px solid rgba(255,255,255,0.08); padding: 22px; border-radius: 16px; text-align: center;">
    <div style="font-size: 2.2rem; font-weight: 900; color: #34d399; font-family:'Outfit',sans-serif;">99.99%</div>
    <div style="color: #94a3b8; font-size: 0.9rem; font-weight: 600; margin-top: 4px;">Guaranteed Uptime SLA</div>
  </div>
  <div style="background: rgba(15,23,42,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); padding: 22px; border-radius: 16px; text-align: center;">
    <div style="font-size: 2.2rem; font-weight: 900; color: #f472b6; font-family:'Outfit',sans-serif;">200+ Gbps</div>
    <div style="color: #94a3b8; font-size: 0.9rem; font-weight: 600; margin-top: 4px;">BDIX & International Capacity</div>
  </div>
  <div style="background: rgba(15,23,42,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); padding: 22px; border-radius: 16px; text-align: center;">
    <div style="font-size: 2.2rem; font-weight: 900; color: #38bdf8; font-family:'Outfit',sans-serif;">24 / 7</div>
    <div style="color: #94a3b8; font-size: 0.9rem; font-weight: 600; margin-top: 4px;">VIP Live Technical Support</div>
  </div>
</div>

<!-- Featured Internet Packages -->
<?php if (!empty($featuredPkgs)): ?>
<div style="margin-bottom: 50px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <h2 style="font-size: 1.8rem; font-weight: 800; color: #fff; margin: 0;">🔥 Popular Internet Plans</h2>
      <p style="color: #94a3b8; margin: 4px 0 0; font-size: 0.95rem;">Select from our top-rated residential & gaming fiber plans</p>
    </div>
    <a href="/pages/packages.php" style="color: #818cf8; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 6px;">
      View All Plans <i class="fa-solid fa-arrow-right"></i>
    </a>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
    <?php foreach ($featuredPkgs as $pkg): 
      $price = (float)($pkg['price_bdt'] > 0 ? $pkg['price_bdt'] : $pkg['price']);
    ?>
      <div style="background: rgba(30,41,59,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 28px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <span style="color: #818cf8; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;"><?= h(strtoupper($pkg['category'])) ?></span>
            <span style="background: rgba(99,102,241,0.2); color: #a5b4fc; padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;">
              ⚡ <?= (int)$pkg['speed_mbps'] ?> Mbps
            </span>
          </div>
          <h3 style="color: #fff; font-size: 1.5rem; font-weight: 800; margin: 0 0 10px;"><?= h($pkg['name']) ?></h3>
          <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px; line-height: 1.5;"><?= h($pkg['description']) ?></p>
          <div style="font-size: 2.2rem; font-weight: 900; color: #fff; margin-bottom: 20px;">
            <?= number_format($price, 0) ?> <span style="font-size: 1rem; color: #818cf8; font-weight: 700;">BDT / mo</span>
          </div>
        </div>
        <a href="/pages/checkout.php?package_id=<?= (int)$pkg['id'] ?>" style="display: block; text-align: center; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 12px; border-radius: 10px; font-weight: 700; text-decoration: none;">
          Get Connected Now →
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Feature Grid with 3D Glass Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 50px;">
  
  <!-- Quick Search -->
  <div class="card glass-card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="font-size: 1.3rem; margin-bottom: 12px; color: #818cf8;"><i class="fa-solid fa-magnifying-glass"></i> Quick Package Finder</h3>
    <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 16px;">Filter internet plans by speed and price range.</p>
    <form method="get" action="/pages/packages.php">
      <label style="display:block; color: #cbd5e1; margin-bottom: 6px; font-size: 0.85rem;">Price Range (BDT)</label>
      <select name="sort" class="input" style="width:100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff; margin-bottom: 14px;">
        <option value="price_asc">Price: Low to High</option>
        <option value="price_desc">Price: High to Low</option>
        <option value="speed_desc">Speed: High to Low</option>
      </select>
      <button class="btn" style="width:100%; background: #6366f1; color: #fff; padding: 12px; border-radius: 8px; font-weight: 600;">Browse Packages</button>
    </form>
  </div>

  <!-- Coverage Check -->
  <div class="card glass-card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="font-size: 1.3rem; margin-bottom: 12px; color: #34d399;"><i class="fa-solid fa-location-dot"></i> Check Coverage Area</h3>
    <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 16px;">Find out if MK Net fiber optic broadband is in your area.</p>
    <form method="get" action="/pages/coverage.php">
      <input class="input" type="text" name="district" placeholder="Enter District (e.g. Bogra)" style="width:100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff; margin-bottom: 14px;">
      <button class="btn" style="width:100%; background: #10b981; color: #fff; padding: 12px; border-radius: 8px; font-weight: 600;">Check Availability</button>
    </form>
  </div>

  <!-- Speed Test -->
  <div class="card glass-card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="font-size: 1.3rem; margin-bottom: 12px; color: #f472b6;"><i class="fa-solid fa-gauge-high"></i> Internet Speed Test</h3>
    <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px;">Test your download, upload and latency in real time.</p>
    <a class="btn" href="https://fast.com" target="_blank" style="display: block; text-align: center; background: #ec4899; color: #fff; padding: 12px; border-radius: 8px; font-weight: 600; text-decoration: none;">Launch Speed Test ⚡</a>
  </div>

</div>

<!-- 3D Network Particle Canvas JavaScript -->
<script>
(function() {
  const canvas = document.getElementById('hero3dCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let width, height;
  let particles = [];

  function resize() {
    width = canvas.width = canvas.offsetWidth;
    height = canvas.height = canvas.offsetHeight;
  }
  window.addEventListener('resize', resize);
  resize();

  class Particle {
    constructor() {
      this.x = Math.random() * width;
      this.y = Math.random() * height;
      this.vx = (Math.random() - 0.5) * 0.9;
      this.vy = (Math.random() - 0.5) * 0.9;
      this.radius = Math.random() * 2.5 + 1;
    }
    update() {
      this.x += this.vx;
      this.y += this.vy;
      if (this.x < 0 || this.x > width) this.vx *= -1;
      if (this.y < 0 || this.y > height) this.vy *= -1;
    }
    draw() {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = '#818cf8';
      ctx.fill();
    }
  }

  for (let i = 0; i < 50; i++) {
    particles.push(new Particle());
  }

  function animate() {
    ctx.clearRect(0, 0, width, height);
    for (let i = 0; i < particles.length; i++) {
      particles[i].update();
      particles[i].draw();
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 120) {
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = `rgba(129, 140, 248, ${1 - dist / 120})`;
          ctx.lineWidth = 0.7;
          ctx.stroke();
        }
      }
    }
    requestAnimationFrame(animate);
  }
  animate();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
