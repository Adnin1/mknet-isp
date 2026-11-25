<?php
require_once __DIR__ . '/../includes/lib/AdminAuth.php';
AdminAuth::destroySessionAndCookie();

// Redirect back to login page
header("Location: login.php");
exit;
?>
