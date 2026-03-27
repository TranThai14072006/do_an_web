<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

define('BASE_URL', '/do_an_web/Jewellery/'); // <-- thêm ngay đây
define('IMG_URL', BASE_URL . 'images/');

// ... phần còn lại

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "User/Login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Lấy thông tin người dùng
$stmt = $conn->prepare("
    SELECT u.email, c.full_name, c.phone, c.address
    FROM users u
    LEFT JOIN customers c ON u.id = c.user_id
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) $user = ['email' => '', 'full_name' => '', 'phone' => '', 'address' => ''];

// Lấy giỏ hàng và sản phẩm
$cart_items = [];
$total_amount = 0;
$cart_stmt = $conn->prepare("SELECT product_id, quantity FROM cart WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$product_ids = [];
$cart_qty = [];
while ($row = $cart_result->fetch_assoc()) {
    $product_ids[] = $row['product_id'];
    $cart_qty[$row['product_id']] = $row['quantity'];
}
if (!empty($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('s', count($product_ids));
    $sql_products = "SELECT id, name, price FROM products WHERE id IN ($placeholders)";
    $prod_stmt = $conn->prepare($sql_products);
    $prod_stmt->bind_param($types, ...$product_ids);
    $prod_stmt->execute();
    $prod_result = $prod_stmt->get_result();
    while ($prod = $prod_result->fetch_assoc()) {
        $qty = $cart_qty[$prod['id']];
        $item_total = $prod['price'] * $qty;
        $cart_items[] = [
            'id' => $prod['id'],
            'name' => $prod['name'],
            'price' => $prod['price'],
            'quantity' => $qty,
            'total' => $item_total
        ];
        $total_amount += $item_total;
    }
}

// Xử lý submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['fullname'] ?? $user['full_name']);
    $phone = trim($_POST['phone'] ?? $user['phone']);
    $address_option = $_POST['addressOption'] ?? 'saved';
    if ($address_option === 'saved') {
        $address = $user['address'];
    } else {
        $address = trim($_POST['c_address'] ?? '');
    }
    $payment_method = $_POST['payment'] ?? 'cod';

    if (empty($full_name) || empty($phone) || empty($address)) {
        $error = "Please fill all required fields.";
    } elseif (empty($cart_items)) {
        $error = "Your cart is empty.";
    } else {
        $conn->begin_transaction();
        try {
            // Lấy hoặc tạo customer
            $cust_stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
            $cust_stmt->bind_param("i", $user_id);
            $cust_stmt->execute();
            $cust_result = $cust_stmt->get_result();
            if ($cust_result->num_rows === 0) {
                $insert_cust = $conn->prepare("INSERT INTO customers (user_id, full_name, phone, address) VALUES (?, ?, ?, ?)");
                $insert_cust->bind_param("isss", $user_id, $full_name, $phone, $address);
                $insert_cust->execute();
                $customer_id = $conn->insert_id;
            } else {
                $customer_id = $cust_result->fetch_assoc()['id'];
                $update_cust = $conn->prepare("UPDATE customers SET full_name=?, phone=?, address=? WHERE id=?");
                $update_cust->bind_param("sssi", $full_name, $phone, $address, $customer_id);
                $update_cust->execute();
            }

            // Tạo đơn hàng
            $order_number = 'ORD' . date('Ymd') . '-' . rand(1000, 9999);
            $order_date = date('Y-m-d');
            $status = 'Pending';
            $insert_order = $conn->prepare("INSERT INTO orders (order_number, customer_id, order_date, total_amount, status) VALUES (?, ?, ?, ?, ?)");
            $insert_order->bind_param("sisds", $order_number, $customer_id, $order_date, $total_amount, $status);
            $insert_order->execute();
            $order_id = $conn->insert_id;

            // Thêm chi tiết đơn hàng
            $insert_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($cart_items as $item) {
                $insert_item->bind_param("issidd", $order_id, $item['id'], $item['name'], $item['quantity'], $item['price'], $item['total']);
                $insert_item->execute();
            }

            // Xóa giỏ hàng
            $delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $delete_cart->bind_param("i", $user_id);
            $delete_cart->execute();

            $conn->commit();
            header("Location: order_success.php?order_id=" . $order_id);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error placing order. Please try again.";
        }
    }
}


$link_home    = BASE_URL . 'User/index.php';
$link_cart    = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_logout  = BASE_URL . 'User/users/logout.php';
$link_search  = BASE_URL . 'User/users/search.php';
$link_history = BASE_URL . 'User/users/History.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout | 36 Jewelry</title>
  <link rel="stylesheet" href="search.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Giữ nguyên CSS từ file HTML gốc */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cormorant Garamond', serif; }
    body {
      background: linear-gradient(rgba(255,255,255,0.8), rgba(255,255,255,0.8)), url("../images/chocolat/pfb10.jpg") no-repeat center center/cover;
      min-height: 100vh; color: #333; display: flex; flex-direction: column; align-items: center;
      opacity: 0; animation: fadeInBody 1s forwards;
    }
    @keyframes fadeInBody { to { opacity: 1; } }
    .main-content {
      width: 90%; max-width: 750px; background: rgba(255,255,255,0.95); border-radius: 20px;
      margin-top: 20px; padding: 40px; box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      animation: fadeInUp 1s ease forwards; transform: translateY(20px); opacity: 0; position: relative;
    }
    @keyframes fadeInUp { to { transform: translateY(0); opacity: 1; } }
    .back-button {
      position: absolute; top: 20px; left: 20px; display: inline-flex; align-items: center; gap: 8px;
      padding: 8px 14px; font-size: 16px; font-weight: 600; color: #fff;
      background: linear-gradient(135deg, #b8860b, #d4a017); border-radius: 30px; text-decoration: none;
      box-shadow: 0 4px 10px rgba(184, 134, 11, 0.3); transition: all 0.3s ease; border: none; outline: none; cursor: pointer;
    }
    .back-button:hover { transform: translateX(-3px) scale(1.05); box-shadow: 0 6px 15px rgba(184, 134, 11, 0.4); background: linear-gradient(135deg, #c89715, #e0b83f); }
    h2 { text-align: center; color: #b8860b; font-size: 28px; margin-bottom: 30px; }
    form { display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-weight: 600; color: #555; }
    .form-group input, .form-group select { padding: 10px 15px; border-radius: 8px; border: 1px solid #ccc; font-size: 16px; transition: border-color 0.3s, box-shadow 0.3s; }
    .form-group input:focus, .form-group select:focus { border-color: #b8860b; box-shadow: 0 0 5px #b8860b; outline: none; }
    .form-check { display: inline-flex; align-items: center; gap: 5px; margin-right: 20px; }
    .form-check input { width: 18px; height: 18px; }
    .btn-submit {
      text-decoration: none; text-align: center; padding: 12px 20px;
      background: linear-gradient(135deg, #b8860b, #ffdb58); color: #fff; font-weight: 700;
      border-radius: 10px; transition: transform 0.3s, background 0.3s; cursor: pointer; display: inline-block; border: none; font-size: 16px;
    }
    .btn-submit:hover { transform: scale(1.05); background: linear-gradient(135deg, #ffdb58, #b8860b); color: #333; }
    .profile-info {
      background: rgba(255, 250, 240, 0.9); border: 1px solid #e0c97f; border-radius: 15px;
      padding: 20px 25px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .profile-info h3 { color: #b8860b; margin-bottom: 10px; font-size: 22px; border-bottom: 1px solid #e0c97f; padding-bottom: 8px; }
    .profile-info p { margin: 6px 0; font-size: 16px; }
    .footer { margin-top: 40px; text-align: center; font-size: 14px; color: #555; padding: 20px 0; }
    .error-message { background: #ffebee; color: #c62828; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
    @media (max-width: 600px) { .main-content { padding: 25px; margin-top: 10px; } }
  </style>
</head>
<body>

<header class="header-container">
  <div class="search-bar">
    <div class="left"><a href="<?= $link_home ?>" class="home-btn"><i class="fas fa-home"></i> Home</a></div>
    <div class="center">
      <a href="<?= $link_home ?>"><img src="<?= IMG_URL ?>36-logo.png" class="header-logo"></a>
      <div class="search-box"><input type="text" id="search-input" placeholder="Search products..."><button onclick="doSearch()"><i class="fas fa-search"></i></button></div>
    </div>
    <div class="right">
      <a href="<?= $link_cart ?>" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
      <a href="<?= $link_profile ?>" class="icon-link"><i class="fas fa-user"></i></a>
    </div>
  </div>
</header>

<main class="main-content">
  <button class="back-button" onclick="window.history.back();">&#8592;</button>
  <h2>Shipping Information</h2>
  <?php if (isset($error)): ?>
    <div class="error-message"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <div class="profile-info">
    <h3>Customer Information</h3>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name'] ?: 'Not set') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?: 'Not set') ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?: 'Not set') ?></p>
  </div>
  <form method="POST" action="">
    <div class="form-group"><label for="name">Full Name</label><input type="text" id="name" name="fullname" required value="<?= htmlspecialchars($user['full_name']) ?>"></div>
    <div class="form-group"><label for="phone">Phone Number</label><input type="tel" id="phone" name="phone" required value="<?= htmlspecialchars($user['phone']) ?>"></div>
    <div class="form-group">
      <label>Address Option</label>
      <div class="form-check"><input type="radio" name="addressOption" id="savedAddressOption" value="saved" checked><label for="savedAddressOption">Use Saved Address</label></div>
      <div class="form-check"><input type="radio" name="addressOption" id="newAddressOption" value="new"><label for="newAddressOption">Enter New Address</label></div>
    </div>
    <div class="form-group" id="savedAddressSection"><label for="saved_address">Saved Address</label><select id="saved_address" name="saved_address"><option value="1"><?= htmlspecialchars($user['address'] ?: 'No saved address') ?></option></select></div>
    <div class="form-group" id="newAddressSection" style="display:none;"><label for="c_address">New Address</label><input type="text" id="c_address" name="c_address" placeholder="Enter your street address"></div>
    <div class="form-group"><label for="payment">Payment Method</label><select id="payment" name="payment"><option value="cod" selected>Cash on Delivery</option><option value="bank">Bank Transfer</option><option value="online">Online Payment</option></select></div>
    <button type="submit" class="btn-submit">Review & Confirm Order</button>
  </form>
</main>
<footer class="footer"><p>&copy; 2025 36 Jewelry. All rights reserved.</p></footer>
<script>
  function doSearch() { const keyword = document.getElementById('search-input').value.trim(); if (keyword !== '') window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(keyword); }
  document.addEventListener("DOMContentLoaded", () => {
    const saved = document.getElementById("savedAddressSection"), newAddr = document.getElementById("newAddressSection"), radios = document.getElementsByName("addressOption");
    radios.forEach(radio => radio.addEventListener("change", () => { if (radio.value === "saved" && radio.checked) { saved.style.display = "block"; newAddr.style.display = "none"; } else if (radio.value === "new" && radio.checked) { saved.style.display = "none"; newAddr.style.display = "block"; } }));
  });
</script>
</body>
</html>