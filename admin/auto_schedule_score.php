<?php

function calculate_readiness($song_id, $date, $time_slot, $conn)
{
    $song_id = (int)$song_id;
    $date = $conn->real_escape_string($date);
    $time_slot = $conn->real_escape_string($time_slot);

    $members = $conn->query("
        SELECT u.id, u.display_name
        FROM song_members sm
        JOIN users u ON sm.user_id = u.id
        WHERE sm.song_id = $song_id
        ORDER BY u.display_name ASC, u.id ASC
    ");

    if (!$members) {
        return [
            "score" => 0,
            "available" => 0,
            "total" => 0,
            "missing" => []
        ];
    }

    $total = 0;
    $available = 0;
    $missing = [];

    while ($m = $members->fetch_assoc()) {
        $total++;
        $userId = (int)$m['id'];

        $check = $conn->query("
            SELECT id
            FROM availability
            WHERE user_id = $userId
              AND date = '$date'
              AND time_slot = '$time_slot'
            LIMIT 1
        ");

        if ($check && $check->num_rows > 0) {
            $available++;
        } else {
            $missing[] = $m['display_name'];
        }
    }

    return [
        "score" => $total > 0 ? $available / $total : 0,
        "available" => $available,
        "total" => $total,
        "missing" => $missing
    ];
}