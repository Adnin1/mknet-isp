<?php $pageTitle='Employees'; require __DIR__.'/../config.php'; include __DIR__.'/../includes/header.php';
$rows=$pdo->query("SELECT * FROM employees")->fetchAll(); ?>
<h2>Our team</h2>
<div class="grid">
<?php foreach($rows as $e): ?>
  <div class="card">
    <img src="/<?=h($e['photo'])?>" alt="<?=h($e['name'])?>" style="width:100%;border-radius:8px;margin-bottom:10px">
    <h3><?=h($e['name'])?></h3>
    <p><b>Role:</b> <?=h($e['role'])?></p>
    <p><b>Email:</b> <?=h($e['email'])?></p>
  </div>
<?php endforeach; ?>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>