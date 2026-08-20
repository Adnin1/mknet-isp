<?php $pageTitle='Client Reviews'; require __DIR__.'/../config.php'; include __DIR__.'/../includes/header.php';
try {
  $rows=$pdo->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll();
} catch (Throwable $e) { $rows = []; }
?>
<h2>Client Reviews</h2>
<div class="grid">
<?php if(!empty($rows)): foreach($rows as $r): ?>
  <div class="card">
    <?php $avatar = "/assets/images/avatars/".strtolower(explode(" ", $r['user_name'])[0]).".jpg"; ?>
<img src="<?=$avatar?>" alt="<?=h($r['user_name'])?>" style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin-bottom:8px" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?=urlencode($r['user_name'])?>&background=6366f1&color=fff';">
    <h3><?=h($r['user_name'])?></h3>
    <p><b>Rating:</b> <?=h($r['rating'])?>/5</p>
    <p><?=h($r['comment'])?></p>
    <p class="tiny"><?=h($r['created_at'])?></p>
  </div>
<?php endforeach; else: ?>
  <p>No customer reviews available yet.</p>
<?php endif; ?>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>