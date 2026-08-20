<?php
declare(strict_types=1);

// Suppress PHP warnings and notices on screen
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

define('APP_DEBUG', false);

// ---------------------------------------------------------------------
// Session hardening. Must run BEFORE any session_start() call, so this
// has to stay at the very top of config.php since every page requires
// this file first.
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
}

// ---------------------------------------------------------------------
// Enterprise Security Headers (Zero Trust Architecture)
// ---------------------------------------------------------------------
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1')) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

// ---------------------------------------------------------------------
// Web Application Firewall (WAF) & Real-time Threat Inspection
// ---------------------------------------------------------------------
if (!function_exists('waf_inspect_request')) {
    function waf_inspect_request(): void {
        $threats = [
            '/union\s+select/i',
            '/select\s+.*\s+from\s+information_schema/i',
            '/insert\s+into\s+.*values/i',
            '/delete\s+from\s+admins/i',
            '/drop\s+table/i',
            '/benchmark\s*\(/i',
            '/sleep\s*\(\s*[0-9]+\s*\)/i',
            '/<script\b[^>]*>(.*?)<\/script>/i',
            '/javascript\s*:/i',
            '/base64_decode\s*\(/i',
            '/\.\.\/\.\.\//i',
        ];
        $inspect = function($data) use (&$inspect, $threats) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $inspect($v);
                } elseif (is_string($v)) {
                    foreach ($threats as $pattern) {
                        if (preg_match($pattern, $v)) {
                            app_log('security', 'WAF Threat Blocked: ' . $v, ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                            http_response_code(403);
                            die('<div style="font-family:system-ui,sans-serif; text-align:center; padding:60px 20px; background:#0f172a; color:#ef4444;">
                                <h1 style="font-size:2rem; margin-bottom:10px;">🛡️ Enterprise Web Application Firewall (WAF)</h1>
                                <p style="color:#f8fafc; font-size:1.1rem;">Malicious payload or SQL Injection pattern detected and neutralized by MK Net Threat Defense System.</p>
                                <p style="color:#64748b; font-size:0.85rem;">IP Logged: ' . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '</p>
                            </div>');
                        }
                    }
                }
            }
        };
        $inspect($_GET);
        $inspect($_POST);
        $inspect($_COOKIE);
    }
}
waf_inspect_request();

// ---------------------------------------------------------------------
// Rate Limiting & Brute-Force Defense
// ---------------------------------------------------------------------
if (!function_exists('check_rate_limit')) {
    function check_rate_limit(string $action = 'login', int $maxAttempts = 5, int $decaySeconds = 600): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = 'rate_' . $action . '_' . md5($ip);
        $data = $_SESSION[$key] ?? ['attempts' => 0, 'first_attempt' => time()];
        if (time() - $data['first_attempt'] > $decaySeconds) {
            $data = ['attempts' => 0, 'first_attempt' => time()];
        }
        return $data['attempts'] < $maxAttempts;
    }

    function increment_rate_limit(string $action = 'login'): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = 'rate_' . $action . '_' . md5($ip);
        $data = $_SESSION[$key] ?? ['attempts' => 0, 'first_attempt' => time()];
        $data['attempts']++;
        $_SESSION[$key] = $data;
    }

    function reset_rate_limit(string $action = 'login'): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = 'rate_' . $action . '_' . md5($ip);
        unset($_SESSION[$key]);
    }
}

// ---------------------------------------------------------------------
// Strong Password Policy Validator
// ---------------------------------------------------------------------
if (!function_exists('validate_password_strength')) {
    function validate_password_strength(string $pass): array {
        $errors = [];
        if (strlen($pass) < 8) $errors[] = "Password must be at least 8 characters long.";
        if (!preg_match('/[A-Z]/', $pass)) $errors[] = "Password must contain at least one uppercase letter (A-Z).";
        if (!preg_match('/[a-z]/', $pass)) $errors[] = "Password must contain at least one lowercase letter (a-z).";
        if (!preg_match('/[0-9]/', $pass)) $errors[] = "Password must contain at least one number (0-9).";
        return $errors;
    }
}

$host = '127.0.0.1';
$db   = 'mknet';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// ---------------------------------------------------------------------
// Helper functions (এখানেই একবার থাকবে — অন্য কোনো ফাইলে e()/h() আবার
// declare করবে না, করলে "Cannot redeclare" fatal error হবে)
// ---------------------------------------------------------------------
if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('e')) {
  function e($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

/**
 * Shared structured logger. Writes one line per entry to
 * storage/logs/{channel}.log and auto-rotates a file once it exceeds
 * ~2MB (renames it to {channel}.log.1, keeping just one backup).
 */
if (!function_exists('app_log')) {
  function app_log(string $channel, string $message, array $context = []): void {
    $dir = __DIR__ . '/storage/logs';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $file = $dir . '/' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $channel) . '.log';

    if (is_file($file) && filesize($file) > 2 * 1024 * 1024) {
      @rename($file, $file . '.1');
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
      $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
    }
    @file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
  }
}

/**
 * CSRF helpers. Call csrf_field() inside any <form>, and csrf_verify()
 * at the top of the handler that processes that form's POST.
 */
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
  }
}
if (!function_exists('csrf_field')) {
  function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
  }
}
if (!function_exists('csrf_verify')) {
  function csrf_verify(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $sent = $_POST['csrf_token'] ?? '';
    $real = $_SESSION['csrf_token'] ?? '';
    return $sent !== '' && $real !== '' && hash_equals($real, $sent);
  }
}

// ---------------------------------------------------------------------
// Feature-upgrade libraries (auth, sessions, OTP, 2FA, coupons,
// notifications, payment gateways). Purely additive - nothing above
// this line changes behavior for pages that don't use them.
// ---------------------------------------------------------------------
require_once __DIR__ . '/includes/lib/Services.php';
require_once __DIR__ . '/includes/lib/Mailer.php';
require_once __DIR__ . '/includes/lib/Sms.php';
require_once __DIR__ . '/includes/lib/Otp.php';
require_once __DIR__ . '/includes/lib/TwoFactor.php';
require_once __DIR__ . '/includes/lib/SessionManager.php';
require_once __DIR__ . '/includes/lib/Coupon.php';
require_once __DIR__ . '/includes/lib/Notifier.php';
require_once __DIR__ . '/includes/lib/PaymentGateways/PaymentGatewayFactory.php';

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_TIMEOUT => 2,
];

// Smart Multi-Database Engine: Auto-detects Live InfinityFree vs Local Environment
$isLiveHosting = isset($_SERVER['HTTP_HOST']) && (str_contains($_SERVER['HTTP_HOST'], 'lovestoblog.com') || str_contains($_SERVER['HTTP_HOST'], 'infinityfree'));

if ($isLiveHosting) {
    // 1. Live Hosting: Connect to InfinityFree MySQL primary first
    $dbConfigs = [
        ['host' => 'sql301.infinityfree.com', 'db' => 'if0_42585245_mknet', 'user' => 'if0_42585245', 'pass' => 'mknet123'],
        ['host' => '127.0.0.1', 'db' => 'mknet', 'user' => 'root', 'pass' => ''],
        ['host' => 'localhost', 'db' => 'mknet', 'user' => 'root', 'pass' => ''],
    ];

    foreach ($dbConfigs as $cfg) {
        try {
            $dsn = "mysql:host={$cfg['host']};dbname={$cfg['db']};charset=utf8mb4";
            $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
            break;
        } catch (PDOException $ex) {
            $lastError = $ex->getMessage();
        }
    }
}

// 2. Try standalone SQLite for instant local speed
if (!$pdo) {
    $sqlitePath = __DIR__ . '/storage/database/mknet.sqlite';
    $sqliteDir = dirname($sqlitePath);
    if (!is_dir($sqliteDir)) @mkdir($sqliteDir, 0777, true);
    if (file_exists($sqlitePath) && extension_loaded('pdo_sqlite')) {
        try {
            $pdo = new PDO("sqlite:" . $sqlitePath, null, null, $options);
            @$pdo->exec("PRAGMA journal_mode=WAL;");
            @$pdo->exec("PRAGMA synchronous=NORMAL;");
        } catch (PDOException $ex) {
            $lastError = $ex->getMessage();
        }
    }
}

// 3. Fallback to Local MySQL if SQLite not active
if (!$pdo) {
    $dbConfigs = [
      ['host' => '127.0.0.1', 'db' => 'mknet', 'user' => 'root', 'pass' => ''],
      ['host' => 'localhost', 'db' => 'mknet', 'user' => 'root', 'pass' => ''],
      ['host' => 'sql301.infinityfree.com', 'db' => 'if0_42585245_mknet', 'user' => 'if0_42585245', 'pass' => 'mknet123'],
    ];

    foreach ($dbConfigs as $cfg) {
      try {
        $dsn = "mysql:host={$cfg['host']};dbname={$cfg['db']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
        break;
      } catch (PDOException $ex) {
        $lastError = $ex->getMessage();
      }
    }
}

if (!$pdo) {
  app_log('db', 'Connection failed: ' . $lastError);
  if (APP_DEBUG) {
    die("DB connection failed: " . $lastError);
  }
  http_response_code(500);
  die("Sorry, the site is temporarily unavailable. Please try again shortly.");
}

// Auto-bootstrap DB schema if required tables are missing
ensure_mknet_db_schema($pdo);

/**
 * Self-healing DB Schema Bootstrapper
 * Automatically creates any missing tables or columns required by MK Net.
 */
function ensure_mknet_db_schema(PDO $pdo): void {
  static $bootstrapped = false;
  if ($bootstrapped) return;
  $bootstrapped = true;

  try {
    // 1. site_content
    $pdo->exec("CREATE TABLE IF NOT EXISTS `site_content` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `content_key` VARCHAR(100) NOT NULL UNIQUE,
      `image_path` VARCHAR(255) NULL,
      `text_value` TEXT NULL,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 2. carousel_slides
    $pdo->exec("CREATE TABLE IF NOT EXISTS `carousel_slides` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(150) NULL,
      `subtitle` VARCHAR(255) NULL,
      `image_path` VARCHAR(255) NOT NULL,
      `link_url` VARCHAR(255) NULL,
      `sort_order` INT NOT NULL DEFAULT 0,
      `active` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 3. duration_plans
    $pdo->exec("CREATE TABLE IF NOT EXISTS `duration_plans` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `label` VARCHAR(50) NOT NULL,
      `days` INT NULL,
      `is_custom` TINYINT(1) NOT NULL DEFAULT 0,
      `min_custom_days` INT NOT NULL DEFAULT 1,
      `max_custom_days` INT NOT NULL DEFAULT 365,
      `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0,
      `active` TINYINT(1) NOT NULL DEFAULT 1,
      `sort_order` INT NOT NULL DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default duration plans if table is empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `duration_plans`")->fetchColumn();
    if ($count === 0) {
      $pdo->exec("INSERT INTO `duration_plans` (`label`, `days`, `is_custom`, `discount_percent`, `sort_order`) VALUES
        ('1 Day', 1, 0, 0.00, 1),
        ('3 Days', 3, 0, 0.00, 2),
        ('7 Days', 7, 0, 2.00, 3),
        ('15 Days', 15, 0, 3.00, 4),
        ('30 Days', 30, 0, 5.00, 5),
        ('60 Days', 60, 0, 7.00, 6),
        ('90 Days', 90, 0, 10.00, 7),
        ('Custom', NULL, 1, 0.00, 8)");
    }

    // 4. Ensure `packages` columns exist (price_bdt, category, visible, features, image_slug, etc.)
    $cols = $pdo->query("SHOW COLUMNS FROM `packages`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('category', $cols)) {
      $pdo->exec("ALTER TABLE `packages` ADD COLUMN `category` ENUM('residential','business','enterprise') NOT NULL DEFAULT 'residential'");
    }
    if (!in_array('price_bdt', $cols)) {
      $pdo->exec("ALTER TABLE `packages` ADD COLUMN `price_bdt` INT UNSIGNED NOT NULL DEFAULT 0");
    }
    if (!in_array('price', $cols)) {
      $pdo->exec("ALTER TABLE `packages` ADD COLUMN `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00");
    }
    if (!in_array('visible', $cols)) {
      $pdo->exec("ALTER TABLE `packages` ADD COLUMN `visible` TINYINT(1) NOT NULL DEFAULT 1");
    }
    if (!in_array('features', $cols)) {
      $pdo->exec("ALTER TABLE `packages` ADD COLUMN `features` LONGTEXT NULL");
    }
    if (!in_array('badge', $cols)) {
      $pdo->exec("ALTER TABLE `packages` ADD COLUMN `badge` VARCHAR(30) NOT NULL DEFAULT 'none'");
    }

    // Synchronize price_bdt and price if any is 0
    $pdo->exec("UPDATE `packages` SET `price_bdt` = `price` WHERE (`price_bdt` IS NULL OR `price_bdt` = 0) AND `price` > 0");
    $pdo->exec("UPDATE `packages` SET `price` = `price_bdt` WHERE (`price` IS NULL OR `price` = 0) AND `price_bdt` > 0");

    // 5. Check Enterprise package existence
    $entCount = (int)$pdo->query("SELECT COUNT(*) FROM `packages` WHERE `category`='enterprise'")->fetchColumn();
    if ($entCount === 0) {
      $pdo->exec("INSERT INTO `packages` (`name`,`speed_mbps`,`price`,`price_bdt`,`description`,`category`,`visible`,`features`) VALUES
        ('Enterprise Dedicated', 200, 14999.00, 14999, 'Dedicated fiber bandwidth with 99.9% SLA and 24/7 VIP support.', 'enterprise', 1, '[\"Dedicated 1:1 Bandwidth\",\"24/7 VIP Support\",\"99.9% Uptime SLA\",\"Static IP Included\"]')");
    }

    // 6. Ensure tickets and ticket_messages exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tickets` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `subject` VARCHAR(200) NOT NULL,
      `department` ENUM('technical','billing','sales','general') NOT NULL DEFAULT 'general',
      `priority` ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
      `status` ENUM('open','in_progress','waiting_customer','resolved','closed') NOT NULL DEFAULT 'open',
      `assigned_admin_id` INT NULL,
      `sla_due_at` DATETIME NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `closed_at` DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `ticket_messages` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `ticket_id` INT NOT NULL,
      `sender_type` ENUM('customer','admin') NOT NULL,
      `sender_id` INT NOT NULL,
      `message` TEXT NOT NULL,
      `is_internal_note` TINYINT(1) NOT NULL DEFAULT 0,
      `attachment_path` VARCHAR(255) NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 7. Ensure `invoices` table and columns exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `invoices` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `package_id` INT NULL,
      `bundle_id` INT NULL,
      `duration_days` INT NULL DEFAULT 30,
      `amount` DECIMAL(10,2) NOT NULL,
      `currency` VARCHAR(10) NOT NULL DEFAULT 'BDT',
      `status` ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
      `payment_method` VARCHAR(50) NULL,
      `trx_id` VARCHAR(100) NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 8. Loyalty Points & Gamification
    $pdo->exec("CREATE TABLE IF NOT EXISTS `loyalty_points` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL UNIQUE,
      `points` INT NOT NULL DEFAULT 100,
      `tier` ENUM('Bronze','Silver','Gold','Platinum') NOT NULL DEFAULT 'Bronze',
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 9. Referrals Engine
    $pdo->exec("CREATE TABLE IF NOT EXISTS `referrals` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `referrer_user_id` INT NOT NULL,
      `referred_email` VARCHAR(150) NOT NULL,
      `referral_code` VARCHAR(50) NOT NULL,
      `status` ENUM('pending','completed') NOT NULL DEFAULT 'pending',
      `reward_points` INT NOT NULL DEFAULT 500,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 10. Network Status & POP Incidents
    $pdo->exec("CREATE TABLE IF NOT EXISTS `network_incidents` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `pop_name` VARCHAR(100) NOT NULL,
      `region` VARCHAR(100) NOT NULL,
      `incident_type` ENUM('maintenance','fiber_cut','hardware','operational') NOT NULL DEFAULT 'operational',
      `status` ENUM('operational','degraded','outage','scheduled') NOT NULL DEFAULT 'operational',
      `estimated_recovery` VARCHAR(100) NULL DEFAULT 'Operational',
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default POP network status if empty
    $popCount = (int)$pdo->query("SELECT COUNT(*) FROM `network_incidents`")->fetchColumn();
    if ($popCount === 0) {
      $pdo->exec("INSERT INTO `network_incidents` (`pop_name`, `region`, `incident_type`, `status`, `estimated_recovery`) VALUES
        ('Dhaka Central POP', 'Gulshan / Banani / Uttara', 'operational', 'operational', 'All Systems Normal'),
        ('Chittagong Port POP', 'Agrabad / GEC / Halishahar', 'operational', 'operational', 'All Systems Normal'),
        ('Sylhet Metro POP', 'Zindabazar / Ambarkhana', 'operational', 'operational', 'All Systems Normal'),
        ('Rajshahi North POP', 'Shaheb Bazar / Boalia', 'operational', 'operational', 'All Systems Normal')");
    }

    $invCols = $pdo->query("SHOW COLUMNS FROM `invoices`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('duration_days', $invCols)) {
      $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `duration_days` INT NOT NULL DEFAULT 30");
    }

    // 8. Ensure `chatbot_rules` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `chatbot_rules` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `trigger_keywords` VARCHAR(255) NOT NULL,
      `bot_response` TEXT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 9. Ensure `contact_submissions` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `contact_submissions` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(100) NOT NULL,
      `email` VARCHAR(120) NOT NULL,
      `phone` VARCHAR(30) NULL,
      `subject` VARCHAR(255) NULL,
      `message` TEXT NOT NULL,
      `status` ENUM('unread','read','replied') NOT NULL DEFAULT 'unread',
      `admin_notes` TEXT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 9. Ensure `addons` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `addons` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(100) NOT NULL,
      `price` DECIMAL(10,2) NOT NULL,
      `description` VARCHAR(255) NULL,
      `icon` VARCHAR(50) DEFAULT 'box',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 9. Ensure `packages` table has all extended columns
    $pkgCols = $pdo->query("SHOW COLUMNS FROM `packages`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('slug', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `slug` VARCHAR(100) NULL");
    if (!in_array('price_bdt', $pkgCols) && in_array('price', $pkgCols)) {
      $pdo->exec("ALTER TABLE `packages` ADD COLUMN `price_bdt` DECIMAL(10,2) NULL");
      $pdo->exec("UPDATE `packages` SET `price_bdt` = `price` WHERE `price_bdt` IS NULL");
    }
    if (!in_array('price_bdt', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `price_bdt` DECIMAL(10,2) NOT NULL DEFAULT 1000.00");
    if (!in_array('data_cap', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `data_cap` DECIMAL(10,2) NULL");
    if (!in_array('description', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `description` TEXT NULL");
    if (!in_array('features', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `features` TEXT NULL");
    if (!in_array('image', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `image` VARCHAR(255) NULL");
    if (!in_array('region', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `region` VARCHAR(100) DEFAULT 'All Bangladesh'");
    if (!in_array('billing_cycle', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `billing_cycle` VARCHAR(50) DEFAULT 'Monthly'");
    if (!in_array('trial_days', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `trial_days` INT NULL");
    if (!in_array('visible', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `visible` TINYINT(1) DEFAULT 1");
    if (!in_array('featured', $pkgCols)) $pkgCols = $pdo->exec("ALTER TABLE `packages` ADD COLUMN `featured` TINYINT(1) DEFAULT 0");
    if (!in_array('order_index', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `order_index` INT DEFAULT 0");
    if (!in_array('promo_price', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `promo_price` DECIMAL(10,2) NULL");
    if (!in_array('promo_start', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `promo_start` DATETIME NULL");
    if (!in_array('promo_end', $pkgCols)) $pdo->exec("ALTER TABLE `packages` ADD COLUMN `promo_end` DATETIME NULL");

    // 10. Ensure default addons exist
    $addonCount = (int)$pdo->query("SELECT COUNT(*) FROM `addons`")->fetchColumn();
    if ($addonCount === 0) {
      $pdo->exec("INSERT INTO `addons` (`name`, `price`, `description`, `icon`) VALUES
        ('Static IP Address', 500.00, 'Dedicated Real IP for hosting, gaming & security cameras', 'globe'),
        ('BDIX Turbo Booster', 300.00, 'Ultra-fast 100Mbps local server peering for BDIX content', 'zap'),
        ('Real-Time Gaming Priority', 400.00, 'Low-latency ping route optimization for gamers', 'gamepad'),
        ('Safe Family Filter & Antivirus', 200.00, 'Parental controls & malicious site blocking', 'shield')");
    }

    $addonCols = $pdo->query("SHOW COLUMNS FROM `addons`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('type', $addonCols)) {
      $pdo->exec("ALTER TABLE `addons` ADD COLUMN `type` ENUM('recurring','one_time') NOT NULL DEFAULT 'recurring'");
    }
    if (!in_array('taxable', $addonCols)) {
      $pdo->exec("ALTER TABLE `addons` ADD COLUMN `taxable` TINYINT(1) NOT NULL DEFAULT 0");
    }
    if (!in_array('visible', $addonCols)) {
      $pdo->exec("ALTER TABLE `addons` ADD COLUMN `visible` TINYINT(1) NOT NULL DEFAULT 1");
    }

    // 10. Ensure admin_invitations table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_invitations` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `email` VARCHAR(120) NOT NULL,
      `role` VARCHAR(30) NOT NULL DEFAULT 'admin',
      `token` VARCHAR(64) NOT NULL UNIQUE,
      `status` ENUM('pending','accepted','expired') NOT NULL DEFAULT 'pending',
      `expires_at` DATETIME NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 11. Ensure `admins` table has `email` and `role` columns
    $adminCols = $pdo->query("SHOW COLUMNS FROM `admins`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $adminCols)) {
      $pdo->exec("ALTER TABLE `admins` ADD COLUMN `email` VARCHAR(120) NULL");
    }
    if (!in_array('role', $adminCols)) {
      $pdo->exec("ALTER TABLE `admins` ADD COLUMN `role` VARCHAR(30) NOT NULL DEFAULT 'admin'");
    }

    // Ensure admin user exists with password admin123
    $defaultAdminHash = '$2y$10$EZcXlK.hHYxcPVEQjyhgme48S5bj/WcuXiX.yuolpDaVZ8JHlNkji';
    $admCheck = $pdo->query("SELECT id FROM admins WHERE username='admin' LIMIT 1")->fetch();
    if (!$admCheck) {
      $pdo->exec("INSERT INTO admins (username, password, role) VALUES ('admin', '$defaultAdminHash', 'admin')");
    } else {
      $pdo->exec("UPDATE admins SET password='$defaultAdminHash' WHERE username='admin'");
    }

    // 12. Ensure `payment_transactions` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `payment_transactions` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `invoice_id` INT NOT NULL,
      `user_id` INT NOT NULL,
      `gateway` VARCHAR(50) NOT NULL,
      `trx_id` VARCHAR(100) NULL,
      `sender_number` VARCHAR(30) NULL,
      `amount` DECIMAL(10,2) NOT NULL,
      `status` ENUM('pending','approved','rejected','failed') NOT NULL DEFAULT 'pending',
      `raw_response` TEXT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 13. Ensure `contact_submissions` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `contact_submissions` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(100) NOT NULL,
      `email` VARCHAR(120) NOT NULL,
      `phone` VARCHAR(30) NULL,
      `subject` VARCHAR(200) NOT NULL,
      `message` TEXT NOT NULL,
      `status` ENUM('unread','read','replied') NOT NULL DEFAULT 'unread',
      `admin_notes` TEXT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 14. Ensure `user_addons` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_addons` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `addon_id` INT NOT NULL,
      `status` ENUM('active','cancelled') NOT NULL DEFAULT 'active',
      `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 15. Ensure `admin_banners` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_banners` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `message` VARCHAR(255) NOT NULL,
      `link_url` VARCHAR(255) NULL,
      `type` VARCHAR(50) NOT NULL DEFAULT 'top_banner',
      `banner_type` VARCHAR(50) NOT NULL DEFAULT 'top_banner',
      `image_path` VARCHAR(255) NULL,
      `active` TINYINT(1) NOT NULL DEFAULT 1,
      `starts_at` DATETIME NULL,
      `ends_at` DATETIME NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $bannerCols = $pdo->query("SHOW COLUMNS FROM `admin_banners`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('type', $bannerCols)) $pdo->exec("ALTER TABLE `admin_banners` ADD COLUMN `type` VARCHAR(50) DEFAULT 'top_banner'");
    if (!in_array('banner_type', $bannerCols)) $pdo->exec("ALTER TABLE `admin_banners` ADD COLUMN `banner_type` VARCHAR(50) DEFAULT 'top_banner'");

    // 15b. Ensure `hero_slides`, `carousel_slides`, and `site_content` tables exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `site_content` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `content_key` VARCHAR(100) NOT NULL UNIQUE,
      `content_value` TEXT NULL,
      `image_path` VARCHAR(255) NULL,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `carousel_slides` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(255) NOT NULL,
      `subtitle` VARCHAR(255) NULL,
      `image_path` VARCHAR(255) NOT NULL,
      `link_url` VARCHAR(255) NULL,
      `sort_order` INT DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `hero_slides` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(255) NOT NULL,
      `subtitle` VARCHAR(255) NULL,
      `image_path` VARCHAR(255) NOT NULL,
      `link_url` VARCHAR(255) NULL,
      `sort_order` INT DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 16. Ensure `settings` table exists with theme and company options
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `site_name` VARCHAR(100) NOT NULL DEFAULT 'MK Net ISP',
      `hotline` VARCHAR(50) DEFAULT '16335 / 09678-123123',
      `email` VARCHAR(120) DEFAULT 'support@mknet.com',
      `address` VARCHAR(255) DEFAULT 'Police Plaza Concord, Gulshan 1, Dhaka',
      `bkash_number` VARCHAR(30) DEFAULT '01700000000',
      `nagad_number` VARCHAR(30) DEFAULT '01700000000',
      `active_theme` VARCHAR(50) DEFAULT 'cyber_dark',
      `custom_css` TEXT NULL,
      `logo_path` VARCHAR(255) NULL,
      `favicon_path` VARCHAR(255) NULL,
      `bg_image_path` VARCHAR(255) NULL,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $setCount = (int)$pdo->query("SELECT COUNT(*) FROM `settings`")->fetchColumn();
    if ($setCount === 0) {
      $pdo->exec("INSERT INTO `settings` (`id`, `site_name`, `hotline`, `email`, `address`, `active_theme`) VALUES
        (1, 'MK Net ISP', '16335 / 09678-123123', 'support@mknet.com', 'Police Plaza Concord, Gulshan 1, Dhaka', 'cyber_dark')");
    }

    // 17. Ensure `users` table has both `name` and `full_name` and `is_regular`
    $userCols = $pdo->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('is_regular', $userCols)) {
      $pdo->exec("ALTER TABLE `users` ADD COLUMN `is_regular` TINYINT(1) NOT NULL DEFAULT 0");
    }
    if (!in_array('name', $userCols) && in_array('full_name', $userCols)) {
      $pdo->exec("ALTER TABLE `users` ADD COLUMN `name` VARCHAR(100) NULL");
      $pdo->exec("UPDATE `users` SET `name` = `full_name` WHERE `name` IS NULL");
    }
    // 18. Ensure `ads` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `ads` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(255) NOT NULL,
      `description` TEXT NULL,
      `image` VARCHAR(255) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 19. Ensure `announcements` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `announcements` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(255) NOT NULL,
      `message` TEXT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 20. Ensure `news` and `news_events` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `news` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(255) NOT NULL,
      `content` TEXT NOT NULL,
      `image` VARCHAR(255) NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `news_events` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(255) NOT NULL,
      `content` TEXT NOT NULL,
      `event_date` DATE NULL,
      `category` VARCHAR(50) DEFAULT 'General',
      `image` VARCHAR(255) NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 21. Ensure `reviews` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `reviews` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `package_id` INT NULL,
      `user_name` VARCHAR(100) NOT NULL,
      `rating` TINYINT(1) DEFAULT 5,
      `comment` TEXT NOT NULL,
      `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 22. Ensure `tickets` table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tickets` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `subject` VARCHAR(255) NOT NULL,
      `department` VARCHAR(50) DEFAULT 'technical',
      `priority` VARCHAR(20) DEFAULT 'normal',
      `status` ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 23. Ensure `chatbot_rules` and `chatbot_messages` exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS `chatbot_rules` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `keyword` VARCHAR(100) NOT NULL,
      `reply` TEXT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `chatbot_messages` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NULL,
      `user_message` TEXT NOT NULL,
      `bot_reply` TEXT NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 24. Ensure `users` has `loyalty_points` and `account_status` columns
    if (!in_array('loyalty_points', $userCols)) {
      $pdo->exec("ALTER TABLE `users` ADD COLUMN `loyalty_points` INT NOT NULL DEFAULT 0");
    }
    if (!in_array('account_status', $userCols)) {
      $pdo->exec("ALTER TABLE `users` ADD COLUMN `account_status` VARCHAR(20) NOT NULL DEFAULT 'active'");
    }

  } catch (Throwable $t) {
    app_log('db_schema', 'Auto schema check error: ' . $t->getMessage());
  }
}

