<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!csrf_verify()) { header("Location: manage_coupons.php"); exit; }
    $code = strtoupper(trim((string)$_POST['code']));
    $type = $_POST['type'] === 'flat' ? 'flat' : 'percent';
    $value = (float)$_POST['value'];
    $minAmount = (float)($_POST['min_amount'] ?? 0);
    $maxUses = $_POST['max_uses'] !== '' ? (int)$_POST['max_uses'] : null;
    $expiresAt = $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null;

    if ($code !== '' && $value > 0) {
        $stmt = $pdo->prepare("INSERT INTO coupons (code, type, value, min_amount, max_uses, expires_at, active) VALUES (?,?,?,?,?,?,1)
                                ON DUPLICATE KEY UPDATE type=VALUES(type), value=VALUES(value), min_amount=VALUES(min_amount), max_uses=VALUES(max_uses), expires_at=VALUES(expires_at)");
        $stmt->execute([$code, $type, $value, $minAmount, $maxUses, $expiresAt]);
    }
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE coupons SET active = 1 - active WHERE id=?")->execute([$id]);
}
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM coupons WHERE id=?")->execute([$id]);
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
?>
<link rel="stylesheet" href="style.css">
<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Manage Coupons</h2>

  <div class="card">
    <h3>Create Coupon</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="text" name="code" placeholder="CODE (e.g. WELCOME10)" required style="text-transform:uppercase;">
      <select name="type">
        <option value="percent">Percent (%)</option>
        <option value="flat">Flat (BDT)</option>
      </select>
      <input type="number" step="0.01" name="value" placeholder="Value" required>
      <input type="number" step="0.01" name="min_amount" placeholder="Min order amount (optional)">
      <input type="number" name="max_uses" placeholder="Max uses (optional)">
      <input type="date" name="expires_at" placeholder="Expires">
      <button type="submit" name="add">Save Coupon</button>
    </form>
  </div>

  <div class="card">
    <h3>All Coupons</h3>
    <table>
      <tr><th>Code</th><th>Type</th><th>Value</th><th>Used</th><th>Max</th><th>Expires</th><th>Status</th><th>Actions</th></tr>
      <?php foreach ($coupons as $c): ?>
      <tr>
        <td><?= h($c['code']) ?></td>
        <td><?= h($c['type']) ?></td>
        <td><?= h($c['value']) ?><?= $c['type'] === 'percent' ? '%' : ' BDT' ?></td>
        <td><?= h($c['used_count']) ?></td>
        <td><?= h($c['max_uses'] ?? '∞') ?></td>
        <td><?= h($c['expires_at'] ?? '—') ?></td>
        <td><?= $c['active'] ? '<span class="badge badge-high">Active</span>' : '<span class="badge badge-low">Inactive</span>' ?></td>
        <td>
          <a href="?toggle=<?= h($c['id']) ?>"><?= $c['active'] ? 'Deactivate' : 'Activate' ?></a> |
          <a href="?delete=<?= h($c['id']) ?>" onclick="return confirm('Delete this coupon?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
