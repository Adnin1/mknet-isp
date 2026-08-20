<?php
declare(strict_types=1);

/**
 * SMS sender. Most Bangladeshi SMS gateways (Alpha SMS / BulkSMSBD,
 * SSL Wireless, REVE) expose a simple REST endpoint taking
 * api_key + sender_id + number + message as GET or POST params, so
 * this ships one generic adapter that fits most of them out of the
 * box. If your provider's request shape differs, adjust sendViaHttp()
 * below - everything else (OTP generation, verification, rate
 * limiting) is provider-independent and won't need to change.
 *
 * NEEDS CONFIGURATION: config/services.php -> 'sms' section.
 * Until configured, messages are written to storage/logs/sms.log
 * instead of being sent, so OTP flows are still fully testable.
 */
class Sms
{
    public static function send(string $toPhone, string $message): bool
    {
        $cfg = Services::config('sms');

        if (empty($cfg['api_url']) || empty($cfg['api_key'])) {
            app_log('sms', "DEV MODE (not configured) to=$toPhone message=\"$message\"");
            return true; // dev mode: treated as "sent" so flows remain testable
        }

        $ok = self::sendViaHttp($cfg, $toPhone, $message);
        app_log('sms', ($ok ? 'SENT' : 'FAILED') . " to=$toPhone");
        return $ok;
    }

    private static function sendViaHttp(array $cfg, string $toPhone, string $message): bool
    {
        $params = http_build_query([
            'api_key'   => $cfg['api_key'],
            'senderid'  => $cfg['sender_id'] ?? '',
            'number'    => $toPhone,
            'message'   => $message,
        ]);

        $ch = curl_init($cfg['api_url'] . '?' . $params);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        $ok = $resp !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) < 400;
        curl_close($ch);
        return $ok;
    }
}
