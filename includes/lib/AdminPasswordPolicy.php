<?php
declare(strict_types=1);

/**
 * Shared NIST SP 800-63B-4 Admin Password Policy, Argon2id Hashing,
 * Persistent Rate Limiting, and Trusted Base URL Helper.
 */
final class AdminPasswordPolicy
{
    public const MIN_LENGTH = 15;
    public const MAX_LENGTH = 128;

    private const BLOCKLIST = [
        'password1234567',
        'password12345678',
        '123456789012345',
        '1234567890123456',
        'qwertyuiopasdfg',
        'admin1234567890',
        'mknetadmin2026!',
        'mknet@admin12345',
        'letmein12345678',
        'welcome12345678',
        'administrator12',
        'changeme1234567',
        'mknet',
        'mknetisp',
        'mknetadmin',
        'administrator',
    ];

    /**
     * Validate UTF-8 and apply Unicode NFC normalization when ext-intl Normalizer is available.
     * Spaces are preserved verbatim per NIST SP 800-63B-4.
     */
    public static function normalize(string $password): string
    {
        if (preg_match('//u', $password) !== 1) {
            throw new \InvalidArgumentException('Password must be valid UTF-8.');
        }

        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($password, \Normalizer::FORM_C);
            if ($normalized === false) {
                throw new \InvalidArgumentException('Password failed Unicode NFC normalization.');
            }
            return $normalized;
        }

        return $password;
    }

    /**
     * Count Unicode code points (NOT UTF-8 bytes).
     */
    public static function codePointLength(string $password): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($password, 'UTF-8');
        }
        $count = preg_match_all('/./us', $password);
        return $count === false ? 0 : $count;
    }

    /**
     * Validate admin password against NIST SP 800-63B-4 rules:
     * - 15 to 128 Unicode code points
     * - No composition rules
     * - Spaces and Unicode preserved
     * - Exact full-password blocklist + exact username match
     *
     * @return string[] List of validation error messages (empty if valid)
     */
    public static function validate(string $password, string $username = ''): array
    {
        try {
            $normalized = self::normalize($password);
        } catch (\InvalidArgumentException $e) {
            return [$e->getMessage()];
        }

        $len = self::codePointLength($normalized);
        if ($len < self::MIN_LENGTH || $len > self::MAX_LENGTH) {
            return ['Admin password must be between ' . self::MIN_LENGTH . ' and ' . self::MAX_LENGTH . ' characters.'];
        }

        $lower = function_exists('mb_strtolower')
            ? mb_strtolower($normalized, 'UTF-8')
            : strtolower($normalized);

        if (in_array($lower, self::BLOCKLIST, true)) {
            return ['Admin password is too common or compromised. Choose a stronger passphrase.'];
        }

        $trimmedUser = trim($username);
        if ($trimmedUser !== '') {
            $lowerUser = function_exists('mb_strtolower')
                ? mb_strtolower($trimmedUser, 'UTF-8')
                : strtolower($trimmedUser);
            if ($lower === $lowerUser) {
                return ['Admin password must not match the username.'];
            }
        }

        return [];
    }

    /**
     * Hash admin password using mandatory PASSWORD_ARGON2ID (fail-closed if unavailable).
     */
    public static function hash(string $password): string
    {
        $normalized = self::normalize($password);

        if (!defined('PASSWORD_ARGON2ID') || !in_array(PASSWORD_ARGON2ID, password_algos(), true)) {
            throw new \RuntimeException('PASSWORD_ARGON2ID is required but unavailable in this PHP runtime.');
        }

        $hash = password_hash($normalized, PASSWORD_ARGON2ID);
        if (!is_string($hash) || $hash === '') {
            throw new \RuntimeException('Failed to hash admin password with Argon2id.');
        }
        return $hash;
    }

    /**
     * Verify password against stored hash with consistent normalization and legacy compatibility.
     */
    public static function verify(string $password, string $storedHash): bool
    {
        if ($storedHash === '' || $password === '') {
            return false;
        }
        if (preg_match('//u', $password) !== 1) {
            return false;
        }

        try {
            $normalized = self::normalize($password);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        if (password_verify($normalized, $storedHash)) {
            return true;
        }

        if ($normalized !== $password && password_verify($password, $storedHash)) {
            return true;
        }

        return false;
    }

    /**
     * Return a trusted configured base URL without trusting HTTP_HOST.
     * Requires https:// in non-local environments; allows http://localhost or http://127.0.0.1
     * only when MKNET_ENV is development, local, or testing.
     */
    public static function trustedBaseUrl(): string
    {
        $raw = getenv('MKNET_APP_URL');
        if (!is_string($raw) || trim($raw) === '') {
            $raw = defined('MKNET_APP_URL') ? (string)MKNET_APP_URL : '';
        }
        $raw = rtrim(trim($raw), '/');
        if ($raw === '') {
            return '';
        }

        $parts = parse_url($raw);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $env = strtolower(trim((string)(getenv('MKNET_ENV') ?: '')));

        if ($scheme === 'https') {
            return $raw;
        }

        if ($scheme === 'http'
            && in_array($env, ['development', 'local', 'testing'], true)
            && in_array($host, ['localhost', '127.0.0.1'], true)
        ) {
            return $raw;
        }

        return '';
    }

    /**
     * Non-session-local persistent rate limit check backed by DB table auth_rate_limits.
     * Fails closed (returns false) on DB/schema error without executing request-time DDL.
     */
    public static function checkPersistentRateLimit(
        \PDO $pdo,
        string $scope,
        string $identifier,
        int $maxAttempts = 5,
        int $windowSeconds = 900
    ): bool {
        try {
            $key = hash('sha256', $scope . '|' . $identifier);
            $now = time();

            // Opportunistic bounded cleanup of expired records
            $pdo->prepare('DELETE FROM auth_rate_limits WHERE expires_at < ?')->execute([$now]);

            $stmt = $pdo->prepare('SELECT attempts, window_start FROM auth_rate_limits WHERE rate_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return true;
            }

            if (($now - (int)$row['window_start']) > $windowSeconds) {
                $pdo->prepare('DELETE FROM auth_rate_limits WHERE rate_key = ?')->execute([$key]);
                return true;
            }

            return (int)$row['attempts'] < $maxAttempts;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Record a rate-limited attempt atomically in auth_rate_limits (no request-time DDL).
     */
    public static function recordRateLimitAttempt(
        \PDO $pdo,
        string $scope,
        string $identifier,
        int $windowSeconds = 900
    ): void {
        $key = hash('sha256', $scope . '|' . $identifier);
        $now = time();
        $expiresAt = $now + $windowSeconds;
        $driver = strtolower((string)$pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));

        try {
            if ($driver === 'sqlite') {
                $pdo->prepare(
                    'INSERT INTO auth_rate_limits (rate_key, attempts, window_start, expires_at)
                     VALUES (?, 1, ?, ?)
                     ON CONFLICT(rate_key) DO UPDATE SET
                       attempts = CASE WHEN (? - auth_rate_limits.window_start) > ? THEN 1 ELSE auth_rate_limits.attempts + 1 END,
                       window_start = CASE WHEN (? - auth_rate_limits.window_start) > ? THEN ? ELSE auth_rate_limits.window_start END,
                       expires_at = ?'
                )->execute([$key, $now, $expiresAt, $now, $windowSeconds, $now, $windowSeconds, $now, $expiresAt]);
                return;
            }

            $pdo->prepare(
                'INSERT INTO auth_rate_limits (rate_key, attempts, window_start, expires_at)
                 VALUES (?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE
                   attempts = IF((? - window_start) > ?, 1, attempts + 1),
                   window_start = IF((? - window_start) > ?, ?, window_start),
                   expires_at = ?'
            )->execute([$key, $now, $expiresAt, $now, $windowSeconds, $now, $windowSeconds, $now, $expiresAt]);
        } catch (\Throwable $e) {
            // Fail closed on next checkPersistentRateLimit if schema/DB is unavailable
        }
    }
}
