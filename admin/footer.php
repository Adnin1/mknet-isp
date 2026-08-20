  </main>

  <footer style="padding: 20px 24px; border-top: 1px solid rgba(255,255,255,0.08); background: rgba(15,23,42,0.6); color: #94a3b8; font-size: 0.85rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>&copy; <?= date('Y') ?> <strong>MK Net ISP</strong>. All rights reserved. Admin System v2.0</div>
    <div>System Status: <span style="color: #34d399; font-weight: 700;">● Engine Operational</span></div>
  </footer>
</div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const menuBtn = document.getElementById('menuBtn');
  const sidebar = document.querySelector('.sidebar');

  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      sidebar.classList.toggle('active');
    });

    document.addEventListener('click', function(e) {
      if (window.innerWidth <= 992 && sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== menuBtn) {
        sidebar.classList.remove('active');
      }
    });
  }

  // Highlight active link in sidebar
  const currentPath = window.location.pathname.split('/').pop();
  document.querySelectorAll('.sidebar ul.menu li a').forEach(link => {
    const linkPath = link.getAttribute('href');
    if (linkPath === currentPath) {
      link.classList.add('active');
      link.style.background = 'rgba(99, 102, 241, 0.25)';
      link.style.color = '#a5b4fc';
      link.style.border = '1px solid rgba(99, 102, 241, 0.4)';
    }
  });

  // Dark Mode Toggle
  const darkModeBtn = document.getElementById('darkModeBtn');
  if (darkModeBtn) {
    darkModeBtn.addEventListener('click', function() {
      document.body.classList.toggle('light-mode');
      if (document.body.classList.contains('light-mode')) {
        this.textContent = "🌙 Dark Mode";
        document.body.style.background = "#f1f5f9";
        document.body.style.color = "#0f172a";
      } else {
        this.textContent = "☀️ Light Mode";
        document.body.style.background = "#090d16";
        document.body.style.color = "#f8fafc";
      }
    });
  }
});
</script>
<script src="/assets/js/dribbble-3d-motion.js"></script>
</body>
</html>
