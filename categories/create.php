<?php
ob_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$error = "";
$user_id = (int)$_SESSION["user"]["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");

  if ($name === "") {
    $error = "Category name is required.";
  } else {
    $stmt = $conn->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $name);

    if ($stmt->execute()) {
      $stmt->close();
      header("Location: /categories/index.php");
      exit;
    } else {
      if ($conn->errno === 1062) {
        $error = "Category already exists.";
      } else {
        $error = "Failed to add category.";
      }
    }

    $stmt->close();
  }
}
?>

<h1>Add Category</h1>
<p>Create a category to group expenses.</p>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
  <div style="margin-bottom:12px;">
    <label>Category name *</label>
    <input type="text" name="name" value="<?= htmlspecialchars($_POST["name"] ?? "") ?>" required>
  </div>

  <div class="actions">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn" href="/categories/index.php">Cancel</a>
  </div>
</form>

<?php
require_once __DIR__ . "/../includes/footer.php";
ob_end_flush();
?>