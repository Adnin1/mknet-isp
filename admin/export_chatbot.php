<?php
require_once __DIR__ . '/../config.php';
session_start();
if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

// Fetch all chatbot messages
$stmt = $pdo->query("SELECT * FROM chatbot_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=chatbot_messages.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Write column headers
fputcsv($output, array('ID','User Message','Bot Reply','Created At'));

// Write rows
foreach($messages as $m){
  fputcsv($output, $m);
}

fclose($output);
exit;
