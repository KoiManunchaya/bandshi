<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BANdSHI</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Optional: icon -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background: #000;
      color: #fff;
    }
    .card-dark {
      background: #2f2f2f;
      border-radius: 16px;
    }
    .btn-pink {
      background: #e84c88;
      color: #fff;
      border-radius: 12px;
    }
    .btn-pink:hover {
      background: #d23c76;
    }
  </style>
</head>

<?php
include 'auth.php';
include 'db.php';

$result = $conn->query("SELECT id,name,email,role FROM users");
?>

<h2>Member Management</h2>

<table border="1" cellpadding="8">
<tr>
  <th>Name</th>
  <th>Email</th>
  <th>Role</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
  <td><?= $row['name'] ?></td>
  <td><?= $row['email'] ?></td>
  <td><?= $row['role'] ?></td>
</tr>
<?php endwhile; ?>
</table>

<br>
<a href="dashboard.php">Back</a>




<div class="bottom-nav">
  <a href="dashboard.php" class="active">
    <i class="bi bi-house"></i>
    Home
  </a>

  <a href="events.php">
    <i class="bi bi-calendar-event"></i>
    Event
  </a>

  <a href="profile.php">
    <i class="bi bi-person"></i>
    Profile
  </a>
</div>
