<?php
declare(strict_types=1);

/**
 * One-time CLI-only initial administrator provisioning.
 * Set MKNET_INITIAL_ADMIN_USERNAME and MKNET_INITIAL_ADMIN_PASSWORD in the
 * shell environment before running this script. It never prints the password.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config.php';

$username = trim((string)(getenv('MKNET_INITIAL_ADMIN_USERNAME') ?: ''));
$password = (string)(getenv('MKNET_INITIAL_ADMIN_PASSWORD') ?: '');

if (!preg_match('/^[a-zA-Z0-9_.-]{3,64}$/', $username)) {
    fwrite(STDERR, "Invalid username. Use 3-64 letters, digits, dot, dash or underscore.\n");
    exit(2);
}

// NIST SP 800-63B-4: minimum 15 characters, no composition rules, support spaces & Unicode, blocklist check
$passLen = function_exists('mb_strlen') ? mb_strlen($password, 'UTF-8') : strlen($password);
if ($passLen < 15 || strlen($password) > 128) {
    fwrite(STDERR, "Password must be between 15 and 128 characters (passphrases with spaces and Unicode are supported).\n");
    exit(2);
}

$lowerPass = function_exists('mb_strtolower') ? mb_strtolower($password, 'UTF-8') : strtolower($password);
$blockedPassphrases = [
    'password1234567', 'password12345678', '123456789012345', '1234567890123456',
    'qwertyuiopasdfg', 'admin1234567890', 'mknetadmin2026!', 'mknet@admin12345',
    'letmein12345678', 'welcome12345678', 'administrator12', 'changeme1234567',
];
if (in_array($lowerPass, $blockedPassphrases, true) || str_contains($lowerPass, strtolower($username))) {
    fwrite(STDERR, "Password is too common or contains the username. Choose a unique passphrase.\n");
    exit(2);
}

$hashAlgo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;

try {
    $pdo->beginTransaction();
    $existing = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($existing !== 0) {
        $pdo->rollBack();
        fwrite(STDERR, "Administrator provisioning refused: an admin account already exists.\n");
        exit(3);
    }

    $stmt = $pdo->prepare('INSERT INTO admins (username, password, role) VALUES (?, ?, ?)');
    $stmt->execute([$username, password_hash($password, $hashAlgo), 'admin']);
    $pdo->commit();

    fwrite(STDOUT, "Initial administrator created. The password was not displayed or logged. Remove the environment variables now.\n");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Administrator provisioning failed. Check the database schema and protected logs.\n");
    exit(1);
} finally {
    $password = '';
}
