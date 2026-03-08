<?php
function createPracticeEvent($conn, $song, $bestSlot) {

  $stmt = $conn->prepare("
    INSERT INTO events (title, song_id, date, time_slot)
    VALUES (?, ?, ?, ?)
  ");

  $title = "Practice: ".$song['title'];
  $stmt->bind_param(
    "siss",
    $title,
    $song['id'],
    $bestSlot['date'],
    $bestSlot['time_slot']
  );
  $stmt->execute();

  return $conn->insert_id;
}
