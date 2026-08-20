<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

$ticketId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ticketId) { header("Location: manage_tickets.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        // fall through, error shown below
    } elseif (isset($_POST['reply'])) {
        $message = trim((string)($_POST['message'] ?? ''));
        $isNote = isset($_POST['is_internal_note']) ? 1 : 0;
        if ($message !== '') {
            $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_type, sender_id, message, is_internal_note) VALUES (?, 'admin', ?, ?, ?)")
                ->execute([$ticketId, (int)$_SESSION['admin_id'], $message, $isNote]);
            if (!$isNote) {
                // A visible admin reply moves the ticket to "waiting on customer" by default
                $pdo->prepare("UPDATE tickets SET status='waiting_customer' WHERE id=?")->execute([$ticketId]);
            }
        }
    } elseif (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, ['open','in_progress','waiting_customer','resolved','closed'], true)) {
            $closedAt = in_array($newStatus, ['resolved','closed'], true) ? ', closed_at=NOW()' : '';
            $pdo->prepare("UPDATE tickets SET status=?$closedAt WHERE id=?")->execute([$newStatus, $ticketId]);
        }
    }
    header("Location: ticket_view.php?id=$ticketId#bottom");
    exit;
}

$stmt = $pdo->prepare("SELECT t.*, u.email, u.full_name, u.phone FROM tickets t JOIN users u ON t.user_id=u.id WHERE t.id=?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();
if (!$ticket) { header("Location: manage_tickets.php"); exit; }

$msgStmt = $pdo->prepare("SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY id ASC");
$msgStmt->execute([$ticketId]);
$messages = $msgStmt->fetchAll();
?>
<link rel="stylesheet" href="style.css">
<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <p><a href="manage_tickets.php">← All Tickets</a></p>
  <h2>#<?= $ticketId ?> — <?= h($ticket['subject']) ?></h2>
  <p>Customer: <b><?= h($ticket['full_name'] ?: $ticket['email']) ?></b> (<?= h($ticket['email']) ?><?= $ticket['phone'] ? ', ' . h($ticket['phone']) : '' ?>)</p>

  <form method="post" style="margin:10px 0;">
    <?= csrf_field() ?>
    <select name="status">
      <?php foreach (['open','in_progress','waiting_customer','resolved','closed'] as $s): ?>
        <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" name="update_status" class="btn small">Update Status</button>
  </form>

  <div class="card" style="padding:16px;margin:16px 0;">
    <?php foreach ($messages as $m):
      $bg = $m['is_internal_note'] ? '#fff8e1' : ($m['sender_type'] === 'admin' ? '#eef6ff' : '#f4f4f4');
    ?>
      <div style="margin-bottom:14px;padding:10px;border-radius:8px;background:<?= $bg ?>;">
        <b><?= $m['is_internal_note'] ? '🗒️ Internal Note' : ($m['sender_type'] === 'admin' ? '🛠️ Support Team' : '👤 Customer') ?></b>
        <span class="small-note"> — <?= h($m['created_at']) ?></span>
        <p style="margin:6px 0 0;white-space:pre-wrap;"><?= h($m['message']) ?></p>
      </div>
    <?php endforeach; ?>
    <div id="bottom"></div>
  </div>

  <form method="post">
    <?= csrf_field() ?>
    <textarea name="message" placeholder="Reply to customer, or check 'internal note' below to leave a private team note..." required rows="3" style="display:block;width:100%;margin:6px 0;padding:8px;"></textarea>
    <label><input type="checkbox" name="is_internal_note"> Internal note (not visible to customer)</label><br>
    <button type="submit" name="reply" class="btn primary" style="margin-top:8px;">Send</button>
  </form>
</div>
<?php include __DIR__ . '/footer.php'; ?>
