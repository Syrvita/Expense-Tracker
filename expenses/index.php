<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$user_id = (int)$_SESSION["user"]["id"];
$month = trim($_GET["month"] ?? ""); // YYYY-MM

$where = "WHERE e.user_id = ?";
$params = [$user_id];
$types = "i";

if ($month !== "") {
    $start = $month . "-01";
    $end = date("Y-m-t", strtotime($start));

    $where .= " AND e.expense_date BETWEEN ? AND ?";
    $params[] = $start;
    $params[] = $end;
    $types .= "ss";
}

$sql = "
SELECT
  e.id,
  e.title,
  e.amount,
  e.expense_date,
  e.notes,
  c.name AS category
FROM expenses e
JOIN categories c ON e.category_id = c.id
$where
ORDER BY e.expense_date DESC, e.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<h1>Expenses</h1>
<p>Track your spending with category and date filters.</p>

<div class="toolbar">
  <a class="btn btn-primary" href="/
expenses/create.php">Add Expense</a>

  <form method="GET" action="" style="margin:0; padding:0; border:none; background:transparent; box-shadow:none;">
    <div style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
      <div style="min-width:180px;">
        <label>Month</label>
        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
      </div>
      <div class="actions">
        <button class="btn" type="submit">Apply</button>
        <a class="btn" href="/
expenses/index.php">Reset</a>
      </div>
    </div>
  </form>
</div>

<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>Title</th>
      <th>Category</th>
      <th>Amount</th>
      <th>Notes</th>
      <th>Actions</th>
    </tr>
  </thead>

  <tbody>
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row["expense_date"]) ?></td>
          <td><?= htmlspecialchars($row["title"]) ?></td>
          <td><span class="badge"><?= htmlspecialchars($row["category"]) ?></span></td>
          <td><?= number_format((float)$row["amount"], 2) ?></td>
          <td><?= htmlspecialchars($row["notes"] ?? "") ?></td>
          <td>
            <div class="actions">
              <a class="btn" href="/
expenses/edit.php?id=<?= (int)$row["id"] ?>">Edit</a>

              <form action="/
expenses/delete.php" method="POST">
                <input type="hidden" name="id" value="<?= (int)$row["id"] ?>">
                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this expense?')">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="6">No expenses found.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

<?php
$stmt->close();
require_once __DIR__ . "/../includes/footer.php";
?>
