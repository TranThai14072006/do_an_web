<?php
/**
 * Migration Runner
 * Truy cập file này 1 lần để thêm cột status vào bảng users.
 * URL: http://localhost/Jewellery/database/run_migration.php
 */
require_once '../config/config.php';

echo "<h2 style='font-family:sans-serif;padding:20px;'>Migration: Add status column to users</h2>";
echo "<pre style='font-family:monospace;padding:0 20px;'>";

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
if ($result && $result->num_rows > 0) {
    echo "✅ Column 'status' already exists in table 'users'. No migration needed.\n";
} else {
    // Add the column
    $sql = "ALTER TABLE users ADD COLUMN status ENUM('Active','Locked') NOT NULL DEFAULT 'Active' AFTER created_at";
    if ($conn->query($sql)) {
        echo "✅ Successfully added column 'status' to table 'users'.\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}

echo "\nDone!";
echo "</pre>";
echo "<p style='font-family:sans-serif;padding:0 20px;'><a href='../admin/Administration_menu.php'>→ Go to Admin Panel</a></p>";
?>
