<?php
session_start();
require_once 'auth.php';
require_once 'db.php';

if (!isset($_GET['id'])) {
    header("Location: announcements.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
  SELECT title, content, image, created_at
  FROM announcements
  WHERE id = ?
");
$stmt->bind_param("i",$id);
$stmt->execute();
$result = $stmt->get_result();
$a = $result->fetch_assoc();

if (!$a) {
    header("Location: announcements.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($a['title']) ?> | BANdSHI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
  margin:0;
  padding-top:70px;
  background:#000;
  color:#fff;
}

.detail-card{
  max-width:900px;
  margin:auto;
  background:#1f1f1f;
  border-radius:24px;
  padding:30px;
  border:1px solid #2a2a2a;
}

.detail-img{
  width:100%;
  max-height:450px;
  object-fit:cover;
  border-radius:18px;
  margin-bottom:25px;
}
.btn-back{
  border:1px solid #5bc0eb;
  color:#5bc0eb;
  background:transparent;
  border-radius:14px;
  padding:10px 18px;
  font-weight:600;
  transition:.2s ease;
}

.btn-back:hover{
  background:#5bc0eb;
  color:#000;
}
</style>
</head>
<body>

<?php require 'header.php'; ?>

<div class="container py-5">

<div class="detail-card">

<?php if (!empty($a['image'])): ?>
  <img src="/bandshi/uploads/announcements/<?= htmlspecialchars($a['image']) ?>"
       class="detail-img">
<?php endif; ?>

<h3><?= htmlspecialchars($a['title']) ?></h3>

<div class="mt-2 mb-3 opacity-50">
  <?= date('d M Y H:i', strtotime($a['created_at'])) ?>
</div>

<p>
  <?= nl2br(htmlspecialchars($a['content'])) ?>
</p>

<div class="mt-4">
  <a href="announcements.php" class="btn btn-back">
    ← Back to Announcements
  </a>
</div>

</div>
</div>

</body>
</html>