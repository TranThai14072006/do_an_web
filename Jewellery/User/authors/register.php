<?php
session_start();
include "../php/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirmPassword'];

    // kiểm tra password
    if ($password != $confirm) {
        $error = "Mật khẩu không khớp!";
    } else {

        // kiểm tra email tồn tại
        $check = $conn->query("SELECT * FROM users WHERE username='$email'");
        
        if ($check->num_rows > 0) {
            $error = "Email đã tồn tại!";
        } else {

            // lưu vào DB
            $conn->query("INSERT INTO users(username, password)
                          VALUES('$email', '$password')");

            // tự login luôn
            $_SESSION['user'] = [
                'username' => $email
            ];

            header("Location: login.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>

  <link rel="stylesheet" href="Register.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

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
        <input type="text" id="searchInput">
        <button id="searchBtn"><i class="fas fa-search"></i></button>
      </div>
    </div>

    <div class="right">
      <a href="Jewelry-cart.php"><i class="fas fa-shopping-cart"></i></a>
      <a href="login.php"><i class="fas fa-user"></i></a>
    </div>

  </div>
</header>

<div class="register-container">

  <button onclick="window.history.back()" class="back-btn">←</button>
  <h2 class="register-title">Create Account</h2>

  <!-- HIỂN THỊ LỖI -->
  <?php if(isset($error)): ?>
    <p style="color:red; text-align:center;">
      <?= $error ?>
    </p>
  <?php endif; ?>

  <!-- FORM PHP -->
  <form method="POST">

    <div class="input-group">
      <label>Full Name</label>
      <input type="text" name="fullname" required>
    </div>

    <div class="input-group">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>

    <div class="input-group">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>

    <div class="input-group">
      <label>Confirm Password</label>
      <input type="password" name="confirmPassword" required>
    </div>

    <!-- NÚT ĐÚNG -->
    <button type="submit" class="btn-register">
      Register
    </button>

  </form>

  <div class="extra-links">
    Already have an account? <a href="login.php">Login</a>
  </div>

</div>

<script>
  // Search
  document.getElementById("searchBtn").onclick = function() {
    let key = document.getElementById("searchInput").value;
    if (key.trim()) alert("Searching: " + key);
  };
</script>

</body>
</html>