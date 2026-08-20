<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

$message = "";
if($_SERVER['REQUEST_METHOD']=='POST'){
  if (!csrf_verify()) { http_response_code(403); die('Security check failed - please refresh the page and try again.'); }
  if(isset($_POST['add'])){
    $stmt = $pdo->prepare("INSERT INTO chatbot_rules (keyword, reply) VALUES (?,?)");
    $stmt->execute([$_POST['keyword'], $_POST['reply']]);
    $message = "New rule added!";
  }
  if(isset($_POST['update'])){
    $stmt = $pdo->prepare("UPDATE chatbot_rules SET keyword=?, reply=? WHERE id=?");
    $stmt->execute([$_POST['keyword'], $_POST['reply'], $_POST['id']]);
    $message = "Rule updated!";
  }
}
if(isset($_GET['delete'])){
  if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) { http_response_code(403); die('Invalid or expired link - please refresh the page and try again.'); }
  $pdo->prepare("DELETE FROM chatbot_rules WHERE id=?")->execute([$_GET['delete']]);
  $message = "Rule deleted!";
}

$rules = $pdo->query("SELECT * FROM chatbot_rules ORDER BY created_at DESC")->fetchAll();
?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Manage Chatbot Rules</h2>
  <?php if(!empty($message)): ?><p style="color:green;"><?= $message ?></p><?php endif; ?>

  <h3>Add New Rule</h3>
  <form method="post">
      <?= csrf_field() ?>
    <input type="text" name="keyword" placeholder="Keyword (e.g. package)" required>
    <textarea name="reply" placeholder="Bot reply" required></textarea>
    <button type="submit" name="add">Add</button>
  </form>

  <h3>Existing Rules</h3>
  <table>
    <tr><th>Keyword</th><th>Reply</th><th>Actions</th></tr>
    <?php foreach($rules as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['keyword']) ?></td>
      <td><?= htmlspecialchars($r['reply']) ?></td>
      <td>
        <form method="post" style="display:inline;">
      <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <input type="text" name="keyword" value="<?= $r['keyword'] ?>">
          <textarea name="reply"><?= $r['reply'] ?></textarea>
          <button type="submit" name="update">Update</button>
        </form>
        <a href="manage_chatbot_rules.php?delete=<?= $r['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete this rule?')">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include __DIR__ . '/footer.php'; ?>
