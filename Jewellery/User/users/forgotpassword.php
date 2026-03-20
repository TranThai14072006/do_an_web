<?php
session_start();
require __DIR__ . "/../../config/config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ✅ lấy dữ liệu an toàn
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

    // ✅ check rỗng
    if ($email === '' || $password === '') {
        $message = "Vui lòng nhập đầy đủ thông tin!";
    }
    elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match!";
    } else {

        // ✅ mã hóa password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // ✅ kiểm tra email tồn tại
        $stmt = $conn_user->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $message = "Email does not exist!";
        } else {

            // ✅ update password
            $stmt = $conn_user->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashedPassword, $email);

            if ($stmt->execute()) {
                $message = "Reset password successful!";
                header("refresh:2; url=login.php"); // ✅ sửa đúng
            } else {
                $message = "Error updating password!";
            }
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link rel="stylesheet" href="forgot-password.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="../index_profile.html" class="home-btn"><i class="fas fa-home"></i> Home</a>
    </div>
    <div class="center">
      <a href="../index.html">
        <img src="../images/36-logo.png" class="header-logo">
      </a>
      <div class="search-box">
        <input type="text" placeholder="Search products...">
        <button onclick="window.location.href='search.html'">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>
    <div class="right">
      <a href="Jewelry-cart.html" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
      <a href="profile.html" class="icon-link"><i class="fas fa-user"></i></a>
    </div>
  </div>
</header>

<div class="reset-container">
  <div class="reset-box">
    <button class="back-icon" onclick="window.history.back()">←</button>

    <div class="logo">
      <img src="../images/36-logo.png">
    </div>

    <h2>Reset Password</h2>
    <p>Reset your password if you forgot them.</p>

    <!-- Hiển thị thông báo -->
    <?php if (!empty($message)) { ?>
      <p style="color:red; text-align:center;"><?php echo $message; ?></p>
    <?php } ?>

    <form method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirmPassword" placeholder="Confirm Password" required>
      <button type="submit" class="btn">Confirm</button>
    </form>

  </div>
</div>

</body>
</html>