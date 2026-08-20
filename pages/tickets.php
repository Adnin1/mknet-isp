<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Support Tickets — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!SessionManager::touch($pdo)) { header("Location: login.php"); exit; }

$userId = (int)$_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    if (!csrf_verify()) {
        $error = "Session expired, please try again.";
    } else {
        $subject = trim((string)($_POST['subject'] ?? ''));
        $department = $_POST['department'] ?? 'general';
        $priority = $_POST['priority'] ?? 'medium';
        $message = trim((string)($_POST['message'] ?? ''));
        $validDepts = ['technical','billing','sales','general'];
        $validPriorities = ['low','medium','high','urgent'];

        if ($subject === '' || $message === '') {
            $error = "Please fill in both subject and message.";
        } else {
            if (!in_array($department, $validDepts, true)) $department = 'general';
            if (!in_array($priority, $validPriorities, true)) $priority = 'medium';

            // Simple SLA targets by priority - purely informational, not enforced automatically.
            $slaHours = ['urgent' => 4, 'high' => 12, 'medium' => 24, 'low' => 72][$priority];

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject, department, priority, sla_due_at) VALUES (?,?,?,?, NOW() + INTERVAL ? HOUR)");
            $stmt->execute([$userId, $subject, $department, $priority, $slaHours]);
            $ticketId = (int)$pdo->lastInsertId();

            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message) VALUES (?, 'customer', ?, ?)")
                ->execute([$ticketId, $userId, $message]);
            $pdo->commit();

            app_log('tickets', "New ticket #$ticketId created by user $userId ($department/$priority)");
            header("Location: ticket_view.php?id=$ticketId");
            exit;
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE user_id=? ORDER BY id DESC");
$stmt->execute([$userId]);
$tickets = $stmt->fetchAll();

$statusLabels = [
    'open' => ['🟢 Open', '#0a6'], 'in_progress' => ['🔵 In Progress', '#06c'],
    'waiting_customer' => ['🟡 Waiting on you', '#c90'], 'resolved' => ['✅ Resolved', '#0a6'],
    'closed' => ['⚪ Closed', '#888'],
];

include __DIR__ . '/../includes/header.php';
?>
<main class="container">
<div style="max-width:820px;margin:30px auto;">
  <h2>Support Tickets</h2>
  <?php if ($error): ?><p style="color:#b00020;"><?= h($error) ?></p><?php endif; ?>

  <!-- AI Pre-Ticket Troubleshooting Gate Banner -->
  <div style="background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(15,23,42,0.9)); border: 1px solid #6366f1; border-radius: 14px; padding: 20px; margin-bottom: 25px; color: #fff;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
      <span style="font-size:1.4em;">🤖</span>
      <h3 style="margin:0; color:#818cf8; font-size:1.1em;">AI Instant Technical Self-Repair Guide</h3>
    </div>
    <p style="color:#94a3b8; font-size:0.9em; margin:0 0 12px;">Before submitting a ticket to our engineering NOC, try these 3 instant diagnostic steps:</p>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; font-size:0.85em; color:#cbd5e1;">
      <div style="background:rgba(15,23,42,0.8); padding:10px 14px; border-radius:8px; border:1px solid #334155;">1. Unplug Wi-Fi router & ONU power for 30 seconds.</div>
      <div style="background:rgba(15,23,42,0.8); padding:10px 14px; border-radius:8px; border:1px solid #334155;">2. Check ONU PON light (ensure no blinking RED LOS light).</div>
      <div style="background:rgba(15,23,42,0.8); padding:10px 14px; border-radius:8px; border:1px solid #334155;">3. Connect Ethernet cable to test speed on fast.com.</div>
    </div>
  </div>

  <div class="card" style="padding:16px;margin-bottom:20px;">
    <h3>Open a New Ticket</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="text" name="subject" placeholder="Subject" required style="display:block;width:100%;margin:6px 0;padding:8px;">
      <div style="display:flex;gap:8px;margin:6px 0;">
        <select name="department" style="flex:1;padding:8px;">
          <option value="technical">Technical Support</option>
          <option value="billing">Billing</option>
          <option value="sales">Sales</option>
          <option value="general" selected>General</option>
        </select>
        <select name="priority" style="flex:1;padding:8px;">
          <option value="low">Low</option>
          <option value="medium" selected>Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>
      </div>
      <textarea name="message" placeholder="Describe your issue..." required rows="4" style="display:block;width:100%;margin:6px 0;padding:8px;"></textarea>
      <button type="submit" name="create_ticket" class="btn primary">Submit Ticket</button>
    </form>
  </div>

  <div class="card" style="padding:16px;">
    <h3>Your Tickets</h3>
    <table style="width:100%;">
      <tr><th>#</th><th>Subject</th><th>Department</th><th>Priority</th><th>Status</th><th>Updated</th></tr>
      <?php if (empty($tickets)): ?><tr><td colspan="6">No tickets yet.</td></tr><?php endif; ?>
      <?php foreach ($tickets as $t): [$label, $color] = $statusLabels[$t['status']]; ?>
      <tr>
        <td><a href="ticket_view.php?id=<?= (int)$t['id'] ?>">#<?= (int)$t['id'] ?></a></td>
        <td><a href="ticket_view.php?id=<?= (int)$t['id'] ?>"><?= h($t['subject']) ?></a></td>
        <td><?= h(ucfirst($t['department'])) ?></td>
        <td><?= h(ucfirst($t['priority'])) ?></td>
        <td><span style="color:<?= $color ?>;font-weight:bold;"><?= $label ?></span></td>
        <td><?= h($t['updated_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
