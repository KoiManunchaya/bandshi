<?php
session_start();
require_once '../db.php';
require_once 'admin_guard.php';

$error = '';
$edit_id = $_GET['edit'] ?? null;

/* ================= DELETE ================= */
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $conn->query("DELETE FROM announcements WHERE id=$id");
  header("Location: admin_announcements.php");
  exit();
}

/* ================= POST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $title   = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $id      = $_POST['id'] ?? null;

  if ($title === '' || $content === '') {
    $error = "Title and content are required.";
  } else {

    $image_name = null;

    if (!empty($_FILES['image']['name'])) {
      $allowed = ['jpg','jpeg','png','webp'];
      $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

      if (in_array($ext,$allowed)) {

        $dir = '../uploads/announcements/';
        if (!is_dir($dir)) mkdir($dir,0777,true);

        $image_name = uniqid('ann_').'.'.$ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $dir.$image_name);
      }
    }

    if ($id) {
      if ($image_name) {
        $stmt = $conn->prepare("
          UPDATE announcements
          SET title=?, content=?, image=?
          WHERE id=?
        ");
        $stmt->bind_param("sssi",$title,$content,$image_name,$id);
      } else {
        $stmt = $conn->prepare("
          UPDATE announcements
          SET title=?, content=?
          WHERE id=?
        ");
        $stmt->bind_param("ssi",$title,$content,$id);
      }
    } else {
      $stmt = $conn->prepare("
        INSERT INTO announcements (title, content, image)
        VALUES (?, ?, ?)
      ");
      $stmt->bind_param("sss",$title,$content,$image_name);
    }

    $stmt->execute();
    header("Location: admin_announcements.php");
    exit();
  }
}

/* ================= LOAD EDIT ================= */
$edit_data = null;
if ($edit_id) {
  $stmt = $conn->prepare("SELECT * FROM announcements WHERE id=?");
  $stmt->bind_param("i",$edit_id);
  $stmt->execute();
  $edit_data = $stmt->get_result()->fetch_assoc();
}

/* ================= LOAD LIST ================= */
$list = $conn->query("
  SELECT *
  FROM announcements
  ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin - Announcements</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
  background:#0f1115;
  color:#ffffff;
}

.card-dark {
  background:#1c1f26;
  border-radius:20px;
}

label {
  color:#ffffff;
  font-weight:600;
}

.form-control {
  background:#2b2f36;
  color:#ffffff;
  border:none;
  border-radius:12px;
}

.form-control:focus {
  background:#2b2f36;
  color:#ffffff;
  box-shadow:none;
}

.form-control::placeholder {
  color:#888;
}

/* ===== Announcement Card ===== */

.announcement-card {
  background:#20242c;
  padding:24px;
  border-radius:16px;
  margin-bottom:20px;
  border:1px solid #2d323c;
}

.announcement-title {
  font-size:18px;
  font-weight:600;
  color:#ffffff;
}

.announcement-date {
  font-size:12px;
  color:#9aa3b2;
  margin-bottom:10px;
}

.announcement-content {
  color:#d6dbe4;
  margin-top:8px;
  line-height:1.6;
}

.btn-outline-light {
  border-color:#444;
  color:#ffffff;
}

.btn-outline-light:hover {
  background:#ffffff;
  color:#000;
}

hr {
  border-color:#333;
}
</style>
</head>

<body class="p-4">

<a href="index.php" class="btn btn-sm btn-outline-light mb-4">
  ← Admin Home
</a>

<h2 class="mb-4">Announcements</h2>

<!-- ================= FORM ================= -->
<div class="card card-dark p-4 mb-4">

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">

    <div class="mb-3">
      <label>Title</label>
      <input name="title"
             class="form-control"
             value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>"
             required>
    </div>

    <div class="mb-3">
      <label>Content</label>
      <textarea name="content"
                rows="4"
                class="form-control"
                required><?= htmlspecialchars($edit_data['content'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
      <label>Image (optional)</label>
      <input type="file" name="image" class="form-control">
    </div>

    <button class="btn btn-primary">
      <?= $edit_data ? 'Update Announcement' : 'Post Announcement' ?>
    </button>

    <?php if ($edit_data): ?>
      <a href="admin_announcements.php" class="btn btn-secondary ms-2">
        Cancel
      </a>
    <?php endif; ?>
  </form>

</div>

<!-- ================= LIST ================= -->

<h4 class="mb-3">All Announcements</h4>

<?php if (!$list || $list->num_rows === 0): ?>
  <p style="color:#9aa3b2;">No announcements yet.</p>
<?php else: ?>

  <?php while($a = $list->fetch_assoc()): ?>

    <div class="announcement-card">

      <div class="announcement-title">
        <?= htmlspecialchars($a['title']) ?>
      </div>

      <div class="announcement-date">
        <?= $a['created_at'] ?>
      </div>

      <div class="announcement-content">
        <?= nl2br(htmlspecialchars($a['content'])) ?>
      </div>

      <?php if (!empty($a['image'])): ?>
        <div class="mt-3">
          <img src="../uploads/announcements/<?= $a['image'] ?>"
               style="max-width:320px; border-radius:12px;">
        </div>
      <?php endif; ?>

      <div class="mt-4">
        <a href="?edit=<?= $a['id'] ?>"
           class="btn btn-sm btn-outline-light">
           Edit
        </a>

        <a href="?delete=<?= $a['id'] ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Delete this announcement?')">
           Delete
        </a>
      </div>

    </div>

  <?php endwhile; ?>

<?php endif; ?>

</body>
</html>