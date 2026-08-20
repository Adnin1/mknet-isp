<?php
declare(strict_types=1);

/**
 * SSLCommerz adapter - implements their documented Session API (to
 * start a payment) and Order Validation API (to confirm a callback is
 * genuine). SSLCommerz is an aggregator: once this one integration is
 * live, customers can pay via bKash, Nagad, Rocket, Upay, Visa,
 * Mastercard and more, all through the same hosted checkout page -
 * this is the fastest path to covering every payment method the
 * project asked for.
 *
 * NEEDS CONFIGURATION: config/services.php -> 'sslcommerz' section
 * (store_id / store_passwd). Get a free sandbox account instantly at
 * https://developer.sslcommerz.com - no business verification needed
 * for sandbox testing.
 *
 * This sandbox environment has no outbound network access to
 * sslcommerz.com, so these calls are implemented exactly to their
 * published API contract but could not be live-tested here. Test
 * against your sandbox store_id before going live (see
 * docs/PAYMENT_GATEWAYS.md).
 */
class SslcommerzGateway implements GatewayInterface
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = Services::config('sslcommerz');
    }

    public function isConfigured(): bool
    {
        return !empty($this->cfg['store_id']) && !empty($this->cfg['store_passwd']);
    }

    public function initiate(array $transaction): array
    {
        if (!$this->isConfigured()) {
            // Dev-mode fallback: let the checkout flow be fully tested without real credentials.
            return [
                'mode' => 'simulate',
                'url' => "/pages/payment_callback.php?invoice_id=" . urlencode((string)$transaction['invoice_id']) . '&status=success&gateway=sslcommerz_sim',
            ];
        }

        $base = !empty($this->cfg['sandbox'])
            ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
            : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';

        $postData = [
            'store_id' => $this->cfg['store_id'],
            'store_passwd' => $this->cfg['store_passwd'],
            'total_amount' => number_format((float)$transaction['amount'], 2, '.', ''),
            'currency' => $transaction['currency'] ?? 'BDT',
            'tran_id' => $transaction['tran_id'],
            'success_url' => $transaction['success_url'],
            'fail_url' => $transaction['fail_url'],
            'cancel_url' => $transaction['cancel_url'],
            'ipn_url' => $transaction['ipn_url'] ?? $transaction['success_url'],
            'cus_name' => $transaction['customer_name'] ?? 'Customer',
            'cus_email' => $transaction['customer_email'] ?? 'customer@example.com',
            'cus_add1' => $transaction['customer_address'] ?? 'N/A',
            'cus_city' => $transaction['customer_city'] ?? 'Dhaka',
            'cus_postcode' => '0000',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $transaction['customer_phone'] ?? '01700000000',
            'shipping_method' => 'NO',
            'product_name' => $transaction['product_name'] ?? 'ISP Package',
            'product_category' => 'Internet Service',
            'product_profile' => 'non-physical-goods',
        ];

        $ch = curl_init($base);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("SSLCommerz session request failed: $err");
        }

        $data = json_decode($response, true);
        if (empty($data['status']) || $data['status'] !== 'SUCCESS' || empty($data['GatewayPageURL'])) {
            throw new RuntimeException('SSLCommerz did not return a valid checkout URL: ' . substr($response, 0, 300));
        }

        return ['mode' => 'redirect', 'url' => $data['GatewayPageURL']];
    }

    public function handleCallback(array $requestData): array
    {
        // Simulated dev-mode callback - no real gateway involved.
        if (($requestData['gateway'] ?? '') === 'sslcommerz_sim') {
            return ['success' => ($requestData['status'] ?? '') === 'success', 'gateway_txn_id' => 'SIM-' . time(), 'raw' => $requestData];
        }

        if (!$this->isConfigured()) {
            return ['success' => false, 'gateway_txn_id' => null, 'raw' => $requestData];
        }

        // Real SSLCommerz callback: always re-validate server-side via the
        // Validation API rather than trusting the POSTed status directly
        // (the docs explicitly warn the success_url POST can be spoofed).
        $valId = $requestData['val_id'] ?? null;
        if (!$valId) {
            return ['success' => false, 'gateway_txn_id' => $requestData['tran_id'] ?? null, 'raw' => $requestData];
        }

        $base = !empty($this->cfg['sandbox'])
            ? 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php'
            : 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';

        $params = http_build_query([
            'val_id' => $valId,
            'store_id' => $this->cfg['store_id'],
            'store_passwd' => $this->cfg['store_passwd'],
            'format' => 'json',
        ]);

        $ch = curl_init($base . '?' . $params);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode((string)$response, true) ?: [];
        $success = in_array($data['status'] ?? '', ['VALID', 'VALIDATED'], true);

        return ['success' => $success, 'gateway_txn_id' => $data['bank_tran_id'] ?? ($requestData['tran_id'] ?? null), 'raw' => $data];
    }
}
