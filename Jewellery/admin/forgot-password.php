<?php
session_start();

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminname        = trim($_POST['adminname'] ?? '');
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($adminname) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // TODO: Implement actual password reset logic (DB update, email code, etc.)
        $message = 'A reset code has been sent. Please check your email.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link rel="stylesheet" href="admin_Login.css">
</head>
<body>

  <div class="login-container">
    <h2 class="title">Reset Password</h2>

    <?php if ($error): ?>
      <p style="color:red; text-align:center; margin-bottom:10px;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($message): ?>
      <p style="color:green; text-align:center; margin-bottom:10px;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form action="forgot-password.php" method="post" class="login-form">

      <!-- Admin Name -->
      <div class="input-group">
        <label for="adminname">Admin Name</label>
        <input type="text" id="adminname" name="adminname"
               placeholder="Enter admin name"
               value="<?php echo htmlspecialchars($_POST['adminname'] ?? ''); ?>"
               required>
      </div>

      <!-- New Password -->
      <div class="input-group">
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password"
               placeholder="Enter new password" required>
      </div>

      <!-- Confirm Password -->
      <div class="input-group">
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password"
               placeholder="Confirm new password" required>
      </div>

      <!-- Send code button -->
      <button type="submit" class="btn">Send Code</button>
    </form>

    <div class="extra-links">
      <a href="admin_login.php">Back to Login</a>
    </div>
  </div>

</body>
</html>