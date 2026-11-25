<?php
declare(strict_types=1);

/**
 * PR #2 Security Regression Tests:
 * 1. Fail-closed database environment contract via BOTH CLI and real HTTP SAPI
 *    (unset, invalid, staging, production vs explicit development/local/testing)
 * 2. NIST SP 800-63B-4 admin provisioning policy:
 *    - Unicode code-point counting (5 Bengali chars = 15 bytes MUST FAIL; 15 Bengali chars MUST PASS)
 *    - 14 ASCII chars MUST FAIL; 15 ASCII chars MUST PASS
 *    - Spaces preserved (no trim)
 *    - Exact full-password blocklist (harmless substring in long passphrase MUST PASS)
 *    - Argon2id required & full-input (>72 bytes) verified without truncation
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
        if ($v === null) putenv($k); else putenv("{$k}={$v}");
    }
    $out = [];
    $rc = 0;
    exec($cmd . ' 2>&1', $out, $rc);
    foreach ($prev as $k => $v) {
        if ($v === false) putenv($k); else putenv("{$k}={$v}");
    }
    return [$rc, implode("\n", $out)];
}

function testHttpSapiEnv(?string $envVal): int {
    global $php, $pr2Root;
    $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mknet_sapi_' . bin2hex(random_bytes(4));
    mkdir($tmpDir, 0777, true);
    $probeFile = $tmpDir . DIRECTORY_SEPARATOR . 'probe.php';
    $cfgPath = str_replace('\\', '/', $pr2Root . '/config.php');
    file_put_contents($probeFile, "<?php require '{$cfgPath}'; echo 'HTTP_OK';");

    $sock = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    $name = stream_socket_get_name($sock, false);
    fclose($sock);
    $port = (int)substr($name, strrpos($name, ':') + 1);

    $prev = [];
    foreach (['MKNET_ENV' => $envVal, 'MKNET_DB_HOST' => null, 'MKNET_DB_NAME' => null, 'MKNET_DB_USER' => null, 'MKNET_DB_PASSWORD' => null] as $k => $v) {
        $prev[$k] = getenv($k);
        if ($v === null) putenv($k); else putenv("{$k}={$v}");
    }
    $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open(escapeshellarg($php) . " -S 127.0.0.1:{$port} -t " . escapeshellarg($tmpDir), $desc, $pipes);
    foreach ($prev as $k => $v) {
        if ($v === false) putenv($k); else putenv("{$k}={$v}");
    }

    usleep(350000);
    $ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 5]]);
    $body = @file_get_contents("http://127.0.0.1:{$port}/probe.php", false, $ctx);
    $status = 0;
    if (!empty($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }
    if (is_resource($proc)) {
        proc_terminate($proc);
        proc_close($proc);
    }
    @unlink($probeFile);
    @rmdir($tmpDir);
    return $status;
}

echo "=== 1. Fail-Closed Environment Tests (CLI & HTTP SAPI) ===\n";
$cfgFile = str_replace('\\', '/', $pr2Root . '/config.php');
foreach (['unset' => null, 'invalid' => 'prod', 'staging' => 'staging', 'production' => 'production'] as $label => $envVal) {
    [$rc, $out] = runPhpWithEnv("require '{$cfgFile}'; echo 'CONNECTED';", [
        'MKNET_ENV' => $envVal, 'MKNET_DB_HOST' => null, 'MKNET_DB_NAME' => null, 'MKNET_DB_USER' => null, 'MKNET_DB_PASSWORD' => null,
    ]);
    check("CLI env '{$label}' without DB credentials fails closed", !str_contains($out, 'CONNECTED'));
    $httpCode = testHttpSapiEnv($envVal);
    check("HTTP SAPI env '{$label}' without DB credentials returns HTTP 500 (got {$httpCode})", $httpCode === 500);
}

$httpDev = testHttpSapiEnv('development');
check("HTTP SAPI explicit MKNET_ENV=development returns HTTP 200 (got {$httpDev})", $httpDev === 200);

echo "\n=== 2. NIST SP 800-63B-4 Unicode & Argon2id Admin Provisioning Tests ===\n";
$adminScript = str_replace('\\', '/', $pr2Root . '/scripts/create_initial_admin.php');
$runAdmin = function(string $user, string $pass) use ($adminScript): array {
    return runPhpWithEnv("require '{$adminScript}';", [
        'MKNET_ENV' => 'testing',
        'MKNET_INITIAL_ADMIN_USERNAME' => $user,
        'MKNET_INITIAL_ADMIN_PASSWORD' => $pass,
    ]);
};

[$rc5Bn, $out5Bn] = $runAdmin('secadmin', 'অআইঈউ'); // 5 Bengali chars = 15 UTF-8 bytes
check("Rejects 5 Bengali chars (15 UTF-8 bytes must NOT pass 15-char min)", $rc5Bn === 2);

[$rc14Asc, $out14Asc] = $runAdmin('secadmin', 'FourteenChar14'); // 14 ASCII chars
check("Rejects 14 ASCII chars (<15)", $rc14Asc === 2);

[$rcBlock, $outBlock] = $runAdmin('secadmin', 'password12345678');
check("Rejects exact blocked passphrase", $rcBlock === 2);

[$rcSubOk, $outSubOk] = $runAdmin('secadmin', 'my secadmin passphrase is long');
check("Allows harmless username substring inside long passphrase (exit != 2)", $rcSubOk !== 2);

[$rc15Bn, $out15Bn] = $runAdmin('secadmin', 'অআইঈউঊঋএঐওঔকখগঘ'); // 15 Bengali code points
check("Accepts 15 Bengali Unicode code points (exit != 2)", $rc15Bn !== 2);

[$rc15Space, $out15Space] = $runAdmin('secadmin', '  space  pass  '); // 15 chars with leading/trailing spaces
check("Preserves leading/trailing spaces for 15-char length (exit != 2)", $rc15Space !== 2);

check("PASSWORD_ARGON2ID is available in password_algos()", defined('PASSWORD_ARGON2ID') && in_array(PASSWORD_ARGON2ID, password_algos(), true));

$longPassA = str_repeat('a', 80) . 'ALPHA_TAIL_1';
$longPassB = str_repeat('a', 80) . 'ALPHA_TAIL_2';
$hashA = password_hash($longPassA, PASSWORD_ARGON2ID);
check("Argon2id hash prefix is \$argon2id\$", str_starts_with($hashA, '$argon2id$'));
check("Argon2id verifies >72-byte password without bcrypt truncation", password_verify($longPassA, $hashA) && !password_verify($longPassB, $hashA));
check("Password not leaked in CLI error output", !str_contains($out14Asc, 'FourteenChar14') && !str_contains($outBlock, 'password12345678'));

echo "\n=== 3. Nagad Fail-Closed Check ===\n";
require_once $pr2Root . '/includes/lib/Services.php';
require_once $pr2Root . '/includes/lib/PaymentGateways/GatewayInterface.php';
require_once $pr2Root . '/includes/lib/PaymentGateways/NagadGateway.php';
$nagad = new NagadGateway();
$nagadRes = $nagad->handleCallback(['status' => 'Success', 'payment_ref_id' => 'FAKE123']);
check("Nagad fails closed on unverified callback", ($nagadRes['success'] ?? true) === false);

echo "\nSummary: {$passed}/{$total} assertions passed.\n";
exit($passed === $total ? 0 : 1);
