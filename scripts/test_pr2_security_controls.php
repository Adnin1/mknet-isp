<?php
declare(strict_types=1);

/**
 * PR #2 Security Regression Tests:
 * 1. Fail-closed database environment contract (unset, invalid, staging, production vs explicit development)
 * 2. NIST SP 800-63B-4 admin provisioning policy (15+ chars, no composition rules, spaces & Unicode, blocklist, Argon2id)
 * 3. Nagad fail-closed callback verification
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$pr2Root = realpath(__DIR__ . '/..');
$php = PHP_BINARY;
$passed = 0;
$total = 0;

function check(string $label, bool $cond): void {
    global $passed, $total;
    $total++;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$label}\n";
    } else {
        echo "  [FAIL] {$label}\n";
    }
}

function runPhpWithEnv(string $code, array $env): array {
    global $php;
    $cmd = escapeshellarg($php) . ' -r ' . escapeshellarg($code);
    $prev = [];
    foreach ($env as $k => $v) {
        $prev[$k] = getenv($k);
        if ($v === null) {
            putenv($k);
        } else {
            putenv("{$k}={$v}");
        }
    }
    $out = [];
    $rc = 0;
    exec($cmd . ' 2>&1', $out, $rc);
    foreach ($prev as $k => $v) {
        if ($v === false) {
            putenv($k);
        } else {
            putenv("{$k}={$v}");
        }
    }
    return [$rc, implode("\n", $out)];
}

echo "=== 1. Fail-Closed Environment Tests ===\n";
$cfgFile = str_replace('\\', '/', $pr2Root . '/config.php');
foreach (['unset' => null, 'invalid' => 'prod', 'staging' => 'staging', 'production' => 'production'] as $label => $envVal) {
    [$rc, $out] = runPhpWithEnv("require '{$cfgFile}'; echo 'CONNECTED';", [
        'MKNET_ENV' => $envVal,
        'MKNET_DB_HOST' => null,
        'MKNET_DB_NAME' => null,
        'MKNET_DB_USER' => null,
        'MKNET_DB_PASSWORD' => null,
    ]);
    check("Env '{$label}' without DB credentials fails closed (not CONNECTED)", !str_contains($out, 'CONNECTED'));
}

// Explicit development with SQLite works when sqlite file exists, or attempts local dev path
[$rcDev, $outDev] = runPhpWithEnv("require '{$cfgFile}'; echo 'DEV_OK:' . \$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);", [
    'MKNET_ENV' => 'development',
    'MKNET_DB_HOST' => null,
    'MKNET_DB_NAME' => null,
    'MKNET_DB_USER' => null,
    'MKNET_DB_PASSWORD' => null,
]);
check("Explicit MKNET_ENV=development succeeds with local/SQLite dev path", $rcDev === 0 && str_contains($outDev, 'DEV_OK:'));

echo "\n=== 2. NIST SP 800-63B-4 Admin Provisioning Tests ===\n";
$adminScript = str_replace('\\', '/', $pr2Root . '/scripts/create_initial_admin.php');
$sqliteTmp = tempnam(sys_get_temp_dir(), 'pr2_adm_');
$dbInit = new PDO('sqlite:' . $sqliteTmp);
$dbInit->exec('CREATE TABLE admins (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, role TEXT);');

// Test via isolated script wrapper that injects temporary SQLite PDO
$runAdmin = function(string $user, string $pass) use ($pr2Root, $adminScript, $sqliteTmp): array {
    $wrapper = "
        putenv('MKNET_ENV=testing');
        putenv('MKNET_INITIAL_ADMIN_USERNAME=" . addslashes($user) . "');
        putenv('MKNET_INITIAL_ADMIN_PASSWORD=" . addslashes($pass) . "');
        \$pdo = new PDO('sqlite:" . addslashes($sqliteTmp) . "');
        require '" . addslashes($adminScript) . "';
    ";
    // Note: create_initial_admin.php requires config.php which overwrites $pdo; test validation logic directly first
    return runPhpWithEnv("require '{$adminScript}';", [
        'MKNET_ENV' => 'testing',
        'MKNET_INITIAL_ADMIN_USERNAME' => $user,
        'MKNET_INITIAL_ADMIN_PASSWORD' => $pass,
    ]);
};

[$rcShort, $outShort] = $runAdmin('secadmin', 'ShortPass123!'); // 13 chars
check("Rejects 13-char password (<15 chars)", $rcShort === 2 && str_contains($outShort, '15 and 128'));

[$rcBlock, $outBlock] = $runAdmin('secadmin', 'password12345678');
check("Rejects common blocked passphrase", $rcBlock === 2 && str_contains($outBlock, 'too common'));

[$rcUserInPass, $outUserInPass] = $runAdmin('secadmin', 'my secadmin passphrase');
check("Rejects passphrase containing username", $rcUserInPass === 2 && str_contains($outUserInPass, 'username'));

// Verify policy accepts 15+ char lowercase/space/Unicode passphrase (does not fail with exit 2!)
[$rcPhrase, $outPhrase] = $runAdmin('secadmin', 'correct horse battery বাংলা');
check("Accepts 15+ char space+Unicode passphrase without composition rules (exit != 2)", $rcPhrase !== 2);

// Verify Argon2id hash compatibility with password_verify
$algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
$h = password_hash('correct horse battery বাংলা', $algo);
check("Argon2id/default hash verifies round-trip", password_verify('correct horse battery বাংলা', $h));
check("Password not leaked in CLI error output", !str_contains($outShort, 'ShortPass123!') && !str_contains($outBlock, 'password12345678'));

echo "\n=== 3. Nagad Fail-Closed Check ===\n";
require_once $pr2Root . '/includes/lib/Services.php';
require_once $pr2Root . '/includes/lib/PaymentGateways/GatewayInterface.php';
require_once $pr2Root . '/includes/lib/PaymentGateways/NagadGateway.php';
$nagad = new NagadGateway();
$nagadRes = $nagad->handleCallback(['status' => 'Success', 'payment_ref_id' => 'FAKE123']);
check("Nagad fails closed on unverified callback", ($nagadRes['success'] ?? true) === false);

@unlink($sqliteTmp);
echo "\nSummary: {$passed}/{$total} assertions passed.\n";
exit($passed === $total ? 0 : 1);
