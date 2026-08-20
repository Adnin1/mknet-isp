<?php
declare(strict_types=1);

/**
 * PayPal integration using the Orders API v2 (create order -> customer
 * approves on PayPal's site -> capture order on return). Implemented to
 * PayPal's documented REST API contract.
 *
 * ============================================================
 * IMPORTANT - read before trying to configure this one
 * ============================================================
 * Unlike every other gateway in this project, this isn't just waiting on
 * an API key. Two structural facts, verified against PayPal's own
 * documentation:
 *
 *   1. PayPal does not support BDT as a payment currency at all (it's
 *      not on their supported-currency list - USD/EUR/GBP and ~25
 *      others are, BDT isn't one of them).
 *   2. Bangladesh is not a PayPal-supported country for a standard
 *      Business account - opening one requires a foreign business
 *      entity (e.g. a US LLC), not just signing up.
 *
 * So this adapter charges in USD by design (see 'currency' below) and
 * will simply have no way to work at all unless MK Net has (or sets up)
 * a PayPal Business account registered under a non-Bangladesh entity.
 * This is a business/legal decision, not something more API integration
 * work can solve - see docs/INCOMPLETE_FEATURES.md.
 *
 * NEEDS CONFIGURATION (only relevant if the above is resolved):
 * config/services.php -> 'paypal' section (client_id, client_secret from
 * https://developer.paypal.com).
 */
class PaypalGateway implements GatewayInterface
{
    private array $cfg;
    private string $base;

    public function __construct()
    {
        $this->cfg = Services::config('paypal');
        $this->base = !empty($this->cfg['sandbox'])
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['client_id']) && !empty($this->cfg['client_secret']);
    }

    private function getAccessToken(): string
    {
        $ch = curl_init($this->base . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERPWD => $this->cfg['client_id'] . ':' . $this->cfg['client_secret'],
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string)$resp, true);
        if (empty($data['access_token'])) {
            throw new RuntimeException('PayPal OAuth token request failed: ' . substr((string)$resp, 0, 300));
        }
        return $data['access_token'];
    }

    public function initiate(array $transaction): array
    {
        if (!$this->isConfigured()) {
            return [
                'mode' => 'simulate',
                'url' => "/pages/payment_callback.php?invoice_id=" . urlencode((string)$transaction['invoice_id']) . '&status=success&gateway=paypal_sim',
            ];
        }

        $token = $this->getAccessToken();
        // PayPal cannot bill in BDT (see class docblock) - this converts
        // using a fixed rate you must set yourself in config, since this
        // sandbox has no network access to fetch a live FX rate. Update
        // 'bdt_to_usd_rate' periodically, or better, fetch it from a live
        // FX API in production.
        $usdAmount = round((float)$transaction['amount'] * (float)($this->cfg['bdt_to_usd_rate'] ?? 0.0082), 2);

        $ch = curl_init($this->base . '/v2/checkout/orders');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
            CURLOPT_POSTFIELDS => json_encode([
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => (string)$transaction['invoice_id'],
                    'description' => $transaction['product_name'] ?? ('MK Net Invoice #' . $transaction['invoice_id']),
                    'amount' => ['currency_code' => 'USD', 'value' => number_format($usdAmount, 2, '.', '')],
                ]],
                'application_context' => [
                    'return_url' => $transaction['success_url'],
                    'cancel_url' => $transaction['cancel_url'],
                ],
            ]),
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string)$resp, true);

        $approveUrl = null;
        foreach ($data['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') { $approveUrl = $link['href']; break; }
        }
        if (!$approveUrl) {
            throw new RuntimeException('PayPal order creation failed: ' . substr((string)$resp, 0, 300));
        }

        return ['mode' => 'redirect', 'url' => $approveUrl];
    }

    public function handleCallback(array $requestData): array
    {
        if (($requestData['gateway'] ?? '') === 'paypal_sim') {
            return ['success' => ($requestData['status'] ?? '') === 'success', 'gateway_txn_id' => 'SIM-' . time(), 'raw' => $requestData];
        }

        $orderId = $requestData['token'] ?? null; // PayPal returns the order id as `token` on the return_url
        if (!$orderId || !$this->isConfigured()) {
            return ['success' => false, 'gateway_txn_id' => $orderId, 'raw' => $requestData];
        }

        $accessToken = $this->getAccessToken();
        $ch = curl_init($this->base . '/v2/checkout/orders/' . urlencode($orderId) . '/capture');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string)$resp, true) ?: [];

        $success = ($data['status'] ?? '') === 'COMPLETED';
        return ['success' => $success, 'gateway_txn_id' => $data['id'] ?? $orderId, 'raw' => $data];
    }
}
