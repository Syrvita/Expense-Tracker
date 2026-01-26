<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$user_id = (int)$_SESSION["user"]["id"];

$stmt = $conn->prepare("SELECT id, name, created_at FROM categories WHERE user_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h1>Categories</h1>
<p>Manage your expense categories.</p>

<div class="toolbar">
  <a class="btn btn-primary" href="/categories/create.php">Add Category</a>
</div>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Created</th>
      <th>Actions</th>
    </tr>
  </thead>

  <tbody>
    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= (int)$row["id"] ?></td>
          <td><?= htmlspecialchars($row["name"]) ?></td>
          <td><?= htmlspecialchars($row["created_at"]) ?></td>
          <td>
            <div class="actions">
              <a class="btn" href="/categories/edit.php?id=<?= (int)$row["id"] ?>">Edit</a>

              <form action="/categories/delete.php" method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= (int)$row["id"] ?>">
                <button class="btn btn-danger" type="submit" onclick="return confirm('Delete this category?')">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="4">No categories found.</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>

<?php
$stmt->close();
require_once __DIR__ . "/../includes/footer.php";
?>