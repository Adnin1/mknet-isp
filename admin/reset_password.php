<?php
require_once __DIR__ . '/../config.php';
$message = "";

if(isset($_GET['token'])){
  $token = $_GET['token'];

  // Validate token
  $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token=? LIMIT 1");
  $stmt->execute([$token]);
  $reset = $stmt->fetch(PDO::FETCH_ASSOC);

  if($reset && strtotime($reset['expires_at']) > time()){
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
      $newpass = $_POST['newpass'];
      $hash = password_hash($newpass, PASSWORD_DEFAULT);

      // Update password
      $stmt = $pdo->prepare("UPDATE admins SET password=? WHERE username=?");
      $stmt->execute([$hash, $reset['username']]);

      // Delete token
      $pdo->prepare("DELETE FROM password_resets WHERE token=?")->execute([$token]);

      $message = "Password reset successful! You can now login.";
    }
  } else {
    $message = "Invalid or expired token.";
  }
}
?>
<link rel="stylesheet" href="style.css">
<div class="main" style="max-width:400px; margin:auto; margin-top:100px;">
  <div class="card">
    <h2>Reset Password</h2>
    <?php if(!empty($message)): ?>
      <p style="color:green;"><?= $message ?></p>
    <?php endif; ?>
    <?php if(isset($reset) && strtotime($reset['expires_at']) > time()): ?>
      <form method="post">
        <label>New Password:</label>
        <input type="password" name="newpass" required>
        <button type="submit">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>
</div>
