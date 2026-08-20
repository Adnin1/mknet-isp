<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$token = trim($_GET['token'] ?? '');
$error = '';
$success = '';
$invite = null;

if ($token !== '') {
    $stmt = $pdo->prepare("SELECT * FROM admin_invitations WHERE token = ? AND status = 'pending' AND expires_at >= NOW() LIMIT 1");
    $stmt->execute([$token]);
    $invite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invite) {
        $error = "Invalid or expired admin invitation token.";
    }
} else {
    $error = "No invitation token provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $invite) {
    if (!csrf_verify()) {
        $error = "Session expired. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($username === '' || strlen($password) < 6) {
            $error = "Username is required and password must be at least 6 characters.";
        } elseif ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            try {
                $ins = $pdo->prepare("INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, ?)");
                $ins->execute([$username, $invite['email'], $hash, $invite['role']]);

                // Mark invitation accepted
                $upd = $pdo->prepare("UPDATE admin_invitations SET status = 'accepted' WHERE id = ?");
                $upd->execute([$invite['id']]);

                $_SESSION['admin_invite_success'] = "Account '$username' created successfully! You can now log in.";
                header("Location: login.php");
                exit;
            } catch (PDOException $ex) {
                $error = "Error: Username '$username' already exists. Please choose a different username.";
            }
        }
    }
}

$pageTitle = 'Accept Admin Invitation — MK Net';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #090d16;
    color: #f8fafc;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card-box {
    background: rgba(30, 41, 59, 0.85);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(99, 102, 241, 0.3);
    border-radius: 20px;
    padding: 36px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.6);
}
</style>
</head>
<body>

<div class="card-box">
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="font-size: 2.5rem; margin-bottom: 8px;">🛡️</div>
        <h2 style="margin: 0; color: #fff; font-size: 1.6rem;">Accept Admin Invitation</h2>
        <?php if ($invite): ?>
            <p style="color: #94a3b8; font-size: 0.9rem; margin-top: 6px;">Invited Email: <strong style="color:#818cf8;"><?= h($invite['email']) ?></strong> (Role: <?= h($invite['role']) ?>)</p>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($invite): ?>
        <form method="post">
            <?= csrf_field() ?>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; color: #cbd5e1; font-size: 0.88rem; margin-bottom: 6px;">Desired Admin Username</label>
                <input type="text" name="username" required placeholder="e.g. alex_admin" style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.2); color: #fff; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; color: #cbd5e1; font-size: 0.88rem; margin-bottom: 6px;">New Password</label>
                <input type="password" name="password" required placeholder="At least 6 characters" style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.2); color: #fff; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; color: #cbd5e1; font-size: 0.88rem; margin-bottom: 6px;">Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Repeat password" style="width: 100%; padding: 12px; border-radius: 8px; background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.2); color: #fff; box-sizing: border-box;">
            </div>

            <button type="submit" style="width: 100%; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 14px; border-radius: 10px; font-weight: 700; border: none; cursor: pointer;">
                Complete Registration & Activate Admin →
            </button>
        </form>
    <?php else: ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="login.php" style="color: #818cf8; text-decoration: none; font-weight: 600;">Go to Admin Login →</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
