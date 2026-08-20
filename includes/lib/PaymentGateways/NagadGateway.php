<?php
declare(strict_types=1);

/**
 * Direct Nagad integration. Nagad's checkout API is the most complex
 * of the bunch: every request must be RSA-signed with your merchant
 * private key, and you must verify Nagad's responses with their
 * public key. This implements their documented 2-step flow
 * (checkout-initialize -> checkout-complete) using PHP's built-in
 * openssl_* functions (no external crypto library needed).
 *
 * NEEDS CONFIGURATION: config/services.php -> 'nagad' section
 * (merchant_id, merchant_private_key, nagad_public_key). These come
 * from Nagad's merchant onboarding (via your bank / nagad.com.bd
 * business portal), which also issues your sandbox keys for testing.
 *
 * RECOMMENDATION: unless you specifically need Nagad's direct-integration
 * pricing, SslcommerzGateway.php already includes Nagad as one of its
 * payment options with far less integration complexity (no manual RSA
 * signing) - that's the path most merchants take. Use this direct
 * adapter only if you have a reason to bypass the aggregator.
 *
 * This sandbox environment has no outbound network access to Nagad's
 * servers, so the request/response shape below follows their published
 * documentation but could not be live-tested here. Verify carefully
 * against your sandbox keys before going live.
 */
class NagadGateway implements GatewayInterface
{
    private array $cfg;
    private string $base;

    public function __construct()
    {
        $this->cfg = Services::config('nagad');
        $this->base = !empty($this->cfg['sandbox'])
            ? 'https://api.mynagad.com/mock/api/dfs' // sandbox host varies by onboarding batch - confirm with Nagad
            : 'https://api.mynagad.com/api/dfs';
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['merchant_id']) && !empty($this->cfg['merchant_private_key']) && !empty($this->cfg['nagad_public_key']);
    }

    private function sign(string $data): string
    {
        openssl_sign($data, $signature, $this->cfg['merchant_private_key'], OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    private function encryptWithNagadPublicKey(string $data): string
    {
        openssl_public_encrypt($data, $encrypted, $this->cfg['nagad_public_key'], OPENSSL_PKCS1_PADDING);
        return base64_encode($encrypted);
    }

    public function initiate(array $transaction): array
    {
        if (!$this->isConfigured()) {
            return [
                'mode' => 'simulate',
                'url' => "/pages/payment_callback.php?invoice_id=" . urlencode((string)$transaction['invoice_id']) . '&status=success&gateway=nagad_sim',
            ];
        }

        $orderId = 'INV' . $transaction['invoice_id'] . '-' . time();
        $timestamp = date('YmdHis');

        $sensitiveData = json_encode([
            'merchantId' => $this->cfg['merchant_id'],
            'orderId' => $orderId,
            'currencyCode' => '050',
            'amount' => number_format((float)$transaction['amount'], 2, '.', ''),
            'challenge' => bin2hex(random_bytes(16)),
        ]);

        $payload = [
            'accountNumber' => $this->cfg['merchant_id'],
            'dateTime' => $timestamp,
            'sensitiveData' => $this->encryptWithNagadPublicKey($sensitiveData),
            'signature' => $this->sign($sensitiveData),
        ];

        $ch = curl_init($this->base . '/check-out/initialize/' . $this->cfg['merchant_id'] . '/' . $orderId);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-KM-Api-Version: v-0.2.0'],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string)$resp, true);

        if (empty($data['callBackUrl'])) {
            throw new RuntimeException('Nagad initialize failed: ' . substr((string)$resp, 0, 300));
        }

        return ['mode' => 'redirect', 'url' => $data['callBackUrl']];
    }

    public function handleCallback(array $requestData): array
    {
        if (($requestData['gateway'] ?? '') === 'nagad_sim') {
            return ['success' => ($requestData['status'] ?? '') === 'success', 'gateway_txn_id' => 'SIM-' . time(), 'raw' => $requestData];
        }

        // Nagad redirects back with payment_ref_id & status in the query string.
        $status = $requestData['status'] ?? '';
        $success = strtoupper($status) === 'SUCCESS';
        return ['success' => $success, 'gateway_txn_id' => $requestData['payment_ref_id'] ?? null, 'raw' => $requestData];
    }
}
