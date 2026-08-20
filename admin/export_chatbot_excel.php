<?php
require_once __DIR__ . '/../config.php';
session_start();
if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

$stmt = $pdo->query("SELECT * FROM chatbot_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=chatbot_messages.xls");

// Output table
echo "<table border='1'>";
echo "<tr><th>ID</th><th>User Message</th><th>Bot Reply</th><th>Created At</th></tr>";
foreach($messages as $m){
  echo "<tr>";
  echo "<td>".$m['id']."</td>";
  echo "<td>".htmlspecialchars($m['user_message'])."</td>";
  echo "<td>".htmlspecialchars($m['bot_reply'])."</td>";
  echo "<td>".$m['created_at']."</td>";
  echo "</tr>";
}
echo "</table>";
exit;
