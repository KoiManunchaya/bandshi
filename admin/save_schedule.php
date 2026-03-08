<?php
require_once '../db.php';
session_start();

$data = json_decode(file_get_contents("php://input"), true);

foreach($data as $item){

    $song_id = intval($item['song_id']);
    $timestamp = strtotime($item['start']);

    $date = date('Y-m-d',$timestamp);
    $time_slot = date('H:i',$timestamp);

    $score = 100; // ตอนนี้ default ไว้ก่อน

    $stmt = $conn->prepare("
        INSERT INTO practice_sessions
        (song_id, date, time_slot, score)
        VALUES (?,?,?,?)
    ");
    $stmt->bind_param("issi",$song_id,$date,$time_slot,$score);
    $stmt->execute();
}
echo "ok";