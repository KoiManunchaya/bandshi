<?php
require_once '../db.php';
require_once 'auto_schedule_score.php';
session_start();
if (!isset($_SESSION['user_id'])) exit;

$result=[];

$songs=$conn->query("SELECT id,name,rehearsal_progress FROM songs");

while($song=$songs->fetch_assoc()){

    $date=date('Y-m-d');
    $time="18:00";

    $readiness=calculate_readiness(
        $song['id'],$date,$time,$conn
    );

    $result[]=[
        "id"=>$song['id'],
        "name"=>$song['name'],
        "progress"=>$song['rehearsal_progress'],
        "readiness_score"=>$readiness['score'],
        "suggested_time"=>$date."T18:00:00"
    ];
}

header('Content-Type: application/json');
echo json_encode($result);