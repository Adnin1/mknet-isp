<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Community Hub';
include __DIR__ . '/../includes/header.php';

// Handle new post
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['new_post'])){
  $title = trim($_POST['title']);
  $content = trim($_POST['content']);
  $user = trim($_POST['user_name']);
  if($title && $content && $user){
    $stmt = $pdo->prepare("INSERT INTO community_posts (user_name,title,content,badge) VALUES (?,?,?,?)");
    $stmt->execute([$user, $title, $content, 'Member']);
  }
}

// Handle new comment
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['new_comment'])){
  $comment = trim($_POST['comment']);
  $user = trim($_POST['user_name']);
  $postId = intval($_POST['post_id']);
  if($comment && $user && $postId){
    $stmt = $pdo->prepare("INSERT INTO community_comments (post_id,user_name,comment) VALUES (?,?,?)");
    $stmt->execute([$postId, $user, $comment]);
  }
}

// Fetch posts
try {
  $posts = $pdo->query("SELECT * FROM community_posts ORDER BY created_at DESC")->fetchAll();
} catch (Throwable $e) {
  $posts = [];
}
?>
<h2>Community & Feedback Hub</h2>

<!-- New Post Form -->
<form method="post" class="form-card">
  <input type="text" name="user_name" placeholder="Your Name" required>
  <input type="text" name="title" placeholder="Post Title" required>
  <textarea name="content" placeholder="Write your feedback or post..." required></textarea>
  <button type="submit" name="new_post">Publish Post</button>
</form>

<hr>

<!-- Display Posts -->
<?php foreach($posts as $p): ?>
<div class="card">
  <h3><?= htmlspecialchars($p['title']) ?></h3>
  <p><strong><?= htmlspecialchars($p['user_name']) ?> (<?= $p['badge'] ?>)</strong> — <?= $p['created_at'] ?></p>
  <p><?= nl2br(htmlspecialchars($p['content'])) ?></p>

  <!-- Fetch comments -->
  <?php
  $stmt = $pdo->prepare("SELECT * FROM community_comments WHERE post_id=? ORDER BY created_at ASC");
  $stmt->execute([$p['id']]);
  $comments = $stmt->fetchAll();
  ?>
  <h4>Comments:</h4>
  <?php foreach($comments as $c): ?>
    <p><strong><?= htmlspecialchars($c['user_name']) ?>:</strong> <?= nl2br(htmlspecialchars($c['comment'])) ?> (<?= $c['created_at'] ?>)</p>
  <?php endforeach; ?>

  <!-- New Comment Form -->
  <form method="post" class="form-card">
    <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
    <input type="text" name="user_name" placeholder="Your Name" required>
    <textarea name="comment" placeholder="Write a comment..." required></textarea>
    <button type="submit" name="new_comment">Add Comment</button>
  </form>
</div>
<hr>
<?php endforeach; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
