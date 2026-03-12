<?php
include 'auth.php';
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today   = date('Y-m-d');

/* ===== JOIN / UNJOIN ===== */
if (isset($_GET['action'], $_GET['event_id'])) {

    $event_id = (int)$_GET['event_id'];

    if ($_GET['action'] === 'join') {
        $stmt = $conn->prepare("
            INSERT INTO event_join (user_id, event_id)
            SELECT ?, ?
            WHERE NOT EXISTS (
                SELECT 1 FROM event_join WHERE user_id=? AND event_id=?
            )
        ");
        $stmt->bind_param("iiii", $user_id, $event_id, $user_id, $event_id);
        $stmt->execute();
    }

    if ($_GET['action'] === 'unjoin') {
        $stmt = $conn->prepare("
            DELETE FROM event_join WHERE user_id=? AND event_id=?
        ");
        $stmt->bind_param("ii", $user_id, $event_id);
        $stmt->execute();
    }

    header("Location: events.php");
    exit();
}

/* ===== FILTER ===== */
$filter = $_GET['filter'] ?? 'all';

$sql = "
SELECT e.*,
       CASE WHEN ej.user_id IS NULL THEN 0 ELSE 1 END AS joined
FROM events e
LEFT JOIN event_join ej
ON e.id = ej.event_id AND ej.user_id = $user_id
";

if ($filter === 'not_joined') {
    $sql .= " WHERE ej.user_id IS NULL
              AND e.status='open'
              AND e.event_date >= '$today'";
}
elseif ($filter === 'joined') {
    $sql .= " WHERE ej.user_id IS NOT NULL
              AND e.status='open'
              AND e.event_date >= '$today'";
}
elseif ($filter === 'finished') {
    $sql .= " WHERE e.status='closed'
              OR e.event_date < '$today'";
}

$sql .= "
ORDER BY 
  (e.event_date < '$today') ASC,
  joined ASC,
  e.event_date ASC
";

$events = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Events | BANdSHI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
  margin:0;
  padding-top:64px;
  background:#000;
  color:#fff;
  font-family:system-ui;
}

.page-title{
  font-weight:800;
  margin-bottom:24px;
}

/* FILTER */
.filter-btn{
  border:1px solid #333;
  background:#111;
  color:#fff;
  padding:8px 18px;
  border-radius:999px;
  margin-right:10px;
  text-decoration:none;
  font-size:14px;
}
.filter-btn.active{
  background:#e84c88;
  border-color:#e84c88;
}

/* CARD */
.event-card{
  position:relative;
  background:#1f1f1f;
  border-radius:20px;
  border:1px solid #2a2a2a;
  transition:.2s;
}
.event-card:hover{
  transform:translateY(-4px);
}

/* JOINED STYLE */
.joined-card{
  background:linear-gradient(135deg,#081f26,#0b2f3a);
  border:1px solid #00c2ff;
}

/* CLOSED */
.past-card{
  opacity:.5;
}

/* BADGE */
.badge-status{
  position:absolute;
  top:15px;
  right:20px;
  font-size:12px;
  padding:4px 10px;
  border-radius:999px;
}
.badge-joined{
  background:#00c2ff;
  color:#000;
}
.badge-finished{
  background:#555;
}

/* TEXT */
.event-title{
  font-weight:700;
}
.event-meta{
  color:#cfd3d7;
  font-size:14px;
}

/* LINK */
.event-link{
  color:#00c2ff;
  text-decoration:none;
  font-weight:500;
}
.event-link:hover{
  color:#6ad7f5;
}

/* BUTTON */
.btn-join{
  background:#e84c88;
  color:#fff;
  border:none;
  border-radius:999px;
  padding:8px 24px;
  font-weight:600;
}
.btn-unjoin{
  background:#00c2ff;
  color:#000;
  border:none;
  border-radius:999px;
  padding:8px 24px;
  font-weight:600;
}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="container my-5">

<h2 class="page-title">Events</h2>

<!-- FILTER -->
<div class="mb-4">
<a href="?filter=all" class="filter-btn <?= $filter==='all'?'active':'' ?>">All</a>
<a href="?filter=not_joined" class="filter-btn <?= $filter==='not_joined'?'active':'' ?>">Not Joined</a>
<a href="?filter=joined" class="filter-btn <?= $filter==='joined'?'active':'' ?>">Joined</a>
<a href="?filter=finished" class="filter-btn <?= $filter==='finished'?'active':'' ?>">Closed</a>
</div>

<?php while ($e = $events->fetch_assoc()): 

$isPast = ($e['status'] === 'closed') || ($e['event_date'] < $today);
$isJoined = $e['joined'] == 1;

$cardClass = '';
if ($isPast) $cardClass = 'past-card';
elseif ($isJoined) $cardClass = 'joined-card';
?>

<div class="event-card p-4 mb-4 <?= $cardClass ?>">

<?php if ($isJoined && !$isPast): ?>
<div class="badge-status badge-joined">Joined</div>
<?php endif; ?>

<?php if ($isPast): ?>
<div class="badge-status badge-finished">Closed</div>
<?php endif; ?>

<h4 class="event-title mb-2">
<?= htmlspecialchars($e['title']) ?>
</h4>

<div class="event-meta mb-2">
📅 <?= htmlspecialchars($e['event_date']) ?>
<?php if ($e['start_time'] && $e['end_time']): ?>
, <?= substr($e['start_time'],0,5) ?>–<?= substr($e['end_time'],0,5) ?>
<?php endif; ?>
</div>

<?php if (!empty($e['location'])): ?>
<div class="event-meta mb-3">
📍 <?= htmlspecialchars($e['location']) ?>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center">

<?php if (!$isPast): ?>
<a href="event_detail.php?id=<?= $e['id'] ?>#members" class="event-link">
See who joined
</a>
<?php endif; ?>

<div>

<?php if ($isJoined && !$isPast): ?>

<a href="?action=unjoin&event_id=<?= $e['id'] ?>" class="btn-unjoin">
Unjoin
</a>

<?php elseif (!$isJoined && !$isPast): ?>

<a href="?action=join&event_id=<?= $e['id'] ?>" class="btn-join">
Join
</a>

<?php endif; ?>

</div>

</div>

</div>

<?php endwhile; ?>

</div>

</body>
</html>