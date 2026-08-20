<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

// Delete post
if(isset($_GET['delete_post'])){
  if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) { http_response_code(403); die('Invalid or expired link - please refresh the page and try again.'); }
  $pdo->prepare("DELETE FROM community_posts WHERE id=?")->execute([$_GET['delete_post']]);
}

// Delete comment
if(isset($_GET['delete_comment'])){
  if (!hash_equals(csrf_token(), (string)($_GET['csrf_token'] ?? ''))) { http_response_code(403); die('Invalid or expired link - please refresh the page and try again.'); }
  $pdo->prepare("DELETE FROM community_comments WHERE id=?")->execute([$_GET['delete_comment']]);
}

$posts = $pdo->query("SELECT * FROM community_posts ORDER BY created_at DESC")->fetchAll();
?>
<link rel="stylesheet" href="style.css">

<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Manage Community Posts</h2>
  <?php foreach($posts as $p): ?>
    <div class="card">
      <h3><?= htmlspecialchars($p['title']) ?></h3>
      <p><strong><?= htmlspecialchars($p['user_name']) ?> (<?= $p['badge'] ?>)</strong> — <?= $p['created_at'] ?></p>
      <p><?= nl2br(htmlspecialchars($p['content'])) ?></p>
      <a href="manage_community.php?delete_post=<?= $p['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete this post?')">Delete Post</a>

      <!-- Comments -->
      <?php
      $stmt = $pdo->prepare("SELECT * FROM community_comments WHERE post_id=? ORDER BY created_at ASC");
      $stmt->execute([$p['id']]);
      $comments = $stmt->fetchAll();
      ?>
      <h4>Comments:</h4>
      <?php foreach($comments as $c): ?>
        <p><strong><?= htmlspecialchars($c['user_name']) ?>:</strong> <?= nl2br(htmlspecialchars($c['comment'])) ?> (<?= $c['created_at'] ?>)
        <a href="manage_community.php?delete_comment=<?= $c['id'] ?>&csrf_token=<?= urlencode(csrf_token()) ?>" onclick="return confirm('Delete this comment?')">Delete</a></p>
      <?php endforeach; ?>
    </div>
    <hr>
  <?php endforeach; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
