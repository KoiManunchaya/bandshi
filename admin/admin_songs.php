<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/admin_guard.php';

/* =====================
   Fetch closed events
===================== */
$events = [];
$res = $conn->query("
  SELECT id, title, event_date
  FROM events
  WHERE status = 'closed'
  ORDER BY event_date DESC
");
if ($res) {
  $events = $res->fetch_all(MYSQLI_ASSOC);
}

/* =====================
   Selected Event
===================== */
$event_id = $_GET['event_id'] ?? null;
$event = null;

if ($event_id) {
  $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
  $stmt->bind_param("i", $event_id);
  $stmt->execute();
  $event = $stmt->get_result()->fetch_assoc();
}

/* =====================
   Add Song
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['song_name'])) {

  $song_name = trim($_POST['song_name']);
  $event_id_post = (int)$_POST['event_id'];

  if ($song_name !== '') {

    $stmt = $conn->prepare("
      INSERT INTO songs (name, event_id, rehearsal_progress)
      VALUES (?, ?, 0)
    ");
    $stmt->bind_param("si", $song_name, $event_id_post);
    $stmt->execute();

    header("Location: admin_songs.php?event_id=".$event_id_post);
    exit();
  }
}

/* =====================
   Update Progress
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {

  $song_id = (int)$_POST['song_id'];
  $progress = (int)$_POST['progress'];

  $allowed = [0,25,50,75,100];

  if (in_array($progress, $allowed)) {

    $stmt = $conn->prepare("
      UPDATE songs
      SET rehearsal_progress = ?
      WHERE id = ?
    ");
    $stmt->bind_param("ii", $progress, $song_id);
    $stmt->execute();
  }

  header("Location: admin_songs.php?event_id=".$event_id);
  exit();
}

/* =====================
   Fetch Songs
===================== */
$songs = [];

if ($event_id) {

  $stmt = $conn->prepare("
    SELECT id, name, rehearsal_progress
    FROM songs
    WHERE event_id = ?
    ORDER BY rehearsal_progress ASC, id ASC
  ");
  $stmt->bind_param("i", $event_id);
  $stmt->execute();
  $songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Songs | Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
  background:#1f232a;
  color:#fff;
}

.card-dark {
  background:#2b2f36;
  border-radius:18px;
}

.section-title {
  font-weight:700;
  font-size:20px;
  margin-bottom:20px;
}

.progress {
  height:8px;
  border-radius:10px;
}

.song-card {
  background:#2b2f36;
  border-radius:18px;
  padding:24px;
}

/* Fix label visibility */
label {
  color: #ffffff !important;
  font-weight: 500;
}

/* ทำให้ข้อความรองอ่านง่ายขึ้น */
.text-secondary {
  color: #b0b6c2 !important;
}

/* ปรับ select ให้ contrast ดีขึ้น */
.form-select {
  background-color: #ffffff;
  color: #000000;
  border: none;
}
</style>
</head>

<body>

<div class="container py-5">

<a href="index.php" class="btn btn-sm btn-outline-light mb-4">
← Admin Home
</a>

<h2 class="mb-4">Songs</h2>

<!-- SELECT EVENT -->
<div class="card card-dark p-4 mb-4">
<form method="get">
<label class="mb-2">Select Event (Closed Join Only)</label>
<select name="event_id" class="form-select" onchange="this.form.submit()">
<option value="">-- Select Event --</option>
<?php foreach ($events as $e): ?>
<option value="<?= $e['id'] ?>"
<?= ($event_id == $e['id']) ? 'selected' : '' ?>>
<?= htmlspecialchars($e['title']) ?> (<?= $e['event_date'] ?>)
</option>
<?php endforeach; ?>
</select>
</form>
</div>

<?php if ($event): ?>

<div class="mb-4 text-secondary">
Managing songs for:
<strong><?= htmlspecialchars($event['title']) ?></strong>
</div>

<!-- ADD SONG -->
<div class="card card-dark p-4 mb-5">
<form method="post" class="row g-3">
<input type="hidden" name="event_id" value="<?= $event_id ?>">

<div class="col-md-9">
<input name="song_name"
class="form-control"
placeholder="Song name"
required>
</div>

<div class="col-md-3">
<button class="btn btn-success w-100">
Add Song
</button>
</div>
</form>
</div>

<!-- SONG LIST -->
<div class="mb-4 section-title">
Songs in this Event
</div>

<?php if (!$songs): ?>
<p class="text-secondary">No songs added yet.</p>
<?php else: ?>

<?php foreach ($songs as $s): ?>

<?php
$p = (int)$s['rehearsal_progress'];

if($p == 0) $color='bg-secondary';
elseif($p == 25) $color='bg-danger';
elseif($p == 50) $color='bg-warning';
elseif($p == 75) $color='bg-info';
else $color='bg-success';
?>

<div class="song-card mb-4">

<div class="d-flex justify-content-between align-items-start">

<div style="width:65%">
<h5 class="mb-2"><?= htmlspecialchars($s['name']) ?></h5>

<span class="badge <?= $color ?>"><?= $p ?>%</span>

<div class="progress mt-2">
<div class="progress-bar <?= $color ?>"
style="width: <?= $p ?>%">
</div>
</div>
</div>

<div class="text-end">

<!-- Stage Buttons -->
<form method="post" class="mb-3">
<input type="hidden" name="song_id" value="<?= $s['id'] ?>">
<input type="hidden" name="update_progress" value="1">

<div class="btn-group">

<?php
$levels = [0,25,50,75,100];
foreach($levels as $level):
$active = ($p == $level) ? 'btn-primary' : 'btn-outline-light';
?>

<button type="submit"
name="progress"
value="<?= $level ?>"
class="btn btn-sm <?= $active ?>">
<?= $level ?>%
</button>

<?php endforeach; ?>

</div>
</form>

<a href="admin_song_members.php?song_id=<?= $s['id'] ?>&event_id=<?= $event_id ?>"
class="btn btn-sm btn-outline-light">
Assign Members
</a>

</div>

</div>
</div>

<?php endforeach; ?>

<?php endif; ?>

<?php else: ?>

<p class="text-secondary">
Please select an event to manage songs.
</p>

<?php endif; ?>

</div>
</body>
</html>