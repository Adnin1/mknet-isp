<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/lib/AdminPasswordPolicy.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Referrer-Policy: no-referrer');

$message = "";
$error = "";
$rawToken = trim((string)($_POST['token'] ?? $_GET['token'] ?? ''));
$reset = null;
$showForm = false;

if ($rawToken !== '') {
    $tokenHash = hash('sha256', $rawToken);
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->execute([$tokenHash]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reset && strtotime((string)$reset['expires_at']) > time()) {
        $showForm = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_verify()) {
                $error = "Session expired. Please try again.";
            } else {
                $newpass = (string)($_POST['newpass'] ?? '');
                $policyErrors = AdminPasswordPolicy::validate($newpass, (string)$reset['username']);
                if ($policyErrors !== []) {
                    $error = $policyErrors[0];
                } else {
                    try {
                        $hash = AdminPasswordPolicy::hash($newpass);
                        $pdo->beginTransaction();

                        $del = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                        $del->execute([$tokenHash]);
                        if ($del->rowCount() !== 1) {
                            $pdo->rollBack();
                            $error = "Invalid or expired token.";
                            $showForm = false;
                        } else {
                            $upd = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
                            $upd->execute([$hash, $reset['username']]);
                            $pdo->commit();
                            $message = "Password reset successful! You can now login.";
                            $showForm = false;
                        }
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $error = "Unable to reset password right now.";
                    }
                }
            }
        }
    } else {
        $error = "Invalid or expired token.";
    }
}
?>
<link rel="stylesheet" href="style.css">
<div class="main" style="max-width:400px; margin:auto; margin-top:100px;">
  <div class="card">
    <h2>Reset Password</h2>
    <?php if (!empty($error)): ?>
      <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if (!empty($message)): ?>
      <p style="color:green;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($showForm): ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($rawToken, ENT_QUOTES, 'UTF-8') ?>">
        <label>New Password (15+ characters):</label>
        <input type="password" name="newpass" required>
        <button type="submit">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>
</div>
