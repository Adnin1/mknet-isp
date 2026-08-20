<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Check Availability';
include __DIR__ . '/../includes/header.php';

$found = null;
$packages = [];
$area = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
  $area = trim($_POST['area'] ?? '');
  if($area !== ''){
    // try postal_code match first
    $stmt = $pdo->prepare("SELECT * FROM coverage WHERE postal_code = ? OR area_name LIKE ? LIMIT 1");
    $stmt->execute([$area, "%{$area}%"]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row){
      $found = $row;
      $pkgIds = json_decode((string)($row['packages'] ?? ''), true) ?: [];
      if(!empty($pkgIds)){
        $in = implode(',', array_map('intval', $pkgIds));
        $packages = $pdo->query("SELECT * FROM packages WHERE id IN ({$in}) AND visible=1 ORDER BY order_index ASC")->fetchAll(PDO::FETCH_ASSOC);
      }
    } else {
      $found = false;
    }
  }
}
?>
<link rel="stylesheet" href="/assets/css/packages.css">
<main class="container">
  <h2>Check Service Availability</h2>
  <div class="card">
    <form method="post">
      <label>Enter your postal code or area name</label>
      <input type="text" name="area" value="<?= e($area) ?>" placeholder="e.g., 5800 or Bogra" required>
      <button class="btn" type="submit">Check</button>
    </form>
  </div>

  <?php if($found === false): ?>
    <div class="card">
      <h3>No coverage found</h3>
      <p>We currently do not show service for that area. Leave your contact and we'll notify you when available.</p>
      <form method="post" action="/pages/notify_me.php">
        <input type="hidden" name="area" value="<?= e($area) ?>">
        <label>Your name</label><input type="text" name="name" required>
        <label>Your phone or email</label><input type="text" name="contact" required>
        <button class="btn" type="submit">Notify Me</button>
      </form>
    </div>
  <?php elseif(is_array($found)): ?>
    <div class="card">
      <h3>Service available in <?= e($found['area_name'] ?: $found['postal_code'] ?: 'your area') ?></h3>
      <p>Packages available for your area:</p>
      <div class="packages-grid">
        <?php if(empty($packages)): ?>
          <div class="card">No packages assigned to this area yet.</div>
        <?php else: foreach($packages as $p): 
          $slug = $p['slug'] ?: strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$p['name']), '-'));
          $img = "/assets/images/packages/{$slug}.jpg";
          if(!file_exists($_SERVER['DOCUMENT_ROOT'].$img)) $img = "/assets/images/packages/default.jpg";
        ?>
          <article class="card">
            <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" class="package-image">
            <div class="card-body">
              <h3><?= e($p['name']) ?></h3>
              <div class="meta"><?= e($p['speed_mbps']) ?> Mbps • <?= e($p['billing_cycle'] ?: 'Monthly') ?></div>
              <div class="price"><span class="price-now"><?= number_format((float)$p['price_bdt'],2) ?> BDT</span></div>
              <p><?= nl2br(e($p['description'])) ?></p>
              <a class="btn" href="/pages/checkout.php?package_id=<?= e($p['id']) ?>">Subscribe</a>
            </div>
          </article>
        <?php endforeach; endif; ?>
      </div>
    </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
