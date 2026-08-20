<?php
declare(strict_types=1);

require_once __DIR__ . '/GatewayInterface.php';
require_once __DIR__ . '/SslcommerzGateway.php';
require_once __DIR__ . '/BkashGateway.php';
require_once __DIR__ . '/NagadGateway.php';
require_once __DIR__ . '/RocketGateway.php';
require_once __DIR__ . '/UpayGateway.php';
require_once __DIR__ . '/CardGateway.php';
require_once __DIR__ . '/ManualGateway.php';
require_once __DIR__ . '/StripeGateway.php';
require_once __DIR__ . '/PaypalGateway.php';

class PaymentGatewayFactory
{
    public const GATEWAYS = [
        'sslcommerz' => ['label' => 'SSLCommerz (Cards, Mobile Banking & more)', 'class' => SslcommerzGateway::class],
        'bkash'      => ['label' => 'bKash', 'class' => BkashGateway::class],
        'nagad'      => ['label' => 'Nagad', 'class' => NagadGateway::class],
        'rocket'     => ['label' => 'Rocket', 'class' => RocketGateway::class],
        'upay'       => ['label' => 'Upay', 'class' => UpayGateway::class],
        'card'       => ['label' => 'Visa / Mastercard', 'class' => CardGateway::class],
        'stripe'     => ['label' => 'Stripe (International Cards)', 'class' => StripeGateway::class],
        'paypal'     => ['label' => 'PayPal (International)', 'class' => PaypalGateway::class],
        'manual'     => ['label' => 'Manual Transfer (send + submit Transaction ID)', 'class' => ManualGateway::class],
    ];

    public static function make(string $gateway): GatewayInterface
    {
        if (!isset(self::GATEWAYS[$gateway])) {
            throw new InvalidArgumentException("Unknown payment gateway: $gateway");
        }
        $class = self::GATEWAYS[$gateway]['class'];
        return new $class();
    }

    /** Record a new payment attempt in the gateway-agnostic ledger. Returns the new payment_transactions id. */
    public static function recordAttempt(PDO $pdo, int $invoiceId, ?int $userId, string $gateway, float $amount, string $currency = 'BDT'): int
    {
        try {
            $stmt = $pdo->prepare("INSERT INTO payment_transactions (invoice_id, user_id, gateway, amount, currency, status) VALUES (?,?,?,?,?, 'initiated')");
            $stmt->execute([$invoiceId, $userId, $gateway, $amount, $currency]);
            return (int)$pdo->lastInsertId();
        } catch (Throwable $e) {
            // Self-healing table creation if missing
            $pdo->exec("CREATE TABLE IF NOT EXISTS `payment_transactions` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `invoice_id` INT NOT NULL,
              `user_id` INT NULL,
              `gateway` VARCHAR(50) NOT NULL,
              `trx_id` VARCHAR(100) NULL,
              `sender_number` VARCHAR(30) NULL,
              `amount` DECIMAL(10,2) NOT NULL,
              `currency` VARCHAR(10) DEFAULT 'BDT',
              `status` VARCHAR(30) NOT NULL DEFAULT 'initiated',
              `gateway_txn_id` VARCHAR(100) NULL,
              `raw_response` TEXT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $stmt = $pdo->prepare("INSERT INTO payment_transactions (invoice_id, user_id, gateway, amount, currency, status) VALUES (?,?,?,?,?, 'initiated')");
            $stmt->execute([$invoiceId, $userId, $gateway, $amount, $currency]);
            return (int)$pdo->lastInsertId();
        }
    }

    public static function recordResult(PDO $pdo, int $transactionId, bool $success, ?string $gatewayTxnId, array $raw): void
    {
        $stmt = $pdo->prepare("UPDATE payment_transactions SET status=?, gateway_txn_id=?, raw_response=? WHERE id=?");
        $stmt->execute([$success ? 'success' : 'failed', $gatewayTxnId, json_encode($raw), $transactionId]);
    }
}
