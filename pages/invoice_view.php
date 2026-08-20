<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!SessionManager::touch($pdo)) { header("Location: login.php"); exit; }

$userId = (int)$_SESSION['user_id'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { die("Invalid invoice."); }

$stmt = $pdo->prepare(
    "SELECT i.*, u.email, u.full_name, p.name as package_name, b.name as bundle_name
     FROM invoices i
     JOIN users u ON i.user_id = u.id
     LEFT JOIN packages p ON i.package_id = p.id
     LEFT JOIN bundles b ON i.bundle_id = b.id
     WHERE i.id=? AND i.user_id=?"
);
$stmt->execute([$id, $userId]);
$inv = $stmt->fetch();

if (!$inv) { die("Invoice not found."); }

$meta = json_decode((string)($inv['meta'] ?? ''), true) ?: [];
$itemLabel = $inv['package_name'] ?? $inv['bundle_name'] ?? ($meta['type'] ?? 'Service');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Invoice #<?= h($inv['id']) ?></title>
<link rel="stylesheet" href="/assets/style.css">
<style>
  .invoice-box{max-width:600px;margin:30px auto;padding:24px;border:1px solid #ddd;border-radius:8px;}
  .invoice-box table{width:100%;border-collapse:collapse;margin-top:16px;}
  .invoice-box th,.invoice-box td{padding:8px;border-bottom:1px solid #eee;text-align:left;}
  .status-paid{color:#0a6;font-weight:bold;}
  .status-pending{color:#c90;font-weight:bold;}
  @media print { .no-print{display:none;} }
</style>
</head>
<body>
<div class="invoice-box">
  <h2>MK Net — Invoice #<?= h($inv['id']) ?></h2>
  <p>Billed to: <?= h($inv['full_name'] ?? $inv['email']) ?> (<?= h($inv['email']) ?>)</p>
  <p>Date: <?= h($inv['created_at']) ?></p>
  <table>
    <tr><th>Item</th><th>Amount</th></tr>
    <tr><td><?= h($itemLabel) ?></td><td><?= h($inv['amount']) ?> <?= h($inv['currency'] ?? 'BDT') ?></td></tr>
  </table>
  <p style="margin-top:16px;">Status: <span class="status-<?= h($inv['status']) ?>"><?= strtoupper(h($inv['status'])) ?></span></p>
  <div class="no-print" style="margin-top:20px;">
    <button onclick="window.print()" class="btn">🖨️ Print / Save as PDF</button>
    <a class="btn" href="dashboard.php">← Back to Dashboard</a>
  </div>
</div>
</body></html>
