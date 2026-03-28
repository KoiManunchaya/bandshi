<?php
require_once '../db.php';
require_once 'auto_schedule_score.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json');

$mode = $_GET['mode'] ?? '';
$event_id = (int)($_GET['event_id'] ?? 0);
$song_id = (int)($_GET['song_id'] ?? 0);

if ($mode === 'songs') {
    if ($event_id <= 0) {
        echo json_encode([]);
        exit;
    }

    $data = [];

    $stmt = $conn->prepare("
        SELECT id, name, rehearsal_progress AS progress
        FROM songs
        WHERE event_id = ?
        ORDER BY name ASC, id ASC
    ");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

if ($mode === 'suggestions') {
    if ($event_id <= 0 || $song_id <= 0) {
        echo json_encode([]);
        exit;
    }

    $stmtSong = $conn->prepare("
        SELECT id, name
        FROM songs
        WHERE id = ? AND event_id = ?
        LIMIT 1
    ");
    $stmtSong->bind_param("ii", $song_id, $event_id);
    $stmtSong->execute();
    $songRes = $stmtSong->get_result();
    $song = $songRes->fetch_assoc();

    if (!$song) {
        echo json_encode([]);
        exit;
    }

    $results = [];
    $timeSlots = [
        ['16:00-17:00', '16:00:00', '17:00:00'],
        ['17:00-18:00', '17:00:00', '18:00:00'],
        ['18:00-19:00', '18:00:00', '19:00:00'],
        ['19:00-20:00', '19:00:00', '20:00:00'],
        ['20:00-21:00', '20:00:00', '21:00:00']
    ];

    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("+$i day"));

        foreach ($timeSlots as $slot) {
            [$slotLabel, $startTime, $endTime] = $slot;

            $readiness = calculate_readiness($song_id, $date, $slotLabel, $conn);

            if ($readiness['total'] <= 0) {
                continue;
            }

            $results[] = [
                "song_id" => (int)$song['id'],
                "song_name" => $song['name'],
                "time_slot" => $slotLabel,
                "start" => $date . 'T' . $startTime,
                "end" => $date . 'T' . $endTime,
                "ready_count" => $readiness['available'],
                "total_count" => $readiness['total'],
                "missing_members" => $readiness['missing'],
                "sub_members" => []
            ];
        }
    }

    usort($results, function ($a, $b) {
        $ra = $a['total_count'] > 0 ? $a['ready_count'] / $a['total_count'] : 0;
        $rb = $b['total_count'] > 0 ? $b['ready_count'] / $b['total_count'] : 0;

        if ($ra == $rb) {
            return strcmp($a['start'], $b['start']);
        }

        return $rb <=> $ra;
    });

    echo json_encode($results);
    exit;
}

echo json_encode([]);