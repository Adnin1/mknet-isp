<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
session_start();
if(!isset($_SESSION['admin_id'])){ header('Location: login.php'); exit; }

// Helpers
$logFile = __DIR__ . '/../storage/logs/manage_packages.log';
if(!is_dir(dirname($logFile))) @mkdir(dirname($logFile), 0777, true);
function log_error(string $m){ global $logFile; @file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] ".$m.PHP_EOL, FILE_APPEND|LOCK_EX); }

// Upload dir
$uploadDirFs = realpath(__DIR__ . "/../uploads/packages") ?: (__DIR__ . "/../uploads/packages");
if(!is_dir($uploadDirFs)) @mkdir($uploadDirFs, 0777, true);

// Safe POST getters
function post_str(string $k, $default=''){ return isset($_POST[$k]) ? trim((string)$_POST[$k]) : $default; }

$message = '';
$errors = [];

// Add / Update handling
if($_SERVER['REQUEST_METHOD']==='POST' && (isset($_POST['add']) || isset($_POST['update']))){
  try {
    $name = post_str('name');
    $slug = post_str('slug') ?: strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name), '-'));
    $speed = (int)post_str('speed', '0');
    $price = (float)post_str('price', '0');
    $cap = post_str('cap') !== '' ? (float)post_str('cap') : null;
    $desc = post_str('description');
    $features = array_filter(array_map('trim', explode("\n", post_str('features'))));
    $featuresJson = !empty($features) ? json_encode(array_values($features), JSON_UNESCAPED_UNICODE) : null;
    $region = post_str('region');
    $billing = post_str('billing_cycle') ?: 'Monthly';
    $trial = post_str('trial_days') !== '' ? (int)post_str('trial_days') : null;
    $visible = isset($_POST['visible']) ? 1 : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $order_index = (int)post_str('order_index', '0');
    $promo_price = post_str('promo_price') !== '' ? (float)post_str('promo_price') : null;
    $promo_start = post_str('promo_start') ?: null;
    $promo_end = post_str('promo_end') ?: null;

    // image upload
    $imageWeb = post_str('old_image','');
    if(!empty($_FILES['image']['name']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK){
      $tmp = $_FILES['image']['tmp_name'];
      $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
      $fileName = time().'_'.bin2hex(random_bytes(6)).'.'.$ext;
      $targetFs = rtrim($uploadDirFs,'/\\').'/'.$fileName;
      if(move_uploaded_file($tmp, $targetFs)){
        $imageWeb = str_replace('\\','/', substr($targetFs, strlen($_SERVER['DOCUMENT_ROOT'])) );
        if($imageWeb === '') $imageWeb = '/uploads/packages/'.$fileName;
      } else {
        $errors[] = 'Image upload failed.';
        log_error("Image upload failed tmp={$tmp} target={$targetFs}");
      }
    }

    if(isset($_POST['add'])){
      $stmt = $pdo->prepare("INSERT INTO packages (name,slug,speed_mbps,price_bdt,data_cap,description,features,image,region,billing_cycle,trial_days,visible,featured,order_index,promo_price,promo_start,promo_end) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
      $stmt->execute([$name,$slug,$speed,$price,$cap,$desc,$featuresJson,$imageWeb,$region,$billing,$trial,$visible,$featured,$order_index,$promo_price,$promo_start,$promo_end]);
      $message = 'Package added.';
    } elseif(isset($_POST['update']) && !empty($_POST['id'])){
      $id = (int)$_POST['id'];
      if(!empty($imageWeb) && !empty($_POST['old_image']) && $_POST['old_image'] !== $imageWeb){
        $oldFs = $_SERVER['DOCUMENT_ROOT'].'/'.ltrim((string)$_POST['old_image'],'/');
        if(is_file($oldFs)) @unlink($oldFs);
      }
      $stmt = $pdo->prepare("UPDATE packages SET name=?,slug=?,speed_mbps=?,price_bdt=?,data_cap=?,description=?,features=?,image=?,region=?,billing_cycle=?,trial_days=?,visible=?,featured=?,order_index=?,promo_price=?,promo_start=?,promo_end=? WHERE id=?");
      $stmt->execute([$name,$slug,$speed,$price,$cap,$desc,$featuresJson,$imageWeb,$region,$billing,$trial,$visible,$featured,$order_index,$promo_price,$promo_start,$promo_end,$id]);
      $message = 'Package updated.';
    }
  } catch (Throwable $ex){
    $errors[] = 'Server error.';
    log_error('Add/Update exception: '.$ex->getMessage());
  }
}

// Delete
if(isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])){
  try {
    $id = (int)$_GET['delete'];
    $row = $pdo->prepare("SELECT image FROM packages WHERE id=?");
    $row->execute([$id]);
    $r = $row->fetch(PDO::FETCH_ASSOC);
    if($r && !empty($r['image'])){
      $file = $_SERVER['DOCUMENT_ROOT'].'/'.ltrim((string)$r['image'],'/');
      if(is_file($file)) @unlink($file);
    }
    $pdo->prepare("DELETE FROM packages WHERE id=?")->execute([$id]);
    $message = 'Package deleted.';
  } catch (Throwable $ex){
    $errors[] = 'Delete failed.';
    log_error('Delete exception: '.$ex->getMessage());
  }
}

// Fetch packages
try {
  $packages = $pdo->query("SELECT * FROM packages ORDER BY order_index ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) {
  $packages = [];
  log_error('Fetch packages exception: '.$ex->getMessage());
}
?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Manage Packages</h2>

  <?php if(!empty($message)): ?><div class="message"><?= e($message) ?></div><?php endif; ?>
  <?php if(!empty($errors)): ?><div class="errors"><?php foreach($errors as $er) echo '<div>'.e($er).'</div>'; ?></div><?php endif; ?>

  <div class="card">
    <h3>Add New Package</h3>
    <form method="post" enctype="multipart/form-data">
      <label>Package Name<input type="text" name="name" required></label>
      <label>Slug (optional)<input type="text" name="slug" placeholder="auto-generated if empty"></label>
      <label>Speed Mbps<input type="number" name="speed" required></label>
      <label>Price BDT<input type="number" step="0.01" name="price" required></label>
      <label>Data Cap GB<input type="number" step="0.01" name="cap"></label>
      <label>Billing Cycle<select name="billing_cycle"><option>Monthly</option><option>Quarterly</option><option>Yearly</option></select></label>
      <label>Trial Days<input type="number" name="trial_days"></label>
      <label>Region<input type="text" name="region" placeholder="e.g., Bogra"></label>
      <label>Features (one per line)<textarea name="features" placeholder="Unlimited support&#10;Static IP"></textarea></label>
      <label>Description<textarea name="description"></textarea></label>
      <label>Image<input type="file" name="image" accept="image/*"></label>
      <label>Promo Price<input type="number" step="0.01" name="promo_price"></label>
      <label>Promo Start<input type="datetime-local" name="promo_start"></label>
      <label>Promo End<input type="datetime-local" name="promo_end"></label>
      <label>Order Index<input type="number" name="order_index" value="0"></label>
      <label><input type="checkbox" name="visible" checked> Visible</label>
      <label><input type="checkbox" name="featured"> Featured</label>
      <button type="submit" name="add">Add Package</button>
    </form>
  </div>

  <div class="card" style="margin-top:16px;">
    <h3>All Packages</h3>
    <table>
      <thead><tr><th>ID</th><th>Image</th><th>Name</th><th>Speed</th><th>Price</th><th>Cap</th><th>Region</th><th>Featured</th><th>Visible</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($packages as $p): ?>
        <tr>
          <td><?= e($p['id']) ?></td>
          <td><?php $img = !empty($p['image']) ? $p['image'] : "/assets/images/packages/default.jpg"; ?><img src="<?= e($img) ?>" style="width:80px;height:50px;object-fit:cover"></td>
          <td><?= e($p['name']) ?></td>
          <td><?= e($p['speed_mbps']) ?> Mbps</td>
          <td><?= e($p['price_bdt']) ?> BDT<?php if(!empty($p['promo_price'])): ?> <br><small>Promo <?= e($p['promo_price']) ?></small><?php endif; ?></td>
          <td><?= isset($p['data_cap']) ? e($p['data_cap']).' GB' : '-' ?></td>
          <td><?= e($p['region']) ?></td>
          <td><?= !empty($p['featured']) ? 'Yes' : 'No' ?></td>
          <td><?= !empty($p['visible']) ? 'Yes' : 'No' ?></td>
          <td><?= e($p['order_index']) ?></td>
          <td>
            <form method="post" enctype="multipart/form-data" style="display:inline-block;">
              <input type="hidden" name="id" value="<?= e($p['id']) ?>">
              <input type="hidden" name="old_image" value="<?= e($p['image']) ?>">
              <input type="text" name="name" value="<?= e($p['name']) ?>" required>
              <input type="text" name="slug" value="<?= e($p['slug']) ?>">
              <input type="number" name="speed" value="<?= e($p['speed_mbps']) ?>" required>
              <input type="number" step="0.01" name="price" value="<?= e($p['price_bdt']) ?>" required>
              <input type="number" name="cap" value="<?= isset($p['data_cap']) ? e($p['data_cap']) : '' ?>">
              <textarea name="features"><?= isset($p['features']) ? implode("\n", json_decode((string)($p['features'] ?? ''), true) ?: []) : '' ?></textarea>
              <textarea name="description"><?= e($p['description']) ?></textarea>
              <input type="file" name="image" accept="image/*">
              <label>Promo Price<input type="number" step="0.01" name="promo_price" value="<?= e($p['promo_price']) ?>"></label>
              <label>Promo Start<input type="datetime-local" name="promo_start" value="<?= !empty($p['promo_start']) ? date('Y-m-d\TH:i', strtotime($p['promo_start'])) : '' ?>"></label>
              <label>Promo End<input type="datetime-local" name="promo_end" value="<?= !empty($p['promo_end']) ? date('Y-m-d\TH:i', strtotime($p['promo_end'])) : '' ?>"></label>
              <label>Order<input type="number" name="order_index" value="<?= e($p['order_index']) ?>"></label>
              <label><input type="checkbox" name="visible" <?= !empty($p['visible']) ? 'checked' : '' ?>> Visible</label>
              <label><input type="checkbox" name="featured" <?= !empty($p['featured']) ? 'checked' : '' ?>> Featured</label>
              <button type="submit" name="update">Update</button>
            </form>
            <a href="?delete=<?= e($p['id']) ?>" onclick="return confirm('Delete this package?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
