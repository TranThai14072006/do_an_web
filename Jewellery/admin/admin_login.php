<?php
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminname = trim($_POST['adminname'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    // TODO: Replace with real DB authentication
    if ($adminname === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = $adminname;
        header('Location: Administration_menu.php');
        exit;
    } else {
        $error = 'Invalid admin name or password.';
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