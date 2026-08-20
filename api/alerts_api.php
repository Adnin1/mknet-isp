<?php
require_once __DIR__ . '/../config.php';
session_start();
if(!isset($_SESSION['user_id'])){
    echo json_encode(['status' => 'guest', 'problems_count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get latest problems count from usage_history
$stmt = $pdo->prepare("SELECT problems_count FROM usage_history WHERE user_id=? ORDER BY date DESC LIMIT 1");
$stmt->execute([$user_id]);
$row = $stmt->fetch();

echo json_encode(['problems_count' => $row ? $row['problems_count'] : 0]);
?>
