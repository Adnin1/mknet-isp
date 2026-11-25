<?php
declare(strict_types=1);

/**
 * Real MariaDB Integration Tests for:
 * 1. migrations/2026_11_26_auth_rate_limits.sql (apply, retry idempotency, InnoDB, PK, index)
 * 2. AdminPasswordPolicy rate limiter (atomic ON DUPLICATE KEY UPDATE, expiry cleanup, missing-table fail-closed)
 * 3. AdminAuth::deleteAdminSafely() real concurrent 2-process last-superadmin race test
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$root = realpath(__DIR__ . '/..');
require_once $root . '/includes/lib/AdminAuth.php';
require_once $root . '/includes/lib/AdminPasswordPolicy.php';

$host = getenv('MKNET_TEST_DB_HOST') ?: '127.0.0.1';
$port = getenv('MKNET_TEST_DB_PORT') ?: '3306';
$dbName = getenv('MKNET_TEST_DB_NAME') ?: 'mknet_ci_test';
$user = getenv('MKNET_TEST_DB_USER') ?: 'root';
$pass = getenv('MKNET_TEST_DB_PASS');
if ($pass === false) {
    $pass = '';
}

$passed = 0;
$total = 0;
function mcheck(string $label, bool $cond): void {
    global $passed, $total;
    $total++;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$label}\n";
    } else {
        echo "  [FAIL] {$label}\n";
    }
}

$dsnBase = "mysql:host={$host};port={$port};charset=utf8mb4";
$rootPdo = new PDO($dsnBase, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$ver = (string)$rootPdo->query('SELECT VERSION()')->fetchColumn();
echo "Connected to MariaDB/MySQL version: {$ver}\n";

$rootPdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
$rootPdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// 1. Fail-closed BEFORE migration is applied
mcheck('Rate limiter fails closed when auth_rate_limits table does not exist yet', AdminPasswordPolicy::checkPersistentRateLimit($pdo, 'admin_forgot_password', '127.0.0.1|admin') === false);

// 2. Apply additive migration twice (idempotency check)
$sql = (string)file_get_contents($root . '/migrations/2026_11_26_auth_rate_limits.sql');
$pdo->exec($sql);
$pdo->exec($sql);
$tblStatus = $pdo->query("SHOW TABLE STATUS LIKE 'auth_rate_limits'")->fetch();
mcheck('Migration creates auth_rate_limits with InnoDB engine and survives retry', is_array($tblStatus) && strcasecmp((string)($tblStatus['Engine'] ?? ''), 'InnoDB') === 0);

$indexes = $pdo->query("SHOW INDEXES FROM auth_rate_limits")->fetchAll();
$pkCols = array_map(fn($r) => $r['Column_name'], array_filter($indexes, fn($r) => ($r['Key_name'] ?? '') === 'PRIMARY'));
$expIdx = array_filter($indexes, fn($r) => ($r['Key_name'] ?? '') === 'idx_auth_rate_limits_expires');
mcheck('auth_rate_limits has PRIMARY KEY(rate_key) and idx_auth_rate_limits_expires', in_array('rate_key', $pkCols, true) && count($expIdx) > 0);

// 3. Atomic upsert & window enforcement & expired cleanup
for ($i = 0; $i < 5; $i++) {
    AdminPasswordPolicy::recordRateLimitAttempt($pdo, 'admin_forgot_password', '10.0.0.1|super1', 900);
}
mcheck('5 recorded attempts reach limit (checkPersistentRateLimit returns false)', AdminPasswordPolicy::checkPersistentRateLimit($pdo, 'admin_forgot_password', '10.0.0.1|super1', 5, 900) === false);

// Insert expired row and verify cleanup removes it on next check
$pdo->prepare('INSERT INTO auth_rate_limits (rate_key, attempts, window_start, expires_at) VALUES (?, 3, ?, ?)')
    ->execute([hash('sha256', 'expired|row'), time() - 3600, time() - 10]);
mcheck('Different identifier remains allowed (no global admin lockout)', AdminPasswordPolicy::checkPersistentRateLimit($pdo, 'admin_forgot_password', '10.0.0.2|other', 5, 900) === true);
$expiredCount = (int)$pdo->query('SELECT COUNT(*) FROM auth_rate_limits WHERE rate_key = ' . $pdo->quote(hash('sha256', 'expired|row')))->fetchColumn();
mcheck('Expired rate-limit rows are cleaned up on check', $expiredCount === 0);

// 4. Last-superadmin concurrency test on real InnoDB rows
$pdo->exec("CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(120) NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$hash1 = AdminPasswordPolicy::hash('SuperOnePassphrase2026!');
$hash2 = AdminPasswordPolicy::hash('SuperTwoPassphrase2026!');
$pdo->prepare("INSERT INTO admins (id, username, password, role) VALUES (1, 'super1', ?, 'superadmin'), (2, 'super2', ?, 'superadmin')")
    ->execute([$hash1, $hash2]);

$workerScript = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mknet_race_' . bin2hex(random_bytes(4)) . '.php';
$authPath = str_replace('\\', '/', $root . '/includes/lib/AdminAuth.php');
file_put_contents($workerScript, "<?php\nrequire_once '{$authPath}';\n\$actor = (int)\$argv[1];\n\$target = (int)\$argv[2];\n\$pdo = new PDO('mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4', '{$user}', " . var_export($pass, true) . ", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);\n\$res = AdminAuth::deleteAdminSafely(\$pdo, \$actor, \$target);\necho (\$res['ok'] ? 'OK' : 'DENIED') . '|' . \$res['error'] . \"\\n\";\n");

$phpBin = escapeshellarg(PHP_BINARY);
$wEsc = escapeshellarg($workerScript);
$desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$p1 = proc_open("{$phpBin} {$wEsc} 1 2", $desc, $pipes1);
$p2 = proc_open("{$phpBin} {$wEsc} 2 1", $desc, $pipes2);
$out1 = stream_get_contents($pipes1[1]); $err1 = stream_get_contents($pipes1[2]);
$out2 = stream_get_contents($pipes2[1]); $err2 = stream_get_contents($pipes2[2]);
proc_close($p1);
proc_close($p2);
@unlink($workerScript);

$okCount = (str_starts_with(trim($out1), 'OK|') ? 1 : 0) + (str_starts_with(trim($out2), 'OK|') ? 1 : 0);
$remainingSupers = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'superadmin'")->fetchColumn();
mcheck("Concurrent mutual superadmin deletion allows strictly 1 success & preserves 1 active superadmin (ok={$okCount}, remaining={$remainingSupers})", $okCount === 1 && $remainingSupers === 1);

$rootPdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
echo "MariaDB Integration Summary: {$passed}/{$total} assertions passed.\n";
exit($passed === $total ? 0 : 1);
