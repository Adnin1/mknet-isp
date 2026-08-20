<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Verify Phone — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!SessionManager::touch($pdo)) { header("Location: login.php"); exit; }

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$message = '';
$error = '';
$codeSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = "Session expired, please try again.";
    } elseif (isset($_POST['send_code'])) {
        try {
            Otp::issue($pdo, $userId, 'phone', 'phone_verify', $user['phone']);
            $codeSent = true;
            $message = "A verification code has been sent to " . h($user['phone']) . ".";
        } catch (RuntimeException $ex) {
            $error = $ex->getMessage();
        }
    } elseif (isset($_POST['verify_code'])) {
        $code = trim((string)($_POST['code'] ?? ''));
        if (Otp::verify($pdo, 'phone_verify', $user['phone'], $code)) {
            $pdo->prepare("UPDATE users SET phone_verified_at = NOW() WHERE id=?")->execute([$userId]);
            Notifier::send($pdo, $userId, 'Phone verified', 'Your phone number has been successfully verified.', 'success');
            header("Location: dashboard.php?phone_verified=1");
            exit;
        } else {
            $error = "Invalid or expired code. Please try again.";
            $codeSent = true;
        }
    }
}

include __DIR__ . '/../includes/header.php';
$alreadyVerified = !empty($user['phone_verified_at']);
$devNote = Services::isDevMode();
?>
<main class="container">
<div class="card" style="max-width:420px;margin:40px auto;">
  <h2>Verify Phone Number</h2>

  <?php if ($alreadyVerified): ?>
    <p style="color:#0a6;">✅ Your phone number (<?= h($user['phone']) ?>) is already verified.</p>
    <p><a href="dashboard.php">Back to dashboard</a></p>
  <?php else: ?>
    <?php if ($devNote): ?>
      <p class="small-note" style="background:#fff8e1;padding:8px;border-radius:6px;">SMS isn't configured yet (dev mode) — the code is written to <code>storage/logs/sms.log</code> instead of being texted. Check that file to test this flow.</p>
    <?php endif; ?>
    <?php if ($message): ?><p style="color:#0a6;"><?= h($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p style="color:#b00020;"><?= h($error) ?></p><?php endif; ?>

    <p>Phone on file: <b><?= h($user['phone']) ?></b></p>

    <?php if (!$codeSent): ?>
      <form method="post">
        <?= csrf_field() ?>
        <button type="submit" name="send_code" class="btn primary" style="width:100%;">Send Verification Code</button>
      </form>
    <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="text" name="code" placeholder="6-digit code" required autofocus
               style="display:block;width:100%;margin:8px 0;padding:8px;font-size:20px;letter-spacing:4px;text-align:center;">
        <button type="submit" name="verify_code" class="btn primary" style="width:100%;">Verify</button>
      </form>
      <form method="post" style="margin-top:8px;">
        <?= csrf_field() ?>
        <button type="submit" name="send_code" class="btn" style="width:100%;">Resend Code</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
