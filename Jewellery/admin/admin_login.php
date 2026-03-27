<?php
session_start();
require_once '../config/config.php';

// Nếu đã login admin → vào admin panel luôn
if (!empty($_SESSION['admin_logged_in'])) {
  header('Location: Administration_menu.php');
  exit;
}



$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $adminname = trim($_POST['adminname'] ?? '');
  $password = trim($_POST['password'] ?? '');

  if ($adminname === '' || $password === '') {
    $error = 'Please enter both username and password.';
  } else {
    // Lấy user theo username, bắt buộc role = 'admin'
    $stmt = $conn->prepare(
      "SELECT id, username, password, role, status FROM users WHERE username = ? LIMIT 1"
    );
    $stmt->bind_param('s', $adminname);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$admin) {
      $error = 'Account not found.';
    } elseif ($admin['role'] !== 'admin') {
      // Tài khoản user thường cố đăng nhập vào admin
      $error = '⛔ Access denied. This account does not have admin privileges.';
    } elseif ($admin['status'] === 'Locked') {
      $error = '🔒 This admin account has been locked. Contact your system administrator.';
    } elseif (!password_verify($password, $admin['password'])) {
      $error = 'Incorrect password.';
    } else {
      // Gán role vào session và kiểm tra lần cuối
      $_SESSION['role'] = $admin['role'];
      if ($_SESSION['role'] !== 'admin') {
        $error = '⛔ Bạn không có quyền truy cập!';
      } else {
        // ✅ Đăng nhập thành công
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name']      = $admin['username'];
        $_SESSION['admin_id']        = $admin['id'];
        header('Location: Administration_menu.php');
        exit;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login – Luxury Jewelry</title>
  <link rel="stylesheet" href="admin_Login.css">
</head>

<body>

  <div class="login-container">
    <h2 class="title">Admin Login</h2>

    <?php if ($error): ?>
      <p style="color:red;text-align:center;margin-bottom:10px;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="admin_login.php" method="post" class="login-form">

      <!-- Admin Name -->
      <div class="input-group">
        <label for="adminname">Admin Name</label>
        <input type="text" id="adminname" name="adminname" placeholder="Enter admin name"
          value="<?php echo htmlspecialchars($_POST['adminname'] ?? ''); ?>" required>
      </div>

      <!-- Password -->
      <div class="input-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>
      </div>

      <!-- Login button -->
      <button type="submit" class="btn">Login</button>
    </form>

    <div class="extra-links">
      <a href="forgot-password.php">Forgot Password?</a>
    </div>
  </div>

</body>

</html>