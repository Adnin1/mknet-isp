<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/lib/AdminPasswordPolicy.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Referrer-Policy: no-referrer');

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
  header("Location: dashboard.php");
  exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_verify()) {
    $error = "Session expired. Please try again.";
  } else {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
      $error = "Invalid username or password!";
    } else {
      // Fetch admin user
      $stmt = $pdo->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
      $stmt->execute([$username]);
      $admin = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($admin && isset($admin['id'], $admin['password']) && AdminPasswordPolicy::verify($password, (string)$admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = (string)$admin['username'];
        $_SESSION['theme'] = $admin['theme_preference'] ?? 'light';

        header("Location: dashboard.php");
        exit;
      } else {
        $error = "Invalid username or password!";
      }
    }
  }
}
?>
<link rel="stylesheet" href="style.css">

<div class="main" style="max-width:400px; margin:auto; margin-top:100px;">
  <div class="card">
    <h2>Admin Login</h2>
    <?php if (!empty($error)): ?>
      <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Username:</label>
      <input type="text" name="username" required>
      <label>Password:</label>
      <input type="password" name="password" required>
      <button type="submit">Login</button>
    </form>
    <p><a href="forgot_password.php">Forgot Password?</a></p>
  </div>
</div>
