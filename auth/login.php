<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $error = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user["password_hash"])) {
            $_SESSION["user"] = [
                "id" => (int)$user["id"],
                "name" => $user["name"],
                "email" => $user["email"],
                "avatar" => $user["avatar"] ?? null
            ];
            header("Location: /index.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}

require_once __DIR__ . "/../includes/header_auth.php";
?>

<div class="auth-avatar"></div>

<h1 class="auth-title">Login</h1>
<p class="auth-subtitle">Access your account.</p>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
  <div style="margin-bottom:12px;">
    <label>Email *</label>
    <input type="email" name="email" required>
  </div>

  <div style="margin-bottom:12px;">
    <label>Password *</label>
    <input type="password" name="password" required>
  </div>

  <div class="actions" style="justify-content:center;">
    <button class="btn btn-primary" type="submit">Login</button>
    <a class="btn" href="/auth/register.php">Register</a>
  </div>
</form>

<?php require_once __DIR__ . "/../includes/footer_auth.php"; ?>
