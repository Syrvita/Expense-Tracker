<?php
ob_start();

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$user_id = (int)$_SESSION["user"]["id"];

$error = "";
$success = "";

/* Load current user info */
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$userRow = $res->fetch_assoc();
$stmt->close();

if (!$userRow) {
  echo "<div class='notice-error'>User not found.</div>";
  require_once __DIR__ . "/../includes/footer.php";
  exit;
}

/* Update name */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $newName = trim($_POST["name"] ?? "");

  if ($newName === "") {
    $error = "Name cannot be empty.";
  } elseif (strlen($newName) < 2) {
    $error = "Name must be at least 2 characters.";
  } elseif (strlen($newName) > 50) {
    $error = "Name must be under 50 characters.";
  } else {
    $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $newName, $user_id);

    if ($stmt->execute()) {
      $success = "Name updated.";

      // update session too (so sidebar updates immediately)
      $_SESSION["user"]["name"] = $newName;

      // reload shown value
      $userRow["name"] = $newName;
    } else {
      $error = "Failed to update name.";
    }

    $stmt->close();
  }
}
?>

<h1>Settings</h1>
<p>Update your account details.</p>

<?php if ($success !== ""): ?>
  <div class="notice-ok"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card">
  <h3 style="margin-top:0;">Profile</h3>

  <form method="POST" action="" style="margin:0;">
    <div style="margin-bottom:12px;">
      <label>Name *</label>
      <input type="text" name="name" value="<?= htmlspecialchars($userRow["name"]) ?>" required>
    </div>

    <div style="margin-bottom:12px;">
      <label>Email</label>
      <input type="email" value="<?= htmlspecialchars($userRow["email"]) ?>" disabled>
    </div>

    <div class="actions">
      <button class="btn btn-primary" type="submit">Save</button>
    </div>
  </form>
</div>

<?php
require_once __DIR__ . "/../includes/footer.php";
ob_end_flush();
?>