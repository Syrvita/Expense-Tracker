<?php
ob_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$error = "";
$id = (int)($_GET["id"] ?? 0);
$user_id = (int)$_SESSION["user"]["id"];

if ($id <= 0) {
  echo "<div class='notice-error'>Invalid category ID.</div>";
  require_once __DIR__ . "/../includes/footer.php";
  exit;
}

/* Load category (must belong to logged-in user) */
$stmt = $conn->prepare("SELECT id, name FROM categories WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$category = $res->fetch_assoc();
$stmt->close();

if (!$category) {
  echo "<div class='notice-error'>Category not found or access denied.</div>";
  require_once __DIR__ . "/../includes/footer.php";
  exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");

  if ($name === "") {
    $error = "Category name is required.";
  } else {
    $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $name, $id, $user_id);

    if ($stmt->execute()) {
      $stmt->close();
      header("Location: /categories/index.php");
      exit;
    } else {
      if ($conn->errno === 1062) {
        $error = "Category already exists.";
      } else {
        $error = "Failed to update category.";
      }
    }

    $stmt->close();
  }
}
?>

<h1>Edit Category</h1>
<p>Update your category name.</p>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
  <div style="margin-bottom:12px;">
    <label>Category name *</label>
    <input type="text" name="name" value="<?= htmlspecialchars($category["name"]) ?>" required>
  </div>

  <div class="actions">
    <button class="btn btn-primary" type="submit">Update</button>
    <a class="btn" href="/categories/index.php">Cancel</a>
  </div>
</form>

<?php
require_once __DIR__ . "/../includes/footer.php";
ob_end_flush();
?>
