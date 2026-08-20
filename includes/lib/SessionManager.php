<?php
declare(strict_types=1);

/**
 * Backs "Remember Me", "Active Sessions", "Device Management" and
 * "Login History". One user_sessions row = one browser/device that is
 * (or recently was) logged in.
 */
class SessionManager
{
    /** Call on every successful login. Starts the PHP session, logs history, optionally sets a remember-me cookie. */
    public static function startSession(PDO $pdo, int $userId, bool $rememberMe): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();

        $ip = self::clientIp();
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'), 0, 250);
        $device = self::friendlyDeviceName($ua);

        self::logLogin($pdo, $userId, null, 'success');

        $selector = null;
        if ($rememberMe) {
            $selector = bin2hex(random_bytes(16));
            $validator = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $validator);

            setcookie('remember_selector', $selector, time() + 60 * 60 * 24 * 30, '/', '', false, true);
            setcookie('remember_validator', $validator, time() + 60 * 60 * 24 * 30, '/', '', false, true);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO user_sessions (user_id, selector, token_hash, php_session_id, ip_address, user_agent, device_label, is_remember_me, last_active_at, expires_at)
             VALUES (?,?,?,?,?,?,?,?,NOW(), ?)"
        );
        $stmt->execute([
            $userId,
            $selector ?? bin2hex(random_bytes(16)), // even non-remember-me sessions get a row, for the Active Sessions list
            $selector ? hash('sha256', $_COOKIE['remember_validator'] ?? '') : hash('sha256', session_id()),
            session_id(),
            $ip,
            $ua,
            $device,
            $rememberMe ? 1 : 0,
            date('Y-m-d H:i:s', time() + ($rememberMe ? 60 * 60 * 24 * 30 : 60 * 60 * 4)),
        ]);
        $_SESSION['session_row_id'] = (int)$pdo->lastInsertId();
    }

    /** Call at the top of any page that requires login, right after session_start(). Enforces idle timeout + refreshes activity. */
    public static function touch(PDO $pdo, int $idleTimeoutSeconds = 1800): bool
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) return false;

        if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idleTimeoutSeconds) {
            self::destroyCurrent($pdo);
            return false;
        }
        $_SESSION['last_activity'] = time();

        if (!empty($_SESSION['session_row_id'])) {
            $pdo->prepare("UPDATE user_sessions SET last_active_at = NOW() WHERE id = ? AND revoked_at IS NULL")
                ->execute([$_SESSION['session_row_id']]);
        }
        return true;
    }

    /** Try to log the user back in from the remember-me cookie pair. Call this when $_SESSION['user_id'] is empty. */
    public static function tryRememberMeLogin(PDO $pdo): ?int
    {
        if (empty($_COOKIE['remember_selector']) || empty($_COOKIE['remember_validator'])) return null;

        $stmt = $pdo->prepare("SELECT * FROM user_sessions WHERE selector=? AND revoked_at IS NULL AND expires_at >= NOW() LIMIT 1");
        $stmt->execute([$_COOKIE['remember_selector']]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $validHash = hash('sha256', $_COOKIE['remember_validator']);
        if (!hash_equals($row['token_hash'], $validHash)) return null;

        self::startSession($pdo, (int)$row['user_id'], true);
        return (int)$row['user_id'];
    }

    public static function destroyCurrent(PDO $pdo): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['session_row_id'])) {
            $pdo->prepare("UPDATE user_sessions SET revoked_at = NOW() WHERE id = ?")->execute([$_SESSION['session_row_id']]);
        }
        setcookie('remember_selector', '', time() - 3600, '/');
        setcookie('remember_validator', '', time() - 3600, '/');
        $_SESSION = [];
        session_destroy();
    }

    /** For the "Active Sessions / Devices" page: revoke one specific session by id (must belong to $userId). */
    public static function revokeSession(PDO $pdo, int $userId, int $sessionId): bool
    {
        $stmt = $pdo->prepare("UPDATE user_sessions SET revoked_at = NOW() WHERE id=? AND user_id=?");
        $stmt->execute([$sessionId, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function revokeAllOtherSessions(PDO $pdo, int $userId, int $keepSessionId): void
    {
        $pdo->prepare("UPDATE user_sessions SET revoked_at = NOW() WHERE user_id=? AND id<>? AND revoked_at IS NULL")
            ->execute([$userId, $keepSessionId]);
    }

    public static function listActiveSessions(PDO $pdo, int $userId): array
    {
        $stmt = $pdo->prepare("SELECT * FROM user_sessions WHERE user_id=? AND revoked_at IS NULL AND expires_at >= NOW() ORDER BY last_active_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function logLogin(PDO $pdo, ?int $userId, ?string $usernameAttempted, string $status): void
    {
        $stmt = $pdo->prepare("INSERT INTO login_history (user_id, username_attempted, status, ip_address, user_agent) VALUES (?,?,?,?,?)");
        $stmt->execute([$userId, $usernameAttempted, $status, self::clientIp(), substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250)]);
    }

    public static function listLoginHistory(PDO $pdo, int $userId, int $limit = 20): array
    {
        $stmt = $pdo->prepare("SELECT * FROM login_history WHERE user_id=? ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function clientIp(): string
    {
        return substr((string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
    }

    public static function friendlyDeviceName(string $ua): string
    {
        $os = 'Unknown OS';
        if (stripos($ua, 'Windows') !== false) $os = 'Windows';
        elseif (stripos($ua, 'Mac OS') !== false) $os = 'macOS';
        elseif (stripos($ua, 'Android') !== false) $os = 'Android';
        elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) $os = 'iOS';
        elseif (stripos($ua, 'Linux') !== false) $os = 'Linux';

        $browser = 'Unknown Browser';
        if (stripos($ua, 'Edg/') !== false) $browser = 'Edge';
        elseif (stripos($ua, 'Chrome') !== false) $browser = 'Chrome';
        elseif (stripos($ua, 'Firefox') !== false) $browser = 'Firefox';
        elseif (stripos($ua, 'Safari') !== false) $browser = 'Safari';

        return "$browser on $os";
    }
}
