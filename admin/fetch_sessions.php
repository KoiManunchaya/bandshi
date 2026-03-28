<?php
require_once '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json');

$event_id = (int)($_GET['event_id'] ?? 0);

if ($event_id <= 0) {
    echo json_encode([]);
    exit;
}

$result = [];

$stmt = $conn->prepare("
    SELECT ps.id, ps.song_id, ps.date, ps.time_slot, ps.score, s.name
    FROM practice_sessions ps
    JOIN songs s ON ps.song_id = s.id
    WHERE s.event_id = ?
    ORDER BY ps.date ASC, ps.time_slot ASC, ps.id ASC
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $times = explode('-', $row['time_slot']);
    if (count($times) !== 2) {
        continue;
    }

    $startTime = $times[0] . ':00';
    $endTime = $times[1] . ':00';

    $result[] = [
        "id" => (int)$row['id'],
        "song_id" => (int)$row['song_id'],
        "title" => $row['name'],
        "start" => $row['date'] . 'T' . $startTime,
        "end" => $row['date'] . 'T' . $endTime,
        "score" => (int)$row['score']
    ];
}

echo json_encode($result);