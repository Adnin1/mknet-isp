<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/lib/AdminPasswordPolicy.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Referrer-Policy: no-referrer');

$message = "";
$genericResponse = "If the account exists and is eligible, password reset instructions have been sent.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = "Session expired. Please try again.";
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $rateKey = $ip . '|' . strtolower($username);

        if (!AdminPasswordPolicy::checkPersistentRateLimit($pdo, 'admin_forgot_password', $rateKey, 5, 900)) {
            $message = $genericResponse;
        } else {
            AdminPasswordPolicy::recordRateLimitAttempt($pdo, 'admin_forgot_password', $rateKey, 900);

            if ($username !== '') {
                $stmt = $pdo->prepare("SELECT id, username, email FROM admins WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($admin && !empty($admin['email'])) {
                    $baseUrl = AdminPasswordPolicy::trustedBaseUrl();
                    if ($baseUrl !== '') {
                        $rawToken = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $rawToken);
                        $expires = date("Y-m-d H:i:s", time() + 900);

                        $pdo->prepare("DELETE FROM password_resets WHERE username = ?")->execute([$admin['username']]);
                        $ins = $pdo->prepare("INSERT INTO password_resets (username, token, expires_at) VALUES (?, ?, ?)");
                        $ins->execute([$admin['username'], $tokenHash, $expires]);

                        $resetLink = $baseUrl . "/admin/reset_password.php?token=" . urlencode($rawToken);
                        try {
                            Mailer::send(
                                (string)$admin['email'],
                                (string)$admin['username'],
                                "MK Net Admin Password Reset",
                                "<p>A password reset was requested for your admin account.</p><p><a href=\"" . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . "\">Reset Admin Password</a></p><p>This link expires in 15 minutes.</p>"
                            );
                        } catch (Throwable $t) {
                            app_log('auth', 'Admin password reset mail send failed for username=' . (string)$admin['username']);
                        }
                    } else {
                        app_log('auth', 'Admin password reset skipped: trusted MKNET_APP_URL is not configured.');
                    }
                }
            }
            $message = $genericResponse;
        }
    }
}
?>
<link rel="stylesheet" href="style.css">
<div class="main" style="max-width:400px; margin:auto; margin-top:100px;">
  <div class="card">
    <h2>Forgot Password</h2>
    <?php if (!empty($message)): ?>
      <p style="color:green;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Username:</label>
      <input type="text" name="username" required>
      <button type="submit">Send Reset Link</button>
    </form>
  </div>
</div>
