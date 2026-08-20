<?php
declare(strict_types=1);

/**
 * "Send money manually, then tell us the transaction ID" - the
 * fallback every ISP in Bangladesh already uses before/alongside API
 * integrations. Needs no credentials at all and works right now: the
 * customer sends money to your bKash/Nagad/Rocket merchant number via
 * their own app, types in the transaction ID, and an admin confirms it
 * in admin/manage_billing.php or admin/verify_payment.php.
 */
class ManualGateway implements GatewayInterface
{
    public function isConfigured(): bool
    {
        return true; // always available
    }

    public function initiate(array $transaction): array
    {
        return [
            'mode' => 'manual',
            'url' => "/pages/payment_redirect.php?invoice_id=" . urlencode((string)$transaction['invoice_id']) . '&manual=1',
        ];
    }

    public function handleCallback(array $requestData): array
    {
        // Manual payments are confirmed by an admin, not an automatic callback.
        return ['success' => false, 'gateway_txn_id' => $requestData['transaction_id'] ?? null, 'raw' => $requestData];
    }
}
