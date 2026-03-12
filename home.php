<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

/* แสดงเฉพาะ event ที่ยังเปิดอยู่ */
$events = $conn->query("
  SELECT id, title, event_date, start_time, location
  FROM events
  WHERE status = 'open'
  ORDER BY event_date ASC
  LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

/* latest announcement */
$announce = $conn->query("
  SELECT title, content, image, created_at
  FROM announcements
  ORDER BY created_at DESC
  LIMIT 1
")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Home | BANdSHI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
  margin:0;
  padding-top:64px;
  background:#000;
  color:#fff;
  font-family:system-ui;
}

/* HERO */
.hero{
  width:100%;
  height:280px;
  overflow:hidden;
}
.hero img{
  width:100%;
  height:100%;
  object-fit:cover;
}

/* SECTION */
.section{
  padding:50px 20px;
  max-width:1100px;
  margin:auto;
}

/* ANNOUNCEMENT */
.card-dark{
  background:#1a1a1a;
  border-radius:24px;
  padding:28px;
  border:1px solid #2a2a2a;
}

/* EVENT HORIZONTAL */
.event-row{
  display:flex;
  gap:20px;
  overflow-x:auto;
  padding-bottom:10px;
  scroll-snap-type:x mandatory;
}

.event-row::-webkit-scrollbar{
  height:8px;
}
.event-row::-webkit-scrollbar-thumb{
  background:#333;
  border-radius:10px;
}

.event-box{
  min-width:300px;
  background:#151515;
  border-radius:22px;
  padding:22px;
  border:1px solid #262626;
  transition:all .25s ease;
  scroll-snap-align:start;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
}

/* แก้ hover */
.event-box:hover{
  background:#1f1f1f;
  transform:translateY(-6px);
  box-shadow:0 0 0 2px #ff4fa3; /* ใช้ shadow แทน border */
}
.event-title{
  font-weight:600;
  font-size:18px;
  margin-bottom:12px;
}

.event-info{
  font-size:14px;
  opacity:.7;
  margin-bottom:4px;
}

.view-btn{
  margin-top:16px;
  background:#ff4fa3;
  border:none;
  padding:10px;
  border-radius:12px;
  font-size:14px;
  font-weight:600;
  color:#fff;
  transition:.2s;
  width:100%;
}

.view-btn:hover{
  background:#ff2f90;
}

.announcement-img{
  width:100%;
  height:400px;
  object-fit:cover;
  border-radius:16px;
  margin-bottom:20px;
}
</style>
</head>

<body>

<?php require 'header.php'; ?>

<!-- HERO -->
<div class="hero">
  <img src="asset/CBS.jpg" alt="BANdSHI">
</div>

<!-- TITLE -->
<div class="section">
  <h2 style="color:#ff4fa3;font-weight:800;">
    BANdSHI Rock!!!!!
  </h2>
</div>

<!-- ANNOUNCEMENT -->
<?php if ($announce): ?>
<div class="section">
  <div class="card-dark">

    <?php if (!empty($announce['image'])): ?>
      <img src="/bandshi/uploads/announcements/<?= htmlspecialchars($announce['image']) ?>"
     class="announcement-img">
    <?php endif; ?>

    <h4><?= htmlspecialchars($announce['title']) ?></h4>

    <p class="mt-3">
      <?= nl2br(htmlspecialchars($announce['content'])) ?>
    </p>

    <small style="opacity:.5;">
      <?= date('d M Y H:i', strtotime($announce['created_at'])) ?>
    </small>

  </div>
</div>
<?php endif; ?>

<!-- EVENTS -->
<div class="section">
  <h4 class="mb-4">Upcoming Events</h4>

  <div class="event-row">
    <?php foreach ($events as $e): ?>
      <div class="event-box">

        <div>
          <div class="event-title">
            <?= htmlspecialchars($e['title']) ?>
          </div>

          <div class="event-info">
            📅 <?= date('d M Y', strtotime($e['event_date'])) ?>
          </div>

          <?php if($e['start_time']): ?>
          <div class="event-info">
            ⏰ <?= date('H:i', strtotime($e['start_time'])) ?>
          </div>
          <?php endif; ?>

          <?php if($e['location']): ?>
          <div class="event-info">
            📍 <?= htmlspecialchars($e['location']) ?>
          </div>
          <?php endif; ?>
        </div>

        <a href="event_detail.php?id=<?= $e['id'] ?>">
          <button class="view-btn">View Event</button>
        </a>

      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>