<?php
include 'db.php';
include 'logic/auto_schedule_score.php';
include 'logic/pick_best_slot.php';
include 'logic/create_practice_event.php';
include 'logic/notify_members.php';

$timeSlots = [
 '09:00-12:00','13:00-16:00','16:00-17:00',
 '17:00-18:00','18:00-19:00','19:00-20:00','20:00-21:00'
];

$weekStart = date('Y-m-d', strtotime("monday this week"));

$songs = $conn->query("SELECT * FROM songs");

while ($song = $songs->fetch_assoc()) {

  // สมาชิกเพลง
  $songMembers = [];
  $res = $conn->query("
    SELECT u.id user_id, u.part, u.instrument
    FROM song_members sm
    JOIN users u ON sm.user_id=u.id
    WHERE sm.song_id={$song['id']}
  ");
  while($r=$res->fetch_assoc()) $songMembers[]=$r;

  $candidates = [];

  for($d=0;$d<7;$d++){
    $date = date('Y-m-d', strtotime("+$d day", strtotime($weekStart)));

    foreach($timeSlots as $slot){

      $avail = [];
      $a = $conn->query("
        SELECT DISTINCT user_id FROM availability
        WHERE date='$date' AND time_slot='$slot'
      ");
      while($x=$a->fetch_assoc()) $avail[]=$x['user_id'];

      $score = scoreSlot($songMembers, $avail);
      if ($score >= 0) {
        $candidates[] = [
          'date'=>$date,
          'time_slot'=>$slot,
          'score'=>$score,
          'available'=>$avail
        ];
      }
    }
  }

  $best = pickBestSlot($candidates);
  if (!$best) continue;

  $eventId = createPracticeEvent($conn, $song, $best);

  notifyMembers(
    $conn,
    $songMembers,
    $best['available'],
    [
      'title'=>"Practice: ".$song['title'],
      'date'=>$best['date'],
      'time_slot'=>$best['time_slot']
    ]
  );
}
