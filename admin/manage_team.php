<?php
// admin/manage_team.php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

/*
  Final, robust Manage Team admin page
  - Add / Update / Delete members
  - Image upload with filesystem -> web path conversion
  - Old image cleanup on update/delete
  - Search, sort (whitelist), pagination (bound integers)
  - Safe input handling (no undefined index warnings)
  - Error logging to storage/logs/manage_team_errors.log
*/

$logFile = __DIR__ . '/../storage/logs/manage_team_errors.log';
if (!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0777, true);
function log_error(string $msg) { global $logFile; @file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] ".$msg.PHP_EOL, FILE_APPEND|LOCK_EX); }

// Validate PDO
if (!isset($pdo) || !($pdo instanceof PDO)) {
    log_error('PDO not configured in config.php');
    http_response_code(500);
    echo 'Server configuration error.';
    exit;
}

// Upload configuration
$uploadDirFs = realpath(__DIR__ . "/../uploads/team") ?: (__DIR__ . "/../uploads/team");
if (!is_dir($uploadDirFs)) @mkdir($uploadDirFs, 0777, true);
$allowedMime = ['image/jpeg','image/png','image/gif','image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Helpers
function fs_to_web(string $fullPath): string {
    $fullPath = str_replace('\\','/',$fullPath);
    $docRoot = str_replace('\\','/', rtrim($_SERVER['DOCUMENT_ROOT'], '/'));
    if (strpos($fullPath, $docRoot) === 0) {
        $web = substr($fullPath, strlen($docRoot));
        if ($web === '') return '/';
        return ($web[0] === '/') ? $web : '/'.$web;
    }
    return $fullPath;
}
function get_str_post(string $k, string $default = ''): string {
    return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default;
}
function get_str_get(string $k, string $default = ''): string {
    $v = filter_input(INPUT_GET, $k, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    return $v === null ? $default : (string)$v;
}
function get_int_get(string $k, int $default = 0): int {
    $v = filter_input(INPUT_GET, $k, FILTER_VALIDATE_INT);
    return $v === false || $v === null ? $default : (int)$v;
}

// Messages and errors
$message = '';
$errors = [];

// Handle POST (Add / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $photoWeb = get_str_post('old_photo', '');

        // Handle file upload if provided
        if (!empty($_FILES['photo']['name']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmp = $_FILES['photo']['tmp_name'];
            $mime = @mime_content_type($tmp);
            $size = @filesize($tmp);

            if (!in_array($mime, $allowedMime, true)) {
                $errors[] = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.';
            } elseif ($size > $maxSize) {
                $errors[] = 'Image too large. Max 5MB allowed.';
            } else {
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $fileName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $targetFs = rtrim($uploadDirFs, '/\\') . '/' . $fileName;
                if (move_uploaded_file($tmp, $targetFs)) {
                    $photoWeb = fs_to_web($targetFs);
                } else {
                    $errors[] = 'Failed to move uploaded file. Check folder permissions.';
                    log_error("move_uploaded_file failed: tmp={$tmp} target={$targetFs}");
                }
            }
        }

        if (empty($errors)) {
            // sanitize inputs
            $name = get_str_post('name', '');
            $designation = get_str_post('designation', '');
            $linkedin = get_str_post('linkedin', '');
            $facebook = get_str_post('facebook', '');
            $twitter = get_str_post('twitter', '');
            $email = get_str_post('email', '');
            $phone = get_str_post('phone', '');
            $bio = get_str_post('bio', '');

            if (isset($_POST['add'])) {
                $stmt = $pdo->prepare("INSERT INTO team (name, designation, linkedin, facebook, twitter, email, phone, bio, photo, join_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $designation, $linkedin, $facebook, $twitter, $email, $phone, $bio, $photoWeb]);
                $message = '✅ Team member added.';
            }

            if (isset($_POST['update']) && !empty($_POST['id'])) {
                $id = (int)$_POST['id'];
                // delete old file if replaced
                if (!empty($photoWeb) && !empty($_POST['old_photo']) && $_POST['old_photo'] !== $photoWeb) {
                    $oldFs = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim((string)$_POST['old_photo'], '/');
                    $oldFs = str_replace('\\','/',$oldFs);
                    if (is_file($oldFs)) @unlink($oldFs);
                }
                $stmt = $pdo->prepare("UPDATE team SET name=?, designation=?, linkedin=?, facebook=?, twitter=?, email=?, phone=?, bio=?, photo=? WHERE id=?");
                $stmt->execute([$name, $designation, $linkedin, $facebook, $twitter, $email, $phone, $bio, $photoWeb, $id]);
                $message = '✏️ Team member updated.';
            }
        }
    } catch (Throwable $ex) {
        $errors[] = 'Server error occurred.';
        log_error('POST exception: ' . $ex->getMessage());
    }
}

// Handle delete
if (isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];
        $row = $pdo->prepare("SELECT photo FROM team WHERE id=?");
        $row->execute([$id]);
        $r = $row->fetch(PDO::FETCH_ASSOC);
        if ($r && !empty($r['photo'])) {
            $file = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim((string)$r['photo'], '/');
            $file = str_replace('\\','/',$file);
            if (is_file($file)) @unlink($file);
        }
        $pdo->prepare("DELETE FROM team WHERE id=?")->execute([$id]);
        $message = '🗑️ Team member deleted.';
    } catch (Throwable $ex) {
        $errors[] = 'Delete failed.';
        log_error('Delete exception: ' . $ex->getMessage());
    }
}

// Search / Sort / Pagination (safe)
$search = get_str_get('search', '');
$sortInput = get_str_get('sort', 'name ASC');
$allowedSort = ['name ASC','name DESC','designation ASC','id DESC','id ASC'];
$sort = in_array($sortInput, $allowedSort, true) ? $sortInput : 'name ASC';
$page = get_int_get('page', 1);
$page = max(1, $page);
$limit = 8;
$offset = ($page - 1) * $limit;

try {
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM team WHERE name LIKE ? OR designation LIKE ?");
    $totalStmt->execute(["%{$search}%","%{$search}%"]);
    $totalRows = (int)$totalStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $limit));

    $query = "SELECT * FROM team WHERE name LIKE ? OR designation LIKE ? ORDER BY {$sort} LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(1, "%{$search}%", PDO::PARAM_STR);
    $stmt->bindValue(2, "%{$search}%", PDO::PARAM_STR);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->bindValue(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $team = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) {
    $team = [];
    $totalRows = 0;
    $totalPages = 1;
    $errors[] = 'Failed to fetch team members.';
    log_error('Fetch exception: ' . $ex->getMessage());
}
?>
<link rel="stylesheet" href="style.css">
<style>
  .form-card input, .form-card textarea, .form-card select { display:block; width:100%; margin:6px 0; padding:8px; }
  table th, table td { padding:8px; border:1px solid #eee; vertical-align:top; }
  img.thumb { width:60px; height:60px; object-fit:cover; border-radius:50%; }
  .message { color:green; }
  .errors { color:#b00020; }
  .small-note { font-size:13px; color:#666; margin-top:8px; }
</style>

<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Manage Team Members</h2>

  <?php if ($message): ?><p class="message"><?= e($message) ?></p><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="errors"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div><?php endif; ?>

  <!-- Search + Sort -->
  <form method="get" class="form-card" style="display:flex; gap:8px; align-items:center;">
    <input type="text" name="search" placeholder="Search name or designation" value="<?= e($search) ?>" style="flex:1;">
    <select name="sort">
      <option value="name ASC" <?= $sort==='name ASC'?'selected':'' ?>>Name (A-Z)</option>
      <option value="name DESC" <?= $sort==='name DESC'?'selected':'' ?>>Name (Z-A)</option>
      <option value="designation ASC" <?= $sort==='designation ASC'?'selected':'' ?>>Designation</option>
      <option value="id DESC" <?= $sort==='id DESC'?'selected':'' ?>>Newest First</option>
      <option value="id ASC" <?= $sort==='id ASC'?'selected':'' ?>>Oldest First</option>
    </select>
    <button type="submit">Apply</button>
  </form>

  <!-- Add New Member -->
  <div class="card" style="margin-top:16px; padding:12px;">
    <h3>Add New Member</h3>
    <form method="post" enctype="multipart/form-data" class="form-card">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="text" name="designation" placeholder="Designation" required>
      <input type="url" name="linkedin" placeholder="LinkedIn URL">
      <input type="url" name="facebook" placeholder="Facebook URL">
      <input type="url" name="twitter" placeholder="Twitter URL">
      <input type="email" name="email" placeholder="Email">
      <input type="text" name="phone" placeholder="Phone">
      <textarea name="bio" placeholder="Short bio"></textarea>
      <input type="file" name="photo" accept="image/*">
      <button type="submit" name="add">Add Member</button>
    </form>
  </div>

  <!-- Existing Members -->
  <div class="card" style="margin-top:18px;">
    <h3>Existing Team Members (<?= $totalRows ?>)</h3>
    <table>
      <thead>
        <tr>
          <th>Photo</th><th>Name</th><th>Designation</th><th>Bio</th><th>Contact</th><th>Links</th><th>Joined</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($team)): ?>
        <tr><td colspan="8">No members found.</td></tr>
      <?php else: foreach ($team as $t): ?>
        <tr>
          <td>
            <?php $photo = !empty($t['photo']) ? $t['photo'] : "/assets/images/default-team.png"; ?>
            <img src="<?= e($photo) ?>" class="thumb" alt="<?= e($t['name']) ?>">
          </td>
          <td><?= e($t['name']) ?></td>
          <td><?= e($t['designation']) ?></td>
          <td><?= e(mb_strimwidth($t['bio'] ?? '', 0, 120, '...')) ?></td>
          <td>📧 <?= e($t['email'] ?? 'N/A') ?><br>📞 <?= e($t['phone'] ?? 'N/A') ?></td>
          <td>
            <?php if (!empty($t['linkedin'])): ?><a href="<?= e($t['linkedin']) ?>" target="_blank">LinkedIn</a><?php endif; ?>
            <?php if (!empty($t['facebook'])): ?> | <a href="<?= e($t['facebook']) ?>" target="_blank">Facebook</a><?php endif; ?>
            <?php if (!empty($t['twitter'])): ?> | <a href="<?= e($t['twitter']) ?>" target="_blank">Twitter</a><?php endif; ?>
          </td>
          <td><?= e($t['join_date'] ?? 'N/A') ?></td>
          <td>
            <form method="post" enctype="multipart/form-data" style="margin-bottom:6px;">
              <input type="hidden" name="id" value="<?= e($t['id']) ?>">
              <input type="hidden" name="old_photo" value="<?= e($t['photo']) ?>">
              <input type="text" name="name" value="<?= e($t['name']) ?>" required>
              <input type="text" name="designation" value="<?= e($t['designation']) ?>" required>
              <input type="url" name="linkedin" value="<?= e($t['linkedin']) ?>">
              <input type="url" name="facebook" value="<?= e($t['facebook']) ?>">
              <input type="url" name="twitter" value="<?= e($t['twitter']) ?>">
              <input type="email" name="email" value="<?= e($t['email']) ?>">
              <input type="text" name="phone" value="<?= e($t['phone']) ?>">
              <textarea name="bio"><?= e($t['bio']) ?></textarea>
              <input type="file" name="photo" accept="image/*">
              <button type="submit" name="update">Update</button>
            </form>
            <a href="?delete=<?= e($t['id']) ?>" onclick="return confirm('Delete this member?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <div style="margin-top:12px;">
      <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>">⬅ Prev</a><?php endif; ?>
      Page <?= $page ?> of <?= $totalPages ?>
      <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>">Next ➡</a><?php endif; ?>
    </div>
  </div>

  <div class="small-note">
    If images invisible: check `uploads/team/` for the file, open the image URL directly, and check `storage/logs/manage_team_errors.log`.
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
