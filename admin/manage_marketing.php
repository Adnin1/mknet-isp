<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
  header("Location: login.php");
  exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_campaign'])) {
    if (!csrf_verify()) {
        $msg = "CSRF verification failed!";
    } else {
        $title = trim((string)($_POST['title'] ?? ''));
        $channel = $_POST['channel'] ?? 'email';
        $body = trim((string)($_POST['body'] ?? ''));
        
        app_log('marketing', "Admin sent $channel marketing campaign: $title");
        $msg = "🚀 Marketing campaign '$title' successfully queued for broadcast via $channel!";
    }
}

include __DIR__ . '/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <h2 style="margin:0; color:#fff;">📢 Marketing Campaign & Template Builder</h2>
</div>

<?php if ($msg): ?>
  <div style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#34d399; padding:14px; border-radius:10px; margin-bottom:20px; font-weight:600;">
    <?= h($msg) ?>
  </div>
<?php endif; ?>

<div class="card" style="background:#1e293b; border-radius:16px; padding:24px; color:#fff; border:1px solid rgba(255,255,255,0.1); margin-bottom:30px;">
  <h3 style="margin-top:0; color:#38bdf8;">Broadcast Campaign Message</h3>
  <form method="post">
    <?= csrf_field() ?>
    <div style="margin-bottom:15px;">
      <label style="display:block; color:#94a3b8; font-size:0.85em; margin-bottom:6px;">Campaign Title</label>
      <input class="input" type="text" name="title" required placeholder="e.g. Eid Fiber Upgrade Offer 50% Off" style="width:100%; padding:10px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff;">
    </div>

    <div style="margin-bottom:15px;">
      <label style="display:block; color:#94a3b8; font-size:0.85em; margin-bottom:6px;">Channel</label>
      <select name="channel" style="width:100%; padding:10px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff;">
        <option value="email">📧 Email Template Broadcast</option>
        <option value="sms">📱 SMS Bulk Gateway</option>
        <option value="push">📲 PWA Mobile Push Notification</option>
      </select>
    </div>

    <div style="margin-bottom:20px;">
      <label style="display:block; color:#94a3b8; font-size:0.85em; margin-bottom:6px;">Campaign Body / Template Content</label>
      <textarea name="body" required rows="5" placeholder="Dear Customer, upgrade your MK Net line today for 100 Mbps ultra-fast BDIX speed!" style="width:100%; padding:10px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff;"></textarea>
    </div>

    <button name="send_campaign" class="btn" style="background:linear-gradient(135deg, #6366f1, #4f46e5); color:#fff; padding:12px 24px; font-weight:700; border:none; border-radius:8px; cursor:pointer;">
      Queue Broadcast Campaign →
    </button>
  </form>
</div>

<?php include __DIR__ . '/footer.php'; ?>
