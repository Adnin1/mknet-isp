<?php
require_once __DIR__ . '/../config.php';
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) session_start();
$adminName = $_SESSION['admin_name'] ?? $_SESSION['admin_user'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MK Net ISP — Admin Panel Control Center</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/admin/style.css?v=20261110-responsive-01">
  <style>
    /* Responsive layout fallback */
    body { margin: 0; background: #090d16; color: #f8fafc; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; display: flex; min-height: 100vh; }
    .admin-layout { display: flex; width: 100%; min-height: 100vh; }
    .admin-main-wrapper { flex: 1; margin-left: 260px; display: flex; flex-direction: column; min-height: 100vh; transition: all 0.3s ease; }
    @media (max-width: 992px) {
      .admin-main-wrapper { margin-left: 0; }
      .sidebar { transform: translateX(-100%); }
      .sidebar.active { transform: translateX(0); }
    }
    .top-navbar { height: 70px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(14px); border-bottom: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 90; }
  </style>
</head>
<body>

<div class="admin-layout">

<?php include __DIR__ . '/sidebar.php'; ?>
  <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Close navigation" tabindex="-1"></button>

<div class="admin-main-wrapper">
  <!-- Top Navigation Header -->
  <header class="top-navbar">
    <div style="display: flex; align-items: center; gap: 16px;">
      <button id="menuBtn" type="button" aria-controls="mknetAdminSidebar" aria-expanded="false" style="background: rgba(99,102,241,0.2); border: 1px solid rgba(99,102,241,0.4); color: #a5b4fc; padding: 8px 14px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-bars"></i> Menu
      </button>
      <h2 style="margin: 0; font-size: 1.2rem; color: #fff; font-weight: 800;">MK Net ISP Admin Engine</h2>
    </div>

    <div style="display: flex; align-items: center; gap: 16px;">
      <button id="darkModeBtn" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; padding: 8px 14px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
        🌙 Dark Mode
      </button>

      <div style="display: flex; align-items: center; gap: 10px; background: rgba(30,41,59,0.8); border: 1px solid rgba(255,255,255,0.1); padding: 6px 14px; border-radius: 30px;">
        <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; font-weight: 800; color: #fff; font-size: 0.85rem;">
          <?= strtoupper(substr($adminName, 0, 1)) ?>
        </div>
        <span style="font-weight: 700; font-size: 0.88rem; color: #fff;"><?= h($adminName) ?></span>
      </div>

      <a href="logout.php" style="color: #fca5a5; font-size: 1.1rem; text-decoration: none; padding: 6px; border-radius: 6px; background: rgba(239,68,68,0.15);" title="Logout">
        <i class="fa-solid fa-power-off"></i>
      </a>
    </div>
  </header>

  <main style="flex: 1; padding: 24px;">

<script>
(function() {
  var darkBtn = document.getElementById('darkModeBtn');
  var menuBtn = document.getElementById('menuBtn');
  var sidebar = document.querySelector('.sidebar');

  var backdrop = document.getElementById('sidebarBackdrop');
  function setSidebarOpen(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('active', open);
    document.body.classList.toggle('sidebar-open', open && window.matchMedia('(max-width: 992px)').matches);
    if (menuBtn) menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', function() {
      setSidebarOpen(!sidebar.classList.contains('active'));
    });
    if (backdrop) backdrop.addEventListener('click', function() { setSidebarOpen(false); });
    sidebar.addEventListener('click', function(event) {
      if (event.target.closest('a')) setSidebarOpen(false);
    });
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') setSidebarOpen(false);
    });
    window.addEventListener('resize', function() {
      if (window.innerWidth > 992) {
        sidebar.classList.remove('active');
        document.body.classList.remove('sidebar-open');
        menuBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function setAdminTheme(theme) {
    if (theme === 'light') {
      document.body.classList.add('light-mode');
      if (darkBtn) darkBtn.innerHTML = '☀️ Light Mode';
    } else {
      document.body.classList.remove('light-mode');
      if (darkBtn) darkBtn.innerHTML = '🌙 Dark Mode';
    }
  }

  var saved = localStorage.getItem('mknet_admin_theme') || 'dark';
  setAdminTheme(saved);

  if (darkBtn) {
    darkBtn.addEventListener('click', function() {
      var current = document.body.classList.contains('light-mode') ? 'light' : 'dark';
      var next = current === 'light' ? 'dark' : 'light';
      localStorage.setItem('mknet_admin_theme', next);
      setAdminTheme(next);
    });
  }
})();
</script>
