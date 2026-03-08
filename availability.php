<?php
include 'auth.php';
include 'db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = $_SESSION['user_id'];

/* ======================= WEEK ======================= */
if (isset($_GET['date'])) {
  $start = new DateTime($_GET['date']);
  $start->modify('monday this week');
  $weekOffset = 0;
} else {
  $weekOffset = isset($_GET['week']) ? intval($_GET['week']) : 0;
  $start = new DateTime();
  $start->modify('monday this week');
  $start->modify(($weekOffset * 7) . ' days');
}

/* ======================= TIME ======================= */
$timeSlots = [
  '09:00-12:00','13:00-16:00','16:00-17:00',
  '17:00-18:00','18:00-19:00','19:00-20:00','20:00-21:00'
];

/* ======================= FETCH ======================= */
$saved = [];
$res = $conn->query("SELECT date, time_slot FROM availability WHERE user_id = $user_id");
while ($row = $res->fetch_assoc()) {
  $saved[$row['date']][$row['time_slot']] = true;
}

/* ======================= AUTO SAVE ======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $weekStart = $start->format('Y-m-d');
  $weekEnd   = (clone $start)->modify('+6 day')->format('Y-m-d');

  $stmt = $conn->prepare("
    DELETE FROM availability 
    WHERE user_id=? AND date BETWEEN ? AND ?
  ");
  $stmt->bind_param("iss", $user_id, $weekStart, $weekEnd);
  $stmt->execute();

  if (!empty($_POST['slot'])) {
    foreach ($_POST['slot'] as $date => $slots) {
      foreach ($slots as $slot => $v) {
        $stmt = $conn->prepare("
          INSERT INTO availability (user_id,date,time_slot)
          VALUES (?,?,?)
        ");
        $stmt->bind_param("iss", $user_id, $date, $slot);
        $stmt->execute();
      }
    }
  }

  exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Availability</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
  background:#000;
  color:#fff;
  padding-top:70px;
}

.avail-card{
  max-width:1200px;
  margin:auto;
  background:#1f1f1f;
  border-radius:24px;
  padding:30px;
  border:1px solid #2a2a2a;
}

/* ===== TABLE ===== */

table{
  background:#111 !important;
  border-color:#2a2a2a !important;
}

th, td{
  border-color:#2a2a2a !important;
  color:#ffffff !important;
}

thead th{
  background:#1a1a1a !important;
  font-weight:600;
}

tbody th{
  background:#1a1a1a !important;
  font-weight:600;
  width:160px;
}

td{ background:#111 !important; }
td:hover{ background:#1b1b1b !important; }

.today{ background:#132c3f !important; }

.past input{
  opacity:.4;
  pointer-events:none;
}

input[type="checkbox"]{
  width:18px;
  height:18px;
  cursor:pointer;
}
input[type="checkbox"]:checked{
  accent-color:#e84c88;
}

.btn-nav{
  background:#1f1f1f;
  border:1px solid #333;
  color:#fff;
  padding:8px 16px;
  border-radius:12px;
}

/* ===== DATE FIX (ไม่หาย + ขาว) ===== */

input[type="date"]{
  background:#2a2a2a !important;
  color:#ffffff !important;
  border:none !important;
  padding:6px 10px;
  border-radius:10px;
}

/* Chrome / Edge */
input[type="date"]::-webkit-calendar-picker-indicator{
  filter: invert(1) brightness(1.7);
  opacity: 1;
  cursor:pointer;
}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="container">
<div class="avail-card">

<h3 class="mb-4">Your Availability</h3>

<div class="d-flex justify-content-between align-items-center mb-3">
  <a href="?week=<?= $weekOffset - 1 ?>" class="btn-nav">← Previous</a>

  <div class="text-center">
    <input type="date"
           value="<?= $start->format('Y-m-d') ?>"
           onchange="location='?date='+this.value">
    <div class="text-secondary small mt-1">
      Week of <?= $start->format('d M Y') ?>
    </div>
  </div>

  <a href="?week=<?= $weekOffset + 1 ?>" class="btn-nav">Next →</a>
</div>

<form id="availForm" method="post">
<div class="table-responsive">
<table class="table table-bordered text-center align-middle">

<thead>
<tr>
<th>Time</th>
<?php
$days=[];
$today=(new DateTime())->format('Y-m-d');

for($i=0;$i<7;$i++){
  $d=clone $start;
  $d->modify("+$i day");
  $days[]=$d;
  $class=$d->format('Y-m-d')===$today?'today':'';
  echo "<th class='$class'>".$d->format('D d M')."</th>";
}
?>
</tr>
</thead>

<tbody>
<?php foreach($timeSlots as $slot): ?>
<tr>
<th><?= $slot ?></th>
<?php foreach($days as $d):
$dateStr=$d->format('Y-m-d');
$checked=isset($saved[$dateStr][$slot])?'checked':'';
$isPast=$dateStr<$today?'past':'';
?>
<td class="<?= $isPast ?>">
<input type="checkbox"
name="slot[<?= $dateStr ?>][<?= $slot ?>]"
<?= $checked ?>>
</td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>
</form>

</div>
</div>

<script>
const form=document.getElementById('availForm');
let isDragging=false;
let dragTargetState=null;
let saveTimer=null;

document.querySelectorAll('input[type="checkbox"]').forEach(cb=>{

cb.addEventListener('mousedown',()=>{
if(cb.closest('td').classList.contains('past'))return;
isDragging=true;
dragTargetState=!cb.checked;
});

cb.addEventListener('mouseenter',()=>{
if(!isDragging)return;
if(cb.closest('td').classList.contains('past'))return;
cb.checked=dragTargetState;
});

cb.addEventListener('change',debounceSave);
});

document.addEventListener('mouseup',()=>{
if(isDragging){
isDragging=false;
debounceSave();
}
});

function debounceSave(){
clearTimeout(saveTimer);
saveTimer=setTimeout(autoSave,300);
}

function autoSave(){
fetch(window.location.href,{
method:'POST',
body:new FormData(form)
});
}
</script>

</body>
</html>