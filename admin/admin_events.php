<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/admin_guard.php';

/*
|--------------------------------------------------------------------------
| Admin Event Management
|--------------------------------------------------------------------------
*/

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title      = trim($_POST['title'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $start_time = $_POST['start_time'] ?? null;
    $end_time   = $_POST['end_time'] ?? null;
    $location   = trim($_POST['location'] ?? '');

    if ($title === '' || $event_date === '' || $location === '') {
        $error = 'Please fill all required fields.';
    }

    if (!$error && ($start_time || $end_time)) {
        if (!$start_time || !$end_time) {
            $error = 'Please specify both start and end time.';
        } elseif ($start_time >= $end_time) {
            $error = 'Start time must be earlier than end time.';
        }
    }

    if (!$error) {

        $stmt = $conn->prepare("
            INSERT INTO events (title, event_date, start_time, end_time, location)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                start_time = VALUES(start_time),
                end_time   = VALUES(end_time),
                location   = VALUES(location)
        ");

        $stmt->bind_param(
            "sssss",
            $title,
            $event_date,
            $start_time,
            $end_time,
            $location
        );

        if ($stmt->execute()) {
            $success = 'Event saved successfully.';
        } else {
            $error = 'Database error occurred.';
        }
    }
}

/* FETCH EVENTS */
$events = [];
$result = $conn->query("
    SELECT *
    FROM events
    ORDER BY event_date DESC, start_time DESC
");

if ($result) {
    $events = $result->fetch_all(MYSQLI_ASSOC);
}

$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>

<a href="index.php" class="btn btn-sm btn-outline-light mb-3">
← Admin Home
</a>

<meta charset="UTF-8">
<title>Admin Events | BANdSHI</title>
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

.btn-pink{
background:#e84c88;
color:#fff;
}

/* STATUS BADGE */

.status-badge{
font-size:12px;
padding:4px 10px;
border-radius:999px;
font-weight:600;
display:inline-block;
margin-top:4px;
}

.status-open{
background:#00c2ff;
color:#000;
}

.status-closed{
background:#ff8c42;
color:#000;
}

.status-finished{
background:#555;
color:#fff;
}

</style>
</head>

<body>

<div class="container py-5">

<h2 class="mb-4">Create / Edit Event</h2>

<div class="card card-dark p-4 mb-4">

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post" class="row g-3">

<div class="col-12">
<input
class="form-control"
name="title"
placeholder="Event name"
required
>
</div>

<div class="col-md-4">
<input
type="date"
class="form-control"
name="event_date"
required
>
</div>

<div class="col-md-4">
<input
type="time"
class="form-control"
name="start_time"
>
</div>

<div class="col-md-4">
<input
type="time"
class="form-control"
name="end_time"
>
</div>

<div class="col-12">
<input
class="form-control"
name="location"
placeholder="Location"
required
>
</div>

<div class="col-12">
<button class="btn btn-pink px-4">Save Event</button>
</div>

</form>

</div>

<h3 class="mb-3">All Events</h3>

<div class="card card-dark p-3">

<ul class="list-group list-group-flush">

<?php foreach ($events as $e):

/* CALCULATE STATUS */

if ($e['event_date'] < $today) {
$status = "Finished";
$statusClass = "status-finished";
}
elseif ($e['status'] === 'closed') {
$status = "Closed Join";
$statusClass = "status-closed";
}
else {
$status = "Open";
$statusClass = "status-open";
}

?>

<li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between align-items-start">

<div>

<strong><?= htmlspecialchars($e['title']) ?></strong><br>

<small style="color:#d0d4d8;">

<?= htmlspecialchars($e['event_date']) ?>

<?= $e['start_time'] ? " | {$e['start_time']}–{$e['end_time']}" : '' ?>

<br>

<?= htmlspecialchars($e['location']) ?>

<br>

<span class="status-badge <?= $statusClass ?>">
<?= $status ?>
</span>

</small>

</div>

<a
href="admin_event_detail.php?id=<?= (int)$e['id'] ?>"
class="btn btn-sm btn-outline-light"
>
Edit
</a>

</li>

<?php endforeach; ?>

</ul>

</div>

</div>

</body>
</html>
