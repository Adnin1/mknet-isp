<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'News & Events';
include __DIR__ . '/../includes/header.php';

// Category filter
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM news_events WHERE 1=1";
$params = [];

if($category){
  $sql .= " AND category=?";
  $params[] = $category;
}
if($search){
  $sql .= " AND (title LIKE ? OR content LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

$sql .= " ORDER BY event_date DESC";
try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll();
} catch (Throwable $e) {
  $rows = [];
}
?>
<h2>News & Events</h2>

<!-- Search + Category Filter -->
<div class="filter-menu">
  <form method="get" style="display:inline;">
    <input type="text" name="search" placeholder="Search news..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
  </form>
  <a href="news.php" <?= $category==''?'style="font-weight:bold"':'' ?>>All</a>
  <a href="news.php?category=Announcements" <?= $category=='Announcements'?'style="font-weight:bold"':'' ?>>Announcements</a>
  <a href="news.php?category=Offers" <?= $category=='Offers'?'style="font-weight:bold"':'' ?>>Offers</a>
  <a href="news.php?category=Community" <?= $category=='Community'?'style="font-weight:bold"':'' ?>>Community</a>
  <a href="news.php?category=General" <?= $category=='General'?'style="font-weight:bold"':'' ?>>General</a>
</div>

<div class="grid">
<?php if ($rows): ?>
  <?php foreach ($rows as $n): ?>
    <div class="card">
      <?php 
        $img = !empty($n['image']) ? str_replace('/mknet/', '/', $n['image']) : '/assets/images/news/default.jpg';
      ?>
      <img src="<?= $img ?>" alt="<?= htmlspecialchars($n['title']) ?>" style="width:100%;border-radius:8px;margin-bottom:10px" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=500';">
      <h3><?= htmlspecialchars($n['title']) ?></h3>
      <p class="tiny"><?= htmlspecialchars($n['event_date']) ?> — <em><?= htmlspecialchars($n['category']) ?></em></p>
      <p><?= nl2br(htmlspecialchars($n['content'])) ?></p>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card">No news found.</div>
<?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
