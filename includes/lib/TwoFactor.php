<?php
declare(strict_types=1);

/**
 * Self-contained TOTP (RFC 6238) implementation - compatible with
 * Google Authenticator, Authy, Microsoft Authenticator, 1Password, etc.
 * No external API or credentials needed; this works entirely offline.
 */
class TwoFactor
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 20): string
    {
        $bytes = random_bytes($length);
        return self::base32Encode($bytes);
    }

    public static function getProvisioningUri(string $secret, string $accountEmail, string $issuer = 'MK Net'): string
    {
        $label = rawurlencode($issuer . ':' . $accountEmail);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ]);
        return "otpauth://totp/{$label}?{$params}";
    }

    /** Convenience: a free QR-image endpoint the browser can load directly (no server-side QR library needed). */
    public static function getQrCodeImageUrl(string $provisioningUri): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($provisioningUri);
    }

    /** Verify a 6-digit code, tolerating +-1 time step (30s) of clock drift. */
    public static function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (!preg_match('/^\d{6}$/', $code)) return false;

        $timeStep = (int)floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::generateCode($secret, $timeStep + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function generateCode(string $secret, ?int $timeStep = null): string
    {
        $timeStep = $timeStep ?? (int)floor(time() / 30);
        $key = self::base32Decode($secret);
        $binTime = pack('N*', 0, $timeStep); // 8-byte big-endian counter

        $hash = hash_hmac('sha1', $binTime, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string)($truncated % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /** @return string[] 10 plain-text recovery codes (store only the hashed form!) */
    public static function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    private static function base32Encode(string $data): string
    {
        $bits = '';
        foreach (str_split($data) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $bits = str_pad($bits, (int)ceil(strlen($bits) / 5) * 5, '0', STR_PAD_RIGHT);

        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            $output .= self::BASE32_ALPHABET[bindec($chunk)];
        }
        return $output;
    }

    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(rtrim($b32, '='));
        $bits = '';
        foreach (str_split($b32) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) continue;
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $output = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }
        return $output;
    }
}
