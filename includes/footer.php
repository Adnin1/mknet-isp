<?php
// Common footer for MK Net ISP
?>
  </main> <!-- end main content wrapper -->

  <footer class="site-footer" style="background: rgba(5, 8, 15, 0.95); border-top: 1px solid rgba(255, 255, 255, 0.08); color: #94a3b8; padding: 60px 24px 30px; position: relative; z-index: 10;">
    <div style="max-width: 1300px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 40px;">
      
      <!-- Brand & About -->
      <div>
        <div class="logo-brand" style="margin-bottom: 16px;">
          <span class="logo-badge">MK</span>
          <span style="color:#fff;">Net <span style="color:#818cf8; font-weight:400;">ISP</span></span>
        </div>
        <p style="font-size: 0.95rem; line-height: 1.6; color: #94a3b8; margin-bottom: 20px;">
          Next-generation ultra-fast fiber optic internet provider delivering high-speed bandwidth, low ping gaming routes, and 24/7 dedicated support.
        </p>
        <div style="display: flex; gap: 12px;">
          <a href="https://facebook.com" target="_blank" rel="noopener" style="width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; transition:all 0.2s ease;" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="https://youtube.com" target="_blank" rel="noopener" style="width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; transition:all 0.2s ease;" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
          <a href="https://linkedin.com" target="_blank" rel="noopener" style="width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; transition:all 0.2s ease;" title="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
          <a href="https://wa.me/8801700000000" target="_blank" rel="noopener" style="width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:center; color:#10b981; text-decoration:none; transition:all 0.2s ease;" title="WhatsApp Support"><i class="fa-brands fa-whatsapp"></i></a>
        </div>
      </div>

      <!-- Quick Navigation -->
      <div>
        <h4 style="margin-top:0; color:#fff; font-size:1.1rem; font-weight:700; border-bottom:2px solid rgba(99,102,241,0.5); padding-bottom:10px; display:inline-block;">Quick Links</h4>
        <ul style="list-style:none; padding:0; margin:16px 0 0; line-height:2.2;">
          <li><a href="/pages/packages.php" style="color:#cbd5e1; text-decoration:none; transition:color 0.2s;"><i class="fa-solid fa-chevron-right" style="font-size:0.75rem; color:#818cf8;"></i> Packages & Custom Plans</a></li>
          <li><a href="/pages/coverage.php" style="color:#cbd5e1; text-decoration:none; transition:color 0.2s;"><i class="fa-solid fa-chevron-right" style="font-size:0.75rem; color:#818cf8;"></i> Coverage Map</a></li>
          <li><a href="/pages/paybill.php" style="color:#cbd5e1; text-decoration:none; transition:color 0.2s;"><i class="fa-solid fa-chevron-right" style="font-size:0.75rem; color:#818cf8;"></i> Online Bill Payment</a></li>
          <li><a href="/pages/selfcare_login.php" style="color:#cbd5e1; text-decoration:none; transition:color 0.2s;"><i class="fa-solid fa-chevron-right" style="font-size:0.75rem; color:#818cf8;"></i> Customer Self Care Portal</a></li>
          <li><a href="/pages/speedtest.php" style="color:#cbd5e1; text-decoration:none; transition:color 0.2s;"><i class="fa-solid fa-chevron-right" style="font-size:0.75rem; color:#818cf8;"></i> Live Speed Test</a></li>
        </ul>
      </div>

      <!-- Contact Info -->
      <?php
      try {
          $footerSettings = $pdo->query("SELECT hotline, email, address FROM settings WHERE id=1")->fetch();
          $ftHotline = $footerSettings['hotline'] ?? '16335 / 09678-123123';
          $ftEmail = $footerSettings['email'] ?? 'support@mknet.com';
          $ftAddr = $footerSettings['address'] ?? 'Police Plaza Concord, Gulshan 1, Dhaka';
      } catch (Throwable $e) {
          $ftHotline = '16335 / 09678-123123';
          $ftEmail = 'support@mknet.com';
          $ftAddr = 'Police Plaza Concord, Gulshan 1, Dhaka';
      }
      ?>
      <div>
        <h4 style="margin-top:0; color:#fff; font-size:1.1rem; font-weight:700; border-bottom:2px solid rgba(99,102,241,0.5); padding-bottom:10px; display:inline-block;">Contact & Support</h4>
        <div style="margin-top:16px; font-size:0.95rem; line-height:1.8;">
          <p style="margin:6px 0;"><i class="fa-solid fa-phone" style="color:#10b981;"></i> <strong>Hotline:</strong> <?= h($ftHotline) ?></p>
          <p style="margin:6px 0;"><i class="fa-solid fa-envelope" style="color:#818cf8;"></i> <strong>Email:</strong> <?= h($ftEmail) ?></p>
          <p style="margin:6px 0;"><i class="fa-solid fa-location-dot" style="color:#ec4899;"></i> <strong>HQ:</strong> <?= h($ftAddr) ?></p>
        </div>
      </div>

      <!-- Newsletter & Updates -->
      <div>
        <h4 style="margin-top:0; color:#fff; font-size:1.1rem; font-weight:700; border-bottom:2px solid rgba(99,102,241,0.5); padding-bottom:10px; display:inline-block;">Stay Connected</h4>
        <p style="font-size:0.9rem; margin-top:16px;">Subscribe for speed upgrades, offer alerts, and coverage expansion updates.</p>
        <form onsubmit="event.preventDefault(); alert('Thank you for subscribing to MK Net updates!');" style="display:flex; gap:6px; margin-top:12px;">
          <input type="email" placeholder="Your email address" required style="padding:10px 14px; border-radius:8px; background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.15); color:#fff; font-size:0.9rem; width:100%;">
          <button type="submit" style="background:#6366f1; color:#fff; border:none; padding:10px 16px; border-radius:8px; font-weight:700; cursor:pointer;"><i class="fa-solid fa-paper-plane"></i></button>
        </form>
      </div>

    </div>

    <!-- Footer Bottom -->
    <div style="max-width: 1300px; margin: 40px auto 0; padding-top: 24px; border-top: 1px solid rgba(255, 255, 255, 0.08); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; font-size: 0.88rem;">
      <p style="margin:0;">&copy; <?= date('Y') ?> <strong>MK Net ISP</strong>. All rights reserved. Engineered for maximum speed.</p>
      <div style="display:flex; gap:16px;">
        <a href="/pages/terms.php" style="color:#94a3b8; text-decoration:none;">Terms & Conditions</a>
        <a href="/pages/contact.php" style="color:#94a3b8; text-decoration:none;">Privacy Policy</a>
      </div>
    </div>
  </footer>
  <script>
  // Prevent double-submit: disable the submit button right after a form is
  // submitted so double-clicks / impatient re-clicks (e.g. on "Subscribe",
  // "Add Bill", "Add Member") can't fire the same request twice.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    var btn = form.querySelector('button[type="submit"], input[type="submit"]');
    if (btn && !btn.disabled) {
      setTimeout(function () { btn.disabled = true; btn.dataset.origText = btn.dataset.origText || btn.innerHTML; btn.innerHTML = 'Please wait…'; }, 0);
    }
  }, true);
  </script>

  <script>
  // PWA: register service worker + offer an "Install App" button when the
  // browser signals the site is installable.
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/mknet/service-worker.js').catch(function (err) {
        console.warn('Service worker registration failed:', err);
      });
    });
  }
  let deferredInstallPrompt = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredInstallPrompt = e;
    var btn = document.getElementById('pwaInstallBtn');
    if (btn) btn.style.display = 'inline-block';
  });
  function installMkNetApp() {
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    deferredInstallPrompt.userChoice.finally(function () {
      deferredInstallPrompt = null;
      var btn = document.getElementById('pwaInstallBtn');
      if (btn) btn.style.display = 'none';
    });
  }
  </script>
  <button id="pwaInstallBtn" onclick="installMkNetApp()" style="display:none;position:fixed;bottom:16px;right:16px;z-index:999;background:#2e7dff;color:#fff;border:none;padding:10px 16px;border-radius:24px;box-shadow:0 2px 8px rgba(0,0,0,.3);cursor:pointer;">📲 Install App</button>

  <?php include __DIR__ . '/voice_widget.php'; ?>
  <script src="/assets/js/dribbble-3d-motion.js"></script>
  </body>
</html>
