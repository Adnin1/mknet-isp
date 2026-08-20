<?php
require_once __DIR__ . '/../config.php';
session_start();
$data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
$theme = $data['theme'] ?? 'dark';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET theme_preference=? WHERE id=?");
    $stmt->execute([$theme, $_SESSION['user_id']]);
}

$_SESSION['theme'] = $theme;
echo json_encode(['status'=>'ok', 'theme'=>$theme]);
?>
