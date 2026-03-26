<?php
session_start();
require __DIR__ . "/../../config/config.php";

/* ===== CHECK LOGIN ===== */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ===== LẤY THÔNG TIN USER + CUSTOMER ===== */
$sql = "SELECT u.username, u.email, 
               c.full_name, c.phone, c.address
        FROM users u
        LEFT JOIN customers c ON u.id = c.user_id
        WHERE u.id = ?";

$stmt = $conn_user->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

/* ===== LẤY ĐƠN HÀNG GẦN NHẤT ===== */
/*
Giả định bảng:
orders(id, user_id, status, created_at)
order_items(order_id, product_name)
*/
$order_sql = "SELECT o.id, o.status
              FROM orders o
              WHERE o.user_id = ?
              ORDER BY o.created_at DESC
              LIMIT 3";

$order_stmt = $conn_user->prepare($order_sql);
$order_stmt->bind_param("i", $user_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

$orders = [];
while ($row = $order_result->fetch_assoc()) {
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Profile | 36 Jewelry</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
<?php echo file_get_contents("profile.css"); ?>
</style>

</head>
<body>

<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="../index_profile.php" class="home-btn"><i class="fas fa-home"></i> Home</a>
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
      <a href="profile.php" class="icon-link"><i class="fas fa-user"></i></a>
    </div>
  </div>
</header>

<div class="profile-container">
  
  <div class="profile-card">

    <div class="profile-header">
      <div class="avatar-wrapper">
        <img src="../images/chocolat/ava.jpg" class="avatar">

        <form method="post" enctype="multipart/form-data" class="change-photo-form">
          <label for="avatar-upload" class="change-photo-label">Change Photo</label>
          <input type="file" id="avatar-upload" name="avatar">
        </form>
      </div>

      <h2><?php echo $user['full_name'] ?? $user['username']; ?></h2>
      <p class="role">Member of 36 Jewelry</p>
    </div>

    <div class="profile-body">

      <div class="info">
        <h3>Personal Information</h3>
        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
        <p><strong>Phone:</strong> <?php echo $user['phone'] ?? 'Chưa có'; ?></p>
        <p><strong>Address:</strong> <?php echo $user['address'] ?? 'Chưa có'; ?></p>
      </div>

      <div class="orders">
        <h3>Recent Orders</h3>

        <ul>
        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $o): ?>
                <li>
                  Order #<?php echo $o['id']; ?> – 
                  <span><?php echo $o['status']; ?></span>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>No orders yet</li>
        <?php endif; ?>
        </ul>

      </div>

    </div>

    <div class="profile-footer">

      <div class="top-buttons">
        <button class="btn edit" onclick="window.location.href='edit_profile.php'">Edit Profile</button>

        <button class="btn logout" onclick="window.location.href='logout.php'">Log Out</button>
      </div>

      <button class="btn view" onclick="window.location.href='history.php'">
        History
      </button>

    </div>

  </div>
</div>

</body>
</html>