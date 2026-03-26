<?php
session_start();
require_once __DIR__ . '/../../config/config.php'; // Đường dẫn tới config chung

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Khởi tạo biến thông báo
$error = null;
$success = null;

// Lấy thông tin hiện tại từ database
$sql_user = "SELECT u.username, u.email, c.full_name, c.phone, c.address, c.birthday, c.gender 
             FROM users u 
             LEFT JOIN customers c ON u.id = c.user_id 
             WHERE u.id = ?";
$stmt = $conn_user->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

// Nếu chưa có thông tin customers, tạo mặc định từ users
if (!$userData) {
    // Lấy thông tin từ users
    $sql_user = "SELECT username, email FROM users WHERE id = ?";
    $stmt = $conn_user->prepare($sql_user);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userData = $stmt->fetch_assoc();
    $stmt->close();
    $userData['full_name'] = $userData['username'];
    $userData['phone'] = '';
    $userData['address'] = '';
    $userData['birthday'] = '';
    $userData['gender'] = '';
}

// Xử lý cập nhật khi form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    if ($fullname === '' || $email === '') {
        $error = "Vui lòng nhập đầy đủ họ tên và email!";
    } else {
        // Cập nhật bảng users
        $sql_update_user = "UPDATE users SET email = ? WHERE id = ?";
        $stmt = $conn_user->prepare($sql_update_user);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $stmt->close();

        // Cập nhật hoặc thêm vào bảng customers
        $check_customer = $conn_user->prepare("SELECT id FROM customers WHERE user_id = ?");
        $check_customer->bind_param("i", $user_id);
        $check_customer->execute();
        $check_customer->store_result();
        $exists = $check_customer->num_rows > 0;
        $check_customer->close();

        if ($exists) {
            $sql_update_customer = "UPDATE customers SET full_name = ?, phone = ?, address = ? WHERE user_id = ?";
            $stmt = $conn_user->prepare($sql_update_customer);
            $stmt->bind_param("sssi", $fullname, $phone, $address, $user_id);
        } else {
            $sql_insert_customer = "INSERT INTO customers (user_id, full_name, phone, address) VALUES (?, ?, ?, ?)";
            $stmt = $conn_user->prepare($sql_insert_customer);
            $stmt->bind_param("isss", $user_id, $fullname, $phone, $address);
        }
        if ($stmt->execute()) {
            $success = "Cập nhật thông tin thành công!";
            // Cập nhật lại session username nếu thay đổi
            $_SESSION['username'] = $fullname;
            // Reload dữ liệu để hiển thị lại
            $userData['full_name'] = $fullname;
            $userData['email'] = $email;
            $userData['phone'] = $phone;
            $userData['address'] = $address;
        } else {
            $error = "Cập nhật thất bại, vui lòng thử lại.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Profile | 36 Jewelry</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="edit profile.css">
  <style>
    /* Thêm style cho thông báo */
    .alert {
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      font-size: 14px;
    }
    .alert-success {
      background-color: rgba(40, 167, 69, 0.8);
      color: #fff;
    }
    .alert-error {
      background-color: rgba(220, 53, 69, 0.8);
      color: #fff;
    }
  </style>
</head>

<body>
  <!-- HEADER -->
  <header class="header-container">
    <div class="search-bar">
      <div class="left">
        <a href="../index_profile.php" class="home-btn"><i class="fas fa-home"></i> Home</a>
      </div>
      <div class="center">
        <a href="../index.php">
          <img src="../images/36-logo.png" alt="36 Jewelry Logo" class="header-logo">
        </a>
        <div class="search-box">
          <input type="text" placeholder="Search products...">
          <button><i class="fas fa-search"></i></button>
        </div>
      </div>
      <div class="right">
        <a href="Jewelry-cart.php" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
        <a href="profile.php" class="icon-link"><i class="fas fa-user"></i></a>
      </div>
    </div>
  </header>

  <!-- MAIN EDIT PROFILE -->
  <div class="edit-container">
    <div class="edit-box">
      <button class="back-btn" onclick="window.history.back()">⟵</button>
      <div class="logo">
        <img src="../../images/36-logo.png" alt="36 Jewelry Logo">
      </div>
      <h2>Edit Profile</h2>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form id="editProfileForm" method="POST" action="">
        <div class="input-group">
          <label>Full Name</label>
          <input type="text" name="fullname" id="fullname" value="<?= htmlspecialchars($userData['full_name'] ?? '') ?>" required>
        </div>

        <div class="input-group">
          <label>Email</label>
          <input type="email" name="email" id="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
        </div>

        <div class="input-group">
          <label>Phone Number</label>
          <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" required>
        </div>

        <div class="input-group">
          <label>Address</label>
          <input type="text" name="address" id="address" value="<?= htmlspecialchars($userData['address'] ?? '') ?>" required>
        </div>

        <button type="submit" class="save-btn">Save Changes</button>
      </form>
    </div>
  </div>

  <script>
    // Search functionality
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