<?php
$host = "localhost";
$dbname = "mknet";
$username = "root";
$password = "";

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
function sendNotification($email,$message){
  mail($email,"Payment Confirmation",$message,"From: no-reply@yourdomain.com");
}
