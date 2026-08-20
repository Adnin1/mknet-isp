<?php
declare(strict_types=1);

/**
 * Visa / Mastercard. Card acquiring in Bangladesh almost universally
 * goes through a payment aggregator rather than a direct bank
 * integration (direct acquiring requires a PCI-DSS audited merchant
 * account, which is a much bigger undertaking than a typical ISP
 * website needs). SSLCommerz's hosted checkout already accepts Visa/
 * Mastercard/Amex directly, so this adapter routes there.
 *
 * NEEDS CONFIGURATION: config/services.php -> 'sslcommerz' section.
 * If you sign a direct contract with a card processor (e.g. Stripe,
 * once available in your region, or a bank's own gateway), swap the
 * body of this class for that processor's SDK/API instead.
 */
class CardGateway implements GatewayInterface
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
                'url' => "/pages/payment_callback.php?invoice_id=" . urlencode((string)$transaction['invoice_id']) . '&status=success&gateway=card_sim',
            ];
        }
        return $this->ssl->initiate($transaction);
    }

    public function handleCallback(array $requestData): array
    {
        if (($requestData['gateway'] ?? '') === 'card_sim') {
            return ['success' => ($requestData['status'] ?? '') === 'success', 'gateway_txn_id' => 'SIM-' . time(), 'raw' => $requestData];
        }
        return $this->ssl->handleCallback($requestData);
    }
}
