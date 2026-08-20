<?php
declare(strict_types=1);

/**
 * Upay (UCB Fintech). Unlike bKash/Nagad,
 * Upay does not offer a simple self-serve merchant API - direct
 * integration requires a bilateral agreement with UCB. The practical
 * path (and what this adapter does) is to route Upay payments
 * through SSLCommerz's hosted checkout, where Upay is already one of
 * the selectable payment methods once your SSLCommerz account is live.
 *
 * NEEDS CONFIGURATION: same as SslcommerzGateway.php (config/services.php
 * -> 'sslcommerz'). If you later negotiate direct UCB access, replace
 * the body of initiate()/handleCallback() with UCB's API per their
 * merchant agreement documentation.
 */
class UpayGateway implements GatewayInterface
{
    private SslcommerzGateway $ssl;

    public function __construct()
    {
        $this->ssl = new SslcommerzGateway();
    }

    public function isConfigured(): bool
    {
        return $this->ssl->isConfigured();
    }

    public function initiate(array $transaction): array
    {
        if (!$this->isConfigured()) {
            return [
                'mode' => 'simulate',
                'url' => "/pages/payment_callback.php?invoice_id=" . urlencode((string)$transaction['invoice_id']) . '&status=success&gateway=upay_sim',
            ];
        }
        return $this->ssl->initiate($transaction);
    }

    public function handleCallback(array $requestData): array
    {
        if (($requestData['gateway'] ?? '') === 'upay_sim') {
            return ['success' => ($requestData['status'] ?? '') === 'success', 'gateway_txn_id' => 'SIM-' . time(), 'raw' => $requestData];
        }
        return $this->ssl->handleCallback($requestData);
    }
}
