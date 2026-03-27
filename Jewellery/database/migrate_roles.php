<?php
/**
 * =========================================================
 * ROLE MIGRATION SCRIPT
 * URL: http://localhost/Jewellery/database/migrate_roles.php
 * 
 * - Thêm cột role vào bảng users
 * - Set tất cả user hiện tại = 'user'
 * - Tạo tài khoản admin mặc định (admin / Admin@123)
 * =========================================================
 */
require_once '../config/config.php';

$results = [];

function run($conn, $label, $sql) {
    global $results;
    $ok = $conn->query($sql);
    $results[] = ['label' => $label, 'ok' => $ok !== false, 'msg' => $ok ? 'Done' : $conn->error];
}

// 1. Thêm cột role vào users (nếu chưa có)
$col = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($col && $col->num_rows === 0) {
    run($conn,
        "Thêm cột role vào users",
        "ALTER TABLE users ADD COLUMN `role` ENUM('admin','user') NOT NULL DEFAULT 'user' AFTER `status`"
    );
} else {
    $results[] = ['label' => 'role column', 'ok' => true, 'msg' => 'Already exists'];
}

// 2. Đặt tất cả user hiện tại = 'user' (nếu chưa có role)
run($conn, "Set tất cả tài khoản = user", "UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''");

// 3. Tạo admin mặc định nếu chưa có
$admin_exists = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if ($admin_exists && $admin_exists->num_rows === 0) {
    $admin_pass = password_hash('Admin@123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "INSERT INTO users (username, email, password, status, role) VALUES (?, ?, ?, 'Active', 'admin')"
    );
    $u = 'admin';
    $e = 'admin@luxuryjewelry.com';
    $stmt->bind_param('sss', $u, $e, $admin_pass);
    $ok = $stmt->execute();
    $stmt->close();
    $results[] = ['label' => 'Tạo admin mặc định (admin / Admin@123)', 'ok' => $ok, 'msg' => $ok ? 'Done' : $conn->error];
} else {
    $results[] = ['label' => 'Admin account', 'ok' => true, 'msg' => 'Already exists'];
}

// 4. Hiển thị danh sách tài khoản sau migration
$list = $conn->query("SELECT id, username, email, status, role FROM users ORDER BY role DESC, id ASC");
$users_after = $list ? $list->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Role Migration</title>
  <style>
    body { font-family: "Segoe UI", sans-serif; background: #f3f3f3; padding: 30px; max-width: 900px; margin: 0 auto; }
    h1 { color: #8e4b00; margin-bottom: 20px; }
    h2 { color: #555; margin: 24px 0 12px; font-size: 16px; }
    .item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; background: #fff; border-radius: 8px; margin-bottom: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
    .item.ok  { border-left: 4px solid #2e7d32; }
    .item.err { border-left: 4px solid #c62828; }
    .label { font-weight: 600; flex: 1; }
    .msg { font-size: 13px; color: #888; }
    .err .msg { color: #c62828; }
    table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    th { background: #8e4b00; color: #f8ce86; padding: 10px 14px; text-align: left; font-size: 13px; }
    td { padding: 10px 14px; border-bottom: 1px solid #f0e5d8; font-size: 13px; }
    tr:last-child td { border-bottom: none; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
    .badge.admin { background: #8e4b00; color: #f8ce86; }
    .badge.user  { background: #e0e0e0; color: #555; }
    .actions { margin-top: 24px; display: flex; gap: 14px; flex-wrap: wrap; }
    .btn { display: inline-block; background: #8e4b00; color: #f8ce86; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
    .btn:hover { background: #a3670b; }
    .btn.sec { background: #6c757d; }
    .info-box { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; }
    .info-box strong { color: #8e4b00; }
  </style>
</head>
<body>
  <h1>🔐 Role Migration — jewelry_db</h1>

  <div class="info-box">
    <strong>Tài khoản Admin mặc định:</strong><br>
    Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>Admin@123</code><br>
    <small>Đăng nhập tại: <a href="../admin/admin_login.php">Admin Login</a></small>
  </div>

  <?php foreach ($results as $r): ?>
    <div class="item <?php echo $r['ok'] ? 'ok' : 'err'; ?>">
      <span><?php echo $r['ok'] ? '✅' : '❌'; ?></span>
      <span class="label"><?php echo htmlspecialchars($r['label']); ?></span>
      <span class="msg"><?php echo htmlspecialchars($r['msg']); ?></span>
    </div>
  <?php endforeach; ?>

  <h2>📋 Danh sách tài khoản sau migration</h2>
  <table>
    <thead>
      <tr><th>ID</th><th>Username</th><th>Email</th><th>Status</th><th>Role</th></tr>
    </thead>
    <tbody>
      <?php foreach ($users_after as $u): ?>
        <tr>
          <td><?php echo $u['id']; ?></td>
          <td><?php echo htmlspecialchars($u['username']); ?></td>
          <td><?php echo htmlspecialchars($u['email'] ?? '—'); ?></td>
          <td><?php echo htmlspecialchars($u['status'] ?? 'Active'); ?></td>
          <td><span class="badge <?php echo $u['role']; ?>"><?php echo strtoupper($u['role']); ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="actions">
    <a href="../admin/admin_login.php" class="btn">→ Admin Login</a>
    <a href="../User/users/login.php" class="btn sec">→ User Login</a>
  </div>
</body>
</html>
