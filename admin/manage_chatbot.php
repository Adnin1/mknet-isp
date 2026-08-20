<?php
require_once __DIR__ . '/../config.php';
session_start();
if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

$chats = $pdo->query("SELECT * FROM chatbot_messages ORDER BY created_at DESC")->fetchAll();
?>
<link rel="stylesheet" href="style.css">

<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Chatbot Conversations</h2>
  <p><a href="export_chatbot.php">⬇ Export CSV</a> | <a href="export_chatbot_excel.php">⬇ Export Excel</a></p>
  <table>
    <tr><th>User Message</th><th>Bot Reply</th><th>Time</th></tr>
    <?php foreach($chats as $chat): ?>
    <tr>
      <td><?= htmlspecialchars($chat['user_message']) ?></td>
      <td><?= htmlspecialchars($chat['bot_reply']) ?></td>
      <td><?= $chat['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include __DIR__ . '/footer.php'; ?>
