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
    $desc = [0 => ['pipe', 'r'], 1 => ['file', $tmpDir . '/srv_out.log', 'a'], 2 => ['file', $tmpDir . '/srv_err.log', 'a']];
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

echo "\n=== 4. Shared AdminPasswordPolicy & HTTP SAPI Auth/Reset/Invite Tests ===\n";
require_once $pr2Root . '/includes/lib/AdminAuth.php';
require_once $pr2Root . '/includes/lib/AdminPasswordPolicy.php';

check("AdminPasswordPolicy rejects 14-char password", AdminPasswordPolicy::validate('FourteenChar14', 'secadmin') !== []);
check("AdminPasswordPolicy accepts 15-char passphrase with spaces", AdminPasswordPolicy::validate('  space  pass  ', 'secadmin') === []);
check("AdminPasswordPolicy accepts 15 Bengali code points", AdminPasswordPolicy::validate('অআইঈউঊঋএঐওঔকখগঘ', 'secadmin') === []);
$policyHash = AdminPasswordPolicy::hash('my secure passphrase 2026');
check("AdminPasswordPolicy::hash uses \$argon2id\$", str_starts_with($policyHash, '$argon2id$'));
check("AdminPasswordPolicy::verify validates correct passphrase", AdminPasswordPolicy::verify('my secure passphrase 2026', $policyHash));
check("AdminPasswordPolicy::verify rejects wrong passphrase", !AdminPasswordPolicy::verify('wrong passphrase 2026', $policyHash));

// Build isolated HTTP SAPI test harness backed by SQLite
$sapiDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mknet_auth_sapi_' . bin2hex(random_bytes(4));
mkdir($sapiDir . '/admin', 0777, true);
mkdir($sapiDir . '/includes/lib', 0777, true);

$sqliteFile = $sapiDir . '/test.sqlite';
$mailSinkFile = $sapiDir . '/mail_sink.log';
$pdoTest = new PDO('sqlite:' . $sqliteFile);
$pdoTest->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdoTest->exec("CREATE TABLE admins (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, email TEXT, password TEXT, role TEXT, theme_preference TEXT, created_at TEXT)");
$pdoTest->exec("CREATE TABLE password_resets (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, token TEXT, expires_at TEXT)");
$pdoTest->exec("CREATE TABLE admin_invitations (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT, role TEXT, token TEXT, status TEXT DEFAULT 'pending', expires_at TEXT)");
$pdoTest->exec("CREATE TABLE auth_rate_limits (rate_key VARCHAR(128) PRIMARY KEY, attempts INT NOT NULL, window_start INT NOT NULL, expires_at INT NOT NULL)");
$realHash = AdminPasswordPolicy::hash('RealAdminPassphrase2026!');
$stmtIns = $pdoTest->prepare("INSERT INTO admins (id, username, email, password, role) VALUES (7, 'realadmin', 'real@mknet.test', ?, 'superadmin')");
$stmtIns->execute([$realHash]);
$normalHash = AdminPasswordPolicy::hash('NormalAdminPassphrase2026!');
$stmtNorm = $pdoTest->prepare("INSERT INTO admins (id, username, email, password, role) VALUES (8, 'normaladmin', 'normal@mknet.test', ?, 'admin')");
$stmtNorm->execute([$normalHash]);
$stmtInvite = $pdoTest->prepare("INSERT INTO admin_invitations (email, role, token, status, expires_at) VALUES ('invitee@mknet.test', 'admin', ?, 'pending', ?)");
$stmtInvite->execute([hash('sha256', 'validinvitetoken123'), date('Y-m-d H:i:s', time() + 3600)]);
$pdoTest = null;

$testConfig = <<<'PHP'
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = new PDO('sqlite:' . __DIR__ . '/test.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
if (!function_exists('h')) {
    function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
    function csrf_field(): string {
        return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
    }
    function csrf_verify(): bool {
        $t = $_POST['csrf_token'] ?? '';
        return is_string($t) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $t);
    }
}
if (!function_exists('app_log')) {
    function app_log(string $ch, string $msg): void {}
}
if (!class_exists('Mailer')) {
    class Mailer {
        public static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
            file_put_contents(__DIR__ . '/mail_sink.log', json_encode(['to' => $toEmail, 'subject' => $subject, 'body' => $htmlBody], JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            return true;
        }
    }
}
PHP;
file_put_contents($sapiDir . '/config.php', $testConfig);
copy($pr2Root . '/includes/lib/AdminAuth.php', $sapiDir . '/includes/lib/AdminAuth.php');
copy($pr2Root . '/includes/lib/AdminPasswordPolicy.php', $sapiDir . '/includes/lib/AdminPasswordPolicy.php');
foreach (['login.php', 'logout.php', 'forgot_password.php', 'reset_password.php', 'accept_invite.php', 'manage_admins.php'] as $f) {
    copy($pr2Root . '/admin/' . $f, $sapiDir . '/admin/' . $f);
}
file_put_contents($sapiDir . '/admin/dashboard.php', "<?php\nrequire_once __DIR__ . '/../config.php';\nrequire_once __DIR__ . '/../includes/lib/AdminAuth.php';\n\$a = AdminAuth::requireAdmin(\$pdo);\necho 'DASHBOARD_ID=' . (int)\$a['id'] . ';ROLE=' . \$a['role'];\n");
file_put_contents($sapiDir . '/admin/header.php', "<html><body>\n");

$sock = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
$name = stream_socket_get_name($sock, false);
fclose($sock);
$port = (int)substr($name, strrpos($name, ':') + 1);

$prevAppUrl = getenv('MKNET_APP_URL');
$prevEnv = getenv('MKNET_ENV');
putenv('MKNET_APP_URL=https://admin.mknet.test');
putenv('MKNET_ENV=production');
$desc = [0 => ['pipe', 'r'], 1 => ['file', $sapiDir . '/srv_out.log', 'a'], 2 => ['file', $sapiDir . '/srv_err.log', 'a']];
$proc = proc_open(escapeshellarg($php) . " -S 127.0.0.1:{$port} -t " . escapeshellarg($sapiDir), $desc, $pipes);
if ($prevAppUrl === false) putenv('MKNET_APP_URL'); else putenv("MKNET_APP_URL={$prevAppUrl}");
if ($prevEnv === false) putenv('MKNET_ENV'); else putenv("MKNET_ENV={$prevEnv}");
usleep(350000);

$httpReq = function(string $method, string $path, array $post = [], string $cookie = '') use ($port): array {
    $headers = [];
    if ($cookie !== '') $headers[] = "Cookie: {$cookie}";
    $opts = [
        'method' => $method,
        'ignore_errors' => true,
        'follow_location' => 0,
        'timeout' => 5,
    ];
    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $opts['content'] = http_build_query($post);
    }
    $opts['header'] = implode("\r\n", $headers);
    $ctx = stream_context_create(['http' => $opts]);
    $body = (string)@file_get_contents("http://127.0.0.1:{$port}{$path}", false, $ctx);
    $respHeaders = $http_response_header ?? [];
    $status = 0;
    $newCookie = $cookie;
    $location = '';
    $hdrMap = [];
    foreach ($respHeaders as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) $status = (int)$m[1];
        if (preg_match('#^Set-Cookie:\s*([^;]+)#i', $h, $m)) $newCookie = $m[1];
        if (preg_match('#^Location:\s*(.+)$#i', $h, $m)) $location = trim($m[1]);
        if (str_contains($h, ':')) {
            [$k, $v] = explode(':', $h, 2);
            $hdrMap[strtolower(trim($k))] = trim($v);
        }
    }
    return ['status' => $status, 'body' => $body, 'cookie' => $newCookie, 'location' => $location, 'headers' => $hdrMap];
};

$extractCsrf = function(string $html): string {
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m)) return $m[1];
    return '';
};

// 4a. Login bypass MUST fail, real login MUST succeed and regenerate session
$loginGet = $httpReq('GET', '/admin/login.php');
$csrf1 = $extractCsrf($loginGet['body']);
check("login.php sets Cache-Control: no-store and Referrer-Policy: no-referrer",
    str_contains($loginGet['headers']['cache-control'] ?? '', 'no-store') &&
    ($loginGet['headers']['referrer-policy'] ?? '') === 'no-referrer'
);
$bypassPost = $httpReq('POST', '/admin/login.php', ['csrf_token' => $csrf1, 'username' => 'admin', 'password' => 'admin123'], $loginGet['cookie']);
check("Hard-coded admin/admin123 login bypass is blocked", $bypassPost['status'] === 200 && str_contains($bypassPost['body'], 'Invalid username or password!'));

$realPost = $httpReq('POST', '/admin/login.php', ['csrf_token' => $csrf1, 'username' => 'realadmin', 'password' => 'RealAdminPassphrase2026!'], $loginGet['cookie']);
check("Real DB admin login redirects to dashboard.php", $realPost['status'] === 302 && $realPost['location'] === 'dashboard.php');
check("session_regenerate_id rotates session cookie on login", $realPost['cookie'] !== '' && $realPost['cookie'] !== $loginGet['cookie']);
$dashGet = $httpReq('GET', '/admin/dashboard.php', [], $realPost['cookie']);
check("Logged-in admin session carries real DB id=7 (no ?? 1 fallback)", trim($dashGet['body']) === 'DASHBOARD_ID=7;ROLE=superadmin');

// 4b. Forgot password generic response, SHA-256 token storage, no token leak, rate limiting
$fpGet1 = $httpReq('GET', '/admin/forgot_password.php');
$fpCsrf1 = $extractCsrf($fpGet1['body']);
$fpUnknown = $httpReq('POST', '/admin/forgot_password.php', ['csrf_token' => $fpCsrf1, 'username' => 'nosuchuser'], $fpGet1['cookie']);
$fpKnown = $httpReq('POST', '/admin/forgot_password.php', ['csrf_token' => $fpCsrf1, 'username' => 'realadmin'], $fpGet1['cookie']);
check("forgot_password.php returns identical response for unknown vs existing admin", $fpUnknown['body'] === $fpKnown['body']);
check("forgot_password.php does NOT disclose reset link or token in HTTP response", !str_contains($fpKnown['body'], 'reset_password.php?token=') && !str_contains($fpKnown['body'], '<a '));

$mailLog = is_file($mailSinkFile) ? (string)file_get_contents($mailSinkFile) : '';
$rawResetToken = '';
if (preg_match('/reset_password\.php\?token=([a-f0-9]{64})/', $mailLog, $m)) {
    $rawResetToken = $m[1];
}
check("forgot_password.php sends trusted HTTPS reset link via Mailer", $rawResetToken !== '' && str_contains($mailLog, 'https://admin.mknet.test/admin/reset_password.php?token='));

$pdoCheck = new PDO('sqlite:' . $sqliteFile);
$storedToken = (string)$pdoCheck->query("SELECT token FROM password_resets ORDER BY id DESC LIMIT 1")->fetchColumn();
check("password_resets stores SHA-256 hash instead of raw token", $storedToken === hash('sha256', $rawResetToken) && $storedToken !== $rawResetToken);

// Rate limiting persists across fresh sessions (5 attempts allowed, 6th blocked -> no new mail)
$mailLinesBefore = count(file($mailSinkFile));
for ($i = 0; $i < 6; $i++) {
    $g = $httpReq('GET', '/admin/forgot_password.php');
    $httpReq('POST', '/admin/forgot_password.php', ['csrf_token' => $extractCsrf($g['body']), 'username' => 'realadmin'], $g['cookie']);
}
$mailLinesAfter = count(file($mailSinkFile));
check("Persistent DB rate limit blocks excessive forgot_password requests across fresh sessions", ($mailLinesAfter - $mailLinesBefore) <= 4);

// Re-read latest token after rate limit test
$mailLines = file($mailSinkFile);
$lastMail = end($mailLines);
if (preg_match('/reset_password\.php\?token=([a-f0-9]{64})/', (string)$lastMail, $m)) {
    $rawResetToken = $m[1];
}

// 4c. Reset password enforces policy, resets with Argon2id, and prevents replay
$rpGet = $httpReq('GET', '/admin/reset_password.php?token=' . $rawResetToken);
$rpCsrf = $extractCsrf($rpGet['body']);
$rpWeak = $httpReq('POST', '/admin/reset_password.php?token=' . $rawResetToken, ['csrf_token' => $rpCsrf, 'token' => $rawResetToken, 'newpass' => 'short123'], $rpGet['cookie']);
check("reset_password.php rejects <15 char password", str_contains($rpWeak['body'], '15 and 128'));

$rpOk = $httpReq('POST', '/admin/reset_password.php?token=' . $rawResetToken, ['csrf_token' => $rpCsrf, 'token' => $rawResetToken, 'newpass' => 'NewResetPassphrase2026!'], $rpGet['cookie']);
check("reset_password.php accepts 15+ char password", str_contains($rpOk['body'], 'Password reset successful'));
$updatedHash = (string)$pdoCheck->query("SELECT password FROM admins WHERE id = 7")->fetchColumn();
check("reset_password.php stores Argon2id hash", str_starts_with($updatedHash, '$argon2id$') && AdminPasswordPolicy::verify('NewResetPassphrase2026!', $updatedHash));

$rpReplay = $httpReq('POST', '/admin/reset_password.php?token=' . $rawResetToken, ['csrf_token' => $rpCsrf, 'token' => $rawResetToken, 'newpass' => 'AnotherPassphrase2026!'], $rpGet['cookie']);
check("reset_password.php blocks token replay", str_contains($rpReplay['body'], 'Invalid or expired token'));

// Verify password reset immediately revokes prior authenticated session
$dashAfterReset = $httpReq('GET', '/admin/dashboard.php', [], $realPost['cookie']);
check("Password reset immediately revokes prior authenticated admin session", $dashAfterReset['status'] === 302 && $dashAfterReset['location'] === 'login.php');

// 4d. accept_invite.php enforces 15-char NIST policy, SHA-256 token hash, & Argon2id
$invGet = $httpReq('GET', '/admin/accept_invite.php?token=validinvitetoken123');
$invCsrf = $extractCsrf($invGet['body']);
$invWeak = $httpReq('POST', '/admin/accept_invite.php?token=validinvitetoken123', [
    'csrf_token' => $invCsrf, 'token' => 'validinvitetoken123', 'username' => 'invadmin', 'password' => 'abc123', 'confirm_password' => 'abc123'
], $invGet['cookie']);
check("accept_invite.php rejects 6-char password", str_contains($invWeak['body'], '15 and 128'));

$invOk = $httpReq('POST', '/admin/accept_invite.php?token=validinvitetoken123', [
    'csrf_token' => $invCsrf, 'token' => 'validinvitetoken123', 'username' => 'invadmin', 'password' => 'InvitedAdminPassphrase2026!', 'confirm_password' => 'InvitedAdminPassphrase2026!'
], $invGet['cookie']);
check("accept_invite.php accepts 15+ char passphrase and redirects to login.php", $invOk['status'] === 302 && $invOk['location'] === 'login.php');
$invHash = (string)$pdoCheck->query("SELECT password FROM admins WHERE username = 'invadmin'")->fetchColumn();
check("accept_invite.php stores Argon2id hash", str_starts_with($invHash, '$argon2id$'));
$invReplay = $httpReq('GET', '/admin/accept_invite.php?token=validinvitetoken123');
check("accept_invite.php rejects replay of consumed invitation token", str_contains($invReplay['body'], 'Invalid or expired admin invitation token'));

// Re-authenticate superadmin with new password after reset
$saLoginGet = $httpReq('GET', '/admin/login.php');
$saLoginPost = $httpReq('POST', '/admin/login.php', [
    'csrf_token' => $extractCsrf($saLoginGet['body']), 'username' => 'realadmin', 'password' => 'NewResetPassphrase2026!'
], $saLoginGet['cookie']);
$saCookie = $saLoginPost['cookie'];

// 4e. manage_admins.php: invitation token hashed in DB, not leaked in HTML, trusted MKNET_APP_URL
$maGet = $httpReq('GET', '/admin/manage_admins.php', [], $saCookie);
$maCsrf = $extractCsrf($maGet['body']);
$mailLinesBeforeInv = count(file($mailSinkFile));
$maInv = $httpReq('POST', '/admin/manage_admins.php', [
    'csrf_token' => $maCsrf, 'send_invitation' => '1', 'email' => 'newadmin@mknet.test', 'role' => 'admin'
], $saCookie);
$mailLinesAfterInv = file($mailSinkFile);
$invMailLine = (string)end($mailLinesAfterInv);
$rawInvToken = '';
if (preg_match('/accept_invite\.php\?token=([a-f0-9]{64})/', $invMailLine, $m)) {
    $rawInvToken = $m[1];
}
$storedInvToken = (string)$pdoCheck->query("SELECT token FROM admin_invitations WHERE email = 'newadmin@mknet.test' ORDER BY id DESC LIMIT 1")->fetchColumn();
check("manage_admins.php sends HTTPS invitation URL from MKNET_APP_URL via Mailer", $rawInvToken !== '' && str_contains($invMailLine, 'https://admin.mknet.test/admin/accept_invite.php?token='));
check("manage_admins.php stores SHA-256 hash of invitation token (never raw token)", $storedInvToken === hash('sha256', $rawInvToken) && $storedInvToken !== $rawInvToken);
check("manage_admins.php does NOT disclose raw invitation token/link in HTTP response", !str_contains($maInv['body'], $rawInvToken) && !str_contains($maInv['body'], 'accept_invite.php?token='));
$maWeak = $httpReq('POST', '/admin/manage_admins.php', [
    'csrf_token' => $maCsrf, 'create_admin' => '1', 'username' => 'weakadmin', 'email' => 'weak@mknet.test', 'password' => 'short123', 'role' => 'admin'
], $saCookie);
check("manage_admins.php rejects weak admin password on create_admin", str_contains($maWeak['body'], '15 and 128'));
check("AdminPasswordPolicy::trustedBaseUrl fails closed without MKNET_APP_URL", (function(): bool {
    $prev = getenv('MKNET_APP_URL');
    putenv('MKNET_APP_URL');
    $res = AdminPasswordPolicy::trustedBaseUrl();
    if ($prev === false) putenv('MKNET_APP_URL'); else putenv("MKNET_APP_URL={$prev}");
    return $res === '';
})());

echo "\n=== 5. P0 Admin Role Privilege Escalation & Last-Superadmin Guard ===\n";
$unauthMa = $httpReq('GET', '/admin/manage_admins.php');
check("Unauthenticated visitor redirected from manage_admins.php", $unauthMa['status'] === 302 && $unauthMa['location'] === 'login.php');

$normLoginGet = $httpReq('GET', '/admin/login.php');
$normLoginPost = $httpReq('POST', '/admin/login.php', [
    'csrf_token' => $extractCsrf($normLoginGet['body']), 'username' => 'normaladmin', 'password' => 'NormalAdminPassphrase2026!'
], $normLoginGet['cookie']);
$normCookie = $normLoginPost['cookie'];
$normMaGet = $httpReq('GET', '/admin/manage_admins.php', [], $normCookie);
check("EXPECTED_SECURITY_DENIAL: normal admin GET manage_admins.php returns 403", $normMaGet['status'] === 403);

$adminsBefore = (int)$pdoCheck->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$superPwBefore = (string)$pdoCheck->query("SELECT password FROM admins WHERE id = 7")->fetchColumn();
$normEscCreate = $httpReq('POST', '/admin/manage_admins.php', [
    'csrf_token' => $maCsrf, 'create_admin' => '1', 'username' => 'rogue', 'email' => 'rogue@mknet.test', 'password' => 'RogueAdminPassphrase2026!', 'role' => 'superadmin'
], $normCookie);
$normEscInvite = $httpReq('POST', '/admin/manage_admins.php', [
    'csrf_token' => $maCsrf, 'send_invitation' => '1', 'email' => 'rogue@mknet.test', 'role' => 'superadmin'
], $normCookie);
$normEscPw = $httpReq('POST', '/admin/manage_admins.php', [
    'csrf_token' => $maCsrf, 'change_password' => '1', 'admin_id' => '7', 'new_password' => 'HijackedPassphrase2026!'
], $normCookie);
$normEscDel = $httpReq('POST', '/admin/manage_admins.php', [
    'csrf_token' => $maCsrf, 'delete_admin' => '1', 'admin_id' => '7'
], $normCookie);
$adminsAfter = (int)$pdoCheck->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$superPwAfter = (string)$pdoCheck->query("SELECT password FROM admins WHERE id = 7")->fetchColumn();
check("EXPECTED_SECURITY_DENIAL: normal admin direct POSTs (create/invite/chpw/delete) all return 403 & leave DB unchanged",
    $normEscCreate['status'] === 403 && $normEscInvite['status'] === 403 && $normEscPw['status'] === 403 && $normEscDel['status'] === 403 &&
    $adminsBefore === $adminsAfter && $superPwBefore === $superPwAfter
);

$badRole = $httpReq('POST', '/admin/manage_admins.php', [
    'csrf_token' => $maCsrf, 'create_admin' => '1', 'username' => 'badroleuser', 'email' => 'bad@mknet.test', 'password' => 'ValidRoleTestPassphrase2026!', 'role' => 'root_god'
], $saCookie);
check("Invalid/unknown role is rejected by server-side allowlist", str_contains($badRole['body'], 'Invalid administrator role') && (int)$pdoCheck->query("SELECT COUNT(*) FROM admins WHERE username='badroleuser'")->fetchColumn() === 0);

$badCsrf = $httpReq('POST', '/admin/manage_admins.php', [
    'csrf_token' => 'forged_csrf_token', 'create_admin' => '1', 'username' => 'csrfuser', 'email' => 'c@mknet.test', 'password' => 'ValidRoleTestPassphrase2026!', 'role' => 'admin'
], $saCookie);
check("CSRF-invalid POST to manage_admins.php is rejected", str_contains($badCsrf['body'], 'Session expired') && (int)$pdoCheck->query("SELECT COUNT(*) FROM admins WHERE username='csrfuser'")->fetchColumn() === 0);

// Test last-superadmin protection (create a temporary second superadmin? No, id=7 is the ONLY superadmin; test deleting id=7 as another superadmin or via AdminAuth::canDeleteOrDemoteAdmin)
check("AdminAuth::canDeleteOrDemoteAdmin blocks deleting the last active superadmin", !AdminAuth::canDeleteOrDemoteAdmin($pdoCheck, 7) && AdminAuth::canDeleteOrDemoteAdmin($pdoCheck, 8));

echo "\n=== 6. Session Cookie Hardening, Logout & Zero Request-Time DDL ===\n";
[$rcSessProd, $outSessProd] = runPhpWithEnv(
    "register_shutdown_function(function(){ echo 'STRICT=' . ini_get('session.use_strict_mode') . ';HTTPONLY=' . ini_get('session.cookie_httponly') . ';SAMESITE=' . ini_get('session.cookie_samesite') . ';SECURE=' . ini_get('session.cookie_secure'); }); require '{$cfgFile}';",
    ['MKNET_ENV' => 'production', 'MKNET_DB_HOST' => null, 'MKNET_DB_NAME' => null, 'MKNET_DB_USER' => null, 'MKNET_DB_PASSWORD' => null]
);
check("config.php sets strict_mode=1, httponly=1, samesite=Lax, cookie_secure=1 in production",
    str_contains($outSessProd, 'STRICT=1;HTTPONLY=1;SAMESITE=Lax;SECURE=1')
);

$logoutResp = $httpReq('GET', '/admin/logout.php', [], $saCookie);
$saDashAfterLogout = $httpReq('GET', '/admin/dashboard.php', [], $saCookie);
check("logout.php destroys session and invalidates subsequent dashboard access", $logoutResp['status'] === 302 && $saDashAfterLogout['status'] === 302 && $saDashAfterLogout['location'] === 'login.php');

$policySrc = (string)file_get_contents($pr2Root . '/includes/lib/AdminPasswordPolicy.php');
check("AdminPasswordPolicy.php contains zero request-time CREATE TABLE DDL", stripos($policySrc, 'CREATE TABLE') === false);
check("Additive migration migrations/2026_11_26_auth_rate_limits.sql exists", is_file($pr2Root . '/migrations/2026_11_26_auth_rate_limits.sql'));
$missingTblPdo = new PDO('sqlite::memory:');
check("Rate limiter fails closed (returns false) when auth_rate_limits table is missing", AdminPasswordPolicy::checkPersistentRateLimit($missingTblPdo, 'admin_forgot_password', '127.0.0.1|x') === false);

$pdoCheck = null;
if (is_resource($proc)) {
    proc_terminate($proc);
    proc_close($proc);
}
$rmTree = function(string $dir) use (&$rmTree): void {
    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        $p = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($p) ? $rmTree($p) : @unlink($p);
    }
    @rmdir($dir);
};
$rmTree($sapiDir);

echo "\nSummary: {$passed}/{$total} assertions passed.\n";
exit($passed === $total ? 0 : 1);
