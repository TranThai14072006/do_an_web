<?php
// DB user
$conn_user = new mysqli("localhost", "root", "", "user_db");

// DB hệ thống (sản phẩm, đơn hàng)
$conn_main = new mysqli("localhost", "root", "", "jewelry_db");

if ($conn_user->connect_error) {
    die("User DB error: " . $conn_user->connect_error);
}

if ($conn_main->connect_error) {
    die("Main DB error: " . $conn_main->connect_error);
}
?>