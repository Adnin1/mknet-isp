<?php
declare(strict_types=1);

class Coupon
{
    /**
     * @return array{valid:bool, message:string, discount:float, coupon:?array}
     */
    public static function validate(PDO $pdo, string $code, float $orderAmount): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return ['valid' => false, 'message' => 'Enter a coupon code.', 'discount' => 0, 'coupon' => null];
        }

        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code=? AND active=1 LIMIT 1");
        $stmt->execute([$code]);
        $c = $stmt->fetch();

        if (!$c) {
            return ['valid' => false, 'message' => 'Invalid coupon code.', 'discount' => 0, 'coupon' => null];
        }
        if ($c['starts_at'] && strtotime($c['starts_at']) > time()) {
            return ['valid' => false, 'message' => 'This coupon is not active yet.', 'discount' => 0, 'coupon' => null];
        }
        if ($c['expires_at'] && strtotime($c['expires_at']) < time()) {
            return ['valid' => false, 'message' => 'This coupon has expired.', 'discount' => 0, 'coupon' => null];
        }
        if ($c['max_uses'] !== null && $c['used_count'] >= $c['max_uses']) {
            return ['valid' => false, 'message' => 'This coupon has reached its usage limit.', 'discount' => 0, 'coupon' => null];
        }
        if ($orderAmount < (float)$c['min_amount']) {
            return ['valid' => false, 'message' => 'Minimum order amount for this coupon is ' . number_format((float)$c['min_amount'], 2) . ' BDT.', 'discount' => 0, 'coupon' => null];
        }

        $discount = $c['type'] === 'percent'
            ? round($orderAmount * ((float)$c['value'] / 100), 2)
            : (float)$c['value'];
        $discount = min($discount, $orderAmount);

        return ['valid' => true, 'message' => 'Coupon applied!', 'discount' => $discount, 'coupon' => $c];
    }

    public static function redeem(PDO $pdo, int $couponId): void
    {
        $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id=?")->execute([$couponId]);
    }
}
