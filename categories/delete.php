<?php
ob_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: /categories/index.php");
  exit;
}

$id = (int)($_POST["id"] ?? 0);
$user_id = (int)$_SESSION["user"]["id"];

if ($id <= 0) {
  header("Location: /categories/index.php");
  exit;
}

$stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: /categories/index.php");
exit;