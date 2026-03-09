<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BANdSHI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background:#000;
  color:#fff;
  min-height:100vh;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  text-align:center;
}
.logo {
  font-size:72px;
  font-weight:800;
}
.logo span:first-child { color:#e84c88; }
.logo span:last-child { color:#5bc0eb; }
.btn-main {
  background:#e84c88;
  color:#fff;
  border-radius:14px;
  padding:14px;
}
.btn-outline {
  border:1px solid #5bc0eb;
  color:#5bc0eb;
  border-radius:14px;
  padding:14px;
}
</style>
</head>
<body>

<div class="logo mb-4">
  <span>BANd.</span><span>SHI</span>
</div>

<h2 class="fw-bold">Ready to rock the stage?</h2>
<p class="text-secondary mb-5">
  It goes beyond singing dancing and playing
</p>

<div class="w-100 px-4" style="max-width:400px;">
  <a href="login.php" class="btn btn-main w-100 mb-3">Sign in</a>
  <a href="register.php" class="btn btn-outline w-100">Create account</a>
</div>

</body>
</html>
