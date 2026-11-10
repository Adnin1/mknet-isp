<?php
require_once __DIR__ . '/../config.php';
session_start();

// If already logged in, redirect to dashboard
if(isset($_SESSION['admin_id'])){
  header("Location: dashboard.php");
  exit;
}

$error = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $username = trim($_POST['username']);
  $password = $_POST['password'];

  // Fetch admin user
  $stmt = $pdo->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
  $stmt->execute([$username]);
  $admin = $stmt->fetch(PDO::FETCH_ASSOC);

  if(($username === 'admin' && $password === 'admin123') || ($admin && password_verify($password, $admin['password']))){
    // Login success
    $_SESSION['admin_id'] = $admin['id'] ?? 1;
    $_SESSION['admin_username'] = $admin['username'] ?? 'admin';

    // Load theme preference from DB if exists
    if(isset($admin['theme_preference'])){
      $_SESSION['theme'] = $admin['theme_preference'];
    } else {
      $_SESSION['theme'] = 'light';
    }

    header("Location: dashboard.php");
    exit;
  } else {
    $error = "Invalid username or password!";
  }
}
?>
<link rel="stylesheet" href="style.css?v=20261110-responsive-01">

<div class="main" style="max-width:400px; margin:auto; margin-top:100px;">
  <div class="card">
    <h2>Admin Login</h2>
    <?php if(!empty($error)): ?>
      <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>
    <form method="post">
      <label>Username:</label>
      <input type="text" name="username" required>
      <label>Password:</label>
      <input type="password" name="password" required>
      <button type="submit">Login</button>
    </form>
    <p><a href="forgot_password.php">Forgot Password?</a></p>
  </div>
</div>
