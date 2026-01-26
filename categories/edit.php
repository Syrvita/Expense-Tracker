<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$error = "";
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id <= 0) {
    echo "<div class='notice-error'>Invalid category ID.</div>";
    require_once __DIR__ . "/../includes/footer.php";
    exit;
}

$stmt = $conn->prepare("SELECT id, name FROM categories WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();
$stmt->close();

if (!$category) {
    echo "<div class='notice-error'>Category not found.</div>";
    require_once __DIR__ . "/../includes/footer.php";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");

    if ($name === "") {
        $error = "Category name is required.";
    } else {
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);

        if ($stmt->execute()) {
            header("Location: /Expense-Tracker/categories/index.php");
            exit;
        } else {
            $error = "Failed to update category.";
        }

        $stmt->close();
    }
}
?>

<h1>Edit Category</h1>
<p>Update an existing category.</p>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
  <div style="margin-bottom:12px;">
    <label>Category Name *</label>
    <input type="text" name="name" value="<?= htmlspecialchars($category["name"]) ?>" required>
  </div>

  <div class="actions">
    <button class="btn btn-primary" type="submit">Update</button>
    <a class="btn" href="/Expense-Tracker/categories/index.php">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>