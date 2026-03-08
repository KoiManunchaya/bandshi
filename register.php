<?php
require_once 'db.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

$error = "";

/* ===== helper สำหรับจำค่า form ===== */
function old($name){
    return htmlspecialchars($_POST[$name] ?? '');
}
function checked($name,$value){
    return (isset($_POST[$name]) && $_POST[$name] === $value) ? 'checked' : '';
}
function selected($name,$value){
    return (isset($_POST[$name]) && $_POST[$name] === $value) ? 'selected' : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $required = [
        'student_id','password','display_name','full_name',
        'gender','birth_date','cohort','part'
    ];

    foreach ($required as $f) {
        if (empty($_POST[$f])) {
            $error = "Please fill all required fields.";
            break;
        }
    }

    if (!$error) {

        $student_id   = trim($_POST['student_id']);
        $password_raw = $_POST['password'];
        $display_name = trim($_POST['display_name']);
        $full_name    = trim($_POST['full_name']);
        $gender       = $_POST['gender'];
        $birth_date   = $_POST['birth_date'];
        $cohort       = trim($_POST['cohort']);
        $part         = $_POST['part'];
        $instrument   = $_POST['instrument'] ?? 'none';

        if (!preg_match('/^[0-9]{10}$/', $student_id)) {
            $error = "Student ID must be 10 digits.";
        }

        if (!$error && !preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password_raw)) {
            $error = "Password must be at least 8 characters and include letters and numbers.";
        }

        $email    = $student_id . "@student.chula.ac.th";
        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        if (!$error) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE student_id=?");
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = "Student ID already registered.";
            }
            $stmt->close();
        }

        if (!$error) {

            $verify_token   = bin2hex(random_bytes(32));
            $email_verified = 0;
            $role           = 'member';

            $stmt = $conn->prepare("
                INSERT INTO users
                (email,password,display_name,full_name,student_id,
                 gender,birth_date,cohort,part,instrument,
                 role,email_verified,verify_token)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");

            $stmt->bind_param(
                "sssssssssssis",
                $email,$password,$display_name,$full_name,$student_id,
                $gender,$birth_date,$cohort,$part,$instrument,
                $role,$email_verified,$verify_token
            );

            if ($stmt->execute()) {

                header("Location: login.php?registered=1");
                exit;

            } else {
                $error = "Database insert failed.";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Register | BANdSHI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{margin:0;background:#000;color:#fff;font-family:system-ui;}
.register-wrapper{display:flex;min-height:100vh;}
.register-left{flex:1;position:relative;overflow:hidden;}
.register-left img{width:100%;height:100%;object-fit:cover;}
.overlay{position:absolute;inset:0;background:linear-gradient(to right,#000 15%,rgba(0,0,0,0.4));}
.register-right{flex:1;display:flex;align-items:center;justify-content:center;padding:60px;}
.form-box{width:100%;max-width:520px;}
.form-control{background:#111 !important;border:1px solid #222 !important;color:#fff !important;border-radius:14px;padding:14px;}
.form-control:focus{border-color:#5bc0eb !important;box-shadow:0 0 0 3px rgba(91,192,235,.15);}
.password-wrapper{position:relative;}
.password-wrapper input{padding-right:50px !important;}
.toggle-pass{position:absolute;right:18px;top:50%;transform:translateY(-50%);font-size:20px;color:#aaa;cursor:pointer;}
.toggle-pass:hover{color:#5bc0eb;}
.btn-blue{background:#5bc0eb;border:none;border-radius:14px;padding:14px;font-weight:600;}
.verify-text{font-size:13px;color:#e44c84;}
.radio-group{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
@media(max-width:992px){.register-left{display:none;}}
input[type="date"]::-webkit-calendar-picker-indicator {filter: invert(1);opacity: 0.8;}
::placeholder {color: rgba(255,255,255,0.55) !important;}
/* ===== Validation ===== */
.is-invalid{
  border-color:#ff4d4f !important;
  box-shadow:0 0 0 3px rgba(255,77,79,.15) !important;
}

.error-text{
  font-size:12px;
  color:#ff4d4f;
  margin-top:6px;
}
</style>
</head>
<body>
<div class="register-wrapper">

<div class="register-left">
    <img src="asset/concert.jpeg">
    <div class="overlay"></div>
</div>

<div class="register-right">
<div class="form-box">

<h1>Create account</h1>
<p>Already have an account? 
   <a href="login.php" style="color:#5bc0eb">Log in</a>
</p>

<?php if($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" id="registerForm">

<!-- Student ID -->
<div class="mb-3">
<label>Student ID*</label>
<input id="student_id"
       name="student_id"
       value="<?= old('student_id') ?>"
       class="form-control"
       placeholder="Enter your 10-digit student ID"
       required>
<div class="error-text" id="studentError"></div>
<small class="verify-text">
Your verification email will be: studentID@student.chula.ac.th
</small>
</div>

<!-- Password -->
<div class="mb-3">
<label>Password*</label>
<div class="password-wrapper">
<input type="password"
       id="passwordInput"
       name="password"
       class="form-control"
       placeholder="Enter your password"
       required>
<i class="bi bi-eye toggle-pass" id="togglePassword"></i>
</div>
<div class="error-text" id="passwordError"></div>

</div>

<!-- Display name -->
<div class="mb-3">
<label>Display name*</label>
<input id="display_name"
       name="display_name"
       value="<?= old('display_name') ?>"
       class="form-control"
       placeholder="Enter your display name"
       required>
<div class="error-text" id="displayError"></div>
</div>

<!-- Thai full name -->
<div class="mb-3">
<label>Thai full name*</label>
<input name="full_name"
       value="<?= old('full_name') ?>"
       class="form-control"
       placeholder="Enter your full name"
       required>
</div>

<!-- Gender -->
<div class="mb-3">
<label>Gender*</label>
<div class="radio-group">
<label><input type="radio" name="gender" value="female" <?= checked('gender','female') ?>> Female</label>
<label><input type="radio" name="gender" value="male" <?= checked('gender','male') ?>> Male</label>
<label><input type="radio" name="gender" value="non-binary" <?= checked('gender','non-binary') ?>> Non-binary</label>
</div>
</div>

<!-- Date of birth -->
<div class="mb-3">
<label>Date of birth*</label>
<input type="date"
       name="birth_date"
       value="<?= old('birth_date') ?>"
       class="form-control"
       required>
</div>

<!-- Cohort -->
<div class="mb-3">
<label>Cohort*</label>
<input name="cohort"
       value="<?= old('cohort') ?>"
       class="form-control"
       placeholder="Enter your cohort"
       required>
</div>

<!-- Part -->
<div class="mb-3">
<label>Part*</label>
<select name="part" class="form-control" required>
<option value="">Select your part</option>
<option value="singer" <?= selected('part','singer') ?>>Singer</option>
<option value="musician" <?= selected('part','musician') ?>>Musician</option>
<option value="dancer" <?= selected('part','dancer') ?>>Dancer</option>
<option value="light&sound" <?= selected('part','light&sound') ?>>Light & Sound</option>
</select>
</div>

<!-- Instrument -->
<div class="mb-4">
<label>Instrument</label>
<select name="instrument" class="form-control">
<option value="none" <?= selected('instrument','none') ?>>None</option>
<option value="guitar" <?= selected('instrument','guitar') ?>>Guitar</option>
<option value="keyboard" <?= selected('instrument','keyboard') ?>>Keyboard</option>
<option value="bass" <?= selected('instrument','bass') ?>>Bass</option>
<option value="drum" <?= selected('instrument','drum') ?>>Drum</option>
</select>
</div>

<button class="btn btn-blue w-100">Create account</button>

</form>
</div>
</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function(){

  const student = document.getElementById("student_id");
  const password = document.getElementById("passwordInput");
  const display = document.getElementById("display_name");

  /* Student ID */
  student.addEventListener("input", function(){
    const error = document.getElementById("studentError");
    if(!/^[0-9]{10}$/.test(this.value.trim())){
      this.classList.add("is-invalid");
      error.textContent = "Student ID must be exactly 10 digits.";
    }else{
      this.classList.remove("is-invalid");
      error.textContent = "";
    }
  });

  /* Password */
  password.addEventListener("input", function(){
    const error = document.getElementById("passwordError");
    if(!/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/.test(this.value)){
      this.classList.add("is-invalid");
      error.textContent = "Minimum 8 characters, include letters and numbers.";
    }else{
      this.classList.remove("is-invalid");
      error.textContent = "";
    }
  });

  /* Display name */
  display.addEventListener("input", function(){
    const error = document.getElementById("displayError");
    if(this.value.trim() === ""){
      this.classList.add("is-invalid");
      error.textContent = "Display name is required.";
    }else{
      this.classList.remove("is-invalid");
      error.textContent = "";
    }
  });

  /* Toggle password */
  document.getElementById("togglePassword").addEventListener("click", function(){
    if(password.type === "password"){
      password.type = "text";
      this.classList.replace("bi-eye","bi-eye-slash");
    }else{
      password.type = "password";
      this.classList.replace("bi-eye-slash","bi-eye");
    }
  });

});
</script>

</body>
</html>