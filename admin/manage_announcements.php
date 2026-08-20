<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$message = '';
$messageType = '';

// Add Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
  if (!csrf_verify()) {
    $message = "Session expired. Please try again.";
    $messageType = "error";
  } else {
    $title = trim($_POST['title'] ?? '');
    $msg = trim($_POST['message'] ?? '');
    if ($title !== '' && $msg !== '') {
      $pdo->prepare("INSERT INTO announcements (title, message) VALUES (?,?)")->execute([$title, $msg]);
      $message = "Announcement published successfully!";
      $messageType = "success";
    }
  }
}

// Update Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
  if (!csrf_verify()) {
    $message = "Session expired. Please try again.";
    $messageType = "error";
  } else {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title'] ?? '');
    $msg = trim($_POST['message'] ?? '');
    $pdo->prepare("UPDATE announcements SET title=?, message=? WHERE id=?")->execute([$title, $msg, $id]);
    $message = "Announcement updated!";
    $messageType = "success";
  }
}

// Delete Announcement
if (isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])) {
  if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) {
    http_response_code(403); die('Invalid CSRF token.');
  }
  $id = (int)$_GET['delete'];
  $pdo->prepare("DELETE FROM announcements WHERE id=?")->execute([$id]);
  $message = "Announcement deleted.";
  $messageType = "success";
}

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">📣 Manage Global System Announcements</h2>
</div>

<?php if ($message): ?>
    <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: <?= $messageType==='error'?'#fca5a5':'#6ee7b7' ?>;">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<!-- Create Announcement -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #38bdf8;">➕ Publish Broadcast Notice</h3>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="add" value="1">
        
        <div style="margin-bottom: 16px;">
            <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Title / Headline *</label>
            <input type="text" name="title" placeholder="e.g. Scheduled Network Maintenance on 5th August" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Announcement Details *</label>
            <textarea name="message" placeholder="Fiber upgrade work will take place between 2:00 AM - 5:00 AM." required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; min-height: 100px;"></textarea>
        </div>

        <button type="submit" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 10px 24px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer;">Broadcast Announcement →</button>
    </form>
</div>

<!-- List Announcements -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="margin-top: 0; color: #34d399;">📋 Published Announcements</h3>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; color: #e2e8f0; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; text-transform: uppercase;">
                    <th style="padding: 12px;">ID</th>
                    <th style="padding: 12px;">Title</th>
                    <th style="padding: 12px;">Message Body</th>
                    <th style="padding: 12px;">Date</th>
                    <th style="padding: 12px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($announcements as $a): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 12px; font-weight: 600;">#<?= (int)$a['id'] ?></td>
                        <td style="padding: 12px; font-weight: 700; color: #fff;"><?= h($a['title']) ?></td>
                        <td style="padding: 12px; color: #cbd5e1;"><?= h($a['message']) ?></td>
                        <td style="padding: 12px; color: #94a3b8; font-size: 0.85rem;"><?= h($a['created_at']) ?></td>
                        <td style="padding: 12px; text-align: right;">
                            <a href="?delete=<?= (int)$a['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete this announcement?')" style="color: #fca5a5; font-size: 0.85rem; text-decoration: none; font-weight: 700;">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
