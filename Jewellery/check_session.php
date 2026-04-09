<?php
// Check current session and user login status
session_start();

echo "Session Status Check:\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID in session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";

if (isset($_SESSION['user_id'])) {
    include 'config/config.php';

    $user_id = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT u.username, c.full_name FROM users u LEFT JOIN customers c ON u.id = c.user_id WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        echo "User found: " . $user['username'] . " (" . $user['full_name'] . ")\n";
    } else {
        echo "User not found in database!\n";
    }
    $stmt->close();
}

echo "\nRecent session messages:\n";
echo "Cancel msg: " . (isset($_SESSION['cancel_msg']) ? $_SESSION['cancel_msg'] : 'none') . "\n";
echo "Cancel error: " . (isset($_SESSION['cancel_error']) ? $_SESSION['cancel_error'] : 'none') . "\n";
echo "Receive msg: " . (isset($_SESSION['receive_msg']) ? $_SESSION['receive_msg'] : 'none') . "\n";
echo "Receive error: " . (isset($_SESSION['receive_error']) ? $_SESSION['receive_error'] : 'none') . "\n";
?>