<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/lib/AdminAuth.php';
require_once __DIR__ . '/../includes/lib/AdminPasswordPolicy.php';
$currentAdmin = AdminAuth::requireSuperadmin($pdo);

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Referrer-Policy: no-referrer');

$message = '';
$messageType = '';

// Handle Admin Creation / Invitation / Password Reset / Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentAdmin = AdminAuth::requireSuperadmin($pdo);
    if (!csrf_verify()) {
        $message = "Session expired, please try again.";
        $messageType = "error";
    } else {
        // Action: Generate Email Invitation Link
        if (isset($_POST['send_invitation'])) {
            $currentAdmin = AdminAuth::requireSuperadmin($pdo);
            $email = trim((string)($_POST['email'] ?? ''));
            $role = strtolower(trim((string)($_POST['role'] ?? 'admin')));
            $baseUrl = AdminPasswordPolicy::trustedBaseUrl();

            if (!AdminAuth::isValidRole($role)) {
                $message = "Invalid administrator role selected.";
                $messageType = "error";
            } elseif ($baseUrl === '') {
                $message = "Trusted MKNET_APP_URL (HTTPS) must be configured before generating invitation links.";
                $messageType = "error";
            } elseif (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

                $stmt = $pdo->prepare("INSERT INTO admin_invitations (email, role, token, expires_at) VALUES (?, ?, ?, ?)");
                $stmt->execute([$email, $role, $tokenHash, $expiresAt]);

                $inviteLink = $baseUrl . "/admin/accept_invite.php?token=" . urlencode($rawToken);

                try {
                    Mailer::send($email, $email, "MK Net Admin Invitation", "Hello,\n\nYou have been invited to become an Admin on MK Net.\nRole: $role\n\nClick the link below to accept your invitation:\n$inviteLink\n\nLink expires in 7 days.");
                } catch (Throwable $t) {
                    app_log('auth', 'Admin invitation mail send failed.');
                }

                $message = "Invitation generated and sent via email to '$email'!";
                $messageType = "success";
            } else {
                $message = "Valid email address is required.";
                $messageType = "error";
            }
        }

        // Action: Direct Create Admin
        if (isset($_POST['create_admin'])) {
            $currentAdmin = AdminAuth::requireSuperadmin($pdo);
            $username = trim((string)($_POST['username'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $role = strtolower(trim((string)($_POST['role'] ?? 'admin')));

            if (!AdminAuth::isValidRole($role)) {
                $message = "Invalid administrator role selected.";
                $messageType = "error";
            } elseif ($username !== '') {
                $policyErrors = AdminPasswordPolicy::validate($password, $username);
                if ($policyErrors !== []) {
                    $message = $policyErrors[0];
                    $messageType = "error";
                } else {
                    try {
                        $hash = AdminPasswordPolicy::hash($password);
                        $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$username, $email ?: null, $hash, $role]);
                        $message = "Admin account '$username' created successfully!";
                        $messageType = "success";
                    } catch (PDOException $ex) {
                        $message = "Error: Username or email already exists.";
                        $messageType = "error";
                    } catch (Throwable $ex) {
                        $message = "Unable to create admin account.";
                        $messageType = "error";
                    }
                }
            } else {
                $message = "Username and password are required.";
                $messageType = "error";
            }
        }

        // Action: Change Admin Password
        if (isset($_POST['change_password'])) {
            $currentAdmin = AdminAuth::requireSuperadmin($pdo);
            $targetAdminId = (int)$_POST['admin_id'];
            $newPassword = (string)($_POST['new_password'] ?? '');
            $uStmt = $pdo->prepare("SELECT username FROM admins WHERE id = ? LIMIT 1");
            $uStmt->execute([$targetAdminId]);
            $targetUsername = (string)($uStmt->fetchColumn() ?: '');

            if ($targetUsername === '') {
                $message = "Target admin account not found.";
                $messageType = "error";
            } else {
                $policyErrors = AdminPasswordPolicy::validate($newPassword, $targetUsername);
                if ($policyErrors !== []) {
                    $message = $policyErrors[0];
                    $messageType = "error";
                } else {
                    try {
                        $newHash = AdminPasswordPolicy::hash($newPassword);
                        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                        $stmt->execute([$newHash, $targetAdminId]);
                        $message = "Password updated successfully for Admin ID #$targetAdminId!";
                        $messageType = "success";
                    } catch (Throwable $ex) {
                        $message = "Unable to update admin password.";
                        $messageType = "error";
                    }
                }
            }
        }

        // Action: Delete Admin
        if (isset($_POST['delete_admin'])) {
            $currentAdmin = AdminAuth::requireSuperadmin($pdo);
            $targetAdminId = (int)$_POST['admin_id'];
            $delRes = AdminAuth::deleteAdminSafely($pdo, (int)$currentAdmin['id'], $targetAdminId);
            if ($delRes['ok']) {
                $message = "Admin account removed successfully.";
                $messageType = "success";
            } else {
                $message = $delRes['error'];
                $messageType = "error";
            }
        }
    }
}

// Fetch all admins safely
$selectFields = ['id', 'username'];
try {
    $adminCols = $pdo->query("SHOW COLUMNS FROM admins")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('email', $adminCols)) $selectFields[] = 'email';
    if (in_array('role', $adminCols)) $selectFields[] = 'role';
    if (in_array('created_at', $adminCols)) $selectFields[] = 'created_at';
} catch (Throwable $e) {
    $selectFields = ['id', 'username', 'email', 'role', 'created_at'];
}

$admins = $pdo->query("SELECT " . implode(', ', $selectFields) . " FROM admins ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch pending invitations
$nowStmt = $pdo->prepare("SELECT * FROM admin_invitations WHERE status='pending' AND expires_at >= ? ORDER BY id DESC");
$nowStmt->execute([date('Y-m-d H:i:s')]);
$pendingInvitations = $nowStmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/header.php';
?>

<div class="main-content" style="padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; color: #fff; font-size: 1.8rem;">🛡️ Admin User & Password Management</h2>
    </div>

    <?php if ($message): ?>
        <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; background: <?= $messageType==='error'?'rgba(239,68,68,0.2)':'rgba(16,185,129,0.2)' ?>; border: 1px solid <?= $messageType==='error'?'#ef4444':'#10b981' ?>; color: <?= $messageType==='error'?'#fca5a5':'#6ee7b7' ?>;">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
        
        <!-- Invite Admin via Email -->
        <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 24px; color: #fff;">
            <h3 style="margin-top: 0; color: #38bdf8;">📩 Invite Admin via Email Link</h3>
            <p style="color: #94a3b8; font-size: 0.85rem;">Send an invitation token link via email. The recipient can set up their own password.</p>
            <form method="post" action="manage_admins.php">
                <?= csrf_field() ?>
                <input type="hidden" name="send_invitation" value="1">
                
                <div style="margin-bottom: 14px;">
                    <label style="display: block; color: #cbd5e1; margin-bottom: 6px; font-size: 0.9rem;">Recipient Email</label>
                    <input type="email" name="email" required placeholder="newadmin@mknet.com" class="input" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: #cbd5e1; margin-bottom: 6px; font-size: 0.9rem;">Role</label>
                    <select name="role" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                        <option value="manager">Manager</option>
                        <option value="support">Support Agent</option>
                    </select>
                </div>

                <button type="submit" class="btn" style="width: 100%; background: #0284c7; color: #fff; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer;">Generate & Send Invite Link</button>
            </form>
        </div>

        <!-- Direct Create Admin Form -->
        <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 24px; color: #fff;">
            <h3 style="margin-top: 0; color: #818cf8;">➕ Direct Create Admin Account</h3>
            <form method="post" action="manage_admins.php">
                <?= csrf_field() ?>
                <input type="hidden" name="create_admin" value="1">
                
                <div style="margin-bottom: 14px;">
                    <label style="display: block; color: #cbd5e1; margin-bottom: 6px; font-size: 0.9rem;">Username</label>
                    <input type="text" name="username" required placeholder="e.g. admin_manager" class="input" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; color: #cbd5e1; margin-bottom: 6px; font-size: 0.9rem;">Email Address (Optional)</label>
                    <input type="email" name="email" placeholder="admin@mknet.com" class="input" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; color: #cbd5e1; margin-bottom: 6px; font-size: 0.9rem;">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="input" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; color: #cbd5e1; margin-bottom: 6px; font-size: 0.9rem;">Role</label>
                    <select name="role" style="width: 100%; padding: 10px; border-radius: 8px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                        <option value="manager">Manager</option>
                        <option value="support">Support Agent</option>
                    </select>
                </div>

                <button type="submit" class="btn" style="width: 100%; background: #6366f1; color: #fff; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer;">Create Admin Account</button>
            </form>
        </div>

        <!-- Admin Users List & Password Updater -->
        <div class="card" style="background: rgba(30,41,59,0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 24px; color: #fff; grid-column: span 2;">
            <h3 style="margin-top: 0; color: #34d399;">👥 Existing Admins</h3>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; color: #e2e8f0;">
                    <thead>
                        <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #94a3b8; font-size: 0.85rem; text-transform: uppercase;">
                            <th style="padding: 12px;">ID</th>
                            <th style="padding: 12px;">Username</th>
                            <th style="padding: 12px;">Email</th>
                            <th style="padding: 12px;">Role</th>
                            <th style="padding: 12px;">Change Password</th>
                            <th style="padding: 12px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $adm): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td style="padding: 12px; font-weight: 600;">#<?= (int)$adm['id'] ?></td>
                                <td style="padding: 12px; font-weight: 700; color: #818cf8;"><?= h($adm['username']) ?></td>
                                <td style="padding: 12px; color: #94a3b8; font-size: 0.9rem;"><?= h($adm['email'] ?: 'N/A') ?></td>
                                <td style="padding: 12px;"><span style="background: rgba(99,102,241,0.2); color: #a5b4fc; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; text-transform: uppercase;"><?= h($adm['role']) ?></span></td>
                                <td style="padding: 12px;">
                                    <form method="post" style="display: flex; gap: 6px;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="change_password" value="1">
                                        <input type="hidden" name="admin_id" value="<?= (int)$adm['id'] ?>">
                                        <input type="password" name="new_password" placeholder="New password" required style="padding: 6px 10px; border-radius: 6px; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.2); color: #fff; font-size: 0.85rem; width: 130px;">
                                        <button type="submit" class="btn" style="background: #10b981; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">Save</button>
                                    </form>
                                </td>
                                <td style="padding: 12px; text-align: right;">
                                    <?php if ((int)$adm['id'] !== (int)$_SESSION['admin_id']): ?>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete admin <?= h($adm['username']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="delete_admin" value="1">
                                            <input type="hidden" name="admin_id" value="<?= (int)$adm['id'] ?>">
                                            <button type="submit" style="background: rgba(239,68,68,0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #34d399; font-size: 0.85rem; font-weight: 600;">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
