<?php
/**
 * full_sync.php
 * One-time script to synchronize all products in the database.
 */
require_once "../config/config.php";
require_once "admin_sync.php";

echo "<h1>Full Inventory & Pricing Synchronization</h1>";
echo "<pre>";

$res = $conn->query("SELECT id, name FROM products");
$count = 0;
while ($row = $res->fetch_assoc()) {
    $pid = $row['id'];
    $name = $row['name'];
    
    if (syncProduct($conn, $pid)) {
        echo "Syncing [{$pid}] {$name} ... OK\n";
        $count++;
    } else {
        echo "Syncing [{$pid}] {$name} ... FAILED\n";
    }
}

echo "\n-----------------------------------\n";
echo "Total synced: {$count} products.\n";
echo "Done.\n";
echo "</pre>";

$conn->close();
?>
