<?php
declare(strict_types=1);

/**
 * Handles OTP (one-time-passcode) generation and verification for
 * phone verification, checkout confirmation, etc. Codes are always
 * stored as a salted hash, never in plain text, and are single-use.
 */
class Otp
{
    /** Generate + store + send a 6-digit OTP. Returns the otp_codes row id. */
    public static function issue(PDO $pdo, ?int $userId, string $channel, string $purpose, string $destination): int
    {
        // Rate limit: max 3 codes per destination+purpose per 10 minutes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM otp_codes WHERE destination=? AND purpose=? AND created_at >= (NOW() - INTERVAL 10 MINUTE)");
        $stmt->execute([$destination, $purpose]);
        if ((int)$stmt->fetchColumn() >= 3) {
            throw new RuntimeException('Too many codes requested. Please wait a few minutes and try again.');
        }

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($code, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO otp_codes (user_id, channel, purpose, destination, code_hash, expires_at) VALUES (?,?,?,?,?, NOW() + INTERVAL 10 MINUTE)");
        $stmt->execute([$userId, $channel, $purpose, $destination, $hash]);
        $id = (int)$pdo->lastInsertId();

        $message = "Your MK Net verification code is: $code (valid 10 minutes). Do not share this code with anyone.";
        if ($channel === 'phone') {
            Sms::send($destination, $message);
        } else {
            Mailer::send($destination, '', 'Your MK Net verification code', "<p>$message</p>");
        }

        return $id;
    }

    /** Verify a submitted code against the most recent unconsumed OTP for this destination+purpose. */
    public static function verify(PDO $pdo, string $purpose, string $destination, string $submittedCode): bool
    {
        $stmt = $pdo->prepare(
            "SELECT * FROM otp_codes WHERE purpose=? AND destination=? AND consumed_at IS NULL AND expires_at >= NOW()
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$purpose, $destination]);
        $row = $stmt->fetch();

        if (!$row) return false;

        if ($row['attempts'] >= $row['max_attempts']) {
            return false;
        }

        $pdo->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE id=?")->execute([$row['id']]);

        if (!password_verify($submittedCode, $row['code_hash'])) {
            return false;
        }

        $pdo->prepare("UPDATE otp_codes SET consumed_at = NOW() WHERE id=?")->execute([$row['id']]);
        return true;
    }
}
