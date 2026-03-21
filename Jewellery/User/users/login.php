<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . "/web project/do_an_web/Jewellery/config/config.php";

// 🔒 Nếu đã đăng nhập thì chuyển trang
if (isset($_SESSION['user_id'])) {
    header("Location: ../index_profile.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ✅ Lấy dữ liệu an toàn
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    //  Check rỗng
    if ($username === '' || $password === '') {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {

        //  Prepare chống SQL Injection
        $stmt = $conn_user->prepare("SELECT id, username, password FROM users WHERE username = ?");

        if (!$stmt) {
            die("SQL error: " . $conn_user->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // ✅ Kiểm tra user tồn tại
        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // 🔑 Kiểm tra mật khẩu
            if (password_verify($password, $user['password'])) {

                session_regenerate_id(true); // chống hijack

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
</head>
<body>

<!-- Header đồng bộ với trang search (đã sửa đường dẫn sang .php) -->
<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="../index.php" class="home-btn"><i class="fas fa-home"></i> Home</a>
    </div>
    <div class="center">
      <a href="../index.php">
        <img src="../images/36-logo.png" alt="Jewelry Store Logo" class="header-logo">
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
      <a href="index_profile.php" class="icon-link"><i class="fas fa-user"></i></a>
    </div>
  </div>
</header>

<!-- Nội dung login -->
<div class="login-container">

  <h2 class="title">User Login</h2>

  <!-- Hiển thị thông báo lỗi nếu có -->
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

    <button type="submit" class="btn">
      Login
    </button>
  </form>

  <div class="extra-links">
    <a href="Register.php">Sign Up</a>
    <a href="forgot-password.php">Forgot Password?</a>
  </div>
</div>

<script>
  // Các hàm xử lý sự kiện cho header
  document.querySelector('.search-box button').addEventListener('click', function() {
    const searchTerm = document.querySelector('.search-box input').value;
    if (searchTerm.trim() !== '') {
      alert(`🔍 Searching for: ${searchTerm}`);
      // Thực tế sẽ chuyển hướng đến trang search với tham số tìm kiếm
      // window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
    }
  });

  // Cho phép tìm kiếm bằng phím Enter
  document.querySelector('.search-box input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      document.querySelector('.search-box button').click();
    }
  });
</script>

</body>
</html>