<?php
session_start();
include "../php/config.php";

// Xử lý đăng nhập
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users 
            WHERE username='$username' AND password='$password'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $_SESSION['user'] = $result->fetch_assoc();

        // chuyển trang sau login
        header("Location: ../index_profile.php");
        exit();
    } else {
        $error = "Sai tài khoản hoặc mật khẩu!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Login</title>

  <link rel="stylesheet" href="search.css">
  <link rel="stylesheet" href="../Login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

<!-- HEADER -->
<header class="header-container">
  <div class="search-bar">

    <div class="left">
      <a href="../index.php" class="home-btn">
        <i class="fas fa-home"></i> Home
      </a>
    </div>

    <div class="center">
      <a href="../index.php">
        <img src="../images/36-logo.png" class="header-logo">
      </a>

      <div class="search-box">
        <input type="text" placeholder="Search products..." id="searchInput">
        <button id="searchBtn">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>

    <div class="right">
      <a href="Jewelry-cart.php" class="icon-link">
        <i class="fas fa-shopping-cart"></i>
      </a>

      <!-- HIỂN THỊ USER -->
      <?php if(isset($_SESSION['user'])): ?>
        <span style="margin-right:10px;">
          <?= $_SESSION['user']['username'] ?>
        </span>
        <a href="../php/logout.php" class="icon-link">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      <?php else: ?>
        <a href="login.php" class="icon-link">
          <i class="fas fa-user"></i>
        </a>
      <?php endif; ?>
    </div>

  </div>
</header>

<!-- LOGIN -->
<div class="login-container">

  <h2 class="title">User Login</h2>

  <!-- HIỂN THỊ LỖI -->
  <?php if(isset($error)): ?>
    <p style="color:red; text-align:center;">
      <?= $error ?>
    </p>
  <?php endif; ?>

  <!-- FORM -->
  <form method="POST" class="login-form">

    <div class="input-group">
      <label>Username</label>
      <input type="text" name="username" placeholder="Enter your username" required>
    </div>

    <div class="input-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password" required>
    </div>

    <!-- NÚT LOGIN -->
    <button type="submit" class="btn">
      Login
    </button>

  </form>

  <div class="extra-links">
    <a href="Register.php">Sign Up</a>
    <a href="forgot-password.php">Forgot Password?</a>
  </div>

</div>

<!-- SCRIPT -->
<script>
  // Search button
  document.getElementById("searchBtn").addEventListener("click", function() {
    const keyword = document.getElementById("searchInput").value;
    if (keyword.trim() !== "") {
      alert("Searching: " + keyword);
    }
  });

  // Enter search
  document.getElementById("searchInput").addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
      document.getElementById("searchBtn").click();
    }
  });
</script>

</body>
</html>