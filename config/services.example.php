<?php
/**
 * =====================================================================
 * MK Net — third-party service credentials
 * =====================================================================
 * Copy this file to config/services.php (same folder) and fill in your
 * real values. config/services.php is where the app actually reads
 * from — this .example file is just the template and is safe to keep
 * in version control (it has no real secrets in it).
 *
 * Until config/services.php exists, the whole site runs in DEV MODE:
 *   - OTP codes and verification links are written to
 *     storage/logs/mail.log and storage/logs/sms.log instead of being
 *     really sent, so you can test every flow end-to-end for free.
 *   - Payment gateways show a "Simulate payment" button instead of
 *     redirecting to the real gateway.
 *
 * Set 'dev_mode' => false only once every section below is filled in
 * and tested, or specific features will silently keep using the dev
 * fallback.
 */

return [

    'dev_mode' => true,

    // -------------------------------------------------------------
    // EMAIL (used for: email verification, password reset, invoice
    // emails, notifications). Any standard SMTP provider works -
    // Gmail/Workspace, SendGrid, Mailgun, Amazon SES, your host's own
    // mail server, etc.
    // -------------------------------------------------------------
    'mail' => [
        'smtp_host'      => '',           // e.g. 'smtp.gmail.com' or 'smtp.sendgrid.net'
        'smtp_port'      => 587,          // 587 = STARTTLS, 465 = SSL
        'smtp_secure'    => 'tls',        // 'tls' | 'ssl' | 'none'
        'smtp_user'      => '',
        'smtp_pass'      => '',
        'smtp_from'      => 'no-reply@yourdomain.com',
        'smtp_from_name' => 'MK Net',
    ],

    // -------------------------------------------------------------
    // SMS / Phone OTP. Bangladesh-focused options: SSL Wireless,
    // Alpha SMS (BulkSMSBD), REVE Systems, or Twilio for international.
    // Each provider's API differs slightly - Sms.php currently ships
    // with a generic HTTP-GET/POST adapter that fits most Bangladeshi
    // gateways (they all take api_key + sender_id + a REST endpoint).
    // Adjust includes/lib/Sms.php if your provider's request shape
    // differs.
    // -------------------------------------------------------------
    'sms' => [
        'provider'   => '',      // e.g. 'alpha_sms', 'ssl_wireless', 'twilio'
        'api_url'    => '',      // the provider's send-SMS endpoint
        'api_key'    => '',
        'sender_id'  => '',
    ],

    // -------------------------------------------------------------
    // SSLCOMMERZ (aggregator: covers bKash/Nagad/Rocket/cards/etc in
    // one integration if you don't want to integrate each separately).
    // Get store_id / store_passwd from https://developer.sslcommerz.com
    // (free sandbox account available for testing).
    // -------------------------------------------------------------
    'sslcommerz' => [
        'store_id'     => '',
        'store_passwd' => '',
        'sandbox'      => true,
    ],

    // -------------------------------------------------------------
    // bKASH (direct integration - only needed if NOT using SSLCommerz
    // for bKash). Get credentials from https://developer.bka.sh
    // -------------------------------------------------------------
    'bkash' => [
        'app_key'    => '',
        'app_secret' => '',
        'username'   => '',
        'password'   => '',
        'sandbox'    => true,
    ],

    // -------------------------------------------------------------
    // NAGAD (direct integration). Merchant onboarding via your bank /
    // https://nagad.com.bd business portal.
    // -------------------------------------------------------------
    'nagad' => [
        'merchant_id'        => '',
        'merchant_private_key' => '',
        'nagad_public_key'   => '',
        'sandbox'            => true,
    ],

    // -------------------------------------------------------------
    // ROCKET (DBBL Mobile Banking) - typically integrated via
    // SSLCommerz rather than a standalone API; direct merchant API
    // access requires a DBBL agreement.
    // -------------------------------------------------------------
    'rocket' => [
        'merchant_id' => '',
        'api_key'     => '',
    ],

    // -------------------------------------------------------------
    // UPAY - typically integrated via SSLCommerz as well.
    // -------------------------------------------------------------
    'upay' => [
        'merchant_id' => '',
        'api_key'     => '',
    ],

    // -------------------------------------------------------------
    // VISA / MASTERCARD - card acquiring almost always goes through
    // an aggregator (SSLCommerz above already covers this) or a
    // dedicated processor like Stripe. Fill this in only if you have
    // a direct card-processor contract separate from SSLCommerz.
    // -------------------------------------------------------------
    'card_gateway' => [
        'provider'    => 'sslcommerz', // cards are routed through SSLCommerz by default
        'public_key'  => '',
        'secret_key'  => '',
    ],
    // -------------------------------------------------------------
    // STRIPE (international cards). Get test-mode keys instantly at
    // https://dashboard.stripe.com/test/apikeys - no business
    // verification needed for testing.
    // -------------------------------------------------------------
    'stripe' => [
        'secret_key' => '',
    ],

    // -------------------------------------------------------------
    // PAYPAL - see the big warning in
    // includes/lib/PaymentGateways/PaypalGateway.php before configuring
    // this one: PayPal doesn't support BDT, and Bangladesh isn't a
    // supported country for a standard PayPal Business account. Only
    // fill this in if you've resolved that (e.g. via a foreign entity).
    // -------------------------------------------------------------
    'paypal' => [
        'client_id'     => '',
        'client_secret' => '',
        'sandbox'       => true,
        'bdt_to_usd_rate' => 0.0082, // update periodically - 1 BDT in USD
    ],
];
