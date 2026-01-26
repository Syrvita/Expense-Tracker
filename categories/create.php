<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");

    if ($name === "") {
        $error = "Category name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);

        if ($stmt->execute()) {
            header("Location: /Expense-Tracker/categories/index.php");
            exit;
        } else {
            $error = "Failed to add category. Maybe it already exists.";
        }

        $stmt->close();
    }
}
?>

<h1>Add Category</h1>
<p>Create a new category for your expenses.</p>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
  <div style="margin-bottom:12px;">
    <label>Category Name *</label>
    <input type="text" name="name" required>
  </div>

  <div class="actions">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn" href="/Expense-Tracker/categories/index.php">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>