<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

// Fetch all about entries
$stmt = $pdo->query("SELECT * FROM about ORDER BY id DESC");
$abouts = $stmt->fetchAll();

$message = "";
if($_SERVER['REQUEST_METHOD']=='POST'){
  if (!csrf_verify()) { http_response_code(403); die('Security check failed - please refresh the page and try again.'); }
  // Handle image upload
  $imagePath = $_POST['old_image'] ?? "";
  if(!empty($_FILES['image']['name'])){
    $targetDir = __DIR__ . "/../assets/images/about/";
    if(!is_dir($targetDir)) mkdir($targetDir,0777,true);
    $fileName = time() . "_" . basename($_FILES['image']['name']);
    $targetFile = $targetDir . $fileName;
    if(move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)){
      $imagePath = "/assets/images/about/" . $fileName;
    }
  }

  if(isset($_POST['add'])){
    $stmt = $pdo->prepare("INSERT INTO about (title, content, image) VALUES (?,?,?)");
    $stmt->execute([$_POST['title'], $_POST['content'], $imagePath]);
    $message = "New About section added!";
  }
  if(isset($_POST['update'])){
    $stmt = $pdo->prepare("UPDATE about SET title=?, content=?, image=? WHERE id=?");
    $stmt->execute([$_POST['title'], $_POST['content'], $imagePath, $_POST['id']]);
    $message = "About section updated!";
  }
}
if(isset($_GET['delete'])){
  if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) { http_response_code(403); die('Invalid or expired link - please refresh the page and try again.'); }
  $pdo->prepare("DELETE FROM about WHERE id=?")->execute([$_GET['delete']]);
  $message = "About section deleted!";
}
?>
<link rel="stylesheet" href="style.css">

<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Manage About Section</h2>
  <?php if(!empty($message)): ?><p style="color:green;"><?= $message ?></p><?php endif; ?>

  <h3>Add New</h3>
  <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
    <input type="text" name="title" placeholder="Title" required>
    <textarea name="content" rows="4" placeholder="Content" required></textarea>
    <input type="file" name="image">
    <button type="submit" name="add">Add</button>
  </form>

  <h3>Existing Sections</h3>
  <table>
    <tr><th>Title</th><th>Content</th><th>Image</th><th>Actions</th></tr>
    <?php foreach($abouts as $a): ?>
    <tr>
      <td><?= $a['title'] ?></td>
      <td><?= substr($a['content'],0,50) ?>...</td>
      <td>
        <?php if(!empty($a['image'])): ?>
          <img src="<?= $a['image'] ?>" style="width:50px;">
        <?php endif; ?>
      </td>
      <td>
        <form method="post" enctype="multipart/form-data" style="display:inline;">
      <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <input type="hidden" name="old_image" value="<?= $a['image'] ?>">
          <input type="text" name="title" value="<?= $a['title'] ?>">
          <textarea name="content"><?= $a['content'] ?></textarea>
          <input type="file" name="image">
          <button type="submit" name="update">Update</button>
        </form>
        <a href="manage_about.php?delete=<?= $a['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete this section?')">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include __DIR__ . '/footer.php'; ?>
