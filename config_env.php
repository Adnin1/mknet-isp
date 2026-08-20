<?php
use Dotenv\Dotenv;

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv') && is_file(__DIR__ . '/.env')) {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }
}

// Now you can access credentials like:
$bkashKey = $_ENV['BKASH_APP_KEY'] ?? '';
$nagadMerchant = $_ENV['NAGAD_MERCHANT_ID'] ?? '';
