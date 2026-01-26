<?php
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/auth.php";
require_login();
require_once __DIR__ . "/../includes/header.php";

$userId = (int)$_SESSION["user"]["id"];
$success = "";
$error = "";

/* Handle avatar upload */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_FILES["avatar"]) || $_FILES["avatar"]["error"] !== UPLOAD_ERR_OK) {
        $error = "Upload failed.";
    } else {
        $tmp = $_FILES["avatar"]["tmp_name"];
        $size = (int)$_FILES["avatar"]["size"];

        if ($size > 2 * 1024 * 1024) {
            $error = "Max file size is 2MB.";
        } else {
            $ext = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
            $allowed = ["jpg", "jpeg", "png", "webp"];

            if (!in_array($ext, $allowed, true)) {
                $error = "Only JPG, PNG, WEBP allowed.";
            } else {
                $newName = "u" . $userId . "_" . time() . "." . $ext;
                $destDir = __DIR__ . "/../uploads/avatars/";
                $destPath = $destDir . $newName;

                if (!is_dir($destDir)) {
                    mkdir($destDir, 0777, true);
                }

                if (move_uploaded_file($tmp, $destPath)) {
                    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                    $stmt->bind_param("si", $newName, $userId);
                    $stmt->execute();
                    $stmt->close();

                    $_SESSION["user"]["avatar"] = $newName;
                    $success = "Avatar updated.";
                } else {
                    $error = "Could not save file.";
                }
            }
        }
    }
}
?>

<h1>Settings</h1>
<p>Profile settings.</p>

<?php if ($success !== ""): ?>
  <div class="notice-ok"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error !== ""): ?>
  <div class="notice-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data">
  <div style="margin-bottom:12px;">
    <label>Profile Name</label>
    <input type="text" value="<?= htmlspecialchars($_SESSION["user"]["name"]) ?>" disabled>
  </div>

  <div style="margin-bottom:12px;">
    <label>Upload Avatar</label>
    <input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" required>
  </div>

  <div class="actions">
    <button class="btn btn-primary" type="submit">Save</button>
  </div>
</form>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
