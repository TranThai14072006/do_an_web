<?php
session_start();
require_once "../../config/config.php";
if (isset($_SESSION['user_id'])) {
    header("Location:../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  /* khó nói  */
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        $stmt = $conn_user->prepare("SELECT id, username, password FROM users WHERE username = ?");
        if (!$stmt) {
            die("SQL error: " . $conn_user->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: ../index_profile.php");
                exit();
            } else {
                $error = "Sai mật khẩu!";
            }
        } else {
            $error = "Tài khoản không tồn tại!";
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
  <title>User Login</title>
  <link rel="stylesheet" href="../search.css">
  <link rel="stylesheet" href="../Login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* FIX: giới hạn kích thước logo trong header */
    .header-container .center a img {
      width: 80px !important;
      height: auto !important;
      display: block;
    }
  </style>
</head>
<body>

<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="../index.php" class="home-btn"><i class="fas fa-home"></i> Home</a>
    </div>
    <div class="center">
      <a href="../indexprofile.php">
        <img src="../../images/36-logo.png" alt="Logo" width="80" height="auto">
      </a>
      <div class="search-box">
        <input type="text" placeholder="Search products...">
        <button onclick="window.location.href='search.php'">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>
    <div class="right">
      <a href="cart.php" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
      <a href="profile.php" class="icon-link"><i class="fas fa-user"></i></a>
    </div>
  </div>
</header>

<div class="login-container">
  <h2 class="title">User Login</h2>

  <?php if(isset($error)): ?>
    <p style="color:red; text-align:center; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST" class="login-form">
    <div class="input-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" placeholder="Enter your username" required>
    </div>
    <div class="input-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter your password" required>
    </div>
    <button type="submit" class="btn">Login</button>
  </form>

  <div class="extra-links">
    <a href="register.php">Sign Up</a>
    <a href="forgotpassword.php">Forgot Password?</a>
  </div>
</div>

<script>
  document.querySelector('.search-box button').addEventListener('click', function() {
    const searchTerm = document.querySelector('.search-box input').value;
    if (searchTerm.trim() !== '') {
      window.location.href = 'search.php?q=' + encodeURIComponent(searchTerm);
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

