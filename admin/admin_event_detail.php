<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/admin_guard.php';

$event_id = $_GET['id'] ?? null;
if (!$event_id) {
  die('Invalid event');
}

/* =====================
   CLOSE JOIN
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_join'])) {
  $stmt = $conn->prepare("
    UPDATE events
    SET status = 'closed'
    WHERE id = ?
  ");
  $stmt->bind_param("i", $event_id);
  $stmt->execute();

  header("Location: admin_event_detail.php?id=" . $event_id);
  exit();
}

/* =====================
   REOPEN JOIN
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reopen_join'])) {
  $stmt = $conn->prepare("
    UPDATE events
    SET status = 'open'
    WHERE id = ?
  ");
  $stmt->bind_param("i", $event_id);
  $stmt->execute();

  header("Location: admin_event_detail.php?id=" . $event_id);
  exit();
}

/* =====================
   FETCH EVENT
===================== */
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
  die('Event not found');
}

/* =====================
   FETCH JOINED MEMBERS
===================== */
$stmt = $conn->prepare("
  SELECT u.full_name, u.part, u.instrument
  FROM event_members em
  JOIN users u ON em.user_id = u.id
  WHERE em.event_id = ?
  ORDER BY u.full_name
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Event Detail | Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
  background:#1f232a;
  color:#ffffff;
}

h2, h5, p, li, span {
  color:#ffffff !important;
}

.text-secondary {
  color:#ffffff !important;
  opacity:0.75;
}

.card-dark {
  background:#2b2f36;
  border-radius:16px;
}

.alert-warning,
.alert-success {
  background:#3a3f47;
  border:none;
  color:#ffffff;
}

ul {
  list-style:none;
  padding-left:0;
}

li {
  padding:6px 0;
}
</style>
</head>

<body>

<div class="container py-5">

  <a href="admin_events.php" class="btn btn-outline-light mb-4">
    ← Back
  </a>

  <h2><?= htmlspecialchars($event['title']) ?></h2>
  <p class="text-secondary mb-4">
    <?= htmlspecialchars($event['event_date']) ?>
    <?php if ($event['start_time'] && $event['end_time']): ?>
      | <?= $event['start_time'] ?>–<?= $event['end_time'] ?>
    <?php endif; ?>
    <br>
    <?= htmlspecialchars($event['location']) ?>
  </p>

  <!-- JOINED MEMBERS -->
  <div class="card card-dark p-4 mb-4">
    <h5>Joined Members (<?= count($members) ?>)</h5>

    <?php if (empty($members)): ?>
      <p>No members joined yet.</p>
    <?php else: ?>
      <ul class="mb-0">
        <?php foreach ($members as $m): ?>
          <li>
            <?= htmlspecialchars($m['full_name']) ?>
            (<?= htmlspecialchars($m['part']) ?>
            <?= $m['instrument'] !== 'none' ? ' / ' . htmlspecialchars($m['instrument']) : '' ?>)
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <!-- STATUS -->
  <div class="card card-dark p-4">
    <h5>Status</h5>

    <?php if ($event['status'] === 'open'): ?>

      <div class="alert alert-warning">
        Event is open for join
      </div>

      <form method="post">
        <button name="close_join"
                class="btn btn-warning"
                onclick="return confirm('Are you sure you want to close joining?')">
          Close Join
        </button>
      </form>

    <?php else: ?>

      <div class="alert alert-success">
        Join closed — ready for song assignment
      </div>

      <form method="post" class="d-inline me-2">
        <button name="reopen_join"
                class="btn btn-outline-light"
                onclick="return confirm('Reopen joining for this event?')">
          Reopen Join
        </button>
      </form>

      <a href="admin_songs.php?event_id=<?= $event_id ?>"
         class="btn btn-success">
        Manage Songs
      </a>

    <?php endif; ?>
  </div>

</div>

</body>
</html>