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

$res = $conn->query("
    SELECT id, title, event_date
    FROM events
    WHERE status = 'closed'
      AND event_date >= CURDATE()
    ORDER BY event_date ASC, id ASC
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);