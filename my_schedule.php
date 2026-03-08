
<?php
session_start();
require_once __DIR__.'/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT ps.date, ps.time_slot, s.name
    FROM practice_sessions ps
    JOIN songs s ON ps.song_id = s.id
    JOIN practice_session_members psm 
        ON psm.practice_session_id = ps.id
    WHERE psm.user_id = ?
    AND ps.date >= CURDATE()
    ORDER BY ps.date ASC, ps.time_slot ASC
");
$stmt->bind_param("i",$userId);
$stmt->execute();
$result = $stmt->get_result();
$sessions = $result->fetch_all(MYSQLI_ASSOC);

$totalSessions = count($sessions);

/* ===== DAY COLOR MAP ===== */
function dayColor($date){
    $day = (int) date('w', strtotime($date));

    return match($day){
        1 => '#f4d35e', // Mon
        2 => '#ff4fa3', // Tue
        3 => '#4caf50', // Wed
        4 => '#ff8c42', // Thu
        5 => '#4da6ff', // Fri
        6 => '#9b5de5', // Sat
        0 => '#ff4d4d', // Sun
        default => '#999999'
    };
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Your Rehearsal Schedule</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
  margin:0;
  padding-top:64px;
  background:#111;
  color:#fff;
}

.card-dark{
  background:#1c1c1c;
  border-radius:16px;
}

.table-dark{
  --bs-table-bg:#1c1c1c;
  --bs-table-color:#ffffff;
}

.summary-box{
  background:#1c1c1c;
  border-radius:12px;
}

.day-divider{
  border-top:2px solid #333;
}

.day-badge{
  padding:4px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:600;
  color:#000;
}

.time-badge{
  padding:6px 14px;
  border-radius:20px;
  font-weight:600;
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="container py-5">

<h2 class="mb-4">Your Rehearsal Schedule</h2>

<div class="summary-box p-3 mb-4">
<strong>This Week Summary</strong><br>
Total Rehearsals: <?= $totalSessions ?> sessions
</div>

<div class="card card-dark p-4">

<table class="table table-dark align-middle mb-0">
<thead>
<tr>
<th style="width:240px;">Date</th>
<th style="width:160px;">Time</th>
<th>Song</th>
</tr>
</thead>
<tbody>

<?php 
$prevDate = null;

if(empty($sessions)){
?>
<tr>
<td colspan="3" class="text-center py-5 text-secondary">
No rehearsal sessions scheduled yet.
</td>
</tr>
<?php
}

foreach ($sessions as $s): 
    $currentDate = $s['date'];
    $color = dayColor($currentDate);
    $dayText = date('D', strtotime($currentDate));

    if ($prevDate !== $currentDate):
?>
<tr class="day-divider">
<td colspan="3" class="py-2">
<span class="day-badge" style="background:<?= $color ?>">
<?= $dayText ?>
</span>
<strong class="ms-2">
<?= date('d M Y', strtotime($currentDate)) ?>
</strong>
</td>
</tr>
<?php endif; ?>

<tr>
<td></td>
<td>
<span class="time-badge"
      style="background: <?= $color ?>30;
             border:1px solid <?= $color ?>;
             color:#ffffff;">
    <?= htmlspecialchars($s['time_slot']) ?>
</span>
</td>
<td>
<strong><?= htmlspecialchars($s['name']) ?></strong>
</td>
</tr>

<?php 
$prevDate = $currentDate;
endforeach; 
?>

</tbody>
</table>

</div>
</div>

</body>
</html>
