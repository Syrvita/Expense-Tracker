<?php
$host = "sql301.infinityfree.com";
$user = "if0_40953408";
$pass = "Bobosirajj0519";
$dbname = "if0_40953408_expense_tracker";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
  die("DB Connection failed: " . $conn->connect_error);
}
?>
