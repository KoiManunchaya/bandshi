<?php
require_once '../db.php';
require_once 'auto_schedule_score.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);

$event_id = (int)($input['event_id'] ?? 0);
$schedules = $input['schedules'] ?? [];

if ($event_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid event"
    ]);
    exit;
}

$conn->begin_transaction();

try {
    $songIds = [];
    $stmtSongIds = $conn->prepare("SELECT id FROM songs WHERE event_id = ?");
    $stmtSongIds->bind_param("i", $event_id);
    $stmtSongIds->execute();
    $songRes = $stmtSongIds->get_result();

    while ($row = $songRes->fetch_assoc()) {
        $songIds[] = (int)$row['id'];
    }

    if (!empty($songIds)) {
        $idList = implode(',', $songIds);
        $conn->query("DELETE FROM practice_sessions WHERE song_id IN ($idList)");
    }

    $stmtInsert = $conn->prepare("
        INSERT INTO practice_sessions (song_id, date, time_slot, score)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($schedules as $item) {
        $song_id = (int)($item['song_id'] ?? 0);

        $start = $item['start'] ?? ($item['start_time'] ?? '');
        $end = $item['end'] ?? ($item['end_time'] ?? '');

        if ($song_id <= 0 || empty($start) || empty($end)) {
            continue;
        }

        $checkSong = $conn->prepare("
            SELECT id
            FROM songs
            WHERE id = ? AND event_id = ?
            LIMIT 1
        ");
        $checkSong->bind_param("ii", $song_id, $event_id);
        $checkSong->execute();
        $checkRes = $checkSong->get_result();

        if (!$checkRes->fetch_assoc()) {
            continue;
        }

        $startTs = strtotime($start);
        $endTs = strtotime($end);

        if (!$startTs || !$endTs || $endTs <= $startTs) {
            continue;
        }

        $date = date('Y-m-d', $startTs);
        $time_slot = date('H:i', $startTs) . '-' . date('H:i', $endTs);

        $readiness = calculate_readiness($song_id, $date, $time_slot, $conn);
        $score = $readiness['total'] > 0
            ? (int) round(($readiness['available'] / $readiness['total']) * 100)
            : 0;

        $stmtInsert->bind_param("issi", $song_id, $date, $time_slot, $score);
        $stmtInsert->execute();
    }

    $conn->commit();

    echo json_encode([
        "success" => true
    ]);
} catch (Throwable $e) {
    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => "Save failed"
    ]);
}