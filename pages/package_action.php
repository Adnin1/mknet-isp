<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!SessionManager::touch($pdo)) { header("Location: login.php"); exit; }

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    header("Location: dashboard.php?error=" . urlencode('Invalid request.'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// ---- Pause / Resume connection ----
if (isset($_POST['pause_connection'])) {
    if ($user['account_status'] === 'paused') {
        header("Location: dashboard.php?notice=" . urlencode('Your connection is already paused.'));
        exit;
    }
    $pdo->prepare("UPDATE users SET account_status='paused', paused_at=NOW() WHERE id=?")->execute([$userId]);
    Notifier::send($pdo, $userId, 'Connection Paused', 'Your internet connection has been paused at your request. Resume anytime from your dashboard.', 'warning');
    header("Location: dashboard.php?notice=" . urlencode('Your connection has been paused.'));
    exit;
}

if (isset($_POST['resume_connection'])) {
    $pdo->prepare("UPDATE users SET account_status='active', paused_at=NULL WHERE id=?")->execute([$userId]);
    Notifier::send($pdo, $userId, 'Connection Resumed', 'Your internet connection has been resumed. Welcome back!', 'success');
    header("Location: dashboard.php?notice=" . urlencode('Your connection has been resumed.'));
    exit;
}

// ---- Upgrade / Downgrade package ----
if (isset($_POST['change_package'])) {
    $newPackageId = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);

    $p = $pdo->prepare("SELECT * FROM packages WHERE id=? AND visible=1");
    $p->execute([$newPackageId]);
    $newPackage = $p->fetch();

    if (!$newPackage) {
        header("Location: dashboard.php?error=" . urlencode('Selected package was not found.'));
        exit;
    }
    if ((int)$newPackage['id'] === (int)$user['package_id']) {
        header("Location: dashboard.php?notice=" . urlencode('You are already on this package.'));
        exit;
    }

    $isUpgrade = (int)$newPackage['speed_mbps'] > (int)$user['package_speed_mbps'];
    $action = $isUpgrade ? 'upgrade' : 'downgrade';

    // Create an invoice for the plan-change fee difference (proration
    // kept simple and transparent: full price of the new package,
    // matching how paybill.php already creates package invoices).
    $stmt = $pdo->prepare(
        "INSERT INTO invoices (user_id, package_id, amount, currency, status, meta, created_at) VALUES (?,?,?,?,?,?,NOW())"
    );
    $stmt->execute([$userId, $newPackage['id'], $newPackage['price_bdt'], 'BDT', 'pending', json_encode(['type' => $action, 'from_package_id' => $user['package_id']])]);
    $invoiceId = (int)$pdo->lastInsertId();

    // Apply the change immediately (common ISP practice: change takes
    // effect now, invoice settles the billing difference).
    $pdo->prepare("UPDATE users SET package_id=?, package_speed_mbps=? WHERE id=?")
        ->execute([$newPackage['id'], $newPackage['speed_mbps'], $userId]);

    Notifier::send(
        $pdo, $userId,
        ucfirst($action) . ' requested',
        "Your package has been " . ($isUpgrade ? 'upgraded' : 'downgraded') . " to {$newPackage['name']} ({$newPackage['speed_mbps']} Mbps). An invoice of " . number_format((float)$newPackage['price_bdt'], 2) . " BDT has been generated.",
        $action,
        "/pages/payment_redirect.php?invoice_id=" . $invoiceId
    );

    header("Location: /pages/payment_redirect.php?invoice_id={$invoiceId}");
    exit;
}

header("Location: dashboard.php");
exit;
