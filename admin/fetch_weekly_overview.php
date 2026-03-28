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

$event_id = (int)($_GET['event_id'] ?? 0);

if ($event_id <= 0) {
    echo json_encode([]);
    exit;
}

$result = [];

$stmt = $conn->prepare("
    SELECT
        ps.id,
        ps.song_id,
        ps.date,
        ps.time_slot,
        ps.score,
        s.name AS song_name,
        s.rehearsal_progress AS song_progress,
        e.title AS event_title
    FROM practice_sessions ps
    JOIN songs s
        ON ps.song_id = s.id
    JOIN events e
        ON s.event_id = e.id
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

    $startRaw = trim($times[0]);
    $endRaw = trim($times[1]);

    $startLabel = substr($startRaw, 0, 5);
    $endLabel = substr($endRaw, 0, 5);

    $readiness = calculate_readiness(
        (int)$row['song_id'],
        $row['date'],
        $row['time_slot'],
        $conn
    );

    $missingMembers = $readiness['missing'] ?? [];
    if (!is_array($missingMembers)) {
        $missingMembers = [];
    }

    $result[] = [
        'id' => (int)$row['id'],
        'event_title' => $row['event_title'],
        'date' => $row['date'],
        'start' => $startLabel,
        'end' => $endLabel,
        'song' => $row['song_name'],
        'score' => (int)$row['score'],
        'song_progress' => (int)$row['song_progress'],
        'ready_count' => (int)($readiness['available'] ?? 0),
        'total_count' => (int)($readiness['total'] ?? 0),
        'missing_members' => $missingMembers,
        'sub_members' => []
    ];
}

echo json_encode($result);