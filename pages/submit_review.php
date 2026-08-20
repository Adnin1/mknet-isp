<?php
require_once __DIR__ . '/../config.php';
if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /pages/packages.php'); exit; }
$package_id = (int)($_POST['package_id'] ?? 0);
$name = trim($_POST['name'] ?? 'Anonymous');
$rating = min(5, max(1, (int)($_POST['rating'] ?? 5)));
$comment = trim($_POST['comment'] ?? '');
$stmt = $pdo->prepare("INSERT INTO reviews (package_id,user_name,rating,comment,status) VALUES (?,?,?,?, 'pending')");
$stmt->execute([$package_id,$name,$rating,$comment]);
header('Location: /pages/packages.php?review_submitted=1');
exit;
