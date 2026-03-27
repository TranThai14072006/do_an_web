<?php
/**
 * ============================================================
 * FULL DATABASE MIGRATION SCRIPT
 * URL: http://localhost/Jewellery/database/migrate_all.php
 * 
 * Kiểm tra và thêm các cột/bảng còn thiếu vào jewelry_db.
 * An toàn để chạy nhiều lần (idempotent).
 * ============================================================
 */
require_once '../config/config.php';

$results = [];

function migrate($conn, $label, $check_sql, $migrate_sql) {
    global $results;
    $check = $conn->query($check_sql);
    if ($check && $check->num_rows > 0) {
        $results[] = ['label' => $label, 'status' => 'skip', 'msg' => 'Already exists'];
        return;
    }
    $ok = $conn->query($migrate_sql);
    $results[] = [
        'label'  => $label,
        'status' => $ok ? 'ok' : 'err',
        'msg'    => $ok ? 'Done' : $conn->error,
    ];
}

// ── 1. Cột gender trong products ────────────────────────────────────────────
migrate($conn,
    "products.gender column",
    "SHOW COLUMNS FROM products LIKE 'gender'",
    "ALTER TABLE products ADD COLUMN `gender` VARCHAR(20) DEFAULT 'Unisex' AFTER `category`"
);

// ── 2. Cột cost_price trong products ─────────────────────────────────────────
migrate($conn,
    "products.cost_price column",
    "SHOW COLUMNS FROM products LIKE 'cost_price'",
    "ALTER TABLE products ADD COLUMN `cost_price` DECIMAL(15,2) DEFAULT 0.00 AFTER `price`"
);

// ── 3. Cột profit_percent trong products ─────────────────────────────────────
migrate($conn,
    "products.profit_percent column",
    "SHOW COLUMNS FROM products LIKE 'profit_percent'",
    "ALTER TABLE products ADD COLUMN `profit_percent` INT(11) DEFAULT 0 AFTER `cost_price`"
);

// ── 4. Cột stock trong products ───────────────────────────────────────────────
migrate($conn,
    "products.stock column",
    "SHOW COLUMNS FROM products LIKE 'stock'",
    "ALTER TABLE products ADD COLUMN `stock` INT(11) DEFAULT 0 AFTER `profit_percent`"
);

// ── 5. Cột category trong products ───────────────────────────────────────────
migrate($conn,
    "products.category column",
    "SHOW COLUMNS FROM products LIKE 'category'",
    "ALTER TABLE products ADD COLUMN `category` VARCHAR(100) DEFAULT NULL AFTER `stock`"
);

// ── 6. Bảng users ─────────────────────────────────────────────────────────────
$users_exists = $conn->query("SHOW TABLES LIKE 'users'");
if (!$users_exists || $users_exists->num_rows === 0) {
    $ok = $conn->query("
        CREATE TABLE `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(100) DEFAULT NULL,
          `email` varchar(100) DEFAULT NULL,
          `password` varchar(255) DEFAULT NULL,
          `status` ENUM('Active','Locked') NOT NULL DEFAULT 'Active',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    $results[] = ['label' => 'CREATE users table', 'status' => $ok ? 'ok' : 'err', 'msg' => $ok ? 'Done' : $conn->error];
} else {
    $results[] = ['label' => 'users table', 'status' => 'skip', 'msg' => 'Already exists'];
    // Thêm cột status nếu chưa có
    migrate($conn,
        "users.status column",
        "SHOW COLUMNS FROM users LIKE 'status'",
        "ALTER TABLE users ADD COLUMN `status` ENUM('Active','Locked') NOT NULL DEFAULT 'Active' AFTER `created_at`"
    );
}

// ── 7. Bảng customers ────────────────────────────────────────────────────────
$cust_exists = $conn->query("SHOW TABLES LIKE 'customers'");
if (!$cust_exists || $cust_exists->num_rows === 0) {
    $ok = $conn->query("
        CREATE TABLE `customers` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `full_name` varchar(255) NOT NULL,
          `phone` varchar(20) DEFAULT NULL,
          `address` text DEFAULT NULL,
          `birthday` date DEFAULT NULL,
          `gender` enum('Male','Female','Other') DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    $results[] = ['label' => 'CREATE customers table', 'status' => $ok ? 'ok' : 'err', 'msg' => $ok ? 'Done' : $conn->error];
} else {
    $results[] = ['label' => 'customers table', 'status' => 'skip', 'msg' => 'Already exists'];
}

// ── 8. Insert users mẫu nếu trống ────────────────────────────────────────────
$uc = $conn->query("SELECT COUNT(*) AS c FROM users")?->fetch_assoc()['c'] ?? 0;
if ($uc == 0) {
    $ok = $conn->query("
        INSERT INTO `users` (`username`, `email`, `password`, `status`) VALUES
        ('sophia.nguyen', 'sophia.nguyen@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
        ('minh.tran', 'minh.tran@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
        ('david.le', 'david.le@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
        ('emily.pham', 'emily.pham@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
        ('huy.dang', 'huy.dang@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
        ('anna.vu', 'anna.vu@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
        ('thanh.ho', 'thanh.ho@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
        ('mai.do', 'mai.do@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
        ('kevin.truong', 'kevin.truong@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active'),
        ('lisa.nguyen', 'lisa.nguyen@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active')
    ");
    $results[] = ['label' => 'Insert 10 sample users', 'status' => $ok ? 'ok' : 'err', 'msg' => $ok ? 'Done' : $conn->error];
}

// ── 9. Insert customers mẫu nếu trống ────────────────────────────────────────
$cc = $conn->query("SELECT COUNT(*) AS c FROM customers")?->fetch_assoc()['c'] ?? 0;
if ($cc == 0) {
    // Lấy user IDs vừa insert
    $u_rows = $conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
    if (count($u_rows) >= 10) {
        $ids = array_column($u_rows, 'id');
        $ok = $conn->query("
            INSERT INTO `customers` (`user_id`, `full_name`, `phone`, `address`, `birthday`, `gender`) VALUES
            ({$ids[0]}, 'Sophia Nguyen', '0901 234 567', '123 Le Loi, Q1, HCMC', '1995-03-12', 'Female'),
            ({$ids[1]}, 'Minh Tran', '0938 456 789', '45 Vo Van Tan, Q3, HCMC', '1990-07-25', 'Male'),
            ({$ids[2]}, 'David Le', '0912 987 654', '56 Hai Ba Trung, Hanoi', '1988-11-05', 'Male'),
            ({$ids[3]}, 'Emily Pham', '0987 112 233', '22 Nguyen Hue, Q1, HCMC', '1997-01-30', 'Female'),
            ({$ids[4]}, 'Huy Dang', '0909 654 321', '78 Le Duan, Da Nang', '1993-06-18', 'Male'),
            ({$ids[5]}, 'Anna Vu', '0935 111 222', '14 Tran Phu, Hue', '1996-09-22', 'Female'),
            ({$ids[6]}, 'Thanh Ho', '0902 222 333', '99 Nguyen Trai, HCMC', '1991-04-10', 'Male'),
            ({$ids[7]}, 'Mai Do', '0944 777 888', '64 Pasteur, Q3, HCMC', '1994-08-14', 'Female'),
            ({$ids[8]}, 'Kevin Truong', '0988 999 000', '11 Hoang Dieu, Hanoi', '1989-12-03', 'Male'),
            ({$ids[9]}, 'Lisa Nguyen', '0911 222 444', '03 Nguyen Van Linh, Da Nang', '1998-05-27', 'Female')
        ");
        $results[] = ['label' => 'Insert 10 sample customers', 'status' => $ok ? 'ok' : 'err', 'msg' => $ok ? 'Done' : $conn->error];
    }
}

// ── 10. Insert products mẫu nếu trống ──────────────────────────────────────
$pc = $conn->query("SELECT COUNT(*) AS c FROM products")?->fetch_assoc()['c'] ?? 0;
if ($pc == 0) {
    $ok = $conn->query("
        INSERT INTO `products` (`id`, `name`, `price`, `image`, `cost_price`, `profit_percent`, `stock`, `category`, `gender`) VALUES
        ('R001', 'Kane Moissanite Ring', 1326.00, 'R001.jpg', 1020.00, 30, 10, 'Ring', 'Male'),
        ('R002', 'Winston Anchor Ring', 1708.00, 'R002.jpg', 1220.00, 40, 5, 'Ring', 'Male'),
        ('R003', 'Ula Opal Teardrop Ring', 49.38, 'R003.jpg', 39.50, 25, 15, 'Ring', 'Female'),
        ('R004', 'Platinum Clover Charm Ring', 1633.50, 'R004.jpg', 1210.00, 35, 8, 'Ring', 'Female'),
        ('R005', 'Paisley Moissanite Ring', 1176.00, 'R005.jpg', 980.00, 20, 12, 'Ring', 'Female'),
        ('R006', 'Niche Crown Stack Ring', 120.00, 'R006.jpg', 60.00, 100, 10, 'Ring', 'Female'),
        ('R007', 'The Zenith Ring', 130.00, 'R007.jpg', 70.00, 85, 15, 'Ring', 'Male'),
        ('R008', 'Silver Eminence Ring', 150.00, 'R008.jpg', 90.00, 67, 8, 'Ring', 'Male'),
        ('R009', 'Elysian Flow', 200.00, 'R009.jpg', 120.00, 67, 5, 'Ring', 'Unisex'),
        ('R010', 'Onyx Edge', 95.00, 'R010.jpg', 50.00, 90, 12, 'Ring', 'Unisex')
    ");
    $results[] = ['label' => 'Insert 10 sample products', 'status' => $ok ? 'ok' : 'err', 'msg' => $ok ? 'Done' : $conn->error];
} else {
    // Cập nhật gender = 'Unisex' cho các sản phẩm chưa có gender
    $upd = $conn->query("UPDATE products SET gender = 'Unisex' WHERE gender IS NULL OR gender = ''");
    $results[] = ['label' => 'Fix NULL gender in products', 'status' => 'ok', 'msg' => "Updated " . $conn->affected_rows . " rows"];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Migration — Jewelry DB</title>
  <style>
    body { font-family: "Segoe UI", sans-serif; background: #f3f3f3; padding: 30px; max-width: 800px; margin: 0 auto; }
    h1 { color: #8e4b00; margin-bottom: 20px; }
    .item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; background: #fff; border-radius: 8px; margin-bottom: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .item.ok   { border-left: 4px solid #2e7d32; }
    .item.err  { border-left: 4px solid #c62828; }
    .item.skip { border-left: 4px solid #aaa; }
    .icon { font-size: 18px; }
    .label { font-weight: 600; flex: 1; }
    .note { font-size: 13px; color: #888; }
    .err .note { color: #c62828; }
    .actions { margin-top: 24px; display: flex; gap: 14px; flex-wrap: wrap; }
    .btn { display: inline-block; background: #8e4b00; color: #f8ce86; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
    .btn:hover { background: #a3670b; }
    .btn.secondary { background: #6c757d; }
  </style>
</head>
<body>
  <h1>🔧 Database Migration — jewelry_db</h1>
  <?php foreach ($results as $r): ?>
    <div class="item <?php echo $r['status']; ?>">
      <span class="icon"><?php echo $r['status'] === 'ok' ? '✅' : ($r['status'] === 'err' ? '❌' : '⏭'); ?></span>
      <span class="label"><?php echo htmlspecialchars($r['label']); ?></span>
      <span class="note"><?php echo htmlspecialchars($r['msg']); ?></span>
    </div>
  <?php endforeach; ?>
  <div class="actions">
    <a href="../admin/admin_login.php" class="btn">→ Admin Login</a>
    <a href="../admin/Administration_menu.php" class="btn secondary">→ Admin Dashboard</a>
  </div>
</body>
</html>
