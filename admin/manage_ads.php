<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$message = '';
$messageType = '';

// Add Ad / Banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
  if (!csrf_verify()) {
    $message = "Session expired. Please try again.";
    $messageType = "error";
  } else {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? 'top_banner');
    $linkUrl = trim($_POST['link_url'] ?? '');

    $imagePath = '';
    if (!empty($_FILES['image']['name'])) {
      $targetDir = __DIR__ . "/../uploads/";
      if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);

      $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
      if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        $fileName = 'ad_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
          $imagePath = 'uploads/' . $fileName;
        }
      }
    }

    if ($title !== '') {
      $stmt = $pdo->prepare("INSERT INTO ads (title, description, image) VALUES (?,?,?)");
      $stmt->execute([$title, $desc, $imagePath]);

      // Also record in admin_banners for top/popup banners
      $pdo->prepare("INSERT INTO admin_banners (message, link_url, banner_type, image_path, active) VALUES (?,?,?,?,1)")
        ->execute([$title, $linkUrl, $type, $imagePath]);

      $message = "Advertisement / Banner added successfully!";
      $messageType = "success";
    }
  }
}

// Delete Ad
if (isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])) {
  if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) {
    http_response_code(403); die('Invalid CSRF token.');
  }
  $id = (int)$_GET['delete'];
  $stmt = $pdo->prepare("SELECT image FROM ads WHERE id=?");
  $stmt->execute([$id]);
  $img = $stmt->fetchColumn();
  if ($img && file_exists(__DIR__ . "/../" . $img)) {
    @unlink(__DIR__ . "/../" . $img);
  }
  $pdo->prepare("DELETE FROM ads WHERE id=?")->execute([$id]);
  $message = "Ad deleted.";
  $messageType = "success";
}

$ads = $pdo->query("SELECT * FROM ads ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">📢 Manage Promotional Banners & Ads</h2>
</div>

<?php if ($message): ?>
    <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: <?= $messageType==='error'?'#fca5a5':'#6ee7b7' ?>;">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<!-- Add New Ad Card -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff; margin-bottom: 30px;">
    <h3 style="margin-top: 0; color: #38bdf8;">➕ Create New Ad or Offer Banner</h3>
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="add" value="1">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Ad Title *</label>
                <input type="text" name="title" placeholder="e.g. Eid Mega Discount 50% Off" required style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
            </div>

            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Target Link URL</label>
                <input type="text" name="link_url" placeholder="/pages/packages.php" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
            </div>

            <div>
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Placement Type</label>
                <select name="type" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff;">
                    <option value="top_banner">Top Announcement Bar</option>
                    <option value="hero_promo">Hero Slider Card</option>
                    <option value="popup_offer">Selfcare Portal Modal Popup</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 16px;">
            <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Description / Details</label>
            <textarea name="description" placeholder="Get 50 Mbps connection at 999 BDT with free router & connection!" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; min-height: 80px;"></textarea>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">Ad Banner Image</label>
            <input type="file" name="image" accept="image/*" style="color: #cbd5e1;">
        </div>

        <button type="submit" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 10px 24px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer;">Publish Ad Banner →</button>
    </form>
</div>

<!-- Active Ads Grid -->
<div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 24px; color: #fff;">
    <h3 style="margin-top: 0; color: #34d399;">🎨 Active Ads & Banner Inventory</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <?php foreach ($ads as $ad): ?>
            <div style="background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 16px; text-align: center;">
                <?php if (!empty($ad['image'])): ?>
                    <img src="/<?= h($ad['image']) ?>" alt="<?= h($ad['title']) ?>" style="max-width: 100%; height: 160px; object-fit: cover; border-radius: 8px; margin-bottom: 12px;">
                <?php else: ?>
                    <div style="height: 120px; background: rgba(99,102,241,0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #818cf8; font-weight: 700; margin-bottom: 12px;">Text Announcement</div>
                <?php endif; ?>

                <p style="color: #94a3b8; font-size: 0.88rem; margin: 0 0 14px 0;"><?= h(($ad['description'] ?? '') ?: 'No description') ?></p>
                
                <a href="?delete=<?= (int)$ad['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete this advertisement?')" style="display: inline-block; background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 6px 14px; border-radius: 6px; font-weight: 700; font-size: 0.85rem; text-decoration: none;">Delete Banner</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
