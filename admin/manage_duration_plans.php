<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = "Session expired, please try again.";
    } elseif (isset($_POST['add'])) {
        $isCustom = isset($_POST['is_custom']) ? 1 : 0;
        $stmt = $pdo->prepare(
            "INSERT INTO duration_plans (label, days, is_custom, min_custom_days, max_custom_days, discount_percent, active, sort_order) VALUES (?,?,?,?,?,?,1,?)"
        );
        $stmt->execute([
            trim($_POST['label']),
            $isCustom ? null : (int)$_POST['days'],
            $isCustom,
            (int)($_POST['min_custom_days'] ?: 1),
            (int)($_POST['max_custom_days'] ?: 365),
            (float)$_POST['discount_percent'],
            (int)($_POST['sort_order'] ?: 0),
        ]);
        $message = "Duration plan added.";
    } elseif (isset($_POST['toggle'])) {
        $pdo->prepare("UPDATE duration_plans SET active = IF(active=1,0,1) WHERE id=?")->execute([(int)$_POST['id']]);
        $message = "Updated.";
    } elseif (isset($_POST['update_discount'])) {
        $pdo->prepare("UPDATE duration_plans SET discount_percent=? WHERE id=?")
            ->execute([(float)$_POST['discount_percent'], (int)$_POST['id']]);
        $message = "Discount updated.";
    }
}

if (isset($_GET['delete'])) {
    if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) {
        http_response_code(403); die('Invalid or expired link - please refresh the page and try again.');
    }
    $pdo->prepare("DELETE FROM duration_plans WHERE id=?")->execute([(int)$_GET['delete']]);
    $message = "Deleted.";
}

$plans = $pdo->query("SELECT * FROM duration_plans ORDER BY sort_order")->fetchAll();
?>
<link rel="stylesheet" href="style.css">
<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Recharge Duration Plans</h2>
  <p class="small-note">These are the short/custom-duration renewal options existing subscribers see on the Recharge page. New customer registration always uses a fixed package, regardless of what's configured here.</p>
  <?php if ($message): ?><p style="color:#0a6;"><?= h($message) ?></p><?php endif; ?>

  <div class="card" style="padding:16px;margin-bottom:16px;">
    <h3>Add Duration Plan</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="text" name="label" placeholder="Label (e.g. 5 Days)" required>
      <input type="number" name="days" placeholder="Days (blank if custom)">
      <label><input type="checkbox" name="is_custom"> This is the "Custom" option</label>
      <input type="number" name="min_custom_days" placeholder="Min custom days" value="1">
      <input type="number" name="max_custom_days" placeholder="Max custom days" value="365">
      <input type="number" step="0.01" name="discount_percent" placeholder="Discount %" value="0">
      <input type="number" name="sort_order" placeholder="Sort order" value="0">
      <button type="submit" name="add">Add Plan</button>
    </form>
  </div>

  <div class="card">
    <table>
      <tr><th>Label</th><th>Days</th><th>Custom Range</th><th>Discount %</th><th>Status</th><th>Actions</th></tr>
      <?php foreach ($plans as $p): ?>
      <tr>
        <td><?= h($p['label']) ?></td>
        <td><?= $p['is_custom'] ? '—' : h($p['days']) ?></td>
        <td><?= $p['is_custom'] ? h($p['min_custom_days']) . '–' . h($p['max_custom_days']) : '—' ?></td>
        <td>
          <form method="post" style="display:flex;gap:4px;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <input type="number" step="0.01" name="discount_percent" value="<?= h($p['discount_percent']) ?>" style="width:70px;">
            <button type="submit" name="update_discount" class="btn small">Save</button>
          </form>
        </td>
        <td><?= $p['active'] ? '✅ Active' : '⛔ Disabled' ?></td>
        <td>
          <form method="post" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button type="submit" name="toggle" class="btn small"><?= $p['active'] ? 'Disable' : 'Enable' ?></button>
          </form>
          <a href="?delete=<?= (int)$p['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete this plan?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
