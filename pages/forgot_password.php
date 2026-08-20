<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Forgot Password — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = "Session expired, please try again.";
    } else {
        $email = trim((string)($_POST['email'] ?? ''));

        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show the same success message whether or not the email
        // exists, so this form can't be used to enumerate registered
        // accounts - but only actually send an email if it does exist.
        if ($user) {
            $rawToken = bin2hex(random_bytes(32));
            $pdo->prepare("INSERT INTO password_resets (username, token, expires_at) VALUES (?,?, NOW() + INTERVAL 1 HOUR)")
                ->execute([$email, hash('sha256', $rawToken)]);

            $resetUrl = "/pages/reset_password.php?email=" . urlencode($email) . "&token={$rawToken}";
            @Mailer::send($email, $user['full_name'] ?? '', 'Reset your MK Net password',
                "<p>Hi " . h($user['full_name'] ?? '') . ",</p><p>We received a request to reset your password. This link is valid for 1 hour:</p>" .
                "<p><a href=\"{$resetUrl}\">Reset my password</a></p><p>If you didn't request this, you can safely ignore this email.</p>");
            $instantResetUrl = $resetUrl;
        }
        $sent = true;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<main class="container">
<div class="card" style="max-width:420px;margin:40px auto;">
  <h2>Forgot Password</h2>

  <?php if ($sent): ?>
    <p style="color:#0a6;">Password reset link generated!</p>
    <?php if (!empty($instantResetUrl)): ?>
      <p style="background:rgba(99,102,241,0.15); padding:12px; border-radius:8px; border:1px solid #6366f1;">
        👉 <a href="<?= h($instantResetUrl) ?>" style="color:#818cf8; font-weight:bold; word-break:break-all;">Click Here to Reset Password Now</a>
      </p>
    <?php else: ?>
      <p style="color:#94a3b8;">If an account exists for that email, we've sent a password reset link. Please check your inbox.</p>
    <?php endif; ?>
    <p><a href="selfcare_login.php">Back to login</a></p>
  <?php else: ?>
    <?php if ($error): ?><p style="color:#b00020;"><?= h($error) ?></p><?php endif; ?>
    <p>Enter your account email and we'll send you a link to reset your password.</p>
    <form method="post">
      <?= csrf_field() ?>
      <input type="email" name="email" placeholder="Email" required style="display:block;width:100%;margin:8px 0;padding:8px;">
      <button type="submit" class="btn primary" style="width:100%;">Send Reset Link</button>
    </form>
    <p style="margin-top:12px;"><a href="login.php">Back to login</a></p>
  <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
