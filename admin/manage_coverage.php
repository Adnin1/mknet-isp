<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$message = '';
$messageType = '';

// Add Coverage Area
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!csrf_verify()) {
        $message = "Session expired, please try again.";
        $messageType = "error";
    } else {
        $district = trim($_POST['district'] ?? '');
        $upazila = trim($_POST['upazila'] ?? '');
        $status = $_POST['status'] ?? 'Covered';
        $customers = (int)($_POST['customers'] ?? 0);

        if ($district !== '' && $upazila !== '') {
            $stmt = $pdo->prepare("INSERT INTO coverage_areas (district, upazila, status, customers) VALUES (?,?,?,?)");
            $stmt->execute([$district, $upazila, $status, $customers]);
            $message = "Coverage area '$district - $upazila' added!";
            $messageType = "success";
        }
    }
}

// Delete Coverage Area
if (isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])) {
    if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) {
        http_response_code(403); die('Invalid CSRF token.');
    }
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM coverage_areas WHERE id=?")->execute([$id]);
    $message = "Coverage area deleted.";
    $messageType = "success";
}

$rows = $pdo->query("SELECT * FROM coverage_areas ORDER BY district ASC, upazila ASC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">📍 Manage Network Coverage Areas</h2>
</div>

<?php if ($message): ?>
    <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: <?= $messageType==='error'?'#fca5a5':'#6ee7b7' ?>;">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<!-- Add Area Card -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #38bdf8;">➕ Add Coverage District / Upazila</h3>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="add" value="1">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">District *</label>
                <input type="text" name="district" placeholder="e.g. Bogra" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
            </div>

            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Upazila / Area *</label>
                <input type="text" name="upazila" placeholder="e.g. Sherpur" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
            </div>

            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Status</label>
                <select name="status" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
                    <option value="Covered">Covered (Active)</option>
                    <option value="Planned">Planned (Upcoming)</option>
                    <option value="Unavailable">Unavailable</option>
                </select>
            </div>
        </div>

        <button type="submit" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 10px 24px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer;">Save Coverage Location →</button>
    </form>
</div>

<!-- List Coverage Areas -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="margin-top: 0; color: #34d399;">🌐 Active Coverage Locations</h3>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; color: #e2e8f0; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; text-transform: uppercase;">
                    <th style="padding: 12px;">ID</th>
                    <th style="padding: 12px;">District</th>
                    <th style="padding: 12px;">Upazila / Area</th>
                    <th style="padding: 12px;">Coverage Status</th>
                    <th style="padding: 12px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 12px; font-weight: 600;">#<?= (int)$r['id'] ?></td>
                        <td style="padding: 12px; font-weight: 700; color: #fff;"><?= h($r['district']) ?></td>
                        <td style="padding: 12px; color: #cbd5e1;"><?= h($r['upazila']) ?></td>
                        <td style="padding: 12px;">
                            <span style="background: <?= $r['status']==='Covered'?'rgba(16,185,129,0.2)':'rgba(245,158,11,0.2)' ?>; color: <?= $r['status']==='Covered'?'#34d399':'#fbbf24' ?>; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                <?= h($r['status']) ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            <a href="?delete=<?= (int)$r['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete this coverage location?')" style="color: #fca5a5; font-size: 0.85rem; text-decoration: none; font-weight: 700;">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
