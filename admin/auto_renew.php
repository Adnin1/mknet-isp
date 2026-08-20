<?php
require_once __DIR__ . '/../config.php';
try {
    $today = date('Y-m-d');
    $bills = $pdo->query("SELECT * FROM billing WHERE auto_renew=1 AND due_date='$today'")->fetchAll();
    foreach($bills as $b){
      $stmt = $pdo->prepare("INSERT INTO billing (user_id, amount, due_date, status, auto_renew) VALUES (?,?,?,?,?)");
      $next_due = date('Y-m-d', strtotime('+30 days'));
      $stmt->execute([$b['user_id'],$b['amount'],$next_due,'pending',1]);
    }
} catch (Throwable $e) {}
?>
