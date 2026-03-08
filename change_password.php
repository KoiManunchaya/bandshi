<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        $error = 'All fields are required';
    }

    if (!$error && strlen($new) < 6) {
        $error = 'New password must be at least 6 characters';
    }

    if (!$error && $new !== $confirm) {
        $error = 'New passwords do not match';
    }

    if (!$error) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            $success = 'Password updated successfully';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Change Password | BANdSHI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
  background:#000;
  color:#fff;
  padding-top:64px;
}
.card-box{
  max-width:480px;
  margin:auto;
  background:#1f1f1f;
  padding:32px;
  border-radius:20px;
}
.form-control{
  border-radius:14px;
}
.btn-pink{
  background:#e84c88;
  border:none;
  border-radius:14px;
  color:#fff;
}
.btn-pink:hover{
  opacity:.9;
}

/* Password toggle */
.password-wrapper{
  position:relative;
}
.password-wrapper input{
  padding-right:45px;
}
.toggle-password{
  position:absolute;
  right:14px;
  top:50%;
  transform:translateY(-50%);
  cursor:pointer;
  font-size:18px;
  color:#aaa;
}
.toggle-password:hover{
  color:#fff;
}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="container my-5">
  <div class="card-box">

    <h4 class="mb-4">Change Password</h4>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="post">

      <div class="mb-3">
        <label>Current Password</label>
        <div class="password-wrapper">
          <input type="password" name="current_password" class="form-control" required>
          <i class="bi bi-eye toggle-password"></i>
        </div>
      </div>

      <div class="mb-3">
        <label>New Password</label>
        <div class="password-wrapper">
          <input type="password" name="new_password" class="form-control" required>
          <i class="bi bi-eye toggle-password"></i>
        </div>
      </div>

      <div class="mb-4">
        <label>Confirm New Password</label>
        <div class="password-wrapper">
          <input type="password" name="confirm_password" class="form-control" required>
          <i class="bi bi-eye toggle-password"></i>
        </div>
      </div>

      <button class="btn btn-pink w-100 mb-3">Update Password</button>
    </form>

    <a href="profile.php" class="btn btn-outline-light w-100">
      Back to Profile
    </a>

  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){

  document.querySelectorAll(".toggle-password").forEach(function(icon){

    icon.addEventListener("click", function(){

      const input = this.previousElementSibling;

      if (input.type === "password") {
        input.type = "text";
        this.classList.remove("bi-eye");
        this.classList.add("bi-eye-slash");
      } else {
        input.type = "password";
        this.classList.remove("bi-eye-slash");
        this.classList.add("bi-eye");
      }

    });

  });

});
</script>

</body>
</html>