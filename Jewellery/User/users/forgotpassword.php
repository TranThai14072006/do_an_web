<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . "/do_an_web/Jewellery/config/config.php";
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

    if ($email === '' || $password === '') {
        $message = "Vui lòng nhập đầy đủ thông tin!";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match!";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn_user->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $message = "Email does not exist!";
        } else {
            $stmt = $conn_user->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashedPassword, $email);
            if ($stmt->execute()) {
                $message = "Reset password successful!";
                header("refresh:2; url=login.php");
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
  <link rel="stylesheet" href="../search.css">
  <link rel="stylesheet" href="../Login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="/do_an_web/Jewellery/pages/index.php" class="home-btn">
        <i class="fas fa-home"></i> Home
      </a>
    </div>
    <div class="center">
      <a href="/do_an_web/Jewellery/pages/index.php">
        <img src="/do_an_web/Jewellery/images/36-logo.png" alt="Logo" width="80">
      </a>
      <div class="search-box">
        <input type="text" placeholder="Search products...">
        <button onclick="window.location.href='/do_an_web/Jewellery/pages/search.php'">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>
    <div class="right">
      <a href="/do_an_web/Jewellery/pages/Jewelry-cart.php" class="icon-link">
        <i class="fas fa-shopping-cart"></i>
      </a>
      <a href="/do_an_web/Jewellery/pages/auth/login.php" class="icon-link">
        <i class="fas fa-user"></i>
      </a>
    </div>
  </div>
</header>

<div class="login-container">
  <h2 class="title">Reset Password</h2>

  <?php if (!empty($message)): ?>
    <p style="color:red; text-align:center; margin-bottom:15px;">
      <?= htmlspecialchars($message) ?>
    </p>
  <?php endif; ?>

  <form method="POST" class="login-form">
    <div class="input-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" placeholder="Enter your email" required>
    </div>

    <div class="input-group">
      <label for="password">New Password</label>
      <input type="password" id="password" name="password" placeholder="Enter new password" required>
    </div>

    <div class="input-group">
      <label for="confirmPassword">Confirm Password</label>
      <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-enter new password" required>
    </div>

    <button type="submit" class="btn">Confirm</button>
  </form>

  <div class="extra-links">
    <a href="login.php">Back to Login</a>
</div>
</div>

<script>
  document.querySelector('.search-box button').addEventListener('click', function() {
    const searchTerm = document.querySelector('.search-box input').value;
    if (searchTerm.trim() !== '') {
      window.location.href = '/do_an_web/Jewellery/pages/search.php?q=' + encodeURIComponent(searchTerm);
    }
  });
  document.querySelector('.search-box input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      document.querySelector('.search-box button').click();
    }
  });
</script>

</body>
</html>