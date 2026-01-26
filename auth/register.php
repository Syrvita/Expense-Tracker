<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/header.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($name === "" || $email === "" || $password === "") {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hash);

        if ($stmt->execute()) {
            header("Location: /Expense-Tracker/auth/login.php");
            exit;
        } else {
            $error = "Email already exists.";
        }
        $stmt->close();
    }
}
?>

<h1>Create Account</h1>
<p>Register to save your expenses.</p>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
  <div class="form-row" style="margin-bottom:12px;">
    <div>
      <label>Name *</label>
      <input type="text" name="name" required>
    </div>
    <div>
      <label>Email *</label>
      <input type="email" name="email" required>
    </div>
  </div>

  <div style="margin-bottom:12px;">
    <label>Password *</label>
    <input type="password" name="password" required>
  </div>

  <div class="actions">
    <button class="btn btn-primary" type="submit">Register</button>
    <a class="btn" href="/Expense-Tracker/auth/login.php">Login</a>
  </div>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
