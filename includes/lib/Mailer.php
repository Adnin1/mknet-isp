<?php
declare(strict_types=1);

/**
 * Minimal mail sender. No Composer/PHPMailer dependency required so it
 * works even though this project has no vendor/ folder installed.
 *
 * - If SMTP_HOST etc. are configured in config/services.php, it speaks
 *   SMTP directly over a socket (supports STARTTLS, AUTH LOGIN).
 * - Otherwise it falls back to PHP's built-in mail().
 * - Every send attempt (success or failure) is logged to
 *   storage/logs/mail.log via app_log(), and in DEV_MODE the full
 *   email body is also written there so you can test flows (email
 *   verification, password reset, invoices) without a working mail
 *   server.
 *
 * NEEDS CONFIGURATION: see config/services.php - set smtp_host,
 * smtp_port, smtp_user, smtp_pass, smtp_from, smtp_from_name.
 * Until configured, mail() is used, which on most shared hosting
 * either fails silently or lands in spam - fine for local testing,
 * NOT reliable for production. This is the #1 thing to configure
 * before launch.
 */
class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $cfg = Services::config('mail');

        if (!empty($cfg['smtp_host'])) {
            $ok = self::sendSmtp($cfg, $toEmail, $toName, $subject, $htmlBody);
        } elseif (Services::isDevMode()) {
            $ok = true; // dev mode: treated as "sent" so flows remain testable without real SMTP
        } else {
            $ok = self::sendPhpMail($cfg, $toEmail, $subject, $htmlBody);
        }

        app_log('mail', ($ok ? 'SENT' : 'FAILED (falls back to logging only)') . " to=$toEmail subject=\"$subject\"" .
            (Services::isDevMode() ? "\n----BODY----\n" . $htmlBody . "\n----END----" : ''));

        return $ok;
    }

    private static function sendPhpMail(array $cfg, string $toEmail, string $subject, string $htmlBody): bool
    {
        $from = $cfg['smtp_from'] ?? 'no-reply@mknet.example';
        $fromName = $cfg['smtp_from_name'] ?? 'MK Net';
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$from}>\r\n";

        if (!function_exists('mail')) return false;
        return @mail($toEmail, $subject, $htmlBody, $headers);
    }

    /** Small dependency-free SMTP client (PLAIN/LOGIN auth, optional STARTTLS). */
    private static function sendSmtp(array $cfg, string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $host = $cfg['smtp_host'];
        $port = (int)($cfg['smtp_port'] ?? 587);
        $user = $cfg['smtp_user'] ?? '';
        $pass = $cfg['smtp_pass'] ?? '';
        $from = $cfg['smtp_from'] ?? $user;
        $fromName = $cfg['smtp_from_name'] ?? 'MK Net';
        $secure = $cfg['smtp_secure'] ?? 'tls'; // tls|ssl|none

        $transport = $secure === 'ssl' ? 'ssl://' : '';
        $fp = @fsockopen($transport . $host, $port, $errno, $errstr, 10);
        if (!$fp) {
            app_log('mail', "SMTP connect failed: $errstr ($errno)");
            return false;
        }

        $read = function () use ($fp) {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $data;
        };
        $write = function (string $cmd) use ($fp) { fwrite($fp, $cmd . "\r\n"); };

        $read();
        $write("EHLO mknet.local"); $read();

        if ($secure === 'tls') {
            $write("STARTTLS"); $read();
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write("EHLO mknet.local"); $read();
        }

        if ($user !== '') {
            $write("AUTH LOGIN"); $read();
            $write(base64_encode($user)); $read();
            $write(base64_encode($pass)); $resp = $read();
            if (strpos($resp, '235') !== 0 && strpos($resp, '235') === false) {
                app_log('mail', "SMTP auth failed: $resp");
                fclose($fp);
                return false;
            }
        }

        $write("MAIL FROM:<{$from}>"); $read();
        $write("RCPT TO:<{$toEmail}>"); $resp = $read();
        if (strpos($resp, '250') === false) { fclose($fp); return false; }
        $write("DATA"); $read();

        $headers = "From: {$fromName} <{$from}>\r\n";
        $headers .= "To: {$toName} <{$toEmail}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $body = str_replace("\n.", "\n..", $htmlBody);

        $write($headers . "\r\n" . $body . "\r\n.");
        $resp = $read();
        $write("QUIT");
        fclose($fp);

        return strpos($resp, '250') !== false;
    }
}
