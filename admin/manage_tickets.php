<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

$statusFilter = $_GET['status'] ?? '';
$deptFilter = $_GET['department'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';

$where = [];
$params = [];
if ($statusFilter) { $where[] = "t.status=?"; $params[] = $statusFilter; }
if ($deptFilter) { $where[] = "t.department=?"; $params[] = $deptFilter; }
if ($priorityFilter) { $where[] = "t.priority=?"; $params[] = $priorityFilter; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("SELECT t.*, u.email, u.full_name FROM tickets t JOIN users u ON t.user_id=u.id $whereSql ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.id DESC LIMIT 200");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$counts = $pdo->query("SELECT status, COUNT(*) c FROM tickets GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$statusLabels = [
    'open' => ['🟢 Open', '#0a6'], 'in_progress' => ['🔵 In Progress', '#06c'],
    'waiting_customer' => ['🟡 Waiting on customer', '#c90'], 'resolved' => ['✅ Resolved', '#0a6'],
    'closed' => ['⚪ Closed', '#888'],
];
?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Support Tickets</h2>
  <div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
    <div class="card" style="padding:10px 16px;">Open: <b><?= (int)($counts['open'] ?? 0) ?></b></div>
    <div class="card" style="padding:10px 16px;">In Progress: <b><?= (int)($counts['in_progress'] ?? 0) ?></b></div>
    <div class="card" style="padding:10px 16px;">Waiting on Customer: <b><?= (int)($counts['waiting_customer'] ?? 0) ?></b></div>
    <div class="card" style="padding:10px 16px;">Resolved: <b><?= (int)($counts['resolved'] ?? 0) ?></b></div>
  </div>

  <div class="card" style="padding:12px;margin-bottom:14px;">
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;">
      <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['open','in_progress','waiting_customer','resolved','closed'] as $s): ?>
          <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="department" onchange="this.form.submit()">
        <option value="">All Departments</option>
        <?php foreach (['technical','billing','sales','general'] as $d): ?>
          <option value="<?= $d ?>" <?= $deptFilter === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="priority" onchange="this.form.submit()">
        <option value="">All Priorities</option>
        <?php foreach (['urgent','high','medium','low'] as $p): ?>
          <option value="<?= $p ?>" <?= $priorityFilter === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($statusFilter || $deptFilter || $priorityFilter): ?><a href="manage_tickets.php" class="btn small">Clear</a><?php endif; ?>
    </form>
  </div>

  <div class="card">
    <table>
      <tr><th>#</th><th>Customer</th><th>Subject</th><th>Dept</th><th>Priority</th><th>Status</th><th>SLA Due</th><th>Updated</th></tr>
      <?php if (empty($tickets)): ?><tr><td colspan="8">No tickets found.</td></tr><?php endif; ?>
      <?php foreach ($tickets as $t): [$label, $color] = $statusLabels[$t['status']];
        $slaOverdue = $t['sla_due_at'] && strtotime($t['sla_due_at']) < time() && !in_array($t['status'], ['resolved','closed'], true);
      ?>
      <tr>
        <td><a href="ticket_view.php?id=<?= (int)$t['id'] ?>">#<?= (int)$t['id'] ?></a></td>
        <td><?= h($t['full_name'] ?: $t['email']) ?></td>
        <td><a href="ticket_view.php?id=<?= (int)$t['id'] ?>"><?= h($t['subject']) ?></a></td>
        <td><?= h(ucfirst($t['department'])) ?></td>
        <td><?= h(ucfirst($t['priority'])) ?></td>
        <td><span style="color:<?= $color ?>;font-weight:bold;"><?= $label ?></span></td>
        <td><?= $slaOverdue ? '<span style="color:#b00020;font-weight:bold;">⚠ Overdue</span>' : h($t['sla_due_at']) ?></td>
        <td><?= h($t['updated_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
