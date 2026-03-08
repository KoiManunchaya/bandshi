<?php
session_start();

$_SESSION = [];
session_destroy();

/* กลับไป root index */
header("Location: /bandshi/index.php");
exit();