<?php
$name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!doctype html><html><head><meta charset="utf-8"><link rel="stylesheet" href="/assets/style.css"></head><body class="container">
<div class="card success">
  <h3>Thanks, <?= $name ?: 'Guest' ?>!</h3>
  <p>Your inquiry was received. Our team will contact you within 24 hours (demo).</p>
  <a class="btn" href="/mknet/">Back to Home</a>
</div>
</body></html>