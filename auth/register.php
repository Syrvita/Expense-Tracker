<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($name === "" || $email === "" || $password === "") {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = $res->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $error = "Email is already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hash);

            if ($stmt->execute()) {
                $success = "Account created. You can login now.";
            } else {
                $error = "Registration failed.";
            }
            $stmt->close();
        }
    }
}

require_once __DIR__ . "/../includes/header_auth.php";
?>

<div class="auth-avatar"></div>

<h1 class="auth-title">Register</h1>
<p class="auth-subtitle">Create your account.</p>

<?php if ($success !== ""): ?>
  <div class="notice-ok"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
  <div style="margin-bottom:12px;">
    <label>Name *</label>
    <input type="text" name="name" required>
  </div>

  <div style="margin-bottom:12px;">
    <label>Email *</label>
    <input type="email" name="email" required>
  </div>

  <div style="margin-bottom:12px;">
    <label>Password *</label>
    <input type="password" name="password" required>
  </div>

  <div class="actions" style="justify-content:center;">
    <button class="btn btn-primary" type="submit">Register</button>
    <a class="btn" href="/auth/login.php">Login</a>
  </div>
</form>

<?php require_once __DIR__ . "/../includes/footer_auth.php"; ?>
