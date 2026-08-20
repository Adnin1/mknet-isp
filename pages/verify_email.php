<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Verify Email — MK Net';

$uid = filter_input(INPUT_GET, 'uid', FILTER_VALIDATE_INT);
$token = $_GET['token'] ?? '';
$status = 'invalid';

if ($uid && $token) {
    $stmt = $pdo->prepare(
        "SELECT * FROM email_verifications WHERE user_id=? AND verified_at IS NULL AND expires_at >= NOW() ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([$uid]);
    $row = $stmt->fetch();

    if ($row && hash_equals($row['token_hash'], hash('sha256', $token))) {
        $pdo->prepare("UPDATE email_verifications SET verified_at = NOW() WHERE id=?")->execute([$row['id']]);
        $pdo->prepare("UPDATE users SET email_verified_at = NOW() WHERE id=?")->execute([$uid]);
        Notifier::send($pdo, (int)$uid, 'Email verified', 'Your email address has been successfully verified.', 'success');
        $status = 'ok';
    } elseif ($row) {
        $status = 'expired';
    }
}

include __DIR__ . '/../includes/header.php';
?>
<main class="container">
<div class="card" style="max-width:480px;margin:60px auto;text-align:center;">
  <?php if ($status === 'ok'): ?>
    <h2 style="color:#0a6;">✅ Email Verified!</h2>
    <p>Your account is now fully verified. You can log in and start using MK Net.</p>
    <p><a class="btn primary" href="login.php">Go to Login</a></p>
  <?php elseif ($status === 'expired'): ?>
    <h2 style="color:#b00020;">Link Expired</h2>
    <p>This verification link has expired. Please log in and request a new one from your account settings.</p>
    <p><a class="btn" href="login.php">Go to Login</a></p>
  <?php else: ?>
    <h2 style="color:#b00020;">Invalid Link</h2>
    <p>This verification link is invalid or has already been used.</p>
    <p><a class="btn" href="login.php">Go to Login</a></p>
  <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
