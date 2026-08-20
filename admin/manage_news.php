<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify()) {
    $message = "Session expired, please try again.";
    $messageType = "error";
  } else {
    $imagePath = $_POST['old_image'] ?? "";
    if (!empty($_FILES['image']['name'])) {
      $targetDir = __DIR__ . "/../uploads/news/";
      if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);
      $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        $fileName = 'news_' . time() . '_' . rand(100, 999) . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
          $imagePath = "uploads/news/" . $fileName;
        }
      }
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $eventDate = $_POST['event_date'] ?: date('Y-m-d');
    $category = $_POST['category'] ?? 'General';

    if (isset($_POST['add']) && $title !== '') {
      $stmt = $pdo->prepare("INSERT INTO news_events (title, content, event_date, category, image) VALUES (?,?,?,?,?)");
      $stmt->execute([$title, $content, $eventDate, $category, $imagePath]);
      $message = "News article published!";
      $messageType = "success";
    }
    if (isset($_POST['update']) && !empty($_POST['id'])) {
      $stmt = $pdo->prepare("UPDATE news_events SET title=?, content=?, event_date=?, category=?, image=? WHERE id=?");
      $stmt->execute([$title, $content, $eventDate, $category, $imagePath, (int)$_POST['id']]);
      $message = "News article updated!";
      $messageType = "success";
    }
  }
}

if (isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])) {
  if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) {
    http_response_code(403); die('Invalid CSRF token.');
  }
  $pdo->prepare("DELETE FROM news_events WHERE id=?")->execute([(int)$_GET['delete']]);
  $message = "News article deleted!";
  $messageType = "success";
}

$rows = $pdo->query("SELECT * FROM news_events ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">📰 Manage News & Community Events</h2>
</div>

<?php if ($message): ?>
    <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: <?= $messageType==='error'?'#fca5a5':'#6ee7b7' ?>;">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<!-- Create News Article -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #38bdf8;">➕ Publish News or Event Story</h3>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="add" value="1">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Article Title *</label>
                <input type="text" name="title" placeholder="e.g. MK Net Expands Fiber Network to Sherpur" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
            </div>

            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Event Date</label>
                <input type="date" name="event_date" value="<?= date('Y-m-d') ?>" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
            </div>

            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Category</label>
                <select name="category" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
                    <option value="Announcements">Announcements</option>
                    <option value="Offers">Special Offers</option>
                    <option value="Community">Community Events</option>
                    <option value="General">General News</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Article Content *</label>
            <textarea name="content" placeholder="Full details of the news post..." required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; min-height: 120px;"></textarea>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Featured Cover Image</label>
            <input type="file" name="image" accept="image/*" style="color: #cbd5e1;">
        </div>

        <button type="submit" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 10px 24px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer;">Publish Article →</button>
    </form>
</div>

<!-- All News Articles Grid -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="margin-top: 0; color: #34d399;">📰 Published Articles Directory</h3>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; color: #e2e8f0; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; text-transform: uppercase;">
                    <th style="padding: 12px;">Cover</th>
                    <th style="padding: 12px;">Title</th>
                    <th style="padding: 12px;">Category</th>
                    <th style="padding: 12px;">Date</th>
                    <th style="padding: 12px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $n): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 12px;">
                            <?php if (!empty($n['image'])): ?>
                                <img src="/<?= h($n['image']) ?>" alt="News" style="width: 60px; height: 40px; object-fit: cover; border-radius: 6px;">
                            <?php else: ?>
                                <span style="color: #64748b; font-size: 0.8rem;">No Cover</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; font-weight: 700; color: #fff;"><?= h($n['title']) ?></td>
                        <td style="padding: 12px;">
                            <span style="background: rgba(99,102,241,0.2); color: #a5b4fc; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;">
                                <?= h($n['category']) ?>
                            </span>
                        </td>
                        <td style="padding: 12px; color: #94a3b8; font-size: 0.85rem;"><?= h($n['event_date']) ?></td>
                        <td style="padding: 12px; text-align: right;">
                            <a href="?delete=<?= (int)$n['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete this news article?')" style="color: #fca5a5; font-size: 0.85rem; text-decoration: none; font-weight: 700;">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
