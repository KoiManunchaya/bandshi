<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';
session_start();

$error = "";

/*
|--------------------------------------------------------------------------
| Handle login
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['student_id']) || empty($_POST['password'])) {
        $error = "Please enter student ID and password.";
    } else {

        $student_id = trim($_POST['student_id']);
        $password   = $_POST['password'];

        // ดึง user จาก student_id
        $stmt = $conn->prepare("SELECT * FROM users WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {

            // เช็ค verify ก่อนเข้าใช้งาน
            if ($user['email_verified'] != 1) {
                $error = "Please verify your email before logging in.";
            } else {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: home.php");
                }
                exit();
            }

        } else {
            $error = "Invalid student ID or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<meta charset="UTF-8">
<title>Login | BANdSHI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{margin:0;background:#000;color:#fff;font-family:system-ui;}
.logo{font-size:48px;font-weight:800;}
.logo span:first-child{color:#e84c88;}
.logo span:last-child{color:#5bc0eb;}
.card-dark{background:#2b2b2b;border-radius:20px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,.8);}
.login-title{color:#ffffff;font-weight:700;}
.login-sub{color:#cfd3d7;font-size:14px;}
.login-sub a{color:#5bc0eb;font-weight:600;text-decoration:none;}
.login-sub a:hover{color:#8fd3ff;}
.form-control{background:#ffffff !important;color:#000 !important;border:none;border-radius:14px;padding:12px;}
.btn-pink{background:#e84c88;color:#fff;border:none;border-radius:16px;padding:12px;font-weight:600;}
.btn-pink:hover{opacity:.9;}
.back-link{color:#bfc3c7;font-size:14px;text-decoration:none;}
.back-link:hover{color:#ffffff;}
.password-wrapper{
  position:relative;
}

.password-wrapper input{
  padding-right:50px !important;
}

.toggle-pass{
  position:absolute;
  right:18px;
  top:50%;
  transform:translateY(-50%);
  font-size:20px;
  color:#aaa;
  cursor:pointer;
  transition:.2s;
}

.toggle-pass:hover{
  color:#5bc0eb;
}
</style>
</head>

<body>

<div class="container vh-100 d-flex align-items-center">
  <div class="row w-100">

    <div class="col-md-6 d-none d-md-flex justify-content-center align-items-center">
      <div class="logo">
        <span>BANd.</span><span>SHI</span>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card-dark">

        <h3 class="login-title mb-3">Hi, Welcome 👋</h3>

        <!-- REGISTER SUCCESS MESSAGE -->
        <?php if(isset($_GET['registered'])): ?>
          <div class="alert alert-success">
            Registration successful. Please check your Chula email to verify your account.
          </div>
        <?php endif; ?>

        <!-- ERROR MESSAGE -->
        <?php if ($error): ?>
          <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="post">
          <input class="form-control mb-3" name="student_id" placeholder="Student ID" required>
         <div class="password-wrapper mb-4">
  <input class="form-control"
         type="password"
         id="loginPassword"
         name="password"
         placeholder="Password"
         required>

  <i class="bi bi-eye toggle-pass" id="toggleLogin"></i>
</div>
          <button class="btn btn-pink w-100">Log in</button>
        </form>

        <div class="text-center mt-3">
          <a href="index.php" class="back-link">← Back to home</a>
        </div>

        <div class="login-sub text-center mt-2">
          Don’t have an account? <a href="register.php">Sign up</a>
        </div>

      </div>
    </div>

  </div>
</div>
<script>
document.getElementById("toggleLogin").addEventListener("click", function(){

  const input = document.getElementById("loginPassword");

  if(input.type === "password"){
    input.type = "text";
    this.classList.remove("bi-eye");
    this.classList.add("bi-eye-slash");
  }else{
    input.type = "password";
    this.classList.remove("bi-eye-slash");
    this.classList.add("bi-eye");
  }

});
</script>
</body>
</html>