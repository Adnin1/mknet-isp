<?php
require_once __DIR__ . '/../config.php';
session_start();
if(!isset($_SESSION['admin_id'])){ header('Location: login.php'); exit; }

$message=''; $errors=[];

// Add bundle
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add'])){
  $name = trim($_POST['name']);
  $desc = trim($_POST['description']);
  $items = $_POST['items'] ?? []; // array of "package:ID" or "addon:ID"
  $itemsArr = [];
  foreach($items as $it){
    [$type,$id] = explode(':', $it);
    $itemsArr[] = ['type'=>$type,'id'=>intval($id)];
  }
  $price = $_POST['price'] !== '' ? (float)$_POST['price'] : null;
  $discount = $_POST['discount'] !== '' ? (int)$_POST['discount'] : null;
  $visible = isset($_POST['visible']) ? 1 : 0;
  $stmt = $pdo->prepare("INSERT INTO bundles (name,description,items,price,discount_percent,visible) VALUES (?,?,?,?,?,?)");
  $stmt->execute([$name,$desc,json_encode($itemsArr),$price,$discount,$visible]);
  $message = 'Bundle added.';
}

// Delete
if(isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])){
  $pdo->prepare("DELETE FROM bundles WHERE id=?")->execute([(int)$_GET['delete']]);
  $message = 'Deleted.';
}

$packages = $pdo->query("SELECT id,name FROM packages WHERE visible=1 ORDER BY order_index ASC")->fetchAll(PDO::FETCH_ASSOC);
$addons = $pdo->query("SELECT id,name,price FROM addons WHERE visible=1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$bundles = $pdo->query("SELECT * FROM bundles ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include __DIR__ . '/sidebar.php'; include __DIR__ . '/header.php'; ?>
<div class="main">
  <h2>Manage Bundles</h2>
  <?php if($message): ?><div class="message"><?= e($message) ?></div><?php endif; ?>
  <div class="card">
    <h3>Create Bundle</h3>
    <form method="post">
      <label>Name<input type="text" name="name" required></label>
      <label>Description<textarea name="description"></textarea></label>
      <label>Items (select packages and addons)</label>
      <select name="items[]" multiple size="8">
        <?php foreach($packages as $p): ?><option value="package:<?= e($p['id']) ?>">Package: <?= e($p['name']) ?></option><?php endforeach; ?>
        <?php foreach($addons as $a): ?><option value="addon:<?= e($a['id']) ?>">Add-on: <?= e($a['name']) ?> (<?= e($a['price']) ?>)</option><?php endforeach; ?>
      </select>
      <label>Bundle Price<input type="number" step="0.01" name="price"></label>
      <label>Discount Percent<input type="number" name="discount"></label>
      <label><input type="checkbox" name="visible" checked> Visible</label>
      <button class="btn" type="submit" name="add">Add Bundle</button>
    </form>
  </div>

  <div class="card" style="margin-top:12px;">
    <h3>All Bundles</h3>
    <table class="table">
      <thead><tr><th>ID</th><th>Name</th><th>Items</th><th>Price</th><th>Discount</th><th>Visible</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($bundles as $b): $items = json_decode((string)($b['items'] ?? ''), true) ?: []; ?>
        <tr>
          <td><?= e($b['id']) ?></td>
          <td><?= e($b['name']) ?></td>
          <td>
            <?php
              $labels = [];
              foreach($items as $it){
                if($it['type']==='package'){
                  $p = $pdo->prepare("SELECT name FROM packages WHERE id=?"); $p->execute([$it['id']]); $labels[] = $p->fetchColumn() ?: "Package {$it['id']}";
                } else {
                  $a = $pdo->prepare("SELECT name FROM addons WHERE id=?"); $a->execute([$it['id']]); $labels[] = $a->fetchColumn() ?: "Addon {$it['id']}";
                }
              }
              echo e(implode(', ', $labels));
            ?>
          </td>
          <td><?= e($b['price']) ?></td>
          <td><?= e($b['discount_percent']) ?>%</td>
          <td><?= $b['visible'] ? 'Yes' : 'No' ?></td>
          <td><a href="?delete=<?= e($b['id']) ?>" onclick="return confirm('Delete?')">Delete</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
