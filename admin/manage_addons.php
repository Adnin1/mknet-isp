<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$message = '';
$messageType = '';

// Add Addon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
  if (!csrf_verify()) {
    $message = "Session expired. Please try again.";
    $messageType = "error";
  } else {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'box');
    $type = ($_POST['type'] ?? 'recurring') === 'one_time' ? 'one_time' : 'recurring';
    $taxable = isset($_POST['taxable']) ? 1 : 0;
    $visible = isset($_POST['visible']) ? 1 : 0;

    if ($name !== '') {
      $stmt = $pdo->prepare("INSERT INTO addons (name, price, description, icon, type, taxable, visible) VALUES (?,?,?,?,?,?,?)");
      $stmt->execute([$name, $price, $desc, $icon, $type, $taxable, $visible]);
      $message = "New add-on '$name' added successfully!";
      $messageType = "success";
    }
  }
}

// Update Addon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
  if (!csrf_verify()) {
    $message = "Session expired. Please try again.";
    $messageType = "error";
  } else {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'box');
    $type = ($_POST['type'] ?? 'recurring') === 'one_time' ? 'one_time' : 'recurring';
    $taxable = isset($_POST['taxable']) ? 1 : 0;
    $visible = isset($_POST['visible']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE addons SET name=?, price=?, description=?, icon=?, type=?, taxable=?, visible=? WHERE id=?");
    $stmt->execute([$name, $price, $desc, $icon, $type, $taxable, $visible, $id]);
    $message = "Add-on ID #$id updated successfully!";
    $messageType = "success";
  }
}

// Delete Addon
if (isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])) {
  if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) {
    http_response_code(403); die('Invalid CSRF token.');
  }
  $id = (int)$_GET['delete'];
  $pdo->prepare("DELETE FROM addons WHERE id=?")->execute([$id]);
  $message = "Add-on deleted.";
  $messageType = "success";
}

$addons = $pdo->query("SELECT * FROM addons ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">🧩 Manage Add-on Features & Digital Passes</h2>
</div>

<?php if ($message): ?>
    <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: <?= $messageType==='error'?'#fca5a5':'#6ee7b7' ?>;">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<!-- Add New Addon Card -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #38bdf8;">➕ Add New Feature / Service Add-on</h3>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="add" value="1">
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Add-on Name *</label>
                <input type="text" name="name" placeholder="e.g. Static IP Address" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
            </div>

            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Price (BDT) *</label>
                <input type="number" step="0.01" name="price" placeholder="500.00" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
            </div>

            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Billing Type</label>
                <select name="type" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
                    <option value="recurring">Recurring (Monthly)</option>
                    <option value="one_time">One-time Fee</option>
                </select>
            </div>

            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Icon (FontAwesome)</label>
                <input type="text" name="icon" placeholder="globe, zap, gamepad, shield" value="globe" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Description</label>
            <input type="text" name="description" placeholder="Dedicated Real IP for hosting, gaming & security cameras" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
        </div>

        <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px;">
            <label style="color: #cbd5e1; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="taxable" value="1"> Taxable
            </label>
            <label style="color: #cbd5e1; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="visible" value="1" checked> Visible to Customers
            </label>
        </div>

        <button type="submit" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 10px 24px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer;">Save Add-on →</button>
    </form>
</div>

<!-- All Addons List -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="margin-top: 0; color: #34d399;">📜 All Configured Add-ons</h3>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; color: #e2e8f0; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; text-transform: uppercase;">
                    <th style="padding: 12px;">ID</th>
                    <th style="padding: 12px;">Name & Description</th>
                    <th style="padding: 12px;">Price</th>
                    <th style="padding: 12px;">Type</th>
                    <th style="padding: 12px;">Status</th>
                    <th style="padding: 12px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($addons as $a): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 12px; font-weight: 600;">#<?= (int)$a['id'] ?></td>
                        <td style="padding: 12px;">
                            <strong style="color: #fff; font-size: 1rem;"><?= h($a['name']) ?></strong><br>
                            <span style="color: #94a3b8; font-size: 0.85rem;"><?= h($a['description'] ?: 'N/A') ?></span>
                        </td>
                        <td style="padding: 12px; font-weight: 800; color: #34d399;"><?= number_format((float)$a['price'], 2) ?> BDT</td>
                        <td style="padding: 12px;">
                            <span style="background: rgba(99,102,241,0.2); color: #a5b4fc; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                <?= h($a['type']) ?>
                            </span>
                        </td>
                        <td style="padding: 12px;">
                            <span style="background: <?= !empty($a['visible'])?'rgba(16,185,129,0.2)':'rgba(239,68,68,0.2)' ?>; color: <?= !empty($a['visible'])?'#34d399':'#fca5a5' ?>; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                <?= !empty($a['visible']) ? 'Visible' : 'Hidden' ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            <a href="?delete=<?= (int)$a['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete this add-on?')" style="color: #fca5a5; font-size: 0.85rem; text-decoration: none; font-weight: 700;">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
