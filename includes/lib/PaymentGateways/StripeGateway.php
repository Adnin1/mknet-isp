<?php
declare(strict_types=1);

/**
 * Stripe integration using Checkout Sessions - the simplest and most
 * current Stripe integration path (Stripe hosts the payment page itself,
 * so no card data ever touches this server, keeping PCI scope minimal).
 * Implemented to Stripe's documented REST API contract.
 *
 * ============================================================
 * IMPORTANT - read before trying to configure this one
 * ============================================================
 * Same fundamental issue as PayPal (see PaypalGateway.php): verified
 * against Stripe's own supported-countries documentation, Bangladesh is
 * not one of Stripe's ~46 fully-supported countries for opening a
 * merchant account. This isn't a missing-API-key problem - a
 * Bangladesh-registered business cannot sign up for a standard Stripe
 * account at all. The common workaround (same as PayPal) is
 * incorporating a business entity in a supported country (US, UK, EU,
 * etc.) and opening the Stripe account under that entity.
 *
 * NEEDS CONFIGURATION (only relevant if the above is resolved):
 * config/services.php -> 'stripe' section (secret_key). Get test-mode
 * keys instantly at https://dashboard.stripe.com/test/apikeys under a
 * supported-country account.
 *
 * This sandbox environment has no outbound network access to
 * stripe.com, so this could not be live-tested here - test against
 * your test-mode keys before going live.
 */
class StripeGateway implements GatewayInterface
{
    private array $cfg;
    private const API_BASE = 'https://api.stripe.com/v1';

    public function __construct()
    {
        $this->cfg = Services::config('stripe');
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['secret_key']);
    }

    private function request(string $method, string $path, array $params = []): array
    {
        $ch = curl_init(self::API_BASE . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->cfg['secret_key']],
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($params); // Stripe expects form-encoded, incl. bracket notation for nested params
        }
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new RuntimeException("Stripe API request failed: $err");
        }
        return json_decode($resp, true) ?: [];
    }

    public function initiate(array $transaction): array
    {
        if (!$this->isConfigured()) {
            return [
                'mode' => 'simulate',
                'url' => "/pages/payment_callback.php?invoice_id=" . urlencode((string)$transaction['invoice_id']) . '&status=success&gateway=stripe_sim',
            ];
        }

        $data = $this->request('POST', '/checkout/sessions', [
            'mode' => 'payment',
            'success_url' => $transaction['success_url'] . (str_contains($transaction['success_url'], '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $transaction['cancel_url'],
            'customer_email' => $transaction['customer_email'] ?? null,
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($transaction['currency'] ?? 'usd'),
                    'product_data' => ['name' => $transaction['product_name'] ?? 'MK Net Invoice #' . $transaction['invoice_id']],
                    'unit_amount' => (int)round((float)$transaction['amount'] * 100), // Stripe uses the smallest currency unit
                ],
                'quantity' => 1,
            ]],
        ]);

        if (empty($data['url'])) {
            throw new RuntimeException('Stripe Checkout Session creation failed: ' . json_encode($data));
        }

        return ['mode' => 'redirect', 'url' => $data['url']];
    }

    public function handleCallback(array $requestData): array
    {
        if (($requestData['gateway'] ?? '') === 'stripe_sim') {
            return ['success' => ($requestData['status'] ?? '') === 'success', 'gateway_txn_id' => 'SIM-' . time(), 'raw' => $requestData];
        }

        $sessionId = $requestData['session_id'] ?? null;
        if (!$sessionId || !$this->isConfigured()) {
            return ['success' => false, 'gateway_txn_id' => null, 'raw' => $requestData];
        }

        // Always re-fetch the session server-side rather than trusting the
        // redirect alone - the query string can be replayed/guessed.
        $session = $this->request('GET', '/checkout/sessions/' . urlencode($sessionId));
        $success = ($session['payment_status'] ?? '') === 'paid';

        return ['success' => $success, 'gateway_txn_id' => $session['payment_intent'] ?? $sessionId, 'raw' => $session];
    }
}
