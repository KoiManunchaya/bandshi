<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
  header("Location: login.php");
  exit;
}

/* =====================
   LOAD USER
===================== */
$stmt = $conn->prepare("
  SELECT full_name, display_name, email, role, part, profile_pic
  FROM users
  WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) die('User not found');

$error = '';
$success = '';

/* =====================
   UPDATE PROFILE
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $full_name     = trim($_POST['full_name'] ?? '');
  $display_name  = trim($_POST['display_name'] ?? '');
  $profile_pic   = $user['profile_pic'];

  if ($full_name === '' || $display_name === '') {
    $error = 'Full name and display name are required';
  }

  /* ===== handle cropped image ===== */
  if (!$error && !empty($_POST['cropped_image'])) {

    $uploadDir = __DIR__ . '/uploads/profile/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }

    $data = $_POST['cropped_image'];
    if (preg_match('/^data:image\/(\w+);base64,/', $data)) {
      $data = substr($data, strpos($data, ',') + 1);
      $data = base64_decode($data);

      $filename = 'user_'.$user_id.'_'.time().'.png';
      file_put_contents($uploadDir . $filename, $data);

      $profile_pic = $filename;
    }
  }

  /* ===== save profile ===== */
  if (!$error) {
    $stmt = $conn->prepare("
      UPDATE users
      SET full_name = ?, display_name = ?, profile_pic = ?
      WHERE id = ?
    ");
    $stmt->bind_param("sssi", $full_name, $display_name, $profile_pic, $user_id);
    $stmt->execute();

    $success = 'Profile updated';

    $user['full_name']    = $full_name;
    $user['display_name'] = $display_name;
    $user['profile_pic']  = $profile_pic;
  }
}

/* =====================
   BADGE LOGIC
===================== */
$badgeText = '';
$badgeColor = '';

if ($user['role'] === 'admin') {
    $badgeText = 'ADMIN';
    $badgeColor = '#ffb703';
} else {
    $badgeText = strtoupper($user['part'] ?? '');

    $badgeColor = match ($user['part']) {
        'singer'       => '#e84c88',
        'musician'     => '#5bc0eb',
        'dancer'       => '#9b5cff',
        'light&sound'  => '#f77f00',
        default        => '#888'
    };
}

$avatar_color = $user['role'] === 'admin'
    ? '#ffb703'
    : $badgeColor;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Profile | BANdSHI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{background:#000;color:#fff;padding-top:64px}
.profile-card{
  max-width:520px;
  margin:auto;
  background:#1f1f1f;
  border-radius:20px;
  padding:32px;
}
.avatar{
  width:140px;
  height:140px;
  border-radius:50%;
  background:<?= $avatar_color ?>;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:48px;
  font-weight:700;
  color:#000;
  overflow:hidden;
  margin:auto;
  cursor:pointer;
  position:relative;
  transition:.2s;
}
.avatar img{
  width:100%;
  height:100%;
  object-fit:cover;
}
.avatar::after{
  content:"Edit";
  position:absolute;
  inset:0;
  background:rgba(0,0,0,0.6);
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:16px;
  font-weight:600;
  opacity:0;
  transition:.2s;
}
.avatar:hover::after{
  opacity:1;
}
.btn-pink{
  background:#e84c88;
  border:none;
  border-radius:14px;
  color:#fff
}
.btn-pink:hover{opacity:.9}
.form-control{border-radius:14px}

.btn-blue{
  border:2px solid #5bc0eb;
  color:#5bc0eb;
  border-radius:14px;
  font-weight:600;
}

.btn-blue:hover{
  background:#5bc0eb;
  color:#000;
}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="container my-5">
<div class="profile-card">

  <div class="text-center mb-3">
    <span style="
      background:<?= $badgeColor ?>;
      color:#000;
      padding:6px 14px;
      border-radius:999px;
      font-size:13px;
      font-weight:700;
      letter-spacing:1px;
    ">
      <?= htmlspecialchars($badgeText) ?>
    </span>
  </div>

  <div class="avatar mb-3" id="avatarPreview">
  <?php if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/uploads/profile/' . $user['profile_pic'])): ?>
   <img src="/bandshi/uploads/profile/<?= htmlspecialchars($user['profile_pic']) ?>">
  <?php else: ?>
    <?= strtoupper(substr($user['display_name'] ?? $user['full_name'],0,1)) ?>
  <?php endif; ?>
</div>
 
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>

  <form method="post">
    <input type="hidden" name="cropped_image" id="croppedImage">
    <input type="file" id="imgInput" accept="image/*" hidden>

    <div class="mb-3">
      <label>Full name</label>
      <input class="form-control" name="full_name"
             value="<?= htmlspecialchars($user['full_name']) ?>">
    </div>

    <div class="mb-3">
      <label>Display name</label>
      <input class="form-control" name="display_name"
             value="<?= htmlspecialchars($user['display_name']) ?>">
    </div>

    <div class="mb-3">
      <label>Email</label>
      <input class="form-control"
             value="<?= htmlspecialchars($user['email']) ?>"
             disabled>
    </div>

    <button class="btn btn-pink w-100 mb-3">Save profile</button>
  </form>

  <a href="change_password.php" class="btn btn-outline-info w-100 mb-3 btn-blue">
  Change Password
</a>

  <a href="logout.php" class="btn btn-outline-danger w-100">
    Logout
  </a>

</div>
</div>

<script>
const input = document.getElementById('imgInput');
const avatar = document.getElementById('avatarPreview');
const hidden = document.getElementById('croppedImage');

avatar.addEventListener('click', () => {
  input.click();
});

input.addEventListener('change', e => {
  const file = e.target.files[0];
  if (!file) return;

  const img = new Image();
  img.onload = () => {
    const size = Math.min(img.width, img.height);
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 300;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(
      img,
      (img.width-size)/2,
      (img.height-size)/2,
      size,size,
      0,0,300,300
    );
    const dataURL = canvas.toDataURL('image/png');
    avatar.innerHTML = `<img src="${dataURL}">`;
    hidden.value = dataURL;
  };
  img.src = URL.createObjectURL(file);
});
</script>

</body>
</html>