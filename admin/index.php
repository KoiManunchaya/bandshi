<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/admin_guard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Home | BANdSHI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
  background:#1f232a;
  color:#fff;
}

.card-admin {
  background:#2b2f36;
  border-radius:18px;
  padding:24px;
  transition: all .2s ease;
  height: 100%;
}

.card-admin:hover {
  transform: translateY(-4px);
  background:#323742;
}

.card-admin h5 {
  margin-bottom:8px;
}

.card-admin p {
  color:#bfc3c7;
  font-size:14px;
}
</style>
</head>

<body>
<a href="/bandshi/admin/admin_logout.php"
   class="btn btn-sm btn-outline-danger">
   Logout
</a>
</div>

<div class="container py-5">

  <h2 class="mb-4">Admin Dashboard</h2>
  <p class="text-secondary mb-5">
    Manage events, songs, members, and scheduling from one place.
  </p>

  <div class="row g-4">

    <!-- EVENTS -->
    <div class="col-md-6 col-lg-4">
      <a href="admin_events.php" class="text-decoration-none text-white">
        <div class="card-admin">
          <h5>Events</h5>
          <p>Create / edit events, view details, manage schedules</p>
        </div>
      </a>
    </div>

    <!-- SONGS -->
    <div class="col-md-6 col-lg-4">
      <a href="admin_songs.php" class="text-decoration-none text-white">
        <div class="card-admin">
          <h5>Songs</h5>
          <p>Manage songs, assign members, prepare rehearsals</p>
        </div>
      </a>
    </div>

    <!-- ANNOUNCEMENTS -->
    <div class="col-md-6 col-lg-4">
      <a href="admin_announcements.php" class="text-decoration-none text-white">
        <div class="card-admin">
          <h5>Announcements</h5>
          <p>Post announcements and updates for members</p>
        </div>
      </a>
    </div>

    <!-- SET SCHEDULE (OPTIONAL LINK) -->
    <div class="col-md-6 col-lg-4">
  <a href="schedules.php" class="text-decoration-none text-white">
    <div class="card-admin">
      <h5>Set Schedule</h5>
      <p>Review schedule suggestions and confirm practice sessions</p>
    </div>
  </a>
</div>

    <!-- REPORT / DASHBOARD -->
    <div class="col-md-6 col-lg-4">
      <a href="dashboard.php" class="text-decoration-none text-white">
        <div class="card-admin">
          <h5>Dashboard</h5>
          <p>Overview of activities and participation</p>
        </div>
      </a>
    </div>

  </div>

</div>

</body>
</html>
