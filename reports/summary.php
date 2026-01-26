<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$user_id = (int)$_SESSION["user"]["id"];

$sql = "
SELECT
  c.name AS category,
  COALESCE(SUM(e.amount), 0) AS total_spent
FROM categories c
LEFT JOIN expenses e
  ON e.category_id = c.id
  AND e.user_id = ?
WHERE c.user_id = ?
GROUP BY c.id
ORDER BY total_spent DESC, c.name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$grandTotal = 0;
$rows = [];

$labels = [];
$values = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    $grandTotal += (float)$row["total_spent"];

    $labels[] = $row["category"];
    $values[] = (float)$row["total_spent"];
  }
}
?>

<h1>Reports</h1>
<p>Total spent per category.</p>

<!-- Chart ABOVE -->
<div class="card chart-wrap" style="margin-bottom:16px;">
  <h3 style="margin-top:0;">Spending Chart</h3>

  <?php if (count($labels) === 0): ?>
    <div class="notice-error">No expenses yet.</div>
  <?php else: ?>
    <div class="chart-box">
      <canvas
        id="reportChart"
        data-labels='<?= htmlspecialchars(json_encode($labels), ENT_QUOTES) ?>'
        data-values='<?= htmlspecialchars(json_encode($values), ENT_QUOTES) ?>'
      ></canvas>
    </div>
  <?php endif; ?>
</div>

<!-- Table BELOW -->
<table>
  <thead>
    <tr>
      <th>Category</th>
      <th>Total Spent</th>
    </tr>
  </thead>

  <tbody>
    <?php if (count($rows) > 0): ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r["category"]) ?></td>
          <td><?= number_format((float)$r["total_spent"], 2) ?></td>
        </tr>
      <?php endforeach; ?>
      <tr>
        <td><strong>Grand Total</strong></td>
        <td><strong><?= number_format($grandTotal, 2) ?></strong></td>
      </tr>
    <?php else: ?>
      <tr>
        <td colspan="2">No data found.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const canvas = document.getElementById("reportChart");
  if (!canvas) return;

  const labels = JSON.parse(canvas.dataset.labels || "[]");
  const vals = JSON.parse(canvas.dataset.values || "[]");

  const ctx = canvas.getContext("2d");

  new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [{
        label: "Total Spent",
        data: vals,
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
});
</script>

<?php
$stmt->close();
require_once __DIR__ . "/../includes/footer.php";
?>
