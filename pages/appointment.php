<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Book Installation & Service Appointment — MK Net';
include __DIR__ . '/../includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    if (!csrf_verify()) {
        $error = "Session expired, please try again.";
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $date = trim((string)($_POST['preferred_date'] ?? ''));
        $service = trim((string)($_POST['service_type'] ?? 'new_connection'));

        if ($name === '' || $phone === '' || $address === '') {
            $error = "Please fill in your name, phone, and address.";
        } else {
            app_log('appointments', "New appointment booked by $name ($phone) for $service on $date");
            $success = "🎉 Your appointment booking request has been confirmed! Our field optical engineer will contact you shortly.";
        }
    }
}
?>

<div class="mk-container" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">

  <div style="background: linear-gradient(135deg, rgba(30,41,59,0.9), rgba(15,23,42,0.95)); padding: 35px; border-radius: 20px; border: 1px solid rgba(99,102,241,0.3); color: #fff; box-shadow: 0 15px 40px rgba(0,0,0,0.5);">
    <h2 style="margin-top:0; font-size:1.8rem; color:#818cf8;">📅 Book Home Optical Fiber Installation & Survey</h2>
    <p style="color:#94a3b8; margin-bottom:25px;">Schedule a free home fiber feasibility survey or new connection setup with our optical engineering team.</p>

    <?php if ($success): ?>
      <div style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#34d399; padding:14px; border-radius:10px; margin-bottom:20px; font-weight:600;">
        <?= h($success) ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div style="background:rgba(239,68,68,0.2); border:1px solid #ef4444; color:#fca5a5; padding:14px; border-radius:10px; margin-bottom:20px; font-weight:600;">
        <?= h($error) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <div style="margin-bottom:15px;">
        <label style="display:block; color:#94a3b8; font-size:0.9em; margin-bottom:6px;">Full Name</label>
        <input class="input" type="text" name="name" required placeholder="e.g. Tanvir Ahmed" style="width:100%; padding:12px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff;">
      </div>

      <div style="display:flex; gap:15px; flex-wrap:wrap; margin-bottom:15px;">
        <div style="flex:1; min-width:200px;">
          <label style="display:block; color:#94a3b8; font-size:0.9em; margin-bottom:6px;">Phone / WhatsApp</label>
          <input class="input" type="text" name="phone" required placeholder="01700000000" style="width:100%; padding:12px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff;">
        </div>

        <div style="flex:1; min-width:200px;">
          <label style="display:block; color:#94a3b8; font-size:0.9em; margin-bottom:6px;">Preferred Date</label>
          <input class="input" type="date" name="preferred_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:12px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff;">
        </div>
      </div>

      <div style="margin-bottom:15px;">
        <label style="display:block; color:#94a3b8; font-size:0.9em; margin-bottom:6px;">Service Type</label>
        <select name="service_type" style="width:100%; padding:12px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff;">
          <option value="new_connection">✨ New Gigabit Optical Fiber Connection</option>
          <option value="home_survey">🔍 Home Feasibility Survey</option>
          <option value="router_setup">📶 Router & ONU Configuration</option>
          <option value="relocation">🚚 Line Relocation / Transfer</option>
        </select>
      </div>

      <div style="margin-bottom:20px;">
        <label style="display:block; color:#94a3b8; font-size:0.9em; margin-bottom:6px;">Installation Address</label>
        <textarea name="address" required rows="3" placeholder="House no, Road no, Area, District" style="width:100%; padding:12px; background:#0f172a; border:1px solid #334155; border-radius:8px; color:#fff;"></textarea>
      </div>

      <button name="book_appointment" class="btn" style="width:100%; padding:14px; background:linear-gradient(135deg, #6366f1, #4f46e5); color:#fff; font-weight:700; border:none; border-radius:10px; cursor:pointer;">
        Confirm Appointment Booking →
      </button>
    </form>
  </div>

  <!-- Live Installation Status Tracker -->
  <div style="background: rgba(30,41,59,0.8); padding: 30px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); margin-top:30px; color:#fff;">
    <h3 style="margin-top:0; color:#38bdf8;">📡 Real-Time Installation Tracker</h3>
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-top:20px;">
      <div style="text-align:center; flex:1; min-width:120px;">
        <div style="width:40px; height:40px; border-radius:50%; background:#10b981; color:#fff; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-weight:700;">✓</div>
        <div style="font-size:0.85em; font-weight:600;">1. Booking Request</div>
      </div>
      <div style="text-align:center; flex:1; min-width:120px;">
        <div style="width:40px; height:40px; border-radius:50%; background:#6366f1; color:#fff; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-weight:700;">2</div>
        <div style="font-size:0.85em; font-weight:600;">2. Feasibility Survey</div>
      </div>
      <div style="text-align:center; flex:1; min-width:120px;">
        <div style="width:40px; height:40px; border-radius:50%; background:#334155; color:#94a3b8; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-weight:700;">3</div>
        <div style="font-size:0.85em; font-weight:600;">3. Fiber Laying</div>
      </div>
      <div style="text-align:center; flex:1; min-width:120px;">
        <div style="width:40px; height:40px; border-radius:50%; background:#334155; color:#94a3b8; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; font-weight:700;">4</div>
        <div style="font-size:0.85em; font-weight:600;">4. ONU Activation</div>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
