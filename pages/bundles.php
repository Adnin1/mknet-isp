<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Bundles';
include __DIR__ . '/../includes/header.php';

function money($v){ return number_format((float)$v, 2); }

try {
  $bundles = $pdo->query("SELECT * FROM bundles WHERE visible=1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) {
  $bundles = [];
}
?>
<link rel="stylesheet" href="/assets/css/packages.css">

<main class="container">
  <h1>Bundles & Offers</h1>
  <p>Save more by choosing curated bundles — packages and add‑ons combined for better value.</p>

  <section class="packages-grid">
    <?php if(empty($bundles)): ?>
      <div class="card">No bundles available right now.</div>
    <?php else: foreach($bundles as $b):
      $items = json_decode((string)($b['items'] ?? ''), true) ?: [];
      // compute separate total for display
      $separateTotal = 0.0;
      $labels = [];
      foreach($items as $it){
        if($it['type'] === 'package'){
          $p = $pdo->prepare("SELECT name, price_bdt FROM packages WHERE id=? LIMIT 1");
          $p->execute([$it['id']]); $pp = $p->fetch(PDO::FETCH_ASSOC);
          if($pp){ $labels[] = $pp['name']; $separateTotal += (float)$pp['price_bdt']; }
        } else {
          $a = $pdo->prepare("SELECT name, price FROM addons WHERE id=? LIMIT 1");
          $a->execute([$it['id']]); $aa = $a->fetch(PDO::FETCH_ASSOC);
          if($aa){ $labels[] = $aa['name']; $separateTotal += (float)$aa['price']; }
        }
      }
      $bundlePrice = $b['price'] !== null ? (float)$b['price'] : null;
      $discount = $b['discount_percent'] !== null ? (int)$b['discount_percent'] : null;
      if($bundlePrice === null && $discount !== null){
        $bundlePrice = $separateTotal * (1 - $discount/100);
      } elseif($bundlePrice === null){
        $bundlePrice = $separateTotal;
      }
      $savings = max(0, $separateTotal - $bundlePrice);
    ?>
    <article class="card">
      <div class="card-body">
        <h3><?= e($b['name']) ?></h3>
        <p class="desc"><?= nl2br(e($b['description'])) ?></p>

        <ul class="features">
          <?php foreach($labels as $lab): ?><li><?= e($lab) ?></li><?php endforeach; ?>
        </ul>

        <div class="price">
          <?php if($savings > 0): ?>
            <span class="price-old"><?= money($separateTotal) ?> BDT</span>
            <span class="price-now"><?= money($bundlePrice) ?> BDT</span>
            <div class="badge">You save <?= money($savings) ?> BDT</div>
          <?php else: ?>
            <span class="price-now"><?= money($bundlePrice) ?> BDT</span>
          <?php endif; ?>
        </div>

        <div class="actions">
          <a class="btn primary" href="/pages/checkout.php?bundle_id=<?= e($b['id']) ?>">Buy Bundle</a>
          <button class="btn outline compare-btn" data-bundle="<?= e($b['id']) ?>">Compare</button>
        </div>
      </div>
    </article>
    <?php endforeach; endif; ?>
  </section>
</main>

<script>
  // small compare placeholder for bundles (client-side)
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.compare-btn').forEach(btn=>{
      btn.addEventListener('click', function(){
        alert('Bundle compare coming soon.');
      });
    });
  });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
