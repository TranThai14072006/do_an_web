<?php
/**
 * ============================================================
 * DATABASE REPAIR & SETUP SCRIPT
 * URL: http://localhost/Jewellery/database/setup.php
 * 
 * Tự động tạo tất cả bảng còn thiếu + thêm cột status vào users
 * Chạy 1 lần duy nhất, sau đó có thể xóa hoặc giữ lại.
 * ============================================================
 */
require_once '../config/config.php';

$results = [];

function run_sql($conn, $sql, $label) {
    global $results;
    $ok = $conn->query($sql);
    $results[] = [
        'label'  => $label,
        'ok'     => ($ok !== false),
        'error'  => $ok === false ? $conn->error : '',
    ];
}

// ── 1. Tạo bảng users nếu chưa có ──────────────────────────────────────
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `username` varchar(100) DEFAULT NULL,
      `email` varchar(100) DEFAULT NULL,
      `password` varchar(255) DEFAULT NULL,
      `status` ENUM('Active','Locked') NOT NULL DEFAULT 'Active',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
", "CREATE users table");

// ── 2. Thêm cột status vào users nếu chưa có ────────────────────────────
$col_check = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
if ($col_check && $col_check->num_rows === 0) {
    run_sql($conn,
        "ALTER TABLE users ADD COLUMN status ENUM('Active','Locked') NOT NULL DEFAULT 'Active' AFTER created_at",
        "Add status column to users"
    );
} else {
    $results[] = ['label' => "status column in users", 'ok' => true, 'error' => 'Already exists'];
}

// ── 3. Tạo bảng customers nếu chưa có ───────────────────────────────────
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS `customers` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `full_name` varchar(255) NOT NULL,
      `phone` varchar(20) DEFAULT NULL,
      `address` text DEFAULT NULL,
      `birthday` date DEFAULT NULL,
      `gender` enum('Male','Female','Other') DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_id` (`user_id`),
      CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
", "CREATE customers table");

// ── 4. Tạo bảng products nếu chưa có ────────────────────────────────────
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS `products` (
      `id` varchar(50) NOT NULL,
      `name` varchar(255) NOT NULL,
      `price` decimal(15,2) DEFAULT 0.00,
      `image` varchar(255) DEFAULT NULL,
      `cost_price` decimal(15,2) DEFAULT 0.00,
      `profit_percent` int(11) DEFAULT 0,
      `stock` int(11) DEFAULT 0,
      `category` varchar(100) DEFAULT NULL,
      `gender` enum('Male','Female','Unisex') DEFAULT 'Unisex',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
", "CREATE products table");

// ── 5. Tạo bảng product_details nếu chưa có ─────────────────────────────
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS `product_details` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `product_id` varchar(50) NOT NULL,
      `description` text DEFAULT NULL,
      `material` varchar(255) DEFAULT NULL,
      `design` varchar(255) DEFAULT NULL,
      `stone` varchar(255) DEFAULT NULL,
      `color` varchar(100) DEFAULT NULL,
      `brand` varchar(255) DEFAULT 'ThirtySix Jewellery',
      PRIMARY KEY (`id`),
      KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
", "CREATE product_details table");

// ── 6. Tạo bảng orders nếu chưa có ──────────────────────────────────────
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS `orders` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `order_number` varchar(50) NOT NULL,
      `customer_id` int(11) NOT NULL,
      `order_date` date NOT NULL,
      `total_amount` decimal(15,2) DEFAULT 0.00,
      `status` enum('Pending','Processed','Delivered','Cancelled') DEFAULT 'Pending',
      PRIMARY KEY (`id`),
      UNIQUE KEY `order_number` (`order_number`),
      KEY `customer_id` (`customer_id`),
      CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
", "CREATE orders table");

// ── 7. Tạo bảng order_items nếu chưa có ─────────────────────────────────
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS `order_items` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `order_id` int(11) NOT NULL,
      `product_id` varchar(50) NOT NULL,
      `product_name` varchar(255) DEFAULT NULL,
      `quantity` int(11) DEFAULT 1,
      `unit_price` decimal(15,2) DEFAULT 0.00,
      `total_price` decimal(15,2) DEFAULT 0.00,
      PRIMARY KEY (`id`),
      KEY `order_id` (`order_id`),
      KEY `product_id` (`product_id`),
      CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
", "CREATE order_items table");

// ── 8. Tạo bảng goods_receipt nếu chưa có ───────────────────────────────
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS `goods_receipt` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `order_number` varchar(50) NOT NULL,
      `entry_date` date NOT NULL,
      `supplier` varchar(255) DEFAULT NULL,
      `total_quantity` int(11) DEFAULT 0,
      `total_value` decimal(15,2) DEFAULT 0.00,
      `status` enum('Draft','Completed') DEFAULT 'Draft',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
", "CREATE goods_receipt table");

// ── 9. Tạo bảng goods_receipt_items nếu chưa có ─────────────────────────
run_sql($conn, "
    CREATE TABLE IF NOT EXISTS `goods_receipt_items` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `receipt_id` int(11) NOT NULL,
      `product_id` varchar(50) NOT NULL,
      `product_name` varchar(255) DEFAULT NULL,
      `quantity` int(11) DEFAULT 1,
      `unit_price` decimal(15,2) DEFAULT 0.00,
      `total_price` decimal(15,2) DEFAULT 0.00,
      PRIMARY KEY (`id`),
      KEY `receipt_id` (`receipt_id`),
      CONSTRAINT `goods_receipt_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `goods_receipt` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
", "CREATE goods_receipt_items table");

// ── 10. Kiểm tra bảng orders có cần thêm product_id vào order_items không
$fk_check = $conn->query("SHOW COLUMNS FROM order_items LIKE 'product_id'");
if ($fk_check && $fk_check->num_rows > 0) {
    // Thêm FK product_id nếu chưa có
    $idx_check = $conn->query("SHOW INDEX FROM order_items WHERE Key_name = 'product_id'");
    if ($idx_check && $idx_check->num_rows === 0) {
        run_sql($conn, "ALTER TABLE order_items ADD KEY `product_id` (`product_id`)", "Add product_id index to order_items");
    }
}

// ── 11. Insert dữ liệu users mẫu nếu bảng còn trống ─────────────────────
$user_count = $conn->query("SELECT COUNT(*) AS cnt FROM users")->fetch_assoc()['cnt'];
if ($user_count == 0) {
    run_sql($conn, "
        INSERT INTO `users` (`id`, `username`, `email`, `password`, `status`, `created_at`) VALUES
        (1, 'sophia.nguyen', 'sophia.nguyen@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 13:03:02'),
        (2, 'minh.tran', 'minh.tran@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 13:03:02'),
        (3, 'david.le', 'david.le@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 13:03:02'),
        (4, 'emily.pham', 'emily.pham@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 13:03:02'),
        (5, 'huy.dang', 'huy.dang@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 13:03:02'),
        (6, 'anna.vu', 'anna.vu@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 13:03:02'),
        (7, 'thanh.ho', 'thanh.ho@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 13:03:02'),
        (8, 'mai.do', 'mai.do@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 13:03:02'),
        (9, 'kevin.truong', 'kevin.truong@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 13:03:02'),
        (10, 'lisa.nguyen', 'lisa.nguyen@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Active', '2026-03-21 13:03:02');
    ", "Insert sample users");
}

// ── 12. Insert dữ liệu customers mẫu nếu bảng còn trống ─────────────────
$cust_count = $conn->query("SELECT COUNT(*) AS cnt FROM customers")->fetch_assoc()['cnt'];
if ($cust_count == 0) {
    run_sql($conn, "
        INSERT INTO `customers` (`id`, `user_id`, `full_name`, `phone`, `address`, `birthday`, `gender`) VALUES
        (1, 1, 'Sophia Nguyen', '0901 234 567', '123 Le Loi, Q1, HCMC', '1995-03-12', 'Female'),
        (2, 2, 'Minh Tran', '0938 456 789', '45 Vo Van Tan, Q3, HCMC', '1990-07-25', 'Male'),
        (3, 3, 'David Le', '0912 987 654', '56 Hai Ba Trung, Hanoi', '1988-11-05', 'Male'),
        (4, 4, 'Emily Pham', '0987 112 233', '22 Nguyen Hue, Q1, HCMC', '1997-01-30', 'Female'),
        (5, 5, 'Huy Dang', '0909 654 321', '78 Le Duan, Da Nang', '1993-06-18', 'Male'),
        (6, 6, 'Anna Vu', '0935 111 222', '14 Tran Phu, Hue', '1996-09-22', 'Female'),
        (7, 7, 'Thanh Ho', '0902 222 333', '99 Nguyen Trai, HCMC', '1991-04-10', 'Male'),
        (8, 8, 'Mai Do', '0944 777 888', '64 Pasteur, Q3, HCMC', '1994-08-14', 'Female'),
        (9, 9, 'Kevin Truong', '0988 999 000', '11 Hoang Dieu, Hanoi', '1989-12-03', 'Male'),
        (10, 10, 'Lisa Nguyen', '0911 222 444', '03 Nguyen Van Linh, Da Nang', '1998-05-27', 'Female');
    ", "Insert sample customers");
}

// ── 13. Insert orders mẫu nếu trống ──────────────────────────────────────
$ord_count = $conn->query("SELECT COUNT(*) AS cnt FROM orders")->fetch_assoc()['cnt'];
if ($ord_count == 0) {
    run_sql($conn, "
        INSERT INTO `orders` (`id`, `order_number`, `customer_id`, `order_date`, `total_amount`, `status`) VALUES
        (1, 'ORD-2025-001', 1, '2025-10-05', 520.00, 'Delivered'),
        (2, 'ORD-2025-002', 2, '2025-10-12', 310.00, 'Delivered'),
        (3, 'ORD-2025-003', 3, '2025-11-01', 780.00, 'Processed'),
        (4, 'ORD-2025-004', 4, '2025-11-15', 410.00, 'Delivered'),
        (5, 'ORD-2025-005', 5, '2025-12-03', 225.00, 'Pending'),
        (6, 'ORD-2025-006', 6, '2025-12-10', 640.00, 'Delivered'),
        (7, 'ORD-2025-007', 7, '2026-01-08', 290.00, 'Cancelled'),
        (8, 'ORD-2025-008', 8, '2026-01-20', 870.00, 'Delivered'),
        (9, 'ORD-2025-009', 9, '2026-02-05', 355.00, 'Processed'),
        (10, 'ORD-2025-010', 10, '2026-02-18', 490.00, 'Pending');
    ", "Insert sample orders");
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Database Setup</title>
  <style>
    body { font-family: "Segoe UI", sans-serif; background: #f3f3f3; padding: 30px; max-width: 800px; margin: 0 auto; }
    h1 { color: #8e4b00; margin-bottom: 20px; }
    .item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; background: #fff; border-radius: 8px; margin-bottom: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .item.ok { border-left: 4px solid #2e7d32; }
    .item.err { border-left: 4px solid #c62828; }
    .icon { font-size: 18px; }
    .label { font-weight: 600; flex: 1; }
    .note { font-size: 13px; color: #888; }
    .err .note { color: #c62828; }
    .actions { margin-top: 24px; display: flex; gap: 14px; }
    .btn { display: inline-block; background: #8e4b00; color: #f8ce86; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
    .btn:hover { background: #a3670b; }
    .btn.secondary { background: #555; }
  </style>
</head>
<body>
  <h1>🏗️ Database Setup & Repair</h1>
  <?php foreach ($results as $r): ?>
    <div class="item <?php echo $r['ok'] ? 'ok' : 'err'; ?>">
      <span class="icon"><?php echo $r['ok'] ? '✅' : '❌'; ?></span>
      <span class="label"><?php echo htmlspecialchars($r['label']); ?></span>
      <span class="note"><?php echo $r['ok'] ? ($r['error'] ?: 'Done') : htmlspecialchars($r['error']); ?></span>
    </div>
  <?php endforeach; ?>
  <div class="actions">
    <a href="../admin/admin_login.php" class="btn">→ Go to Admin Login</a>
    <a href="../admin/Administration_menu.php" class="btn secondary">→ Admin Dashboard</a>
  </div>
</body>
</html>
