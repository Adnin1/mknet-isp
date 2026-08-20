<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Self Care Login — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
$notice = filter_input(INPUT_GET, 'notice', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$MAX_ATTEMPTS = 5;
$LOCK_MINUTES = 15;

// Step 2: 2FA Challenge
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['twofa_code']) && !empty($_SESSION['pending_2fa_user_id'])) {
  if (!csrf_verify()) {
    $error = "Session expired, please try again.";
  } else {
    $uid = (int)$_SESSION['pending_2fa_user_id'];
    $stmt = $pdo->prepare("SELECT * FROM user_2fa WHERE user_id=?");
    $stmt->execute([$uid]);
    $tfa = $stmt->fetch();

    $code = trim((string)$_POST['twofa_code']);
    $ok = $tfa && class_exists('TwoFactor') && TwoFactor::verifyCode($tfa['secret'], $code);

    if ($ok) {
      unset($_SESSION['pending_2fa_user_id']);
      $_SESSION['user_id'] = $uid;
      $redirect = $_SESSION['post_login_redirect'] ?? "/pages/dashboard.php";
      unset($_SESSION['post_login_redirect']);
      header("Location: " . $redirect);
      exit;
    } else {
      $error = "Invalid authentication code.";
    }
  }
}
// Step 1: Login via Email / Phone / Customer ID + Password
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password']) && !isset($_POST['twofa_code'])) {
  if (!csrf_verify()) {
    $error = "Session expired, please try again.";
  } else {
    $loginInput = trim((string)($_POST['login_input'] ?? $_POST['email'] ?? ''));
    $password   = (string)($_POST['password'] ?? '');
    $remember   = isset($_POST['remember_me']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? OR phone=? OR id=? LIMIT 1");
    $stmt->execute([$loginInput, $loginInput, is_numeric($loginInput) ? (int)$loginInput : 0]);
    $user = $stmt->fetch();

    $storedHash = $user['password_hash'] ?? $user['password'] ?? '';

    if ($user && !empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
      $mins = ceil((strtotime($user['locked_until']) - time()) / 60);
      $error = "Too many failed attempts. Account locked for {$mins} minute(s).";
    } elseif ($user && $storedHash !== '' && password_verify($password, $storedHash)) {
      // Login Success!
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['user_email'] = $user['email'];
      $_SESSION['user_name'] = $user['name'] ?? $user['email'];

      $pdo->prepare("UPDATE users SET failed_login_attempts=0, locked_until=NULL WHERE id=?")->execute([$user['id']]);

      $redirect = $_SESSION['post_login_redirect'] ?? "/pages/dashboard.php";
      unset($_SESSION['post_login_redirect']);
      header("Location: " . $redirect);
      exit;
    } else {
      $error = "Invalid Mobile Number, Email, or Password. (Demo user: user@example.com / password: MkNetAdmin@2026)";
      if ($user) {
        $attempts = (int)($user['failed_login_attempts'] ?? 0) + 1;
        $lockUntil = $attempts >= $MAX_ATTEMPTS ? date('Y-m-d H:i:s', time() + $LOCK_MINUTES * 60) : null;
        $pdo->prepare("UPDATE users SET failed_login_attempts=?, locked_until=? WHERE id=?")->execute([$attempts, $lockUntil, $user['id']]);
      }
    }
  }
}

include __DIR__ . '/../includes/header.php';
$awaiting2fa = !empty($_SESSION['pending_2fa_user_id']);
?>

<main class="container" style="padding: 40px 20px;">
  <div class="card" style="max-width: 440px; margin: 0 auto; background: rgba(30,41,59,0.8); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 32px; color: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="font-size: 42px; margin-bottom: 8px;">🔐</div>
      <h2 style="font-size: 1.8rem; font-weight: 700; margin: 0; color: #fff;">Self Care Login</h2>
      <p style="color: #94a3b8; font-size: 0.9rem; margin-top: 6px;">Access your internet package, bills, usage & support.</p>
    </div>

    <?php if ($notice): ?>
      <div style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; background: rgba(16,185,129,0.15); border: 1px solid #10b981; color: #6ee7b7; font-size: 0.9rem;">
        <?= h($notice) ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #fca5a5; font-size: 0.9rem;">
        <?= h($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($awaiting2fa): ?>
      <form method="post">
        <?= csrf_field() ?>
        <label style="display:block; color:#cbd5e1; margin-bottom:8px;">2FA Verification Code</label>
        <input type="text" name="twofa_code" placeholder="123456" required autofocus class="input" style="width:100%; padding:12px; border-radius:8px; background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.2); color:#fff; text-align:center; font-size:1.4rem; letter-spacing:4px; margin-bottom:20px;">
        <button type="submit" class="btn" style="width:100%; background:#6366f1; color:#fff; padding:14px; border-radius:10px; font-weight:700;">Verify & Login →</button>
      </form>
    <?php else: ?>
      <form method="post">
        <?= csrf_field() ?>
        
        <div style="margin-bottom: 16px;">
          <label style="display:block; color:#cbd5e1; margin-bottom:6px; font-size:0.85rem;">Mobile Number / Email / Customer ID</label>
          <input type="text" name="login_input" required placeholder="017XXXXXXXX or user@example.com" class="input" style="width:100%; padding:12px; border-radius:8px; background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.2); color:#fff;">
        </div>

        <div style="margin-bottom: 20px;">
          <label style="display:block; color:#cbd5e1; margin-bottom:6px; font-size:0.85rem;">Password</label>
          <input type="password" name="password" required placeholder="••••••••" class="input" style="width:100%; padding:12px; border-radius:8px; background:rgba(15,23,42,0.8); border:1px solid rgba(255,255,255,0.2); color:#fff;">
        </div>

        <button type="submit" class="btn" style="width:100%; background:linear-gradient(135deg, #6366f1, #4f46e5); color:#fff; padding:14px; border-radius:10px; font-weight:700; font-size:1rem; cursor:pointer; box-shadow:0 4px 15px rgba(99,102,241,0.4);">
          Sign In to Self Care →
        </button>
      </form>

      <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; font-size: 0.85rem;">
        <a href="forgot_password.php" style="color: #94a3b8; text-decoration: none;">Forgot password?</a>
        <a href="register.php" style="color: #818cf8; font-weight: 600; text-decoration: none;">New user? Register →</a>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
