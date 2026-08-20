<?php
declare(strict_types=1);

/**
 * Loads third-party credentials from config/services.php (see
 * config/services.example.php for the template - copy it to
 * config/services.php and fill in your real keys).
 *
 * If config/services.php doesn't exist yet, every service simply comes
 * back "not configured" and the app runs in DEV MODE: OTPs and email
 * links are written to storage/logs/ instead of actually being sent,
 * so you can fully test every flow (registration, checkout, 2FA)
 * before you have real SMTP/SMS/payment credentials.
 */
class Services
{
    private static ?array $all = null;

    private static function load(): array
    {
        if (self::$all !== null) return self::$all;

        $path = __DIR__ . '/../../config/services.php';
        self::$all = is_file($path) ? (require $path) : [];
        return self::$all;
    }

    public static function config(string $section): array
    {
        $all = self::load();
        return $all[$section] ?? [];
    }

    public static function isDevMode(): bool
    {
        $all = self::load();
        return empty($all) || !empty($all['dev_mode']);
    }

    public static function isConfigured(string $section): bool
    {
        $cfg = self::config($section);
        return !empty($cfg) && !empty(array_filter($cfg));
    }
}
