<?php
require_once 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['token'])) {
    die("No token provided.");
}

$token = $_GET['token'];

echo "Token received: " . htmlspecialchars($token) . "<br><br>";

$stmt = $conn->prepare("SELECT id FROM users WHERE verify_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Token not found in database.");
}

echo "User found. Updating...<br>";

$update = $conn->prepare("UPDATE users SET email_verified = 1, verify_token = NULL WHERE id = ?");
$update->bind_param("i", $user['id']);
$update->execute();

echo "Update done. Email verified = 1";