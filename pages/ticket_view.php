<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!SessionManager::touch($pdo)) { header("Location: login.php"); exit; }

$userId = (int)$_SESSION['user_id'];
$ticketId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ticketId) { header("Location: tickets.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id=? AND user_id=?");
$stmt->execute([$ticketId, $userId]);
$ticket = $stmt->fetch();
if (!$ticket) { header("Location: tickets.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    if (!csrf_verify()) {
        $error = "Session expired, please try again.";
    } elseif (in_array($ticket['status'], ['resolved', 'closed'], true)) {
        $error = "This ticket is closed. Please open a new ticket if you need further help.";
    } else {
        $message = trim((string)($_POST['message'] ?? ''));
        if ($message !== '') {
            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message) VALUES (?, 'customer', ?, ?)")
                ->execute([$ticketId, $userId, $message]);
            // A customer reply on a ticket the team was waiting on moves it back to "open"
            if ($ticket['status'] === 'waiting_customer') {
                $pdo->prepare("UPDATE tickets SET status='open' WHERE id=?")->execute([$ticketId]);
            }
            header("Location: ticket_view.php?id=$ticketId#bottom");
            exit;
        }
    }
}

$msgStmt = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id=? AND is_internal_note=0 ORDER BY id ASC");
$msgStmt->execute([$ticketId]);
$messages = $msgStmt->fetchAll();

$pageTitle = 'Ticket #' . $ticketId . ' — MK Net';
$statusLabels = [
    'open' => ['🟢 Open', '#0a6'], 'in_progress' => ['🔵 In Progress', '#06c'],
    'waiting_customer' => ['🟡 Waiting on you', '#c90'], 'resolved' => ['✅ Resolved', '#0a6'],
    'closed' => ['⚪ Closed', '#888'],
];
[$label, $color] = $statusLabels[$ticket['status']];

include __DIR__ . '/../includes/header.php';
?>
<main class="container">
<div style="max-width:760px;margin:30px auto;">
  <p><a href="tickets.php">← Back to all tickets</a></p>
  <h2>#<?= $ticketId ?> — <?= h($ticket['subject']) ?></h2>
  <p>
    <span style="color:<?= $color ?>;font-weight:bold;"><?= $label ?></span> ·
    <?= h(ucfirst($ticket['department'])) ?> · <?= h(ucfirst($ticket['priority'])) ?> priority
    <?php if ($ticket['sla_due_at']): ?> · Target response by <?= h($ticket['sla_due_at']) ?><?php endif; ?>
  </p>

  <?php if ($error): ?><p style="color:#b00020;"><?= h($error) ?></p><?php endif; ?>

  <div class="card" style="padding:16px;margin:16px 0;">
    <?php foreach ($messages as $m): ?>
      <div style="margin-bottom:14px;padding:10px;border-radius:8px;background:<?= $m['sender_type'] === 'admin' ? '#eef6ff' : '#f4f4f4' ?>;">
        <b><?= $m['sender_type'] === 'admin' ? '🛠️ Support Team' : '👤 You' ?></b>
        <span class="small-note"> — <?= h($m['created_at']) ?></span>
        <p style="margin:6px 0 0;white-space:pre-wrap;"><?= h($m['message']) ?></p>
      </div>
    <?php endforeach; ?>
    <div id="bottom"></div>
  </div>

  <?php if (!in_array($ticket['status'], ['resolved', 'closed'], true)): ?>
    <form method="post">
      <?= csrf_field() ?>
      <textarea name="message" placeholder="Type your reply..." required rows="3" style="display:block;width:100%;margin:6px 0;padding:8px;"></textarea>
      <button type="submit" name="reply" class="btn primary">Send Reply</button>
    </form>
  <?php else: ?>
    <p class="small-note">This ticket is <?= h($ticket['status']) ?>. <a href="tickets.php">Open a new ticket</a> if you need further help.</p>
  <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
