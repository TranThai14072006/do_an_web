<?php
require_once "../../config/config.php";

echo "<style>
  body{font-family:monospace;padding:20px;background:#f5f5f5;}
  h2{color:#8e4b00;margin:20px 0 8px;}
  table{border-collapse:collapse;margin:8px 0;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.1);}
  td,th{border:1px solid #ddd;padding:10px 16px;}
  th{background:#8e4b00;color:#f8ce86;}
  .ok{color:green;font-weight:bold;}
  .err{color:red;font-weight:bold;}
  .warn{color:orange;font-weight:bold;}
  pre{background:#fff;padding:15px;border-radius:8px;border:1px solid #ddd;}
</style>";

echo "<h1>🔍 Debug Report</h1>";

// ── 1. user_db.users ─────────────────────────────────────────
echo "<h2>1. user_db → bảng <code>users</code></h2>";
$r = $conn_user->query("SELECT id, username, email FROM users ORDER BY id LIMIT 15");
if ($r === false) {
    echo "<p class='err'>❌ LỖI: " . $conn_user->error . "</p>";
} elseif ($r->num_rows === 0) {
    echo "<p class='err'>⚠️ Bảng users TRỐNG!</p>";
} else {
    echo "<table><tr><th>id</th><th>username</th><th>email</th></tr>";
    while ($row = $r->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['username']}</td><td>{$row['email']}</td></tr>";
    }
    echo "</table>";
}

// ── 2. user_db.customers ─────────────────────────────────────
echo "<h2>2. user_db → bảng <code>customers</code></h2>";
// Kiểm tra cột tồn tại
$cols = $conn_user->query("DESCRIBE customers");
if ($cols === false) {
    echo "<p class='err'>❌ Bảng customers không tồn tại: " . $conn_user->error . "</p>";
} else {
    echo "<b>Cấu trúc bảng:</b><table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($col = $cols->fetch_assoc()) {
        $highlight = $col['Field'] === 'user_id' ? ' style="background:#d4edda"' : '';
        echo "<tr$highlight><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
    }
    echo "</table>";

    $r2 = $conn_user->query("SELECT * FROM customers ORDER BY id LIMIT 15");
    if ($r2->num_rows === 0) {
        echo "<p class='err'>⚠️ Bảng customers TRỐNG!</p>";
    } else {
        // Build dynamic table from actual columns
        $rows = $r2->fetch_all(MYSQLI_ASSOC);
        $headers = array_keys($rows[0]);
        echo "<b>Dữ liệu:</b><table><tr>";
        foreach ($headers as $h) echo "<th>$h</th>";
        echo "</tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $v) echo "<td>" . htmlspecialchars((string)$v) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// ── 3. JOIN test ─────────────────────────────────────────────
echo "<h2>3. Test query JOIN customers + users</h2>";
$r3 = $conn_user->query("
    SELECT c.id, c.full_name, c.phone, u.email
    FROM customers c
    JOIN users u ON c.user_id = u.id
    LIMIT 5
");
if ($r3 === false) {
    echo "<p class='err'>❌ JOIN thất bại: " . $conn_user->error . "</p>";
} elseif ($r3->num_rows === 0) {
    echo "<p class='warn'>⚠️ JOIN không trả về dữ liệu — user_id không khớp hoặc bảng trống</p>";
    // Thêm kiểm tra: customers không có cột user_id?
    $check = $conn_user->query("SELECT id FROM customers LIMIT 3");
    if ($check) {
        $sample = $check->fetch_all(MYSQLI_ASSOC);
        echo "<p>customers.id có trong DB: " . implode(', ', array_column($sample, 'id')) . "</p>";
    }
} else {
    echo "<p class='ok'>✔ JOIN thành công!</p>";
    echo "<table><tr><th>c.id</th><th>full_name</th><th>phone</th><th>email</th></tr>";
    while ($row = $r3->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['full_name']}</td><td>{$row['phone']}</td><td>{$row['email']}</td></tr>";
    }
    echo "</table>";
}

// ── 4. jewelry_db.orders ─────────────────────────────────────
echo "<h2>4. jewelry_db → bảng <code>orders</code> (customer_id)</h2>";
$r4 = $conn->query("SELECT id, order_number, customer_id, status FROM orders ORDER BY id LIMIT 15");
if ($r4 === false) {
    echo "<p class='err'>❌ LỖI: " . $conn->error . "</p>";
} elseif ($r4->num_rows === 0) {
    echo "<p class='err'>⚠️ Bảng orders TRỐNG!</p>";
} else {
    echo "<table><tr><th>id</th><th>order_number</th><th>customer_id</th><th>status</th></tr>";
    while ($row = $r4->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['order_number']}</td><td><b>{$row['customer_id']}</b></td><td>{$row['status']}</td></tr>";
    }
    echo "</table>";
}

// ── 5. Khớp ID ───────────────────────────────────────────────
echo "<h2>5. Kiểm tra customer_id ↔ customers.id</h2>";
$oids = $conn->query("SELECT DISTINCT customer_id FROM orders ORDER BY customer_id");
$cids_r = $conn_user->query("SELECT id FROM customers ORDER BY id");
if ($oids && $cids_r) {
    $order_cids   = array_column($oids->fetch_all(MYSQLI_ASSOC), 'customer_id');
    $customer_ids = array_column($cids_r->fetch_all(MYSQLI_ASSOC), 'id');
    echo "<table><tr><th>customer_id trong orders</th><th>Tồn tại trong customers?</th></tr>";
    foreach ($order_cids as $cid) {
        $ok = in_array($cid, $customer_ids);
        $mark = $ok ? "<span class='ok'>✔ Có</span>" : "<span class='err'>✘ KHÔNG — đây là nguyên nhân N/A!</span>";
        echo "<tr><td>$cid</td><td>$mark</td></tr>";
    }
    echo "</table>";
}

$conn->close();
$conn_user->close();
?>