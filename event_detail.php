<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

$user_id  = $_SESSION['user_id'] ?? 0;
$event_id = intval($_GET['id'] ?? 0);

if (!$event_id) die("Invalid event");

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
if (!$event) die("Event not found");

$stmt = $conn->prepare("
  SELECT id FROM event_join
  WHERE user_id = ? AND event_id = ?
");
$stmt->bind_param("ii", $user_id, $event_id);
$stmt->execute();
$is_joined = $stmt->get_result()->num_rows > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (isset($_POST['join']) && !$is_joined) {
    $stmt = $conn->prepare("
      INSERT INTO event_join (user_id, event_id)
      VALUES (?, ?)
    ");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    header("Location: event_detail.php?id=$event_id");
    exit;
  }

  if (isset($_POST['unjoin']) && $is_joined) {
    $stmt = $conn->prepare("
      DELETE FROM event_join
      WHERE user_id = ? AND event_id = ?
    ");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    header("Location: event_detail.php?id=$event_id");
    exit;
  }
}

$stmt = $conn->prepare("
  SELECT COUNT(*) AS total
  FROM event_join
  WHERE event_id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$count = $stmt->get_result()->fetch_assoc()['total'];

$members = $conn->query("
  SELECT u.display_name, u.profile_pic
  FROM event_join ej
  JOIN users u ON ej.user_id = u.id
  WHERE ej.event_id = $event_id
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($event['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
  background:#000;
  color:#fff;
  font-family:system-ui;
}

.event-card{
  max-width:1000px;
  margin:60px auto;
  background:#1f1f1f;
  border-radius:24px;
  padding:32px;
  border:1px solid #2a2a2a;
}

.avatar{
  width:60px;
  height:60px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:700;
  font-size:18px;
  color:#fff;
  background:#333;
  border:2px solid #00c2ff;
  overflow:hidden;
}

.avatar img{
  width:100%;
  height:100%;
  object-fit:cover;
}

.joined-grid{
  display:flex;
  gap:20px;
  flex-wrap:wrap;
}

.btn-join{
  background:#e84c88;
  color:#fff;
  border:none;
  border-radius:999px;
  padding:10px 28px;
  font-weight:600;
}

.btn-unjoin{
  background:#00c2ff;
  color:#000;
  border:none;
  border-radius:999px;
  padding:10px 28px;
  font-weight:600;
}

.badge-joined{
  background:#00c2ff;
  color:#000;
  padding:6px 14px;
  border-radius:999px;
  font-weight:600;
  margin-left:12px;
}

.event-footer{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-top:24px;
}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="event-card">

  <div class="d-flex align-items-center mb-3">
    <h3><?= htmlspecialchars($event['title']) ?></h3>
    <?php if ($is_joined): ?>
      <span class="badge-joined">Joined</span>
    <?php endif; ?>
  </div>

  <p>📅 <?= $event['event_date'] ?>
     <?= substr($event['start_time'],0,5) ?>–<?= substr($event['end_time'],0,5) ?></p>

  <p>📍 <?= htmlspecialchars($event['location']) ?></p>

  <hr>

  <h5 class="mb-3">Members</h5>

  <?php if ($count == 0): ?>
    <p class="text-secondary">No one joined yet</p>
  <?php else: ?>
    <div class="joined-grid">
      <?php while ($m = $members->fetch_assoc()): ?>
        <div class="text-center">

          <div class="avatar">
            <?php if (!empty($m['profile_pic'])): ?>
              <img src="/bandshi/uploads/profile/<?= htmlspecialchars($m['profile_pic']) ?>">
            <?php else: ?>
              <?= strtoupper(substr($m['display_name'],0,1)) ?>
            <?php endif; ?>
          </div>

          <small><?= htmlspecialchars($m['display_name']) ?></small>

        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>

  <div class="event-footer">
    <span>👥 <?= $count ?> joined</span>

    <form method="post">
      <?php if ($is_joined): ?>
        <button class="btn-unjoin" name="unjoin">Unjoin</button>
      <?php else: ?>
        <button class="btn-join" name="join">Join</button>
      <?php endif; ?>
    </form>
  </div>

</div>

</body>
</html>