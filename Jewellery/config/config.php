<?php
$host = "localhost";
$user = "root";
$pass = "";

// DB user
$conn_user = new mysqli($host, $user, $pass, "user_db");

// DB chính
$conn = new mysqli($host, $user, $pass, "jewelry_db");

// Check lỗi user DB
if ($conn_user->connect_error) {
    die("User DB error: " . $conn_user->connect_error);
}

// Check lỗi main DB
if ($conn->connect_error) {
    die("Main DB error: " . $conn->connect_error);
}

// Charset (chỉ chạy khi OK)
$conn->set_charset("utf8mb4");
$conn_user->set_charset("utf8mb4");
?>