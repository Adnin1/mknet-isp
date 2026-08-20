<?php
declare(strict_types=1);

/**
 * Direct bKash integration using the "Tokenized Checkout" API (grant
 * token -> create payment -> redirect -> execute on callback).
 * Implemented to bKash's published v1.2.0-beta API contract.
 *
 * NEEDS CONFIGURATION: config/services.php -> 'bkash' section
 * (app_key, app_secret, username, password from https://developer.bka.sh
 * merchant sandbox signup).
 *
 * Note: if you'd rather not manage bKash's token lifecycle yourself,
 * SslcommerzGateway.php already includes bKash as one of its payment
 * options with a single, simpler integration - use this direct adapter
 * only if you have a reason to bypass the aggregator (e.g. a lower
 * per-transaction fee negotiated directly with bKash).
 *
 * This sandbox environment has no outbound network access to bka.sh,
 * so this could not be live-tested here - verify against your sandbox
 * credentials before going live.
 */
class BkashGateway implements GatewayInterface
{
    private array $cfg;
    private string $base;

    public function __construct()
    {
        $this->cfg = Services::config('bkash');
        $this->base = !empty($this->cfg['sandbox'])
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['app_key']) && !empty($this->cfg['app_secret'])
            && !empty($this->cfg['username']) && !empty($this->cfg['password']);
    }

    private function grantToken(): string
    {
        $ch = curl_init($this->base . '/tokenized/checkout/token/grant');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'username: ' . $this->cfg['username'],
                'password: ' . $this->cfg['password'],
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'app_key' => $this->cfg['app_key'],
                'app_secret' => $this->cfg['app_secret'],
            ]),
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string)$resp, true);
        if (empty($data['id_token'])) {
            throw new RuntimeException('bKash token grant failed: ' . substr((string)$resp, 0, 300));
        }
        return $data['id_token'];
    }

    public function initiate(array $transaction): array
    {
        if (!$this->isConfigured()) {
            return [
                'mode' => 'simulate',
                'url' => "/pages/payment_callback.php?invoice_id=" . urlencode((string)$transaction['invoice_id']) . '&status=success&gateway=bkash_sim',
            ];
        }

        $token = $this->grantToken();

        $ch = curl_init($this->base . '/tokenized/checkout/create');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $token,
                'X-App-Key: ' . $this->cfg['app_key'],
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'mode' => '0011',
                'payerReference' => (string)$transaction['invoice_id'],
                'callbackURL' => $transaction['success_url'],
                'amount' => number_format((float)$transaction['amount'], 2, '.', ''),
                'currency' => 'BDT',
                'intent' => 'sale',
                'merchantInvoiceNumber' => 'INV-' . $transaction['invoice_id'],
            ]),
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string)$resp, true);

        if (empty($data['bkashURL'])) {
            throw new RuntimeException('bKash create payment failed: ' . substr((string)$resp, 0, 300));
        }

        return ['mode' => 'redirect', 'url' => $data['bkashURL']];
    }

    public function handleCallback(array $requestData): array
    {
        if (($requestData['gateway'] ?? '') === 'bkash_sim') {
            return ['success' => ($requestData['status'] ?? '') === 'success', 'gateway_txn_id' => 'SIM-' . time(), 'raw' => $requestData];
        }

        $paymentId = $requestData['paymentID'] ?? null;
        $status = $requestData['status'] ?? '';
        if (!$paymentId || $status !== 'success' || !$this->isConfigured()) {
            return ['success' => false, 'gateway_txn_id' => $paymentId, 'raw' => $requestData];
        }

        // Execute the payment to actually capture the funds (bKash requires this explicit step).
        $token = $this->grantToken();
        $ch = curl_init($this->base . '/tokenized/checkout/execute/' . urlencode($paymentId));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . $token,
                'X-App-Key: ' . $this->cfg['app_key'],
            ],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string)$resp, true) ?: [];

        $success = ($data['transactionStatus'] ?? '') === 'Completed';
        return ['success' => $success, 'gateway_txn_id' => $data['trxID'] ?? $paymentId, 'raw' => $data];
    }
}
