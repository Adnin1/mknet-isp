<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Account Security — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!SessionManager::touch($pdo)) { header("Location: login.php"); exit; }

$userId = (int)$_SESSION['user_id'];
$message = '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$tfaStmt = $pdo->prepare("SELECT * FROM user_2fa WHERE user_id=?");
$tfaStmt->execute([$userId]);
$tfa = $tfaStmt->fetch();
$tfaEnabled = $tfa && !empty($tfa['enabled_at']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = "Session expired, please try again.";
    }

    // ---- Change password ----
    elseif (isset($_POST['change_password'])) {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['new_password_confirm'] ?? '');

        if (!password_verify($current, $user['password'])) {
            $error = "Current password is incorrect.";
        } elseif (strlen($new) < 6) {
            $error = "New password must be at least 6 characters.";
        } elseif ($new !== $confirm) {
            $error = "New passwords do not match.";
        } else {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            Notifier::send($pdo, $userId, 'Password changed', 'Your password was just changed from your account settings.', 'security');
            $message = "Password updated successfully.";
        }
    }

    // ---- Start 2FA setup: generate secret, show QR ----
    elseif (isset($_POST['start_2fa'])) {
        $secret = TwoFactor::generateSecret();
        $_SESSION['pending_2fa_secret'] = $secret;
        $message = "__SHOW_2FA_SETUP__";
    }

    // ---- Confirm 2FA setup: verify a code against the pending secret, then enable ----
    elseif (isset($_POST['confirm_2fa'])) {
        $secret = $_SESSION['pending_2fa_secret'] ?? '';
        $code = trim((string)($_POST['twofa_code'] ?? ''));

        if ($secret && TwoFactor::verifyCode($secret, $code)) {
            $recoveryCodes = TwoFactor::generateRecoveryCodes();
            $hashedCodes = array_map(fn($c) => password_hash($c, PASSWORD_DEFAULT), $recoveryCodes);

            $pdo->prepare("INSERT INTO user_2fa (user_id, secret, enabled_at, recovery_codes) VALUES (?,?,NOW(),?)
                           ON DUPLICATE KEY UPDATE secret=VALUES(secret), enabled_at=NOW(), recovery_codes=VALUES(recovery_codes)")
                ->execute([$userId, $secret, json_encode($hashedCodes)]);

            unset($_SESSION['pending_2fa_secret']);
            Notifier::send($pdo, $userId, '2FA enabled', 'Two-factor authentication has been enabled on your account.', 'security');

            $_SESSION['show_recovery_codes'] = $recoveryCodes;
            $tfaEnabled = true;
            $message = "__2FA_ENABLED__";
        } else {
            $error = "Incorrect code. Please scan the QR code again and try the current 6-digit code.";
            $message = "__SHOW_2FA_SETUP__";
        }
    }

    // ---- Disable 2FA ----
    elseif (isset($_POST['disable_2fa'])) {
        $current = (string)($_POST['confirm_password'] ?? '');
        if (!password_verify($current, $user['password'])) {
            $error = "Incorrect password.";
        } else {
            $pdo->prepare("DELETE FROM user_2fa WHERE user_id=?")->execute([$userId]);
            Notifier::send($pdo, $userId, '2FA disabled', 'Two-factor authentication has been disabled on your account.', 'security');
            $tfaEnabled = false;
            $message = "Two-factor authentication has been disabled.";
        }
    }

    // ---- Revoke one session ----
    elseif (isset($_POST['revoke_session'])) {
        $sid = filter_input(INPUT_POST, 'session_id', FILTER_VALIDATE_INT);
        if ($sid && SessionManager::revokeSession($pdo, $userId, $sid)) {
            $message = "Device signed out.";
        }
    }

    // ---- Revoke all other sessions ----
    elseif (isset($_POST['revoke_all_others'])) {
        SessionManager::revokeAllOtherSessions($pdo, $userId, (int)($_SESSION['session_row_id'] ?? 0));
        $message = "All other devices have been signed out.";
    }
}

$pendingSecret = $_SESSION['pending_2fa_secret'] ?? null;
$showSetup = $message === '__SHOW_2FA_SETUP__';
$show2faEnabled = $message === '__2FA_ENABLED__';
$recoveryCodesToShow = $_SESSION['show_recovery_codes'] ?? null;
if ($show2faEnabled) unset($_SESSION['show_recovery_codes']);
if ($message === '__SHOW_2FA_SETUP__' || $message === '__2FA_ENABLED__') $message = '';

$activeSessions = SessionManager::listActiveSessions($pdo, $userId);
$loginHistory = SessionManager::listLoginHistory($pdo, $userId, 15);
$currentSessionId = (int)($_SESSION['session_row_id'] ?? 0);

include __DIR__ . '/../includes/header.php';
?>
<main class="container">
<div style="max-width:760px;margin:30px auto;">
  <h2>Account Security</h2>
  <?php if ($message): ?><p style="color:#0a6;background:#e6ffed;padding:10px;border-radius:6px;"><?= h($message) ?></p><?php endif; ?>
  <?php if ($error): ?><p style="color:#b00020;background:#ffe6e6;padding:10px;border-radius:6px;"><?= h($error) ?></p><?php endif; ?>

  <?php if ($show2faEnabled && $recoveryCodesToShow): ?>
    <div class="card" style="border:2px solid #0a6;padding:16px;margin-bottom:16px;">
      <h3>✅ 2FA Enabled — Save your recovery codes</h3>
      <p>Store these somewhere safe. Each code can be used once to log in if you lose access to your authenticator app. They will not be shown again.</p>
      <pre style="background:#f4f4f4;padding:12px;border-radius:6px;font-size:14px;"><?php foreach ($recoveryCodesToShow as $c) echo h($c) . "\n"; ?></pre>
    </div>
  <?php endif; ?>

  <!-- 2FA -->
  <div class="card" style="padding:16px;margin-bottom:16px;">
    <h3>Two-Factor Authentication (2FA)</h3>
    <?php if ($tfaEnabled): ?>
      <p>🔒 2FA is currently <b style="color:#0a6;">enabled</b> on your account.</p>
      <form method="post">
        <?= csrf_field() ?>
        <input type="password" name="confirm_password" placeholder="Enter password to disable" required style="padding:8px;margin-right:8px;">
        <button type="submit" name="disable_2fa" class="btn" onclick="return confirm('Disable 2FA?')">Disable 2FA</button>
      </form>
    <?php elseif ($showSetup && $pendingSecret): ?>
      <p>Scan this QR code with Google Authenticator, Authy, or any TOTP app, then enter the 6-digit code it shows.</p>
      <img src="<?= h(TwoFactor::getQrCodeImageUrl(TwoFactor::getProvisioningUri($pendingSecret, $user['email']))) ?>" alt="2FA QR Code" style="display:block;margin:12px 0;">
      <p class="small-note">Can't scan? Enter this key manually: <code><?= h($pendingSecret) ?></code></p>
      <form method="post">
        <?= csrf_field() ?>
        <input type="text" name="twofa_code" placeholder="123456" required style="padding:8px;margin-right:8px;letter-spacing:2px;">
        <button type="submit" name="confirm_2fa" class="btn primary">Confirm & Enable</button>
      </form>
    <?php else: ?>
      <p>🔓 2FA is currently <b>disabled</b>. Enable it to add an extra layer of security to your account.</p>
      <form method="post">
        <?= csrf_field() ?>
        <button type="submit" name="start_2fa" class="btn primary">Enable 2FA</button>
      </form>
    <?php endif; ?>
  </div>

  <!-- Change Password -->
  <div class="card" style="padding:16px;margin-bottom:16px;">
    <h3>Change Password</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="password" name="current_password" placeholder="Current password" required style="display:block;width:100%;margin:6px 0;padding:8px;">
      <input type="password" name="new_password" placeholder="New password" required minlength="6" style="display:block;width:100%;margin:6px 0;padding:8px;">
      <input type="password" name="new_password_confirm" placeholder="Confirm new password" required minlength="6" style="display:block;width:100%;margin:6px 0;padding:8px;">
      <button type="submit" name="change_password" class="btn primary">Update Password</button>
    </form>
  </div>

  <!-- Active Sessions / Device Management -->
  <div class="card" style="padding:16px;margin-bottom:16px;">
    <h3>Active Sessions & Devices</h3>
    <form method="post" style="margin-bottom:10px;">
      <?= csrf_field() ?>
      <button type="submit" name="revoke_all_others" class="btn" onclick="return confirm('Sign out all other devices?')">Sign out all other devices</button>
    </form>
    <table style="width:100%;">
      <tr><th>Device</th><th>IP</th><th>Last Active</th><th>Type</th><th></th></tr>
      <?php foreach ($activeSessions as $s): ?>
      <tr>
        <td><?= h($s['device_label']) ?><?= $s['id'] == $currentSessionId ? ' <b>(this device)</b>' : '' ?></td>
        <td><?= h($s['ip_address']) ?></td>
        <td><?= h($s['last_active_at']) ?></td>
        <td><?= $s['is_remember_me'] ? 'Remembered' : 'Session' ?></td>
        <td>
          <?php if ($s['id'] != $currentSessionId): ?>
          <form method="post" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="session_id" value="<?= h($s['id']) ?>">
            <button type="submit" name="revoke_session" class="btn small">Sign out</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <!-- Login History -->
  <div class="card" style="padding:16px;">
    <h3>Recent Login History</h3>
    <table style="width:100%;">
      <tr><th>Date</th><th>Status</th><th>IP</th><th>Device</th></tr>
      <?php foreach ($loginHistory as $l): ?>
      <tr>
        <td><?= h($l['created_at']) ?></td>
        <td><?= $l['status'] === 'success' ? '✅ Success' : ($l['status'] === 'locked' ? '🔒 Locked out' : '❌ Failed') ?></td>
        <td><?= h($l['ip_address']) ?></td>
        <td><?= h(SessionManager::friendlyDeviceName((string)$l['user_agent'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
