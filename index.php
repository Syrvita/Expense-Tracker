<?php
require_once __DIR__ . "/includes/db.php";
require_once __DIR__ . "/includes/auth.php";
require_login();
require_once __DIR__ . "/includes/header.php";

$user_id = (int)$_SESSION["user"]["id"];

/* =========================
   Recent 5 expenses (user only)
========================= */
$stmt = $conn->prepare("
  SELECT e.id, e.title, e.amount, e.expense_date, c.name AS category
  FROM expenses e
  JOIN categories c ON e.category_id = c.id
  WHERE e.user_id = ?
  ORDER BY e.expense_date DESC, e.id DESC
  LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recentRes = $stmt->get_result();
$recent = $recentRes->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* =========================
   Monthly totals by category (user only)
========================= */
$startMonth = date("Y-m-01");
$endMonth   = date("Y-m-t");

$stmt = $conn->prepare("
  SELECT c.name AS category, COALESCE(SUM(e.amount),0) AS total
  FROM categories c
  LEFT JOIN expenses e
    ON e.category_id = c.id
    AND e.user_id = ?
    AND e.expense_date BETWEEN ? AND ?
  WHERE c.user_id = ?
  GROUP BY c.id
  ORDER BY total DESC, c.name ASC
");
$stmt->bind_param("issi", $user_id, $startMonth, $endMonth, $user_id);
$stmt->execute();
$res = $stmt->get_result();

$labels = [];
$values = [];
while ($r = $res->fetch_assoc()) {
  $labels[] = $r["category"];
  $values[] = (float)$r["total"];
}
$stmt->close();

/* =========================
   Budget (user only)
========================= */
$stmt = $conn->prepare("SELECT * FROM budgets WHERE user_id = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budgetRes = $stmt->get_result();
$budget = $budgetRes->fetch_assoc();
$stmt->close();

$budgetAmount = 0;
$budgetPeriod = "";
$budgetStart = "";
$budgetEnd = "";
$budgetSpent = 0;
$budgetRemaining = 0;
$budgetPercent = 0;
$budgetStatus = "No Budget";
$progressClass = "progress-safe";

if ($budget) {
  $budgetAmount = (float)$budget["amount"];
  $budgetPeriod = $budget["period"] ?? "monthly";

  if ($budgetPeriod === "daily") {
    $budgetStart = date("Y-m-d");
    $budgetEnd = date("Y-m-d");
  } elseif ($budgetPeriod === "weekly") {
    $budgetStart = date("Y-m-d", strtotime("monday this week"));
    $budgetEnd = date("Y-m-d", strtotime("sunday this week"));
  } else {
    $budgetPeriod = "monthly";
    $budgetStart = date("Y-m-01");
    $budgetEnd = date("Y-m-t");
  }

  $stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_spent
    FROM expenses
    WHERE user_id = ?
      AND expense_date BETWEEN ? AND ?
  ");
  $stmt->bind_param("iss", $user_id, $budgetStart, $budgetEnd);
  $stmt->execute();
  $spentRes = $stmt->get_result();
  $spentRow = $spentRes->fetch_assoc();
  $budgetSpent = (float)$spentRow["total_spent"];
  $stmt->close();

  $budgetRemaining = $budgetAmount - $budgetSpent;

  if ($budgetAmount > 0) {
    $budgetPercent = ($budgetSpent / $budgetAmount) * 100;
    if ($budgetPercent > 100) $budgetPercent = 100;
    if ($budgetPercent < 0) $budgetPercent = 0;
  }

  if ($budgetPercent < 70) {
    $budgetStatus = "Safe";
    $progressClass = "progress-safe";
  } elseif ($budgetPercent < 90) {
    $budgetStatus = "Warning";
    $progressClass = "progress-warning";
  } else {
    $budgetStatus = "Exceeded";
    $progressClass = "progress-danger";
  }
}
?>

<h1>Home</h1>
<p>Recent activity and this month's spending by category.</p>

<div style="display:grid; grid-template-columns: 1fr 420px; gap:20px; align-items:start;">

  <!-- LEFT -->
  <div>

    <!-- Recent Expenses -->
    <div class="card">
      <h3 style="margin-top:0;">Recent Expenses</h3>

      <div class="recent-list">
        <?php if (count($recent) === 0): ?>
          <div class="notice-error">No recent expenses.</div>
        <?php else: ?>
          <?php foreach ($recent as $row): ?>
            <div class="recent-item">

              <div>
                <div style="font-weight:700;"><?= htmlspecialchars($row["title"]) ?></div>
                <div style="font-size:13px; color:var(--muted);">
                  <?= htmlspecialchars($row["category"]) ?> • <?= htmlspecialchars($row["expense_date"]) ?>
                </div>
              </div>

              <div style="display:flex; align-items:center; gap:14px;">
                <div style="font-weight:700; min-width:110px; text-align:right;">
                  <?= number_format((float)$row["amount"], 2) ?>
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                  <a class="btn btn-mini" href="/expenses/edit.php?id=<?= (int)$row["id"] ?>">Edit</a>

                  <form class="form-plain" method="POST" action="/expenses/delete.php"
                        onsubmit="return confirm('Delete this expense?');">
                    <input type="hidden" name="id" value="<?= (int)$row["id"] ?>">
                    <button class="btn btn-danger btn-mini" type="submit">Delete</button>
                  </form>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div style="margin-top:12px;">
        <a class="btn" href="/expenses/index.php">View all expenses</a>
      </div>
    </div>


    <!-- Budget Card -->
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
        <h3 style="margin:0;">Current Budget</h3>

        <?php if ($budget): ?>
          <span class="badge"><?= htmlspecialchars(ucfirst($budgetPeriod)) ?> • <?= htmlspecialchars($budgetStatus) ?></span>
        <?php else: ?>
          <span class="badge">No Budget</span>
        <?php endif; ?>
      </div>

      <?php if (!$budget): ?>
        <p style="margin-top:10px;">No active budget set.</p>
        <a class="btn btn-primary" href="/budget/index.php">Set Budget</a>
      <?php else: ?>

        <div style="margin-top:10px; color:var(--muted); font-size:13px;">
          <?= htmlspecialchars(strtoupper($budgetPeriod)) ?> • <?= htmlspecialchars($budgetStart) ?> → <?= htmlspecialchars($budgetEnd) ?>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px; margin-top:12px;">
          <div>
            <div style="color:var(--muted); font-size:12px;">Budget</div>
            <div style="font-weight:800; font-size:18px;"><?= number_format($budgetAmount, 2) ?></div>
          </div>

          <div>
            <div style="color:var(--muted); font-size:12px;">Spent</div>
            <div style="font-weight:800; font-size:18px;"><?= number_format($budgetSpent, 2) ?></div>
          </div>

          <div>
            <div style="color:var(--muted); font-size:12px;">Remaining</div>
            <div style="font-weight:800; font-size:18px;"><?= number_format($budgetRemaining, 2) ?></div>
          </div>
        </div>

        <div style="margin-top:14px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <div style="color:var(--muted); font-size:12px;">Progress</div>
            <div style="font-weight:700; font-size:13px;"><?= number_format($budgetPercent, 0) ?>%</div>
          </div>

          <div style="height:10px; background:rgba(205,196,185,0.08); border:1px solid var(--border); border-radius:999px; overflow:hidden;">
            <div class="<?= htmlspecialchars($progressClass) ?>" style="height:100%; width:<?= (int)$budgetPercent ?>%;"></div>
          </div>
        </div>

        <div style="margin-top:16px;">
          <a class="btn manage-budget-btn" href="/budget/index.php">Manage Budget</a>
        </div>

      <?php endif; ?>
    </div>

  </div>

  <!-- RIGHT -->
  <div>
    <div class="card chart-wrap">
      <h3 style="margin-top:0;">This Month — By Category</h3>

      <div class="chart-box">
        <canvas
          id="monthlyChart"
          data-labels='<?= htmlspecialchars(json_encode($labels), ENT_QUOTES) ?>'
          data-values='<?= htmlspecialchars(json_encode($values), ENT_QUOTES) ?>'
        ></canvas>
      </div>
    </div>

    <div style="height:16px;"></div>

    <div class="card">
      <h4 style="margin-top:0;">Quick Actions</h4>
      <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a class="btn btn-primary" href="/expenses/create.php">Add Expense</a>
        <a class="btn" href="/categories/index.php">Manage Categories</a>
        <a class="btn" href="/reports/summary.php">View Reports</a>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
