
<?php
require_once '../db.php';

$result=[];

$res=$conn->query("
SELECT ps.*, s.name
FROM practice_sessions ps
JOIN songs s ON ps.song_id = s.id
");

while($row=$res->fetch_assoc()){
    $result[]=[
        "title"=>$row['name']." ({$row['score']}%)",
        "start"=>$row['date']."T".$row['time_slot']
    ];
}

header('Content-Type: application/json');
echo json_encode($result);