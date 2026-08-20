<?php
/**
 * Lightweight smoke test: hits key pages on a running local server and
 * flags anything that looks like a fatal error, so you can catch a
 * broken page before your visitors do.
 *
 * Usage:
 *   1) php -S 127.0.0.1:8090   (run this from the mknet/ folder in another terminal)
 *   2) php scripts/smoke_test.php
 */

$base = 'http://127.0.0.1:8000';
$pages = [
    '/index.php', '/pages/about.php', '/pages/packages.php', '/pages/bundles.php',
    '/pages/availability.php', '/pages/coverage.php', '/pages/news.php', '/pages/reviews.php',
    '/pages/contact.php', '/pages/register.php', '/pages/login.php',
    '/admin/login.php',
];

$failures = 0;
foreach ($pages as $path) {
    $ch = curl_init($base . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $issue = null;
    if ($body === false) {
        $issue = 'could not connect (is the php -S server running?)';
    } elseif (preg_match('/fatal error|parse error|cannot redeclare|uncaught error/i', (string)$body, $m)) {
        $issue = $m[0];
    } elseif ($code >= 500) {
        $issue = "HTTP $code";
    }

    if ($issue) {
        echo "FAIL  $path -> $issue\n";
        $failures++;
    } else {
        echo "OK    $path (HTTP $code)\n";
    }
}

echo "\n" . ($failures ? "$failures page(s) need attention." : "All good!") . "\n";
