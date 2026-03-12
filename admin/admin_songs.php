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
  WHERE status='closed'
  ORDER BY event_date DESC
");

if($res){
  $events = $res->fetch_all(MYSQLI_ASSOC);
}

/* =====================
   Selected Event
===================== */
$event_id = $_GET['event_id'] ?? null;
$event = null;

if($event_id){

  $stmt = $conn->prepare("
    SELECT * FROM events
    WHERE id=?
  ");

  $stmt->bind_param("i",$event_id);
  $stmt->execute();

  $event = $stmt->get_result()->fetch_assoc();
}


/* =====================
   DELETE SONG
===================== */

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_song'])){

  $song_id = (int)$_POST['song_id'];

  $stmt = $conn->prepare("
    DELETE FROM songs
    WHERE id=?
  ");

  $stmt->bind_param("i",$song_id);
  $stmt->execute();

  header("Location: admin_songs.php?event_id=".$event_id);
  exit();
}


/* =====================
   Add Song
===================== */

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['song_name'])){

  $song_name = trim($_POST['song_name']);
  $type = $_POST['performance_type'] ?? 'band';

  if($song_name!==''){

    $stmt = $conn->prepare("
      INSERT INTO songs (name,event_id,rehearsal_progress,performance_type)
      VALUES (?, ?, 0, ?)
    ");

    $stmt->bind_param("sis",$song_name,$event_id,$type);
    $stmt->execute();

    header("Location: admin_songs.php?event_id=".$event_id);
    exit();
  }
}


/* =====================
   Update Progress
===================== */

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_progress'])){

  $song_id = (int)$_POST['song_id'];
  $progress = (int)$_POST['progress'];

  $allowed=[0,25,50,75,100];

  if(in_array($progress,$allowed)){

    $stmt = $conn->prepare("
      UPDATE songs
      SET rehearsal_progress=?
      WHERE id=?
    ");

    $stmt->bind_param("ii",$progress,$song_id);
    $stmt->execute();
  }

  header("Location: admin_songs.php?event_id=".$event_id);
  exit();
}


/* =====================
   Fetch Songs
===================== */

$songs=[];

if($event_id){

  $stmt = $conn->prepare("
    SELECT id,name,rehearsal_progress,performance_type
    FROM songs
    WHERE event_id=?
    ORDER BY rehearsal_progress ASC,id ASC
  ");

  $stmt->bind_param("i",$event_id);
  $stmt->execute();

  $songs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Songs | Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
background:#1f232a;
color:#fff;
}

.card-dark{
background:#2b2f36;
border-radius:18px;
}

.section-title{
font-weight:700;
font-size:20px;
margin-bottom:20px;
}

.song-card{
background:#2b2f36;
border-radius:18px;
padding:24px;
}

.progress{
height:8px;
border-radius:10px;
}
/* fix dark text */

label{
color:#ffffff !important;
}

.text-secondary{
color:#cbd5e1 !important;
}

.form-control{
background:#1f232a;
color:#ffffff;
border:1px solid #444;
}

.form-control::placeholder{
color:#9ca3af;
}

.form-select{
background:#1f232a;
color:#ffffff;
border:1px solid #444;
}

.form-select option{
color:#000;
}
.form-select{
background-color:#1f232a;
color:#fff;
border:1px solid #444;

/* fix dropdown arrow */
background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='white'%3E%3Cpath fill-rule='evenodd' d='M1.5 5.5l6 6 6-6'/%3E%3C/svg%3E");
background-repeat:no-repeat;
background-position:right .75rem center;
background-size:16px 12px;
}

</style>
</head>

<body>

<div class="container py-5">

<a href="index.php" class="btn btn-outline-light mb-4">
← Admin Home
</a>

<h2 class="mb-4">Songs</h2>

<!-- SELECT EVENT -->

<div class="card card-dark p-4 mb-4">

<form method="get">

<label class="mb-2">Select Event (Closed Join Only)</label>

<select name="event_id" class="form-select" onchange="this.form.submit()">

<option value="">-- Select Event --</option>

<?php foreach($events as $e): ?>

<option
value="<?= $e['id'] ?>"
<?= ($event_id==$e['id'])?'selected':'' ?>
>

<?= htmlspecialchars($e['title']) ?> (<?= $e['event_date'] ?>)

</option>

<?php endforeach; ?>

</select>

</form>

</div>


<?php if($event): ?>

<div class="mb-4 text-secondary">

Managing songs for:

<strong><?= htmlspecialchars($event['title']) ?></strong>

</div>


<!-- ADD SONG -->

<div class="card card-dark p-4 mb-5">

<form method="post" class="row g-3">

<div class="col-md-6">

<input
name="song_name"
class="form-control"
placeholder="Song name"
required>

</div>

<div class="col-md-3">

<select name="performance_type" class="form-select">

<option value="band">Band</option>
<option value="dance">Dance</option>

</select>

</div>

<div class="col-md-3">

<button class="btn btn-success w-100">

Add Song

</button>

</div>

</form>

</div>


<div class="section-title">

Songs in this Event

</div>


<?php if(!$songs): ?>

<p class="text-secondary">No songs added yet.</p>

<?php else: ?>

<?php foreach($songs as $s): ?>

<?php

$p=(int)$s['rehearsal_progress'];

if($p==0) $color='bg-secondary';
elseif($p==25) $color='bg-danger';
elseif($p==50) $color='bg-warning';
elseif($p==75) $color='bg-info';
else $color='bg-success';

?>

<div class="song-card mb-4">

<div class="d-flex justify-content-between">

<div style="width:65%">

<h5><?= htmlspecialchars($s['name']) ?></h5>

<span class="badge <?= $color ?>"><?= $p ?>%</span>

<span class="badge bg-dark ms-2">

<?= $s['performance_type'] ?>

</span>

<div class="progress mt-2">

<div
class="progress-bar <?= $color ?>"
style="width:<?= $p ?>%">

</div>

</div>

</div>


<div class="text-end">


<form method="post" class="mb-3">

<input type="hidden" name="song_id" value="<?= $s['id'] ?>">
<input type="hidden" name="update_progress" value="1">

<div class="btn-group">

<?php
$levels=[0,25,50,75,100];
foreach($levels as $level):

$active=($p==$level)?'btn-primary':'btn-outline-light';
?>

<button
type="submit"
name="progress"
value="<?= $level ?>"
class="btn btn-sm <?= $active ?>">

<?= $level ?>%

</button>

<?php endforeach; ?>

</div>

</form>


<a
href="admin_song_members.php?song_id=<?= $s['id'] ?>&event_id=<?= $event_id ?>"
class="btn btn-sm btn-outline-light me-2">

Assign Members

</a>


<form method="post" style="display:inline">

<input type="hidden" name="song_id" value="<?= $s['id'] ?>">

<button
name="delete_song"
class="btn btn-sm btn-danger"
onclick="return confirm('Delete this song?')">

Delete

</button>

</form>

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