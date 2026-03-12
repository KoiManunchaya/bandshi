<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/admin_guard.php';

$event_id = $_GET['id'] ?? null;
if (!$event_id) {
  die('Invalid event');
}

/* =====================
   DELETE EVENT
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {

  $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
  $stmt->bind_param("i", $event_id);
  $stmt->execute();

  header("Location: admin_events.php");
  exit();
}

/* =====================
   CLOSE JOIN
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_join'])) {

  $stmt = $conn->prepare("
    UPDATE events
    SET status='closed'
    WHERE id=?
  ");
  $stmt->bind_param("i",$event_id);
  $stmt->execute();

  header("Location: admin_event_detail.php?id=".$event_id);
  exit();
}

/* =====================
   REOPEN JOIN
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reopen_join'])) {

  $stmt = $conn->prepare("
    UPDATE events
    SET status='open'
    WHERE id=?
  ");
  $stmt->bind_param("i",$event_id);
  $stmt->execute();

  header("Location: admin_event_detail.php?id=".$event_id);
  exit();
}

/* =====================
   UPDATE EVENT INFO
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {

  $title      = $_POST['title'];
  $event_date = $_POST['event_date'];
  $start_time = $_POST['start_time'];
  $end_time   = $_POST['end_time'];
  $location   = $_POST['location'];

  $stmt = $conn->prepare("
    UPDATE events
    SET title=?, event_date=?, start_time=?, end_time=?, location=?
    WHERE id=?
  ");

  $stmt->bind_param(
    "sssssi",
    $title,
    $event_date,
    $start_time,
    $end_time,
    $location,
    $event_id
  );

  $stmt->execute();

  header("Location: admin_event_detail.php?id=".$event_id);
  exit();
}

/* =====================
   FETCH EVENT
===================== */
$stmt = $conn->prepare("SELECT * FROM events WHERE id=?");
$stmt->bind_param("i",$event_id);
$stmt->execute();

$event = $stmt->get_result()->fetch_assoc();

if(!$event){
  die("Event not found");
}

/* =====================
   FETCH JOINED MEMBERS
===================== */
$stmt = $conn->prepare("
  SELECT u.display_name, u.part, u.instrument
  FROM event_join ej
  JOIN users u ON ej.user_id = u.id
  WHERE ej.event_id = ?
  ORDER BY u.display_name
");

$stmt->bind_param("i",$event_id);
$stmt->execute();

$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Event Detail | Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
background:#1f232a;
color:#ffffff;
}

h2,h5,p,li,label{
color:#ffffff;
}

.card-dark{
background:#2b2f36;
border-radius:16px;
}

.alert-warning,
.alert-success{
background:#3a3f47;
border:none;
color:#ffffff;
}

ul{
list-style:none;
padding-left:0;
}

li{
padding:6px 0;
}

.form-control{
background:#1f232a;
border:1px solid #444;
color:#fff;
}

</style>
</head>

<body>

<div class="container py-5">

<a href="admin_events.php" class="btn btn-outline-light mb-4">
← Back
</a>

<!-- EDIT EVENT -->

<div class="card card-dark p-4 mb-4">

<h5 class="mb-3">Edit Event</h5>

<form method="post">

<div class="row g-3">

<div class="col-12">
<label>Title</label>
<input
class="form-control"
name="title"
value="<?= htmlspecialchars($event['title']) ?>"
required>
</div>

<div class="col-md-4">
<label>Date</label>
<input
type="date"
class="form-control"
name="event_date"
value="<?= $event['event_date'] ?>"
required>
</div>

<div class="col-md-4">
<label>Start Time</label>
<input
type="time"
class="form-control"
name="start_time"
value="<?= $event['start_time'] ?>">
</div>

<div class="col-md-4">
<label>End Time</label>
<input
type="time"
class="form-control"
name="end_time"
value="<?= $event['end_time'] ?>">
</div>

<div class="col-12">
<label>Location</label>
<input
class="form-control"
name="location"
value="<?= htmlspecialchars($event['location']) ?>"
required>
</div>

<div class="col-12 d-flex justify-content-between mt-2">

<button
name="update_event"
class="btn btn-primary">
Save Changes
</button>

<button
name="delete_event"
class="btn btn-danger"
onclick="return confirm('Delete this event?')">
Delete Event
</button>

</div>

</div>

</form>

</div>

<!-- JOINED MEMBERS -->

<div class="card card-dark p-4 mb-4">

<h5>Joined Members (<?= count($members) ?>)</h5>

<?php if(empty($members)): ?>

<p>No members joined yet.</p>

<?php else: ?>

<ul>

<?php foreach($members as $m): ?>

<li>

<?= htmlspecialchars($m['display_name']) ?>

(<?= htmlspecialchars($m['part']) ?>

<?= $m['instrument'] !== 'none' ? ' / '.htmlspecialchars($m['instrument']) : '' ?>)

</li>

<?php endforeach; ?>

</ul>

<?php endif; ?>

</div>

<!-- STATUS -->

<div class="card card-dark p-4">

<h5>Status</h5>

<?php if($event['status']==='open'): ?>

<div class="alert alert-warning mb-3">
Event is open for join
</div>

<div class="d-flex gap-2">

<form method="post">
<button
name="close_join"
class="btn btn-warning"
onclick="return confirm('Close joining for this event?')">
Close Join
</button>
</form>

<a
href="admin_songs.php?event_id=<?= $event_id ?>"
class="btn btn-success">
Manage Songs
</a>

</div>

<?php else: ?>

<div class="alert alert-success mb-3">
Join closed — ready for song assignment
</div>

<div class="d-flex gap-2">

<form method="post">
<button
name="reopen_join"
class="btn btn-outline-light"
onclick="return confirm('Reopen joining?')">
Reopen Join
</button>
</form>

<a
href="admin_songs.php?event_id=<?= $event_id ?>"
class="btn btn-success">
Manage Songs
</a>

</div>

<?php endif; ?>

</div>

</div>

</body>
</html>