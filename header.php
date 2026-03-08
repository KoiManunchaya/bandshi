<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$current = basename($_SERVER['PHP_SELF']);
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<nav class="main-nav">
  <div class="main-nav-inner">

    <!-- LEFT -->
    <div class="main-left">
      <a href="home.php" class="main-logo">
        <span class="logo-pink">BANd.</span><span class="logo-blue">SHI</span>
      </a>
    </div>

    <!-- CENTER -->
    <div class="main-center">
      <a href="home.php" class="<?= $current==='home.php'?'active':'' ?>">Home</a>

      <a href="events.php"
         class="<?= str_contains($current,'event')?'active':'' ?>">
         Events
      </a>

      <a href="availability.php"
         class="<?= $current==='availability.php'?'active':'' ?>">
         Availability
      </a>

      <a href="my_schedule.php"
         class="<?= $current==='my_schedule.php'?'active':'' ?>">
         My Schedule
      </a>
    </div>

    <!-- RIGHT -->
    <div class="main-right">
      <a href="announcements.php"
         class="<?= $current==='announcements.php' || $current==='announcement_detail.php' ? 'active-icon' : '' ?>">
         <i class="bi bi-megaphone-fill"></i>
      </a>

      <a href="profile.php"
         class="<?= $current==='profile.php' ? 'active-icon' : '' ?>">
         <i class="bi bi-person-circle"></i>
      </a>
    </div>

  </div>
</nav>

<style>
.main-nav{
  position:fixed;
  top:0;
  left:0;
  width:100%;
  height:64px;
  background:#000;
  border-bottom:1px solid #222;
  z-index:9999;
}

.main-nav-inner{
  height:64px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 40px;
}

/* LEFT */
.main-logo{
  font-weight:800;
  font-size:20px;
  text-decoration:none;
}

.logo-pink{ color:#e84c88; }
.logo-blue{ color:#5bc0eb; }

/* CENTER */
.main-center{
  display:flex;
  gap:32px;
}

.main-center a{
  color:#aaa;
  text-decoration:none;
  font-weight:600;
  padding-bottom:4px;
}

.main-center a:hover{
  color:#fff;
}

.main-center a.active{
  color:#e84c88;
  border-bottom:2px solid #e84c88;
}

/* RIGHT */
.main-right{
  display:flex;
  gap:20px;
}

.main-right a{
  color:#aaa;
  font-size:24px;
  transition:.2s ease;
}

.main-right a:hover{
  transform:scale(1.15);
  opacity:.8;
}

/* ACTIVE ICON */
.main-right a.active-icon{
  color:#e84c88;
}
</style>