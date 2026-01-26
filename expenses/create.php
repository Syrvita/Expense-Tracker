<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$error = "";

/* Load categories for dropdown */
$catResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

$categories = [];
if ($catResult && $catResult->num_rows > 0) {
    while ($c = $catResult->fetch_assoc()) {
        $categories[] = $c;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $category_id = (int)($_POST["category_id"] ?? 0);
    $amount = trim($_POST["amount"] ?? "");
    $expense_date = trim($_POST["expense_date"] ?? "");
    $notes = trim($_POST["notes"] ?? "");

    if ($title === "" || $category_id <= 0 || $amount === "" || $expense_date === "") {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($amount) || (float)$amount <= 0) {
        $error = "Amount must be a number greater than 0.";
    } else {
        $user_id = (int)$_SESSION["user"]["id"];

        $stmt = $conn->prepare("
            INSERT INTO expenses (user_id, category_id, title, amount, expense_date, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $amt = (float)$amount;
        $stmt->bind_param("iisdss", $user_id, $category_id, $title, $amt, $expense_date, $notes);

        if ($stmt->execute()) {
            header("Location: /Expense-Tracker/expenses/index.php");
            exit;
        } else {
            $error = "Failed to add expense.";
        }

        $stmt->close();
    }
}
?>

<h1>Add Expense</h1>
<p>Log a new expense entry.</p>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (count($categories) === 0): ?>
  <div class="notice-error">No categories found. Create a category first.</div>
  <div class="toolbar">
    <a class="btn btn-primary" href="/Expense-Tracker/categories/create.php">Add Category</a>
    <a class="btn" href="/Expense-Tracker/expenses/index.php">Back</a>
  </div>
<?php else: ?>

<form method="POST" action="">
  <div class="form-row" style="margin-bottom:12px;">
    <div>
      <label>Title *</label>
      <input type="text" name="title" required>
    </div>

    <div>
      <label>Category *</label>
      <select name="category_id" required>
        <option value="">-- Select Category --</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat["id"] ?>">
            <?= htmlspecialchars($cat["name"]) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="form-row" style="margin-bottom:12px;">
    <div>
      <label>Amount *</label>
      <input type="number" step="0.01" name="amount" required>
    </div>

    <div>
      <label>Date *</label>
      <input type="date" name="expense_date" required>
    </div>
  </div>

  <div style="margin-bottom:12px;">
    <label>Notes</label>
    <textarea name="notes" rows="3"></textarea>
  </div>

  <div class="actions">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn" href="/Expense-Tracker/expenses/index.php">Cancel</a>
  </div>
</form>

<?php endif; ?>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
