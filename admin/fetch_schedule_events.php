<?php
require_once '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json');

$data = [];

$sql = "
    SELECT
        e.id,
        e.title,
        e.event_date,
        COUNT(ps.id) AS schedule_count
    FROM events e
    JOIN songs s
        ON s.event_id = e.id
    LEFT JOIN practice_sessions ps
        ON ps.song_id = s.id
    WHERE e.event_date >= CURDATE()
    GROUP BY e.id, e.title, e.event_date
    ORDER BY e.event_date ASC, e.id ASC
";

$res = $conn->query($sql);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'event_date' => $row['event_date'],
            'schedule_count' => (int)$row['schedule_count']
        ];
    }
}

echo json_encode($data);