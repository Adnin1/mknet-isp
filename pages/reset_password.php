<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Reset Password — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();

$email = $_GET['email'] ?? $_POST['email'] ?? '';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$done = false;

function findValidReset(PDO $pdo, string $email, string $token) {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE (username=? OR token=?) AND expires_at >= NOW() ORDER BY id DESC LIMIT 5");
    $stmt->execute([$email, $token]);
    foreach ($stmt->fetchAll() as $row) {
        if ($row['token'] === $token || hash_equals($row['token'], hash('sha256', $token))) {
            return $row;
        }
    }
    return null;
}

$validReset = ($email && $token) ? findValidReset($pdo, $email, $token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = "Session expired, please try again.";
    } elseif (!$validReset) {
        $error = "This reset link is invalid or has expired.";
    } else {
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');

        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password=? WHERE email=?")->execute([$hash, $email]);
            // Invalidate all outstanding reset tokens + all active sessions for this account (security best practice)
            $pdo->prepare("DELETE FROM password_resets WHERE username=?")->execute([$email]);

            $u = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $u->execute([$email]);
            if ($userRow = $u->fetch()) {
                $pdo->prepare("UPDATE user_sessions SET revoked_at=NOW() WHERE user_id=? AND revoked_at IS NULL")->execute([$userRow['id']]);
                Notifier::send($pdo, (int)$userRow['id'], 'Password changed', 'Your password was just reset. If this wasn\'t you, contact support immediately.', 'security');
            }
            $done = true;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<main class="container">
<div class="card" style="max-width:420px;margin:40px auto;">
  <h2>Reset Password</h2>

  <?php if ($done): ?>
    <p style="color:#0a6;">Your password has been reset successfully. You've been logged out of all devices for security.</p>
    <p><a class="btn primary" href="login.php">Login now</a></p>
  <?php elseif (!$validReset): ?>
    <p style="color:#b00020;">This password reset link is invalid or has expired.</p>
    <p><a href="forgot_password.php">Request a new link</a></p>
  <?php else: ?>
    <?php if ($error): ?><p style="color:#b00020;"><?= h($error) ?></p><?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="email" value="<?= h($email) ?>">
      <input type="hidden" name="token" value="<?= h($token) ?>">
      <input type="password" name="password" placeholder="New password (min 6 characters)" required minlength="6" style="display:block;width:100%;margin:8px 0;padding:8px;">
      <input type="password" name="password_confirm" placeholder="Confirm new password" required minlength="6" style="display:block;width:100%;margin:8px 0;padding:8px;">
      <button type="submit" class="btn primary" style="width:100%;">Reset Password</button>
    </form>
  <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
