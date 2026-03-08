<?php

function calculate_readiness($song_id,$date,$time,$conn){

    // 🔹 ดึงสมาชิกของเพลงนี้
    $members = $conn->query("
        SELECT u.id, u.display_name
        FROM song_members sm
        JOIN users u ON sm.user_id = u.id
        WHERE sm.song_id = $song_id
    ");

    $total = 0;
    $available = 0;
    $missing = [];

    while($m = $members->fetch_assoc()){

        $total++;

        // 🔹 เช็ค availability ว่าวันนี้ + เวลานี้ ว่างไหม
        $check = $conn->query("
            SELECT id FROM availability
            WHERE user_id = {$m['id']}
            AND date = '$date'
            AND time_slot = '$time'
        ");

        if($check->num_rows > 0){
            $available++;
        } else {
            $missing[] = $m['display_name'];
        }
    }

    $score = $total > 0 ? $available / $total : 0;

    return [
        "score" => $score,              // 0–1
        "available" => $available,
        "total" => $total,
        "missing" => $missing           // array รายชื่อ
    ];
}