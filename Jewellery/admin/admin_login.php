<?php
session_start();
require_once '../config/config.php';

// Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

// If already logged in, redirect to admin panel
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: Administration_menu.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminname = trim($_POST['adminname'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    if ($adminname !== '' && $password !== '') {
        $stmt = $conn->prepare("SELECT id, username, password, status FROM users WHERE (username = ? OR email = ?) AND role = 'admin'");
        $stmt->bind_param('ss', $adminname, $adminname);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['status'] !== 'Active') {
                $error = 'Admin account is locked.';
            } else {
                // Verify password (supports both legacy plaintext and bcrypt)
                $valid = false;
                if (password_verify($password, $user['password'])) {
                    $valid = true;
                } elseif ($password === $user['password']) {
                    $valid = true;
                    // Auto-upgrade plaintext password to bcrypt hash
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $upd->bind_param('si', $newHash, $user['id']);
                    $upd->execute();
                    $upd->close();
                }

                if ($valid) {
                    session_regenerate_id(true); // Prevent session fixation
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id']        = $user['id'];
                    $_SESSION['admin_name']      = $user['username'];
                    header('Location: Administration_menu.php');
                    exit;
                } else {
                    $error = 'Invalid password.';
                }
            }
        } else {
            $error = 'Admin username not found.';
        }
        $stmt->close();
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
  <link rel="stylesheet" href="admin_Login.css">
</head>
<body>

  <div class="login-container">
    <h2 class="title">Admin Login</h2>

    <?php if ($error): ?>
      <p style="color:red; text-align:center; margin-bottom:10px;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="admin_login.php" method="post" class="login-form">

      <!-- Admin Name -->
      <div class="input-group">
        <label for="adminname">Admin Name</label>
        <input type="text" id="adminname" name="adminname"
               placeholder="Enter admin name"
               value="<?php echo htmlspecialchars($_POST['adminname'] ?? ''); ?>"
               required>
      </div>

      <!-- Password -->
      <div class="input-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="Enter your password" required>
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