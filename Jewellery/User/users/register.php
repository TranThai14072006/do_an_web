<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . "/do_an_web/Jewellery/config/config.php";
if (isset($_SESSION['user_id'])) {
    header("Location: ../index_profile.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirmPassword'] ?? '');

    if ($fullname === '' || $email === '' || $password === '') {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } elseif ($password !== $confirm) {
        $error = "Mật khẩu không khớp!";
    } else {
        $stmt = $conn_user->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) {
            die("SQL error: " . $conn_user->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email đã tồn tại!";
            $stmt->close();
        } else {
            $stmt->close();

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn_user->prepare(
                "INSERT INTO users(username, email, password) VALUES (?, ?, ?)"
            );
            $stmt->bind_param("sss", $fullname, $email, $hash);

            if ($stmt->execute()) {
                $_SESSION['user_id']  = $stmt->insert_id;
                $_SESSION['username'] = $fullname;
                $stmt->close();
                header("Location: login.php");
                exit();
            } else {
                $error = "Lỗi đăng ký!";
                $stmt->close();
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
  <title>Register</title>
  <link rel="stylesheet" href="../search.css">
  <link rel="stylesheet" href="../Login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
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
      <a href="../index.php">
        <img src="/do_an_web/Jewellery/images/36-logo.png" alt="Logo" width="80" height="auto">
      </a>
      <div class="search-box">
        <input type="text" placeholder="Search products...">
        <button onclick="window.location.href='search.php'">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>
    <div class="right">
      <a href="Jewelry-cart.php" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
      <a href="login.php" class="icon-link"><i class="fas fa-user"></i></a>
    </div>
  </div>
</header>

<div class="login-container">
  <h2 class="title">Create Account</h2>

  <?php if (isset($error)): ?>
    <p style="color:red; text-align:center; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="POST" class="login-form">

    <div class="input-group">
      <label for="fullname">Full Name</label>
      <input type="text" id="fullname" name="fullname"
             placeholder="Enter your full name"
             value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" required>
    </div>

    <div class="input-group">
      <label for="email">Email</label>
      <input type="email" id="email" name="email"
             placeholder="Enter your email"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>

    <div class="input-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password"
             placeholder="Enter your password" required>
    </div>

    <div class="input-group">
      <label for="confirmPassword">Confirm Password</label>
      <input type="password" id="confirmPassword" name="confirmPassword"
             placeholder="Re-enter your password" required>
    </div>

    <button type="submit" class="btn">Register</button>

  </form>

  <div class="extra-links">
    Already have an account? <a href="login.php">Login</a>
  </div>
</div>

<script>
  document.querySelector('.search-box button').addEventListener('click', function () {
    const searchTerm = document.querySelector('.search-box input').value;
    if (searchTerm.trim() !== '') {
      window.location.href = 'search.php?q=' + encodeURIComponent(searchTerm);
    }
  });
  document.querySelector('.search-box input').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      document.querySelector('.search-box button').click();
    }
  });
</script>

</body>
</html>