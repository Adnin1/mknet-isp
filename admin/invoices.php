<?php
// admin/invoices.php
require_once __DIR__ . '/../config.php';
session_start();
if(!isset($_SESSION['admin_id'])){ header('Location: login.php'); exit; }

// Quick status change (mark paid / pending / failed) - kept intentionally
// simple; a full invoice detail page can be added later if needed.
if (isset($_GET['set_status'], $_GET['id'])) {
    $allowed = ['pending','paid','failed'];
    $newStatus = $_GET['set_status'];
    $id = (int)$_GET['id'];
    if (in_array($newStatus, $allowed, true) && $id > 0) {
        $pdo->prepare("UPDATE invoices SET status=? WHERE id=?")->execute([$newStatus, $id]);
    }
    header('Location: invoices.php?' . http_build_query(array_filter([
        'search' => $_GET['search'] ?? '', 'status' => $_GET['status'] ?? '', 'page' => $_GET['page'] ?? 1,
    ])));
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $pdo->prepare("DELETE FROM invoices WHERE id=?")->execute([$id]);
    }
    header('Location: invoices.php?' . http_build_query(array_filter([
        'search' => $_GET['search'] ?? '', 'status' => $_GET['status'] ?? '', 'page' => $_GET['page'] ?? 1,
    ])));
    exit;
}

// Search / filter / pagination
$search = trim((string)($_GET['search'] ?? ''));
$statusFilter = $_GET['status'] ?? '';
$allowedStatus = ['', 'pending', 'paid', 'failed'];
if (!in_array($statusFilter, $allowedStatus, true)) $statusFilter = '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(u.email LIKE ? OR i.id = ?)";
    $params[] = "%{$search}%";
    $params[] = ctype_digit($search) ? (int)$search : 0;
}
if ($statusFilter !== '') {
    $where[] = "i.status = ?";
    $params[] = $statusFilter;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM invoices i LEFT JOIN users u ON i.user_id = u.id $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $limit));

$sql = "SELECT i.*, u.email AS user_email FROM invoices i LEFT JOIN users u ON i.user_id = u.id $whereSql ORDER BY i.id DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$i = 1;
foreach ($params as $p) { $stmt->bindValue($i++, $p); }
$stmt->bindValue($i++, $limit, PDO::PARAM_INT);
$stmt->bindValue($i++, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include __DIR__ . '/header.php'; ?>
<div class="main">
  <h2>Invoices (<?= e($total) ?>)</h2>

  <form method="get" style="display:flex; gap:8px; margin-bottom:12px; align-items:center;">
    <input type="text" name="search" placeholder="Search by email or invoice ID" value="<?= e($search) ?>">
    <select name="status">
      <option value="">All statuses</option>
      <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
      <option value="paid" <?= $statusFilter==='paid'?'selected':'' ?>>Paid</option>
      <option value="failed" <?= $statusFilter==='failed'?'selected':'' ?>>Failed</option>
    </select>
    <button type="submit">Filter</button>
    <?php if ($search !== '' || $statusFilter !== ''): ?><a href="invoices.php">Clear</a><?php endif; ?>
  </form>

  <div class="card">
    <table class="table">
      <thead><tr><th>ID</th><th>User</th><th>Bundle</th><th>Package</th><th>Amount</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="8">No invoices found.</td></tr>
      <?php else: foreach($rows as $r): ?>
        <tr>
          <td><?= e($r['id']) ?></td>
          <td><?= e($r['user_email'] ?? ('user #'.$r['user_id']) ?: '-') ?></td>
          <td><?= e($r['bundle_id'] ?? '-') ?></td>
          <td><?= e($r['package_id'] ?? '-') ?></td>
          <td><?= e(number_format((float)$r['amount'],2)) ?> <?= e($r['currency']) ?></td>
          <td>
            <?php if ($r['status']==='paid'): ?><span class="badge badge-high">Paid</span>
            <?php elseif ($r['status']==='failed'): ?><span class="badge badge-low">Failed</span>
            <?php else: ?><span class="badge badge-low">Pending</span><?php endif; ?>
          </td>
          <td><?= e($r['created_at']) ?></td>
          <td>
            <?php if ($r['status'] !== 'paid'): ?>
              <a href="?set_status=paid&id=<?= e($r['id']) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" onclick="return confirm('Mark invoice #<?= e($r['id']) ?> as paid?')">Mark Paid</a> |
            <?php endif; ?>
            <?php if ($r['status'] !== 'failed'): ?>
              <a href="?set_status=failed&id=<?= e($r['id']) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" onclick="return confirm('Mark invoice #<?= e($r['id']) ?> as failed?')">Mark Failed</a> |
            <?php endif; ?>
            <a href="?delete=<?= e($r['id']) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>" style="color:#ef4444;" onclick="return confirm('Permanently delete invoice #<?= e($r['id']) ?>?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>

    <div style="margin-top:12px;">
      <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">⬅ Prev</a><?php endif; ?>
      Page <?= $page ?> of <?= $totalPages ?>
      <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">Next ➡</a><?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
