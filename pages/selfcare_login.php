<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// If already logged in, redirect to selfcare dashboard
if (!empty($_SESSION['user_id'])) {
    header("Location: /pages/selfcare_dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = "Session expired. Please refresh and try again.";
    } else {
        $loginInput = trim($_POST['login_input'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if ($loginInput === '' || $password === '') {
            $error = "Please provide both Email/Phone and Password.";
        } else {
            try {
                // Search by email or phone
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
                $stmt->execute([$loginInput, $loginInput]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $userHash = $user['password_hash'] ?? $user['password'] ?? '';
                $validPassword = false;

                if (!empty($userHash) && password_verify($password, $userHash)) {
                    $validPassword = true;
                } elseif (!empty($userHash) && $userHash === $password) {
                    $validPassword = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password_hash = ?, password = ? WHERE id = ?")->execute([$newHash, $newHash, $user['id']]);
                }

                if ($user && $validPassword) {
                    if (($user['account_status'] ?? 'active') === 'suspended') {
                        $error = "Your account is currently suspended. Please contact customer support.";
                    } else {
                        // Reset failed attempts
                        $pdo->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$user['id']]);

                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['user_name'] = $user['name'] ?? $user['full_name'] ?? 'Valued Customer';
                        $_SESSION['user_email'] = $user['email'];

                        header("Location: /pages/selfcare_dashboard.php");
                        exit;
                    }
                } else {
                    $error = "Invalid Email/Phone or Password. Please check your credentials.";
                }
            } catch (Exception $ex) {
                $error = "An unexpected error occurred. Please try again later.";
            }
        }
    }
}

$pageTitle = 'Self Care Portal Login — MK Net ISP';
include __DIR__ . '/../includes/header.php';
?>

<div style="min-height: 75vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px;">
    <div style="background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(20px); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 24px; padding: 40px; width: 100%; max-width: 460px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); color: #fff;">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 18px; margin-bottom: 16px; box-shadow: 0 0 25px rgba(99,102,241,0.5);">
                <i class="fa-solid fa-user-shield" style="font-size: 1.8rem; color: #fff;"></i>
            </div>
            <h2 style="margin: 0; font-size: 1.8rem; font-weight: 800; color: #fff;">Self Care Login</h2>
            <p style="color: #94a3b8; font-size: 0.95rem; margin-top: 6px;">Manage your connection, recharge & support tickets</p>
        </div>

        <?php if ($error): ?>
            <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="selfcare_login.php">
            <?= csrf_field() ?>

            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #cbd5e1; font-weight: 600; margin-bottom: 8px; font-size: 0.9rem;">
                    Email or Mobile Number
                </label>
                <div style="position: relative;">
                    <i class="fa-solid fa-envelope" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                    <input type="text" name="login_input" required placeholder="user@example.com or 01700000000" style="width: 100%; padding: 12px 14px 12px 42px; border-radius: 12px; background: rgba(30, 41, 59, 0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.95rem; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="color: #cbd5e1; font-weight: 600; font-size: 0.9rem;">Password</label>
                    <a href="/pages/forgot_password.php" style="color: #818cf8; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Forgot?</a>
                </div>
                <div style="position: relative;">
                    <i class="fa-solid fa-lock" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #64748b;"></i>
                    <input type="password" id="selfcarePassInput" name="password" required placeholder="••••••••" style="width: 100%; padding: 12px 42px 12px 42px; border-radius: 12px; background: rgba(30, 41, 59, 0.9); border: 1px solid rgba(255,255,255,0.15); color: #fff; font-size: 0.95rem; box-sizing: border-box;">
                    <i class="fa-solid fa-eye" id="toggleSelfcarePass" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: #64748b; cursor: pointer;"></i>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 24px;">
                <label style="color: #cbd5e1; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="remember" value="1" style="accent-color: #6366f1;"> Keep me logged in
                </label>
                <a href="/pages/forgot_password.php" style="color: #818cf8; font-weight: 700; font-size: 0.85rem; text-decoration: none;">Forgot Password?</a>
            </div>

            <button type="submit" style="width: 100%; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; padding: 14px; border-radius: 12px; font-weight: 800; font-size: 1rem; border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(99,102,241,0.4); display: flex; align-items: center; justify-content: center; gap: 10px;">
                Log In to Self Care Portal <i class="fa-solid fa-right-to-bracket"></i>
            </button>
        </form>

        <div style="text-align: center; margin-top: 24px; font-size: 0.9rem; color: #94a3b8;">
            Don't have an account? <a href="/pages/register.php" style="color: #34d399; font-weight: 700; text-decoration: none;">Register New Connection</a>
        </div>

        <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.08); text-align: center; font-size: 0.85rem; color: #64748b;">
            💡 Demo Account: <code>user@example.com</code> / <code>MkNetAdmin@2026</code>
        </div>
    </div>
</div>

<script>
document.getElementById('toggleSelfcarePass').addEventListener('click', function() {
    const input = document.getElementById('selfcarePassInput');
    if (input.type === 'password') {
        input.type = 'text';
        this.classList.remove('fa-eye');
        this.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        this.classList.remove('fa-eye-slash');
        this.classList.add('fa-eye');
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
