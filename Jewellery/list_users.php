<?php
require_once 'config/config.php';
$res = $conn->query("SELECT id, username, email, role, status FROM users");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | User: {$row['username']} | Email: {$row['email']} | Role: {$row['role']} | Status: {$row['status']}\n";
}
?>
