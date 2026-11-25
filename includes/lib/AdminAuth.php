<?php
declare(strict_types=1);

/**
 * Server-side Admin Authorization, Session Lifecycle, and Last-Superadmin Guard.
 */
final class AdminAuth
{
    public const ALLOWED_ROLES = ['superadmin', 'admin', 'manager', 'support'];
    public const IDLE_TIMEOUT_SECONDS = 1800;      // 30 minutes
    public const ABSOLUTE_TIMEOUT_SECONDS = 28800; // 8 hours

    public static function isValidRole(string $role): bool
    {
        return in_array($role, self::ALLOWED_ROLES, true);
    }

    /**
     * Reload the authenticated admin from the database, enforce idle/absolute timeouts,
     * and revoke sessions whose password hash changed (e.g. after password reset).
     */
    public static function requireAdmin(\PDO $pdo): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['admin_id'])) {
            self::redirectToLogin();
        }

        $adminId = (int)$_SESSION['admin_id'];
        if ($adminId <= 0) {
            self::destroySessionAndCookie();
            self::redirectToLogin();
        }

        $now = time();
        $authAt = (int)($_SESSION['admin_auth_at'] ?? 0);
        $lastActive = (int)($_SESSION['admin_last_active'] ?? 0);
        if ($authAt <= 0 || ($now - $authAt) > self::ABSOLUTE_TIMEOUT_SECONDS) {
            self::destroySessionAndCookie();
            self::redirectToLogin();
        }
        if ($lastActive <= 0 || ($now - $lastActive) > self::IDLE_TIMEOUT_SECONDS) {
            self::destroySessionAndCookie();
            self::redirectToLogin();
        }

        $stmt = $pdo->prepare('SELECT id, username, email, role, password FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$admin || !isset($admin['password'])) {
            self::destroySessionAndCookie();
            self::redirectToLogin();
        }

        $expectedPwVer = hash('sha256', (string)$admin['password']);
        $sessionPwVer = (string)($_SESSION['admin_pw_ver'] ?? '');
        if ($sessionPwVer === '' || !hash_equals($expectedPwVer, $sessionPwVer)) {
            self::destroySessionAndCookie();
            self::redirectToLogin();
        }

        $_SESSION['admin_last_active'] = $now;
        return $admin;
    }

    /**
     * Enforce superadmin privilege from authoritative DB record (never from client/session role).
     */
    public static function requireSuperadmin(\PDO $pdo): array
    {
        $admin = self::requireAdmin($pdo);
        $role = strtolower(trim((string)($admin['role'] ?? '')));
        if ($role !== 'superadmin') {
            http_response_code(403);
            echo '403 Forbidden: Superadmin privileges are required.';
            exit;
        }
        return $admin;
    }

    /**
     * Prevent deleting or demoting the last active superadmin account.
     */
    public static function canDeleteOrDemoteAdmin(\PDO $pdo, int $targetAdminId): bool
    {
        $stmt = $pdo->prepare('SELECT role FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$targetAdminId]);
        $targetRole = strtolower(trim((string)($stmt->fetchColumn() ?: '')));
        if ($targetRole !== 'superadmin') {
            return true;
        }

        $count = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'superadmin'")->fetchColumn();
        return $count > 1;
    }

    public static function destroySessionAndCookie(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?: '/',
                'domain' => $params['domain'] ?: '',
                'secure' => (bool)$params['secure'],
                'httponly' => (bool)$params['httponly'],
                'samesite' => $params['samesite'] ?: 'Lax',
            ]);
        }
        session_destroy();
    }

    private static function redirectToLogin(): void
    {
        header('Location: login.php');
        exit;
    }
}
