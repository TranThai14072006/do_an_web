<?php
$host = "localhost";
$user = "root";
$pass = "";

// Chỉ dùng 1 DB duy nhất: jewelry_db (đã bao gồm users & customers)
$conn = new mysqli($host, $user, $pass, "jewelry_db");

if ($conn->connect_error) {
    die("Connection error: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>