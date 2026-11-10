<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0f172a">
<link rel="icon" href="/assets/images/favicon.png">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- FontAwesome Icons & Swiper CSS & Chart.js -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link rel="stylesheet" href="/assets/style.css?v=20261110-responsive-01">

<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function() {
    navigator.serviceWorker.register('/sw.js').catch(function(err) {});
  });
}
</script>

<style>
:root {
  --primary-glow: #6366f1;
  --secondary-glow: #8b5cf6;
  --accent-cyan: #06b6d4;
  --accent-emerald: #10b981;
  --bg-dark: #090d16;
  --card-bg: rgba(15, 23, 42, 0.75);
  --border-glass: rgba(255, 255, 255, 0.12);
}

body {
  margin: 0;
  font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif;
  background: var(--bg-dark);
  color: #f8fafc;
  overflow-x: hidden;
}

.site-header {
  background: rgba(9, 13, 22, 0.85);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding: 14px 28px;
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 4px 30px rgba(0, 0, 0, 0.4);
}

.site-header nav ul {
  list-style: none;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0;
  margin: 0;
}

.site-header nav a {
  color: #cbd5e1;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  padding: 8px 16px;
  border-radius: 10px;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  gap: 8px;
}

.site-header nav a:hover {
  background: rgba(99, 102, 241, 0.15);
  color: #a5b4fc;
  transform: translateY(-1px);
}

.site-header nav a.active {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(139, 92, 246, 0.25));
  color: #818cf8;
  border: 1px solid rgba(99, 102, 241, 0.3);
}

.dropdown { position: relative; }
.dropdown-content {
  display: none;
  position: absolute;
  top: 100%;
  right: 0;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 14px;
  min-width: 220px;
  box-shadow: 0 15px 35px rgba(0,0,0,0.6);
  z-index: 1001;
  overflow: hidden;
  padding: 8px 0;
  animation: fadeInDown 0.25s ease-out forwards;
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-8px); }
  to { opacity: 1; transform: translateY(0); }
}

.dropdown-content a {
  padding: 10px 18px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
  color: #cbd5e1;
  border-radius: 0;
}
.dropdown-content a:hover {
  background: rgba(99, 102, 241, 0.2);
  color: #fff;
}
.dropdown:hover .dropdown-content, .dropdown.active .dropdown-content { display: block !important; }

.logo-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  color: #fff;
  font-family: 'Outfit', sans-serif;
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.5px;
}
.logo-badge {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  padding: 6px 12px;
  border-radius: 10px;
  box-shadow: 0 0 15px rgba(99,102,241,0.5);
  font-weight: 900;
}

.btn-header-glow {
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff !important;
  font-weight: 700 !important;
  padding: 8px 18px !important;
  border-radius: 10px !important;
  box-shadow: 0 4px 15px rgba(16,185,129,0.3);
}
.btn-header-glow:hover {
  box-shadow: 0 6px 20px rgba(16,185,129,0.5);
  transform: translateY(-2px) !important;
}

.mobile-nav-toggle {
  display: none;
  background: rgba(99, 102, 241, 0.2);
  border: 1px solid rgba(99, 102, 241, 0.4);
  color: #fff;
  font-size: 1.25rem;
  padding: 8px 14px;
  border-radius: 10px;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}
.mobile-nav-toggle:hover {
  background: rgba(99, 102, 241, 0.35);
}

@media (max-width: 992px) {
  .site-header { padding: 12px 18px; }
  .mobile-nav-toggle { display: inline-flex !important; }
  .site-header nav {
    width: 100%;
    display: none;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }
  .site-header nav.mobile-open {
    display: block !important;
    animation: fadeInDown 0.25s ease-out forwards;
  }
  .site-header nav ul {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  .site-header nav a {
    padding: 12px 16px;
    font-size: 1rem;
    border-radius: 10px;
    justify-content: flex-start;
  }
  .dropdown-content {
    position: static !important;
    backdrop-filter: none !important;
    box-shadow: none !important;
    border: none !important;
    padding-left: 16px !important;
    background: transparent !important;
  }
  .dropdown-content a {
    padding: 10px 14px !important;
  }
}

/* Global Responsive Fixes */
html, body {
  width: 100%;
  max-width: 100%;
  overflow-x: clip !important;
}

img, video, iframe, canvas {
  max-width: 100% !important;
  height: auto;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  margin-bottom: 1rem;
}

@media (max-width: 768px) {
  .container { padding: 12px !important; }
  .grid, .packages-grid, .hero, .footer-grid, .cards-grid, .stats-grid {
    grid-template-columns: 1fr !important;
    gap: 16px !important;
  }
  .modal-content {
    width: 94% !important;
    max-height: 88vh !important;
    overflow-y: auto !important;
    padding: 16px !important;
  }
  button, .btn, input[type="submit"], input[type="button"] {
    min-height: 44px;
  }
}
</style>

<style>
@media (max-width: 992px) {
  .site-header > div { width: 100%; padding-inline: 12px; }
  #siteHeaderNav { display: none; width: 100%; max-height: min(72dvh, 620px); overflow-y: auto; }
  #siteHeaderNav.mobile-open { display: block !important; }
  #siteHeaderNav > ul { display: flex; flex-direction: column; align-items: stretch; gap: 6px; }
  #siteHeaderNav > ul > li { width: 100%; min-width: 0; }
  #siteHeaderNav a, #siteHeaderNav button { width: 100%; min-height: 46px; justify-content: flex-start; }
  #siteHeaderNav .dropdown-content { position: static !important; width: 100%; }
}
@media (min-width: 993px) {
  #siteHeaderNav { display: block !important; }
}
</style>
</head>
<body>

<?php
try {
    $activeBanner = $pdo->query(
        "SELECT * FROM admin_banners WHERE active=1 AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY id DESC LIMIT 1"
    )->fetch();
    if ($activeBanner) {
        echo '<div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color:#fff; text-align:center; padding:10px 16px; font-weight:700; font-size:14px; letter-spacing:0.5px; box-shadow:0 2px 10px rgba(99,102,241,0.4);">';
        if (!empty($activeBanner['link_url'])) echo '<a href="' . h($activeBanner['link_url']) . '" style="color:#fff;text-decoration:underline;">';
        echo '✨ ' . h($activeBanner['message']);
        if (!empty($activeBanner['link_url'])) echo '</a>';
        echo '</div>';
    }
} catch (Throwable $e) {}
?>

<header class="site-header">
  <div style="display:flex; align-items:center; justify-content:space-between; max-width:1300px; margin:0 auto; flex-wrap:wrap; gap:16px;">
    <!-- Logo -->
    <a class="logo-brand" href="/index.php">
      <span class="logo-badge">MK</span>
      <span>Net <span style="color:#818cf8; font-weight:400;">ISP</span></span>
    </a>

    <!-- Mobile Menu Hamburger Button -->
    <button type="button" class="mobile-nav-toggle" id="mobileNavToggleBtn" aria-label="Open navigation" aria-controls="siteHeaderNav" aria-expanded="false">
      <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Navigation -->
    <nav id="siteHeaderNav" aria-label="Primary navigation">
      <ul>
        <li><a href="/index.php"><i class="fa-solid fa-house"></i> Home</a></li>
        <li><a href="/pages/ai_experience.php" style="background: rgba(236,72,153,0.15); border: 1px solid rgba(236,72,153,0.4); color: #f472b6; font-weight: 800;"><i class="fa-solid fa-brain"></i> AI Experience Hub</a></li>
        <li><a href="/pages/packages.php"><i class="fa-solid fa-box-open"></i> Packages</a></li>
        <li><a href="/pages/paybill.php"><i class="fa-solid fa-credit-card"></i> Pay Bill</a></li>
        <li><a href="/pages/selfcare_login.php" class="btn-header-glow"><i class="fa-solid fa-user-gear"></i> Self Care Portal</a></li>
        <li><a href="/pages/coverage.php"><i class="fa-solid fa-network-wired"></i> Coverage</a></li>
        <li><a href="/pages/about.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
        <li><a href="/pages/contact.php"><i class="fa-solid fa-headset"></i> Contact</a></li>

        <!-- Services Dropdown -->
        <li class="dropdown">
          <a href="javascript:void(0)" onclick="this.parentNode.classList.toggle('active')"><i class="fa-solid fa-bars"></i> More <i class="fa-solid fa-chevron-down" style="font-size:0.75rem;"></i></a>
          <div class="dropdown-content">
            <a href="/pages/appointment.php"><i class="fa-solid fa-calendar-check" style="color:#38bdf8;"></i> Book Appointment</a>
            <a href="/pages/network_status.php"><i class="fa-solid fa-signal" style="color:#10b981;"></i> Network Status</a>
            <a href="/pages/community.php"><i class="fa-solid fa-users"></i> Community</a>
            <a href="/pages/reviews.php"><i class="fa-solid fa-star" style="color:#f59e0b;"></i> Customer Reviews</a>
            <a href="/pages/news.php"><i class="fa-solid fa-newspaper"></i> Latest News</a>
            <a href="/pages/speedtest.php"><i class="fa-solid fa-gauge-high"></i> Speed Test</a>
            <a href="javascript:void(0)" onclick="if(window.mknetOpenAssistant) window.mknetOpenAssistant();"><i class="fa-solid fa-microphone" style="color:#ec4899;"></i> Voice Assistant</a>
            <hr style="border:0; border-top:1px solid rgba(255,255,255,0.08); margin:4px 0;">
            <a href="/admin/login.php" style="color:#818cf8; font-weight:700;"><i class="fa-solid fa-shield-halved"></i> Admin Login</a>
          </div>
        </li>

        <!-- Light/Dark Mode Toggle Button -->
        <li>
          <button id="siteThemeToggleBtn" type="button" style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); color:#cbd5e1; padding:6px 14px; border-radius:10px; font-weight:700; cursor:pointer; font-size:0.88rem; display:flex; align-items:center; gap:6px;">
            <i class="fa-solid fa-moon"></i> <span id="siteThemeBtnText">Dark</span>
          </button>
        </li>
      </ul>
    </nav>
  </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var mobileBtn = document.getElementById('mobileNavToggleBtn');
  var headerNav = document.getElementById('siteHeaderNav');
  if (mobileBtn && headerNav) {
    function setMenuOpen(open) {
      headerNav.classList.toggle('mobile-open', open);
      mobileBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      mobileBtn.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
      var icon = mobileBtn.querySelector('i');
      if (icon) icon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
    }
    mobileBtn.addEventListener('click', function() {
      setMenuOpen(!headerNav.classList.contains('mobile-open'));
    });
    headerNav.addEventListener('click', function(event) {
      var link = event.target.closest('a');
      if (!link || !window.matchMedia('(max-width: 992px)').matches) return;
      var href = (link.getAttribute('href') || '').trim().toLowerCase();
      if (href && !href.startsWith('javascript:')) setMenuOpen(false);
    });
    document.addEventListener('click', function(event) {
      if (headerNav.classList.contains('mobile-open') &&
          !headerNav.contains(event.target) && !mobileBtn.contains(event.target)) {
        setMenuOpen(false);
      }
    });
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') setMenuOpen(false);
    });
    window.addEventListener('resize', function() {
      if (window.innerWidth > 992) setMenuOpen(false);
    });
  }

  /* Give wide data tables a contained touch-scroll region without changing cells. */
  document.querySelectorAll('table').forEach(function(table) {
    if (table.closest('.table-responsive, .table-scroll, [data-table-scroll]')) return;
    var wrapper = document.createElement('div');
    wrapper.className = 'table-responsive';
    wrapper.setAttribute('role', 'region');
    wrapper.setAttribute('aria-label', 'Scrollable table');
    wrapper.setAttribute('tabindex', '0');
    table.parentNode.insertBefore(wrapper, table);
    wrapper.appendChild(table);
  });
});
</script>

<style>
/* ==========================================================================
   SUPER-PREMIUM ICONIC LIGHT MODE DESIGN SYSTEM
   ========================================================================== */
body.light-mode {
  background: #f8fafc !important;
  background-image: 
    radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.08) 0px, transparent 50%),
    radial-gradient(at 100% 0%, rgba(236, 72, 153, 0.06) 0px, transparent 50%),
    radial-gradient(at 50% 100%, rgba(56, 189, 248, 0.06) 0px, transparent 50%) !important;
  background-attachment: fixed !important;
  color: #0f172a !important;
}

/* Iconic Light Header */
body.light-mode .site-header {
  background: rgba(255, 255, 255, 0.85) !important;
  backdrop-filter: blur(20px) saturate(180%) !important;
  -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
  border-bottom: 1px solid rgba(226, 232, 240, 0.8) !important;
  box-shadow: 0 4px 25px -5px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(0, 0, 0, 0.02) !important;
}

body.light-mode .logo-brand span {
  color: #0f172a !important;
}

body.light-mode .site-header nav a {
  color: #475569 !important;
  font-weight: 600 !important;
}

body.light-mode .site-header nav a:hover {
  background: rgba(99, 102, 241, 0.08) !important;
  color: #4f46e5 !important;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.12) !important;
}

/* Premium Light Mode Cards & Panels */
body.light-mode .card,
body.light-mode .form-card,
body.light-mode div[style*="background: #1e293b"],
body.light-mode div[style*="background:rgba(30,41,59"],
body.light-mode div[style*="background: rgba(30, 41, 59"] {
  background: rgba(255, 255, 255, 0.92) !important;
  backdrop-filter: blur(16px) !important;
  color: #0f172a !important;
  border: 1px solid rgba(226, 232, 240, 0.9) !important;
  box-shadow: 0 10px 30px -5px rgba(99, 102, 241, 0.08), 0 4px 12px rgba(15, 23, 42, 0.03) !important;
  border-radius: 20px !important;
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
}

body.light-mode .card:hover {
  transform: translateY(-4px) !important;
  border-color: rgba(99, 102, 241, 0.35) !important;
  box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.18), 0 8px 16px rgba(15, 23, 42, 0.04) !important;
}

/* Light Mode Typography & Accents */
body.light-mode h1, 
body.light-mode h2, 
body.light-mode h3, 
body.light-mode h4 {
  color: #0f172a !important;
  letter-spacing: -0.02em !important;
}

body.light-mode p, 
body.light-mode label, 
body.light-mode span[style*="color: #94a3b8"],
body.light-mode div[style*="color: #94a3b8"] {
  color: #475569 !important;
}

body.light-mode span[style*="color: #a5b4fc"],
body.light-mode div[style*="color: #a5b4fc"] {
  color: #4f46e5 !important;
  font-weight: 700 !important;
}

/* Light Mode Form Elements & Inputs */
body.light-mode input, 
body.light-mode select, 
body.light-mode textarea {
  background: #ffffff !important;
  color: #0f172a !important;
  border: 1.5px solid #cbd5e1 !important;
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03) !important;
}

body.light-mode input:focus, 
body.light-mode select:focus, 
body.light-mode textarea:focus {
  border-color: #6366f1 !important;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15) !important;
}

/* Light Mode Dropdown Menu */
body.light-mode .dropdown-content {
  background: rgba(255, 255, 255, 0.96) !important;
  backdrop-filter: blur(20px) !important;
  border: 1px solid rgba(226, 232, 240, 0.9) !important;
  box-shadow: 0 20px 40px -5px rgba(15, 23, 42, 0.12) !important;
  border-radius: 16px !important;
}

body.light-mode .dropdown-content a {
  color: #334155 !important;
}

body.light-mode .dropdown-content a:hover {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.08)) !important;
  color: #4f46e5 !important;
}

body.light-mode #siteThemeToggleBtn {
  background: rgba(99, 102, 241, 0.1) !important;
  border: 1px solid rgba(99, 102, 241, 0.25) !important;
  color: #4f46e5 !important;
  box-shadow: 0 2px 8px rgba(99, 102, 241, 0.12) !important;
}
</style>

<script>
(function() {
  var btn = document.getElementById('siteThemeToggleBtn');
  var txt = document.getElementById('siteThemeBtnText');
  var icon = btn ? btn.querySelector('i') : null;

  function applyTheme(theme) {
    if (theme === 'light') {
      document.body.classList.add('light-mode');
      if (txt) txt.textContent = 'Light';
      if (icon) icon.className = 'fa-solid fa-sun';
      if (btn) btn.style.color = '#eab308';
    } else {
      document.body.classList.remove('light-mode');
      if (txt) txt.textContent = 'Dark';
      if (icon) icon.className = 'fa-solid fa-moon';
      if (btn) btn.style.color = '#cbd5e1';
    }
  }

  var savedTheme = localStorage.getItem('mknet_theme') || 'dark';
  applyTheme(savedTheme);

  if (btn) {
    btn.addEventListener('click', function() {
      var current = document.body.classList.contains('light-mode') ? 'light' : 'dark';
      var next = current === 'light' ? 'dark' : 'light';
      localStorage.setItem('mknet_theme', next);
      applyTheme(next);
    });
  }
})();
</script>

<main class="container" style="min-height: 80vh;">
