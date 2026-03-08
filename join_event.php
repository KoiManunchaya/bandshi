<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

/*
|--------------------------------------------------------------------------
| Ensure user is logged in
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    die('Invalid event.');
}

/*
|--------------------------------------------------------------------------
| Ensure event is open for joining
|--------------------------------------------------------------------------
*/
if ($event['status'] !== 'open') {
  die('Event closed');
}

/*
|--------------------------------------------------------------------------
| Step 1: Check event capacity
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT capacity, COUNT(em.user_id) AS joined
    FROM events e
    LEFT JOIN event_members em ON e.id = em.event_id
    WHERE e.id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    die('Event not found.');
}

if ($event['joined'] >= $event['capacity']) {
    die('This event is already full.');
}

/*
|--------------------------------------------------------------------------
| Step 2: Check time conflict with existing joined events
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT 1
    FROM events e
    JOIN event_members em ON e.id = em.event_id
    JOIN events target ON target.id = ?
    WHERE em.user_id = ?
      AND e.start_time < target.end_time
      AND e.end_time > target.start_time
");
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();

if ($stmt->get_result()->fetch()) {
    die('You already joined another event at this time.');
}

/*
|--------------------------------------------------------------------------
| Step 3: Join event (ignore duplicate join)
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    INSERT IGNORE INTO event_members (event_id, user_id)
    VALUES (?, ?)
");
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();

/*
|--------------------------------------------------------------------------
| Step 4: Redirect back to events page
|--------------------------------------------------------------------------
*/
header("Location: events.php");
exit();
