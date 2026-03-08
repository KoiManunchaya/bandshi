<?php
session_start();
require_once 'auth.php';
require_once 'db.php';

$list = $conn->query("
  SELECT id, title, created_at
  FROM announcements
  ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Announcements | BANdSHI</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
  margin:0;
  padding-top:70px;
  background:#000;
  color:#fff;
}

.section-title{
  font-weight:800;
  color:#ff4fa3;
}

.announce-item{
  background:#1f1f1f;
  border-radius:18px;
  padding:20px 24px;
  margin-bottom:18px;
  transition:.2s;
  border:1px solid #2a2a2a;
}

.announce-item:hover{
  background:#2a2a2a;
  transform:translateY(-3px);
}

.announce-date{
  font-size:13px;
  opacity:.5;
  margin-bottom:6px;
}

.announce-link{
  text-decoration:none;
  color:#fff;
}

.announce-link:hover{
  color:#ff4fa3;
}

/* ทำไอคอน active */
.nav-announcement.active{
  color:#ff4fa3 !important;
}
</style>
</head>

<body>

<?php require 'header.php'; ?>

<div class="container py-4">

  <h2 class="section-title mb-4">
    <i class="bi bi-megaphone-fill nav-announcement active me-2"></i>
    Announcements
  </h2>

  <?php while($a = $list->fetch_assoc()): ?>
    <a href="announcement_detail.php?id=<?= $a['id'] ?>" class="announce-link">
      <div class="announce-item">
        <div class="announce-date">
          <?= date('d M Y H:i', strtotime($a['created_at'])) ?>
        </div>
        <h5 class="mb-0">
          <?= htmlspecialchars($a['title']) ?>
        </h5>
      </div>
    </a>
  <?php endwhile; ?>

  <?php if ($list->num_rows === 0): ?>
    <div class="text-center mt-5 opacity-50">
      No announcements yet.
    </div>
  <?php endif; ?>

</div>

</body>
</html>