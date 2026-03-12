<?php

$host = $_ENV['MYSQLHOST'] ?? 'localhost';
$user = $_ENV['MYSQLUSER'] ?? 'root';
$pass = $_ENV['MYSQLPASSWORD'] ?? 'root';
$db   = $_ENV['MYSQLDATABASE'] ?? 'bandshi_db';
$port = $_ENV['MYSQLPORT'] ?? 8889;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");