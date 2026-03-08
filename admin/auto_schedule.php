<?php
require_once '../db.php';
require_once 'auto_schedule_score.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    exit;
}

$weekStart = $_GET['week'] ?? date('Y-m-d');

$slots = [];
$songs = $conn->query("SELECT id,name FROM songs");

while($song = $songs->fetch_assoc()){

    for($d=0;$d<7;$d++){

        $date = date('Y-m-d', strtotime($weekStart." +$d days"));

        foreach(["17:00","18:00","19:00"] as $time){

            $scoreData = calculate_readiness($song['id'],$date,$time,$conn);

            if($scoreData['score'] >= 0.7){ // suggest only 70%+
                $slots[] = [
                    "song_id"=>$song['id'],
                    "song_name"=>$song['name'],
                    "date"=>$date,
                    "time"=>$time,
                    "ready_score"=>$scoreData['score'],
                    "available_core"=>$scoreData['available'],
                    "missing_members"=>$scoreData['missing']
                ];
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($slots);