<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'About MK Net';
include __DIR__ . '/../includes/header.php';


try {
  $stmt = $pdo->query("SELECT * FROM about ORDER BY id DESC");
  $abouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) { $abouts = []; }

try {
  $stmt2 = $pdo->query("SELECT * FROM team ORDER BY id ASC");
  $team = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) { $team = []; }
?>
<main class="container">
  <h2>About Us</h2>
  <div class="grid">
    <?php if (!empty($abouts)): foreach ($abouts as $a): ?>
      <div class="card">
        <?php $img = !empty($a['image']) ? str_replace('/mknet/', '/', $a['image']) : '/assets/images/default-about.png'; ?>
        <img src="<?= e($img) ?>" alt="<?= e($a['title']) ?>" style="width:100%;border-radius:8px;margin-bottom:10px">
        <h3><?= e($a['title']) ?></h3>
        <p><?= nl2br(e($a['content'])) ?></p>
      </div>
    <?php endforeach; else: ?>
      <p>No About content available. Please add from Admin Panel.</p>
    <?php endif; ?>
  </div>

  <h2>Our Team</h2>
  <div class="grid">
    <?php if (!empty($team)): foreach ($team as $t): ?>
      <div class="card">
        <?php $photo = !empty($t['photo']) ? str_replace('/mknet/', '/', $t['photo']) : '/assets/images/default-team.png'; ?>
        <img src="<?= e($photo) ?>" alt="<?= e($t['name']) ?>" style="width:100%;border-radius:8px;margin-bottom:10px">
        <h3><?= e($t['name']) ?></h3>
        <p><strong><?= e($t['designation'] ?? $t['role'] ?? '') ?></strong></p>
        <p><?= nl2br(e($t['bio'] ?? '')) ?></p>
        <p>📧 <?= !empty($t['email']) ? e($t['email']) : 'N/A' ?> | 📞 <?= !empty($t['phone']) ? e($t['phone']) : 'N/A' ?></p>
      </div>
    <?php endforeach; else: ?>
      <p>No Team members available. Please add from Admin Panel.</p>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
