<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$error = "";
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$user_id = (int)$_SESSION["user"]["id"];

if ($id <= 0) {
    echo "<div class='notice-error'>Invalid expense ID.</div>";
    require_once __DIR__ . "/../includes/footer.php";
    exit;
}

/* Load expense (only if it belongs to the logged-in user) */
$stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$expense = $res->fetch_assoc();
$stmt->close();

if (!$expense) {
    echo "<div class='notice-error'>Expense not found or access denied.</div>";
    require_once __DIR__ . "/../includes/footer.php";
    exit;
}

/* Load categories */
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
        $stmt = $conn->prepare("
            UPDATE expenses
            SET category_id = ?, title = ?, amount = ?, expense_date = ?, notes = ?
            WHERE id = ? AND user_id = ?
        ");

        $amt = (float)$amount;
        $stmt->bind_param("isdssii", $category_id, $title, $amt, $expense_date, $notes, $id, $user_id);

        if ($stmt->execute()) {
            header("Location: /Expense-Tracker/expenses/index.php");
            exit;
        } else {
            $error = "Failed to update expense.";
        }

        $stmt->close();
    }
}
?>

<h1>Edit Expense</h1>
<p>Update an existing expense entry.</p>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
  <div class="form-row" style="margin-bottom:12px;">
    <div>
      <label>Title *</label>
      <input type="text" name="title" value="<?= htmlspecialchars($expense["title"]) ?>" required>
    </div>

    <div>
      <label>Category *</label>
      <select name="category_id" required>
        <option value="">-- Select Category --</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat["id"] ?>"
            <?= ((int)$expense["category_id"] === (int)$cat["id"]) ? "selected" : "" ?>>
            <?= htmlspecialchars($cat["name"]) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="form-row" style="margin-bottom:12px;">
    <div>
      <label>Amount *</label>
      <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars($expense["amount"]) ?>" required>
    </div>

    <div>
      <label>Date *</label>
      <input type="date" name="expense_date" value="<?= htmlspecialchars($expense["expense_date"]) ?>" required>
    </div>
  </div>

  <div style="margin-bottom:12px;">
    <label>Notes</label>
    <textarea name="notes" rows="3"><?= htmlspecialchars($expense["notes"] ?? "") ?></textarea>
  </div>

  <div class="actions">
    <button class="btn btn-primary" type="submit">Update</button>
    <a class="btn" href="/Expense-Tracker/expenses/index.php">Cancel</a>
  </div>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
s