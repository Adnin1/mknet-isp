<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'My Profile — MK Net';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!SessionManager::touch($pdo)) { header("Location: login.php"); exit; }

$userId = (int)$_SESSION['user_id'];
$message = '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = "Session expired, please try again.";
    } else {
        $name = trim((string)($_POST['full_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));

        if ($name === '') {
            $error = "Name cannot be empty.";
        } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
            $error = "Please enter a valid phone number.";
        } else {
            $phoneChanged = $phone !== $user['phone'];
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?" . ($phoneChanged ? ", phone_verified_at=NULL" : "") . " WHERE id=?");
            $stmt->execute([$name, $phone, $userId]);

            $user['full_name'] = $name;
            $user['phone'] = $phone;
            if ($phoneChanged) $user['phone_verified_at'] = null;

            $message = "Profile updated successfully." . ($phoneChanged ? " Please re-verify your new phone number." : "");
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<main class="container">
<div class="card" style="max-width:480px;margin:30px auto;">
  <h2>Update Profile</h2>
  <?php if ($message): ?><p style="color:#0a6;"><?= h($message) ?></p><?php endif; ?>
  <?php if ($error): ?><p style="color:#b00020;"><?= h($error) ?></p><?php endif; ?>

  <form method="post">
    <?= csrf_field() ?>
    <label>Full Name</label>
    <input type="text" name="full_name" value="<?= h($user['full_name']) ?>" required style="display:block;width:100%;margin:6px 0 12px;padding:8px;">

    <label>Email <span class="small-note">(contact support to change)</span></label>
    <input type="email" value="<?= h($user['email']) ?>" disabled style="display:block;width:100%;margin:6px 0 12px;padding:8px;background:#f4f4f4;">

    <label>Phone <?= $user['phone_verified_at'] ? '✅ Verified' : '⚠️ Not verified' ?></label>
    <input type="text" name="phone" value="<?= h($user['phone']) ?>" required style="display:block;width:100%;margin:6px 0 12px;padding:8px;">
    <?php if (!$user['phone_verified_at']): ?>
      <p><a href="verify_phone.php">Verify phone number →</a></p>
    <?php endif; ?>

    <button type="submit" class="btn primary" style="width:100%;">Save Changes</button>
  </form>
  <p style="margin-top:12px;"><a href="account_security.php">Account Security & 2FA →</a></p>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
