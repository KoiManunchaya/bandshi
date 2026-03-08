<?php
session_start();
require_once __DIR__.'/../db.php';
require_once __DIR__.'/admin_guard.php';
require_once __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/* ============================
   FILTER
============================ */
$cohort = $_GET['cohort'] ?? '';
$part   = $_GET['part'] ?? '';
$search = $_GET['search'] ?? '';

$where = " WHERE u.role='member' ";

if($cohort) $where .= " AND u.cohort='". $conn->real_escape_string($cohort)."'";
if($part)   $where .= " AND u.part='". $conn->real_escape_string($part)."'";
if($search) $where .= " AND u.full_name LIKE '%".$conn->real_escape_string($search)."%'";

/* ============================
   SUMMARY
============================ */
$total_members = $conn->query("SELECT COUNT(*) c FROM users WHERE role='member'")
->fetch_assoc()['c'];

$total_events = $conn->query("SELECT COUNT(*) c FROM events")
->fetch_assoc()['c'];

/* ============================
   TOP ACTIVE
============================ */
$top_active = $conn->query("
SELECT u.full_name,
COUNT(ej.event_id) as join_count
FROM users u
LEFT JOIN event_join ej ON u.id=ej.user_id
WHERE u.role='member'
GROUP BY u.id
ORDER BY join_count DESC
LIMIT 5
");

/* ============================
   LOW AVAILABILITY
============================ */
$low_availability = $conn->query("
SELECT u.full_name,
ROUND((COUNT(DISTINCT a.date)/7)*100,2) as percent
FROM users u
LEFT JOIN availability a ON u.id=a.user_id
WHERE u.role='member'
GROUP BY u.id
HAVING percent < 40
");

/* ============================
   LEAST JOINED EVENT
============================ */
$least_event = $conn->query("
SELECT e.title, COUNT(ej.user_id) join_count
FROM events e
LEFT JOIN event_join ej ON e.id=ej.event_id
GROUP BY e.id
ORDER BY join_count ASC
LIMIT 1
")->fetch_assoc();

/* ============================
   INCOMPLETE SONGS
============================ */
$incomplete_songs = $conn->query("
SELECT s.name, e.title event_title,
COUNT(sm.user_id) assigned_count
FROM songs s
LEFT JOIN song_members sm ON s.id=sm.song_id
JOIN events e ON s.event_id=e.id
GROUP BY s.id
HAVING assigned_count < 5
");

/* ============================
   MEMBER RANKING
============================ */
$members = $conn->query("
SELECT 
u.full_name,
u.cohort,
u.part,
COUNT(DISTINCT ej.event_id) join_count,
COUNT(DISTINCT a.date) available_days,
ROUND((COUNT(DISTINCT a.date)/7)*100,2) availability_percent,
(COUNT(DISTINCT ej.event_id)*2
+ ROUND((COUNT(DISTINCT a.date)/7)*100,2)) activity_score
FROM users u
LEFT JOIN event_join ej ON u.id=ej.user_id
LEFT JOIN availability a ON u.id=a.user_id
$where
GROUP BY u.id
ORDER BY activity_score DESC
");

/* ============================
   CHART DATA
============================ */
$chart_data = [];
$chart_labels = [];

$chart_query = $conn->query("
SELECT u.full_name,
ROUND((COUNT(DISTINCT a.date)/7)*100,2) percent
FROM users u
LEFT JOIN availability a ON u.id=a.user_id
WHERE u.role='member'
GROUP BY u.id
");

while($row=$chart_query->fetch_assoc()){
    $chart_labels[] = $row['full_name'];
    $chart_data[] = $row['percent'];
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Analytics Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{background:#0f1115;color:#fff;}
.card-dark{background:#1c1f26;padding:24px;border-radius:20px;margin-bottom:30px;}
.stat-box{background:#1c1f26;padding:24px;border-radius:18px;text-align:center;}
.stat-number{font-size:28px;font-weight:700;}
</style>
</head>

<body>
<div class="container py-5">

<h2 class="mb-4">Admin Analytics Dashboard</h2>

<!-- SUMMARY -->
<div class="row mb-5">
<div class="col-md-6">
<div class="stat-box">
<div>Total Members</div>
<div class="stat-number"><?= $total_members ?></div>
</div>
</div>
<div class="col-md-6">
<div class="stat-box">
<div>Total Events</div>
<div class="stat-number"><?= $total_events ?></div>
</div>
</div>
</div>

<!-- TOP ACTIVE -->
<div class="card-dark">
<h4>🔥 Top 5 Active Members</h4>
<ul>
<?php while($row=$top_active->fetch_assoc()): ?>
<li><?= htmlspecialchars($row['full_name']) ?> (<?= $row['join_count'] ?> joins)</li>
<?php endwhile; ?>
</ul>
</div>

<!-- LOW AVAILABILITY -->
<div class="card-dark">
<h4>⚠ Members Availability < 40%</h4>
<ul>
<?php while($row=$low_availability->fetch_assoc()): ?>
<li><?= htmlspecialchars($row['full_name']) ?> (<?= $row['percent'] ?>%)</li>
<?php endwhile; ?>
</ul>
</div>

<!-- LEAST EVENT -->
<div class="card-dark">
<h4>📉 Least Joined Event</h4>
<?php if($least_event): ?>
<p><?= htmlspecialchars($least_event['title']) ?> — <?= $least_event['join_count'] ?> joins</p>
<?php endif; ?>
</div>

<!-- INCOMPLETE SONG -->
<div class="card-dark">
<h4>🎵 Incomplete Songs</h4>
<ul>
<?php while($row=$incomplete_songs->fetch_assoc()): ?>
<li><?= htmlspecialchars($row['name']) ?> (<?= $row['assigned_count'] ?>/5)</li>
<?php endwhile; ?>
</ul>
</div>

<!-- MEMBER TABLE -->
<div class="card-dark">
<h4>📊 Member Ranking</h4>
<table class="table table-dark">
<thead>
<tr>
<th>Name</th>
<th>Cohort</th>
<th>Part</th>
<th>Join</th>
<th>Avail %</th>
<th>Score</th>
</tr>
</thead>
<tbody>
<?php while($row=$members->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['full_name']) ?></td>
<td><?= $row['cohort'] ?></td>
<td><?= $row['part'] ?></td>
<td><?= $row['join_count'] ?></td>
<td><?= $row['availability_percent'] ?>%</td>
<td><strong><?= $row['activity_score'] ?></strong></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<!-- CHART -->
<div class="card-dark">
<h4>📈 Availability Chart</h4>
<canvas id="chart"></canvas>
</div>

</div>

<script>
new Chart(document.getElementById('chart'),{
type:'bar',
data:{
labels: <?= json_encode($chart_labels) ?>,
datasets:[{
label:'Availability %',
data: <?= json_encode($chart_data) ?>,
backgroundColor:'#1ea7dc'
}]
},
options:{responsive:true}
});
</script>

</body>
</html>