<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/admin_guard.php';

$song_id  = $_GET['song_id'] ?? 0;
$event_id = $_GET['event_id'] ?? 0;

if (!$song_id || !$event_id) {
  header("Location: admin_songs.php");
  exit;
}

/* ======================
   LOAD SONG + EVENT
====================== */
$stmt = $conn->prepare("
  SELECT s.*, e.title AS event_title
  FROM songs s
  JOIN events e ON s.event_id = e.id
  WHERE s.id = ?
");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$song = $stmt->get_result()->fetch_assoc();

if (!$song) die("Song not found");

/* ======================
   LOAD MEMBERS (event participants)
====================== */
$stmt = $conn->prepare("
  SELECT u.id, u.full_name
  FROM event_members em
  JOIN users u ON em.user_id = u.id
  WHERE em.event_id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ======================
   SAVE ASSIGNMENT
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $conn->query("DELETE FROM song_members WHERE song_id = $song_id");

  if (!empty($_POST['slot'])) {
    foreach ($_POST['slot'] as $position => $user_id) {
      if ($user_id) {
        $stmt = $conn->prepare("
          INSERT INTO song_members (song_id, user_id, position)
          VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $song_id, $user_id, $position);
        $stmt->execute();
      }
    }
  }

  header("Location: admin_song_members.php?song_id=$song_id&event_id=$event_id");
  exit;
}

/* ======================
   SLOT STRUCTURE
====================== */
$slots = [
  'musician' => [
    'drum'     => '🥁 Drum',
    'bass'     => '🎸 Bass',
    'guitar1'  => '🎸 Guitar 1',
    'guitar2'  => '🎸 Guitar 2',
    'piano'    => '🎹 Piano',
    'synth'    => '🎛️ Synth',
  ],
  'singer' => [
    'singer1'  => '🎤 Singer 1',
    'singer2'  => '🎤 Singer 2',
    'singer3'  => '🎤 Singer 3',
  ],
];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assign Members</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#1f232a; color:#fff; }
.card-dark { background:#2b2f36; border-radius:18px; }
.section-title { font-weight:700; margin-bottom:20px; font-size:18px; }
.slot-label { font-weight:600; margin-bottom:6px; }
select.form-select {
  background:#1f232a;
  color:#fff;
  border:1px solid #444;
  border-radius:12px;
  height:42px;
}
select.form-select option { color:#000; }
.save-btn {
  background:#22c55e;
  border:none;
  border-radius:12px;
  padding:10px 24px;
}
.save-btn:hover { opacity:.9; }
body {
  background:#1f232a;
  color:#fff;
}

/* บังคับข้อความทั้งหมดใน card เป็นสีขาว */
.card-dark,
.card-dark * {
  color:#fff !important;
}

/* หัวข้อ section */
.section-title {
  font-weight:700;
  font-size:18px;
  color:#fff !important;
}

/* label ของตำแหน่ง */
.slot-label {
  font-weight:600;
  color:#fff !important;
}

/* dropdown */
select.form-select {
  background:#1f232a;
  color:#fff !important;
  border:1px solid #444;
}

select.form-select option {
  color:#000; /* option ด้านในยังคงเป็นดำ */
}

</style>
</head>

<body>

<div class="container py-5">

  <a href="admin_songs.php?event_id=<?= $event_id ?>"
     class="btn btn-sm btn-outline-light mb-4">
    ← Back to Songs
  </a>

  <h2 class="mb-2">Assign Members</h2>

  <div class="mb-4 text-secondary">
    Song: <strong><?= htmlspecialchars($song['name']) ?></strong><br>
    Event: <?= htmlspecialchars($song['event_title']) ?>
  </div>

  <form method="post">

    <div class="card card-dark p-4 mb-4">

      <!-- MUSICIANS -->
      <div class="section-title">🎼 Musicians</div>
      <div class="row mb-4">
        <?php foreach ($slots['musician'] as $key => $label): ?>
          <div class="col-md-6 mb-3">
            <div class="slot-label"><?= $label ?></div>
            <select name="slot[<?= $key ?>]" class="form-select">
              <option value="">— none —</option>
              <?php foreach ($members as $m): ?>
                <option value="<?= $m['id'] ?>">
                  <?= htmlspecialchars($m['full_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- SINGERS -->
      <div class="section-title">🎤 Singers</div>
      <div class="row">
        <?php foreach ($slots['singer'] as $key => $label): ?>
          <div class="col-md-6 mb-3">
            <div class="slot-label"><?= $label ?></div>
            <select name="slot[<?= $key ?>]" class="form-select">
              <option value="">— none —</option>
              <?php foreach ($members as $m): ?>
                <option value="<?= $m['id'] ?>">
                  <?= htmlspecialchars($m['full_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endforeach; ?>
      </div>

    </div>

    <button class="save-btn">
      💾 Save Assignment
    </button>

  </form>

</div>


</body>
</html>
