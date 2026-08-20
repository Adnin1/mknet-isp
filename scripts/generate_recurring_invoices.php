<?php
declare(strict_types=1);

/**
 * Recurring billing automation.
 *
 * Run this once a day via cron:
 *   0 6 * * *  php /path/to/mknet/scripts/generate_recurring_invoices.php
 *
 * What it does:
 *  1. Finds every customer whose billing_cycles.next_billing_at has arrived
 *     (and who hasn't turned off auto-renew) and creates their next
 *     invoice automatically, then advances their next billing date by
 *     one month.
 *  2. Finds pending invoices older than LATE_FEE_GRACE_DAYS and applies a
 *     one-time late fee (tracked in the invoice's `meta` JSON so no schema
 *     change was needed), then notifies the customer.
 *
 * Safe to run more than once a day: both steps are idempotent (a user is
 * only billed once their next_billing_at has passed AND is immediately
 * pushed a month forward; a late fee is only ever applied once per
 * invoice, tracked via meta.late_fee_applied).
 */

require_once __DIR__ . '/../config.php';

const LATE_FEE_GRACE_DAYS = 7;
const LATE_FEE_PERCENT = 5.0; // 5% of the invoice amount

$today = date('Y-m-d');
$billedCount = 0;
$lateFeeCount = 0;

// ---------------------------------------------------------------------
// 1) Generate renewal invoices
// ---------------------------------------------------------------------
try {
    $due = $pdo->prepare(
        "SELECT bc.*, u.email, u.full_name, p.name AS package_name, p.price_bdt
         FROM billing_cycles bc
         JOIN users u ON bc.user_id = u.id
         LEFT JOIN packages p ON bc.package_id = p.id
         WHERE bc.auto_renew = 1 AND bc.next_billing_at <= ? AND u.account_status = 'active'"
    );
    $due->execute([$today]);

foreach ($due->fetchAll() as $row) {
    $amount = (float)($row['price_bdt'] ?? 0);
    if ($amount <= 0) continue; // package was deleted/misconfigured - skip rather than bill $0

    $meta = json_encode([
        'type' => 'package_renewal',
        'package_id' => $row['package_id'],
        'auto_generated' => true,
        'billing_cycle_id' => $row['id'],
    ]);

    $pdo->beginTransaction();
    $ins = $pdo->prepare("INSERT INTO invoices (user_id, package_id, amount, currency, status, meta, created_at) VALUES (?,?,?,?,'pending',?,NOW())");
    $ins->execute([$row['user_id'], $row['package_id'], $amount, 'BDT', $meta]);
    $invoiceId = (int)$pdo->lastInsertId();

    $pdo->prepare("UPDATE billing_cycles SET last_billed_at = ?, next_billing_at = ? + INTERVAL 1 MONTH WHERE id = ?")
        ->execute([$today, $today, $row['id']]);
    $pdo->commit();

    Notifier::send(
        $pdo, (int)$row['user_id'], 'Your new invoice is ready',
        "Your {$row['package_name']} package has renewed. Invoice #{$invoiceId} for " . number_format($amount, 2) . " BDT is now due.",
        'billing', "/pages/paybill.php?package_id=" . $row['package_id']
    );
    Mailer::send($row['email'], $row['full_name'] ?? '', 'Your MK Net invoice is ready',
        "<p>Hi " . h($row['full_name'] ?? '') . ",</p><p>Your <b>{$row['package_name']}</b> package has renewed. " .
        "Invoice #{$invoiceId} for <b>" . number_format($amount, 2) . " BDT</b> is now due.</p>" .
        "<p><a href=\"http://" . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com') . "/pages/dashboard.php\">View in your dashboard</a></p>");

    app_log('billing_cron', "Generated renewal invoice #$invoiceId for user {$row['user_id']} ({$amount} BDT)");
    $billedCount++;
}

// ---------------------------------------------------------------------
// 2) Apply late fees to overdue pending invoices
// ---------------------------------------------------------------------
$overdue = $pdo->prepare(
    "SELECT i.*, u.email, u.full_name FROM invoices i
     JOIN users u ON i.user_id = u.id
     WHERE i.status = 'pending' AND i.created_at <= (NOW() - INTERVAL ? DAY)"
);
$overdue->execute([LATE_FEE_GRACE_DAYS]);

foreach ($overdue->fetchAll() as $inv) {
    $meta = json_decode((string)$inv['meta'], true) ?: [];
    if (!empty($meta['late_fee_applied'])) continue; // already charged once, never stack

    $lateFee = round((float)$inv['amount'] * (LATE_FEE_PERCENT / 100), 2);
    $newAmount = (float)$inv['amount'] + $lateFee;
    $meta['late_fee_applied'] = true;
    $meta['late_fee_amount'] = $lateFee;
    $meta['late_fee_applied_at'] = date('Y-m-d H:i:s');

    $pdo->prepare("UPDATE invoices SET amount = ?, meta = ? WHERE id = ?")
        ->execute([$newAmount, json_encode($meta), $inv['id']]);

    Notifier::send(
        $pdo, (int)$inv['user_id'], 'Late fee applied',
        "Invoice #{$inv['id']} was overdue by more than " . LATE_FEE_GRACE_DAYS . " days. A late fee of " . number_format($lateFee, 2) . " BDT has been added.",
        'warning', "/pages/dashboard.php"
    );

    app_log('billing_cron', "Applied late fee of {$lateFee} BDT to invoice #{$inv['id']} (user {$inv['user_id']})");
    $lateFeeCount++;
}

} catch (Throwable $e) {
    app_log('billing_cron', 'Cron Notice: ' . $e->getMessage());
}

$summary = "Recurring billing run complete: $billedCount invoice(s) generated, $lateFeeCount late fee(s) applied.";
app_log('billing_cron', $summary);
echo $summary . PHP_EOL;
