<?php
require_once 'db.php';

if (!isset($_GET['token'])) {
    die("Invalid verification link.");
}

$token = $_GET['token'];

/* ===== หา user ===== */

$stmt = $conn->prepare("SELECT id,email_verified FROM users WHERE verify_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

/* ===== token ไม่ถูก ===== */

if (!$user) {
    die("Invalid or expired verification link.");
}

/* ===== ถ้า verify แล้ว ===== */

if ($user['email_verified'] == 1) {
    header("Location: login.php?verified=1");
    exit;
}

/* ===== update verify ===== */

$update = $conn->prepare("UPDATE users SET email_verified = 1, verify_token = NULL WHERE id = ?");
$update->bind_param("i", $user['id']);
$update->execute();

/* ===== redirect ===== */

header("Location: verify_success.php");
exit;