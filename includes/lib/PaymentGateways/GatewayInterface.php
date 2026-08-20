<?php
declare(strict_types=1);

interface GatewayInterface
{
    /**
     * Start a payment. Returns an array:
     *   ['mode' => 'redirect', 'url' => '...']   - send the browser here
     *   ['mode' => 'simulate', 'url' => '...']   - dev-mode fallback (no real credentials yet)
     * Throws RuntimeException on failure.
     */
    public function initiate(array $transaction): array;

    /** Validate + interpret a callback/IPN hit from the gateway. Returns ['success'=>bool, 'gateway_txn_id'=>?string, 'raw'=>array]. */
    public function handleCallback(array $requestData): array;

    public function isConfigured(): bool;
}
