<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/header.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password_hash, avatar FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user["password_hash"])) {
            $error = "Invalid login.";
        } else {
            $_SESSION["user"] = [
                "id" => (int)$user["id"],
                "name" => $user["name"],
                "email" => $user["email"],
                "avatar" => $user["avatar"]
            ];

            header("Location: /Expense-Tracker/index.php");
            exit;
        }
    }
}
?>

<h1>Login</h1>
<p>Access your expenses and reports.</p>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
  <div class="form-row" style="margin-bottom:12px;">
    <div>
      <label>Email *</label>
      <input type="email" name="email" required>
    </div>
    <div>
      <label>Password *</label>
      <input type="password" name="password" required>
    </div>
  </div>

  <div class="actions">
    <button class="btn btn-primary" type="submit">Login</button>
    <a class="btn" href="/Expense-Tracker/auth/register.php">Create Account</a>
  </div>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
