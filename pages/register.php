<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Register';

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (!csrf_verify()) {
    $errors[] = "Session expired, please try again.";
  } else {
    $old['name'] = trim((string)($_POST['name'] ?? ''));
    $old['email'] = trim((string)($_POST['email'] ?? ''));
    $old['phone'] = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $package = filter_input(INPUT_POST, 'package', FILTER_VALIDATE_INT);
    $refCode = trim((string)($_POST['ref'] ?? ''));

    if ($old['name'] === '') $errors[] = "Please enter your full name.";
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $old['phone'])) $errors[] = "Please enter a valid phone number.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if (!$package) $errors[] = "Please select a package.";

    if (empty($errors)) {
      $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
      $check->execute([$old['email']]);
      if ($check->fetch()) {
        $errors[] = "An account with this email already exists. Try logging in instead.";
      }
    }

    if (empty($errors)) {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $myReferralCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

      $referredBy = null;
      if ($refCode !== '') {
        $r = $pdo->prepare("SELECT id FROM users WHERE referral_code=?");
        $r->execute([strtoupper($refCode)]);
        $refUser = $r->fetch();
        if ($refUser) $referredBy = (int)$refUser['id'];
      }

      // Fetch speed from selected package
      $pkgStmt = $pdo->prepare("SELECT speed_mbps FROM packages WHERE id = ?");
      $pkgStmt->execute([$package]);
      $speedMbps = (int)($pkgStmt->fetchColumn() ?: 20);

      $stmt = $pdo->prepare(
        "INSERT INTO users (name, full_name, email, phone, password, password_hash, package_speed_mbps, package_id, referral_code, referred_by_user_id, account_status)
         VALUES (?,?,?,?,?,?,?,?,?,?,'active')"
      );
      $stmt->execute([
        $old['name'], $old['name'], $old['email'], $old['phone'], $hash, $hash,
        $speedMbps, $package,
        $myReferralCode, $referredBy,
      ]);
      $userId = (int)$pdo->lastInsertId();

      // Log in user immediately
      $_SESSION['user_id'] = $userId;
      $_SESSION['user_name'] = $old['name'];
      $_SESSION['user_email'] = $old['email'];

      if ($referredBy) {
        $pdo->prepare("INSERT INTO referrals (referrer_user_id, referred_user_id, code, reward_amount, status) VALUES (?,?,?,?, 'pending')")
          ->execute([$referredBy, $userId, strtoupper($refCode), 100.00]);
        Notifier::send($pdo, $referredBy, 'Referral signed up!', $old['name'] . ' just joined using your referral code. Your reward will be credited once they make their first payment.', 'referral');
      }

      // Email verification
      $rawToken = bin2hex(random_bytes(32));
      try {
          $pdo->prepare("INSERT INTO email_verifications (user_id, token_hash, expires_at) VALUES (?,?, NOW() + INTERVAL 24 HOUR)")
            ->execute([$userId, hash('sha256', $rawToken)]);
      } catch (Throwable $t) {}

      Notifier::send($pdo, $userId, 'Welcome to MK Net!', 'Your account has been created. Enjoy your high-speed internet service!', 'welcome');

      header("Location: /pages/selfcare_dashboard.php?registered=1");
      exit;
    }
  }
}

include __DIR__ . '/../includes/header.php';

$packages = $pdo->query("SELECT id, name, speed_mbps FROM packages WHERE visible=1 ORDER BY speed_mbps ASC")->fetchAll();
$prefRef = $_GET['ref'] ?? '';
?>
<main class="container">
<div class="card" style="max-width:480px;margin:40px auto;">
  <h2>Create your account</h2>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert" style="background:#e6ffed;border:1px solid #34c759;padding:12px;border-radius:6px;">
      <strong>Registration successful!</strong> We've sent a verification link to your email.
      Please check your inbox (and spam folder) before logging in.
      <p style="margin-top:8px;"><a href="login.php">Go to login</a></p>
    </div>
  <?php else: ?>

    <?php if (!empty($errors)): ?>
      <div class="alert" style="background:#ffe6e6;border:1px solid #d00;padding:10px;border-radius:6px;">
        <?php foreach ($errors as $err): ?><p style="margin:2px 0;color:#b00020;"><?= h($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="ref" value="<?= h($prefRef) ?>">
      <input type="text" name="name" placeholder="Full Name" value="<?= h($old['name']) ?>" required style="display:block;width:100%;margin:8px 0;padding:8px;">
      <input type="email" name="email" placeholder="Email" value="<?= h($old['email']) ?>" required style="display:block;width:100%;margin:8px 0;padding:8px;">
      <input type="text" name="phone" placeholder="Phone (e.g. 01700000000)" value="<?= h($old['phone']) ?>" required style="display:block;width:100%;margin:8px 0;padding:8px;">
      <input type="password" name="password" placeholder="Password (min 6 characters)" required minlength="6" style="display:block;width:100%;margin:8px 0;padding:8px;">
      <select name="package" required style="display:block;width:100%;margin:8px 0;padding:8px;">
        <option value="">Select a package</option>
        <?php foreach ($packages as $p): ?>
          <option value="<?= h($p['id']) ?>"><?= h($p['name']) ?> — <?= h($p['speed_mbps']) ?> Mbps</option>
        <?php endforeach; ?>
      </select>
      <?php if ($prefRef): ?><p class="small-note">Referral code applied: <b><?= h($prefRef) ?></b></p><?php endif; ?>
      <button type="submit" class="btn primary" style="width:100%;">Register</button>
    </form>
    <p style="margin-top:12px;">Already have an account? <a href="login.php">Login</a></p>
  <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
