<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$user_id = (int)$_SESSION["user"]["id"];
$error = "";
$success = "";

/* Load current active budget */
$stmt = $conn->prepare("SELECT * FROM budgets WHERE user_id = ? AND is_active = 1 LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budgetRes = $stmt->get_result();
$budget = $budgetRes->fetch_assoc();
$stmt->close();

/* Handle create/update */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $period = trim($_POST["period"] ?? "");
    $amount = trim($_POST["amount"] ?? "");

    $validPeriods = ["daily", "weekly", "monthly"];

    if (!in_array($period, $validPeriods, true)) {
        $error = "Invalid period.";
    } elseif ($amount === "" || !is_numeric($amount) || (float)$amount <= 0) {
        $error = "Budget amount must be greater than 0.";
    } else {
        $amt = (float)$amount;

        if ($budget) {
            $stmt = $conn->prepare("UPDATE budgets SET period = ?, amount = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sdii", $period, $amt, $budget["id"], $user_id);
            if ($stmt->execute()) {
                $success = "Budget updated.";
            } else {
                $error = "Failed to update budget.";
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO budgets (user_id, period, amount, is_active) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("isd", $user_id, $period, $amt);
            if ($stmt->execute()) {
                $success = "Budget saved.";
            } else {
                $error = "Failed to save budget.";
            }
            $stmt->close();
        }

        // reload budget after update/insert
        $stmt = $conn->prepare("SELECT * FROM budgets WHERE user_id = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $budgetRes = $stmt->get_result();
        $budget = $budgetRes->fetch_assoc();
        $stmt->close();
    }
}

/* Compute current period date range */
$period = $budget["period"] ?? "monthly";

if ($period === "daily") {
    $start = date("Y-m-d");
    $end = date("Y-m-d");
} elseif ($period === "weekly") {
    $start = date("Y-m-d", strtotime("monday this week"));
    $end = date("Y-m-d", strtotime("sunday this week"));
} else {
    $start = date("Y-m-01");
    $end = date("Y-m-t");
}

/* Calculate spending in current period */
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_spent
    FROM expenses
    WHERE user_id = ?
      AND expense_date BETWEEN ? AND ?
");
$stmt->bind_param("iss", $user_id, $start, $end);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$spent = (float)$row["total_spent"];
$stmt->close();

$budgetAmount = isset($budget["amount"]) ? (float)$budget["amount"] : 0;
$remaining = $budgetAmount - $spent;

$percent = 0;
if ($budgetAmount > 0) {
    $percent = ($spent / $budgetAmount) * 100;
    if ($percent > 999) $percent = 999;
}

/* Status + Progress Color */
$status = "No Budget";
$progressClass = "progress-safe";

if ($budgetAmount > 0) {
    if ($percent < 70) {
        $status = "Safe";
        $progressClass = "progress-safe";
    } elseif ($percent < 90) {
        $status = "Warning";
        $progressClass = "progress-warning";
    } else {
        $status = "Exceeded";
        $progressClass = "progress-danger";
    }
}

/* UI values */
$percentText = number_format($percent, 0) . "%";
$spentText = number_format($spent, 2);
$budgetText = number_format($budgetAmount, 2);
$remainingText = number_format($remaining, 2);
?>

<h1>Budget</h1>
<p>Set a daily, weekly, or monthly budget and track your spending.</p>

<?php if ($success !== ""): ?>
  <div class="notice-ok"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:start;">

  <div class="card">
    <h3 style="margin-top:0;">Set Budget</h3>

    <form method="POST" action="" style="margin:0;">
      <div style="margin-bottom:12px;">
        <label>Period *</label>
        <select name="period" required>
          <option value="daily"   <?= ($period === "daily") ? "selected" : "" ?>>Daily</option>
          <option value="weekly"  <?= ($period === "weekly") ? "selected" : "" ?>>Weekly</option>
          <option value="monthly" <?= ($period === "monthly") ? "selected" : "" ?>>Monthly</option>
        </select>
      </div>

      <div style="margin-bottom:12px;">
        <label>Amount *</label>
        <input type="number" step="0.01" name="amount" value="<?= htmlspecialchars($budget["amount"] ?? "") ?>" required>
      </div>

      <div class="actions">
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0;">Current Period</h3>

    <div style="display:flex; justify-content:space-between; gap:12px; margin-bottom:8px;">
      <div style="color:var(--muted); font-size:13px;">
        <?= htmlspecialchars(strtoupper($period)) ?> • <?= htmlspecialchars($start) ?> → <?= htmlspecialchars($end) ?>
      </div>
      <div class="badge"><?= htmlspecialchars($status) ?></div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:10px;">
      <div>
        <div style="color:var(--muted); font-size:12px;">Budget</div>
        <div style="font-weight:800; font-size:18px;"><?= $budgetText ?></div>
      </div>
      <div>
        <div style="color:var(--muted); font-size:12px;">Spent</div>
        <div style="font-weight:800; font-size:18px;"><?= $spentText ?></div>
      </div>
      <div>
        <div style="color:var(--muted); font-size:12px;">Remaining</div>
        <div style="font-weight:800; font-size:18px;"><?= $remainingText ?></div>
      </div>
      <div>
        <div style="color:var(--muted); font-size:12px;">Progress</div>
        <div style="font-weight:800; font-size:18px;"><?= htmlspecialchars($percentText) ?></div>
      </div>
    </div>

    <div style="margin-top:14px;">
      <div style="height:10px; background:rgba(205,196,185,0.08); border:1px solid var(--border); border-radius:999px; overflow:hidden;">
        <div class="<?= htmlspecialchars($progressClass) ?>" style="height:100%; width:<?= (int)$percent ?>%;"></div>
      </div>
    </div>

    <div style="margin-top:10px; color:var(--muted); font-size:13px;">
      Tip: If you exceed budget, reduce spending or increase budget for this period.
    </div>
  </div>

</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>