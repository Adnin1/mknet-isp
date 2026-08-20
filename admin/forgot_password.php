<?php
require_once __DIR__ . '/../config.php';
$message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $username = trim($_POST['username']);

  // Check if user exists
  $stmt = $pdo->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
  $stmt->execute([$username]);
  $admin = $stmt->fetch(PDO::FETCH_ASSOC);

  if($admin){
    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", time() + 900); // 15 minutes

    // Store token in DB
    $stmt = $pdo->prepare("INSERT INTO password_resets (username, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$username, $token, $expires]);

    // Send email (example)
    $resetLink = "/admin/reset_password.php?token=$token";
    @mail($admin['email'] ?? 'admin@mknet.com', "Admin Password Reset", "Click here to reset: $resetLink");

    $message = "Password reset link generated! <br><br>👉 <a href='$resetLink' style='color:#38bdf8; font-weight:bold; word-break:break-all;'>Click Here to Reset Admin Password Now</a>";
  } else {
    $message = "Username not found in system.";
  }
}
?>
<link rel="stylesheet" href="style.css">
<div class="main" style="max-width:400px; margin:auto; margin-top:100px;">
  <div class="card">
    <h2>Forgot Password</h2>
    <?php if(!empty($message)): ?>
      <p style="color:green;"><?= $message ?></p>
    <?php endif; ?>
    <form method="post">
      <label>Username:</label>
      <input type="text" name="username" required>
      <button type="submit">Send Reset Link</button>
    </form>
  </div>
</div>
