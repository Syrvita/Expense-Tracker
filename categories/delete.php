<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /Expense-Tracker/categories/index.php");
    exit;
}

$id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

if ($id <= 0) {
    header("Location: /Expense-Tracker/categories/index.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    // category is probably used by expenses (FK RESTRICT)
    header("Location: /Expense-Tracker/categories/index.php?error=used");
    exit;
}

$stmt->close();

header("Location: /Expense-Tracker/categories/index.php");
exit;
