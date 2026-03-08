<?php
require_once 'db.php';

/* =========================
   INPUT
========================= */
$song_id = $_GET['song_id'] ?? 0;
if (!$song_id) die("song_id required");

/* =========================
   GET SONG MEMBERS
========================= */
$members = [];
$res = $conn->query("
  SELECT u.id, u.part, u.instrument
  FROM song_members sm
  JOIN users u ON sm.user_id = u.id
  WHERE sm.song_id = $song_id
");
while ($row = $res->fetch_assoc()) {
  $members[$row['id']] = $row;
}
$total_members = count($members);

/* =========================
   GET AVAILABILITY
========================= */
$user_ids = implode(',', array_keys($members));

$slots = [];
$res = $conn->query("
  SELECT date, time_slot, user_id
  FROM availability
  WHERE user_id IN ($user_ids)
");

while ($r = $res->fetch_assoc()) {
  $key = $r['date'].'|'.$r['time_slot'];
  $slots[$key]['date'] = $r['date'];
  $slots[$key]['time_slot'] = $r['time_slot'];
  $slots[$key]['users'][] = $r['user_id'];
}

/* =========================
   EVALUATE SLOT
========================= */
function validSlot($missing, $members) {

  $missing_singer = 0;
  $missing_musician = 0;
  $missing_drum = false;

  foreach ($missing as $uid) {
    if ($members[$uid]['part'] === 'singer') $missing_singer++;
    if ($members[$uid]['part'] === 'musician') {
      $missing_musician++;
      if ($members[$uid]['instrument'] === 'drum') {
        $missing_drum = true;
      }
    }
  }

  if ($missing_drum) return false;
  if ($missing_musician > 1) return false;
  if ($missing_singer > 2) return false;
  if ($missing_musician >= 2 && count($missing) >= 2) return false;

  return true;
}

$best = null;
$best_score = -999;

/* =========================
   LOOP SLOT
========================= */
foreach ($slots as $slot) {

  $present = $slot['users'];
  $missing = array_diff(array_keys($members), $present);

  if (!validSlot($missing, $members)) continue;

  $missing_count = count($missing);

  if ($missing_count === 0) $score = 5;
  elseif ($missing_count === 1) $score = 3;
  elseif ($missing_count === 2) $score = 1;
  else continue;

  if ($score > $best_score) {
    $best_score = $score;
    $best = [
      'date' => $slot['date'],
      'time_slot' => $slot['time_slot'],
      'present' => $present
    ];
  }
}

/* =========================
   OUTPUT
========================= */
if (!$best) {
  echo json_encode([
    'status' => 'fail',
    'message' => 'No suitable slot'
  ]);
  exit();
}

list($start, $end) = explode('-', $best['time_slot']);

echo json_encode([
  'status' => 'success',
  'date' => $best['date'],
  'start_time' => $start,
  'end_time' => $end,
  'attendees' => $best['present']
]);
