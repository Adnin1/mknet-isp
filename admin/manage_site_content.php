<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['admin_id'])){ header("Location: login.php"); exit; }

$message = '';
$uploadDir = __DIR__ . '/../assets/images/uploads/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
$uploadUrlBase = "/assets/images/uploads/";

function handleImageUpload(string $fieldName, string $uploadDir, string $uploadUrlBase): ?string {
    if (empty($_FILES[$fieldName]['name'])) return null;
    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException("Only JPG, PNG, or WEBP images are allowed.");
    }
    if ($_FILES[$fieldName]['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException("Image must be under 5MB.");
    }
    $fileName = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $uploadDir . $fileName)) {
        throw new RuntimeException("Upload failed.");
    }
    return $uploadUrlBase . $fileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = "Session expired, please try again.";
    } else {
        try {
            // --- Homepage / page background ---
            if (isset($_POST['set_background'])) {
                $key = trim($_POST['content_key']);
                $path = handleImageUpload('background_image', $uploadDir, $uploadUrlBase);
                if ($path) {
                    $pdo->prepare("INSERT INTO site_content (content_key, image_path) VALUES (?,?) ON DUPLICATE KEY UPDATE image_path=VALUES(image_path)")
                        ->execute([$key, $path]);
                    $message = "Background updated for: " . h($key);
                } else {
                    $message = "Please choose an image to upload.";
                }
            }

            // --- Carousel slide add ---
            if (isset($_POST['add_slide'])) {
                $path = handleImageUpload('slide_image', $uploadDir, $uploadUrlBase);
                if (!$path) throw new RuntimeException("Please choose a slide image.");
                $pdo->prepare("INSERT INTO carousel_slides (title, subtitle, image_path, link_url, sort_order) VALUES (?,?,?,?,?)")
                    ->execute([$_POST['title'], $_POST['subtitle'], $path, $_POST['link_url'], (int)($_POST['sort_order'] ?: 0)]);
                $message = "Slide added.";
            }

            // --- Banner add ---
            if (isset($_POST['add_banner'])) {
                $pdo->prepare("INSERT INTO admin_banners (message, type, link_url, starts_at, ends_at) VALUES (?,?,?,?,?)")
                    ->execute([$_POST['message'], $_POST['type'], $_POST['link_url'] ?: null, $_POST['starts_at'] ?: null, $_POST['ends_at'] ?: null]);
                $message = "Banner added.";
            }
        } catch (Throwable $ex) {
            $message = $ex->getMessage();
        }
    }
}

if (isset($_GET['toggle_slide'])) {
    $pdo->prepare("UPDATE carousel_slides SET active=IF(active=1,0,1) WHERE id=?")->execute([(int)$_GET['toggle_slide']]);
}
if (isset($_GET['delete_slide'])) {
    $pdo->prepare("DELETE FROM carousel_slides WHERE id=?")->execute([(int)$_GET['delete_slide']]);
}
if (isset($_GET['toggle_banner'])) {
    $pdo->prepare("UPDATE admin_banners SET active=IF(active=1,0,1) WHERE id=?")->execute([(int)$_GET['toggle_banner']]);
}
if (isset($_GET['delete_banner'])) {
    $pdo->prepare("DELETE FROM admin_banners WHERE id=?")->execute([(int)$_GET['delete_banner']]);
}

$backgrounds = $pdo->query("SELECT * FROM site_content")->fetchAll(PDO::FETCH_ASSOC);
$backgroundsByKey = array_column($backgrounds, null, 'content_key');
$slides = $pdo->query("SELECT * FROM carousel_slides ORDER BY sort_order")->fetchAll();
$banners = $pdo->query("SELECT * FROM admin_banners ORDER BY id DESC")->fetchAll();
?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Site Content &amp; Visuals</h2>
  <?php if ($message): ?><p style="color:#0a6;"><?= h($message) ?></p><?php endif; ?>

  <div class="card" style="padding:16px;margin-bottom:16px;">
    <h3>Page Backgrounds</h3>
    <p class="small-note">Upload a background image for any page. Falls back to the site default if none is set.</p>
    <?php foreach (['homepage_background' => 'Homepage', 'dashboard_background' => 'Customer Dashboard', 'packages_background' => 'Packages Page', 'login_background' => 'Login Page'] as $key => $label): ?>
      <form method="post" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
        <?= csrf_field() ?>
        <input type="hidden" name="content_key" value="<?= h($key) ?>">
        <b style="width:160px;"><?= h($label) ?>:</b>
        <?php if (!empty($backgroundsByKey[$key]['image_path'])): ?>
          <img src="<?= h($backgroundsByKey[$key]['image_path']) ?>" style="height:40px;border-radius:4px;">
        <?php endif; ?>
        <input type="file" name="background_image" accept="image/*">
        <button type="submit" name="set_background" class="btn small">Upload</button>
      </form>
    <?php endforeach; ?>
  </div>

  <div class="card" style="padding:16px;margin-bottom:16px;">
    <h3>Homepage Carousel</h3>
    <form method="post" enctype="multipart/form-data" style="margin-bottom:14px;">
      <?= csrf_field() ?>
      <input type="text" name="title" placeholder="Slide title">
      <input type="text" name="subtitle" placeholder="Subtitle">
      <input type="text" name="link_url" placeholder="Link URL (optional)">
      <input type="number" name="sort_order" placeholder="Order" value="0" style="width:70px;">
      <input type="file" name="slide_image" accept="image/*" required>
      <button type="submit" name="add_slide" class="btn small">Add Slide</button>
    </form>
    <table>
      <tr><th>Preview</th><th>Title</th><th>Status</th><th>Actions</th></tr>
      <?php foreach ($slides as $s): ?>
      <tr>
        <td><img src="<?= h($s['image_path']) ?>" style="height:40px;border-radius:4px;"></td>
        <td><?= h($s['title']) ?></td>
        <td><?= $s['active'] ? '✅ Active' : '⛔ Disabled' ?></td>
        <td>
          <a href="?toggle_slide=<?= (int)$s['id'] ?>"><?= $s['active'] ? 'Disable' : 'Enable' ?></a> |
          <a href="?delete_slide=<?= (int)$s['id'] ?>" onclick="return confirm('Delete this slide?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card" style="padding:16px;">
    <h3>Site Banners</h3>
    <p class="small-note">Shows as a bar at the top of every page (e.g. Festival Offer, Maintenance Notice).</p>
    <form method="post" style="margin-bottom:14px;">
      <?= csrf_field() ?>
      <input type="text" name="message" placeholder="Banner message" required style="width:300px;">
      <select name="type">
        <option value="offer">Offer</option>
        <option value="maintenance">Maintenance</option>
        <option value="info">Info</option>
        <option value="urgent">Urgent</option>
      </select>
      <input type="text" name="link_url" placeholder="Link URL (optional)">
      <label>From: <input type="datetime-local" name="starts_at"></label>
      <label>To: <input type="datetime-local" name="ends_at"></label>
      <button type="submit" name="add_banner" class="btn small">Add Banner</button>
    </form>
    <table>
      <tr><th>Message</th><th>Type</th><th>Window</th><th>Status</th><th>Actions</th></tr>
      <?php foreach ($banners as $b): ?>
      <tr>
        <td><?= h($b['message']) ?></td>
        <td><?= h($b['type']) ?></td>
        <td><?= h($b['starts_at'] ?: '—') ?> → <?= h($b['ends_at'] ?: '—') ?></td>
        <td><?= $b['active'] ? '✅ Active' : '⛔ Disabled' ?></td>
        <td>
          <a href="?toggle_banner=<?= (int)$b['id'] ?>"><?= $b['active'] ? 'Disable' : 'Enable' ?></a> |
          <a href="?delete_banner=<?= (int)$b['id'] ?>" onclick="return confirm('Delete this banner?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
