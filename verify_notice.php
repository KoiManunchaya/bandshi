<?php
$email = $_GET['email'] ?? '@student.chula.ac.th';
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Verify Email | BANdSHI</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>

body{
background:#000;
color:#fff;
font-family:system-ui;
height:100vh;
display:flex;
align-items:center;
justify-content:center;
}

.verify-card{
background:#111;
padding:55px;
border-radius:20px;
text-align:center;
width:460px;
border:1px solid #222;
box-shadow:0 15px 40px rgba(0,0,0,.6);
}

.mail-icon{
font-size:70px;
color:#5bc0eb;
margin-bottom:20px;
}

.verify-card h1{
font-size:28px;
font-weight:700;
margin-bottom:10px;
}

.verify-card p{
color:#aaa;
margin-bottom:25px;
line-height:1.6;
}

.email-box{
background:#0b0b0b;
border:1px solid #222;
padding:14px;
border-radius:10px;
font-weight:500;
margin-bottom:30px;
}

.btn-login{
background:#5bc0eb;
border:none;
padding:14px 26px;
border-radius:12px;
font-weight:600;
transition:.2s;
}

.btn-login:hover{
background:#46a9d3;
}

.secondary{
display:block;
margin-top:16px;
font-size:14px;
color:#888;
text-decoration:none;
}

.secondary{
display:block;
margin-top:16px;
font-size:14px;
color:#888;
text-decoration:none;
transition:0.2s;
}

.secondary:hover{
color:#e44c84;
text-decoration:underline;
}

</style>

</head>

<body>

<div class="verify-card">

<i class="bi bi-envelope-paper-fill mail-icon"></i>

<h1>Check your student email</h1>

<p>
We have sent a verification link to your student email.<br>
Please open your inbox and click the link to activate your account.
</p>

<div class="email-box">
<?= htmlspecialchars($email) ?>
</div>

<a href="https://mail.google.com" class="btn btn-login" target="_blank">
Open Gmail
</a>

<a href="login.php" class="secondary">
Already verified? Login
</a>

</div>

</body>
</html>