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
if (strlen($password) < 14
    || !preg_match('/[a-z]/', $password)
    || !preg_match('/[A-Z]/', $password)
    || !preg_match('/[0-9]/', $password)
    || !preg_match('/[^a-zA-Z0-9]/', $password)) {
    fwrite(STDERR, "Password must be at least 14 characters and include upper/lowercase, a number and a symbol.\n");
    exit(2);
}

try {
    $pdo->beginTransaction();
    $existing = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($existing !== 0) {
        $pdo->rollBack();
        fwrite(STDERR, "Administrator provisioning refused: an admin account already exists.\n");
        exit(3);
    }

    $stmt = $pdo->prepare('INSERT INTO admins (username, password, role) VALUES (?, ?, ?)');
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), 'admin']);
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
