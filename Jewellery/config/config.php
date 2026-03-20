<?php
$host = "localhost";
$user = "root";
$pass = "";

// Kết nối DB user
$conn_user = new mysqli($host, $user, $pass, "user_db");

// Kết nối DB chính
$conn = new mysqli($host, $user, $pass, "jewelry_db");

// Check lỗi
if ($conn_user->connect_error) {
    die("User DB error: " . $conn_user->connect_error);
}

if ($conn->connect_error) {
    die("Main DB error: " . $conn->connect_error);
}

// Set charset (rất quan trọng)
$conn->set_charset("utf8mb4");
$conn_user->set_charset("utf8mb4");
?>