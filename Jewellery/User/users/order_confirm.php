<?php
// order_confirm.php - Bỏ kiểm tra đăng nhập, dùng user_id mặc định = 1

session_start();
require_once __DIR__ . '/../../config/config.php';

// Định nghĩa BASE_URL và IMG_URL
if (!defined('BASE_URL')) define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))  define('IMG_URL',  BASE_URL . 'images/');

// Bỏ kiểm tra đăng nhập, gán user_id mặc định (có thể sửa lại số này)
$user_id = 1; // user_id test, đảm bảo tồn tại trong database

// Các link cho header (điều chỉnh theo cấu trúc thư mục)
$link_home    = BASE_URL . 'User/index.php';          // Trang chủ
$link_cart    = BASE_URL . 'User/Cart/Jewelry-cart.html';     // Giỏ hàng (nếu có cart.php trong users, nếu không thì sửa)
$link_profile = BASE_URL . 'User/users/profile.php';  // Hồ sơ
$link_search  = BASE_URL . 'User/Search/search.html';   // Tìm kiếm

// Lấy thông tin khách hàng
$user = [
    'email'     => '',
    'full_name' => '',
    'phone'     => '',
    'address'   => ''
];
$stmt = $conn->prepare("
    SELECT u.email,
           COALESCE(c.full_name, u.name, u.username, '') AS full_name,
           COALESCE(c.phone, '')   AS phone,
           COALESCE(c.address, '') AS address
    FROM users u
    LEFT JOIN customers c ON u.id = c.user_id
    WHERE u.id = ?
    LIMIT 1
");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $fetched = $stmt->get_result()->fetch_assoc();
    if ($fetched) $user = $fetched;
    $stmt->close();
}

// Lấy giỏ hàng và tính tổng
$cart_items   = [];
$total_amount = 0;

$cart_stmt = $conn->prepare("
    SELECT c.product_id, c.quantity,
           p.name, p.price, p.image,
           p.cost_price, p.profit_percent
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
if ($cart_stmt) {
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    while ($row = $cart_result->fetch_assoc()) {
        $cost   = (float)($row['cost_price']     ?? 0);
        $profit = (int)  ($row['profit_percent']  ?? 0);
        $price  = (float)($row['price']           ?? 0);
        $selling_price = ($cost > 0) ? $cost * (1 + $profit / 100) : $price;

        $qty        = (int)$row['quantity'];
        $item_total = $selling_price * $qty;

        $cart_items[] = [
            'id'       => $row['product_id'],
            'name'     => $row['name'],
            'image'    => IMG_URL . $row['image'],
            'price'    => $selling_price,
            'quantity' => $qty,
            'total'    => $item_total,
        ];
        $total_amount += $item_total;
    }
    $cart_stmt->close();
}

// Xử lý đặt hàng
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name      = trim($_POST['fullname']      ?? '');
    $phone          = trim($_POST['phone']         ?? '');
    $payment_method = trim($_POST['payment']       ?? 'cod');
    $address_option = trim($_POST['addressOption'] ?? 'saved');
    $address        = ($address_option === 'new')
                        ? trim($_POST['c_address'] ?? '')
                        : trim($_POST['saved_address'] ?? $user['address']);

    if (!$full_name || !$phone || !$address) {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif (empty($cart_items)) {
        $error = 'Giỏ hàng của bạn đang trống.';
    } else {
        $conn->begin_transaction();
        try {
            // Upsert customers
            $cust_stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
            $cust_stmt->bind_param("i", $user_id);
            $cust_stmt->execute();
            $cust_result = $cust_stmt->get_result();
            $cust_stmt->close();

            if ($cust_result->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO customers (user_id, full_name, phone, address) VALUES (?, ?, ?, ?)");
                $ins->bind_param("isss", $user_id, $full_name, $phone, $address);
                $ins->execute();
                $customer_id = $conn->insert_id;
                $ins->close();
            } else {
                $cust_row    = $cust_result->fetch_assoc();
                $customer_id = (int)$cust_row['id'];
                $upd = $conn->prepare("UPDATE customers SET full_name=?, phone=?, address=? WHERE id=?");
                $upd->bind_param("sssi", $full_name, $phone, $address, $customer_id);
                $upd->execute();
                $upd->close();
            }

            // Tạo đơn hàng
            $order_number = 'ORD' . date('Ymd') . '-' . rand(1000, 9999);
            $order_date   = date('Y-m-d');
            $status       = 'Pending';

            $ins_order = $conn->prepare("
                INSERT INTO orders (order_number, customer_id, order_date, total_amount, payment_method, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $ins_order->bind_param("sisdss", $order_number, $customer_id, $order_date, $total_amount, $payment_method, $status);
            $ins_order->execute();
            $order_id = $conn->insert_id;
            $ins_order->close();

            // Thêm chi tiết đơn hàng và cập nhật stock
            $ins_item = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($cart_items as $item) {
                $ins_item->bind_param("iissdd", $order_id, $item['id'], $item['name'], $item['quantity'], $item['price'], $item['total']);
                $ins_item->execute();

                // Giảm stock
                $upd_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $upd_stock->bind_param("isi", $item['quantity'], $item['id'], $item['quantity']);
                $upd_stock->execute();
                $upd_stock->close();
            }
            $ins_item->close();

            // Xoá giỏ hàng
            $del = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $del->bind_param("i", $user_id);
            $del->execute();
            $del->close();

            $conn->commit();

            // Lưu thông tin đơn hàng vào session
            $_SESSION['last_order_id']     = $order_id;
            $_SESSION['last_order_number'] = $order_number;

            header("Location: order_success.php?order_id=" . $order_id);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Đặt hàng thất bại: ' . $e->getMessage() . '. Vui lòng thử lại.';
        }
    }
}
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
    /* Giữ nguyên CSS như file gốc, không thay đổi */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Cormorant Garamond', serif;
    }

    body {
      background: linear-gradient(rgba(255,255,255,0.8), rgba(255,255,255,0.8)),
                  url("../images/chocolat/pfb10.jpg") no-repeat center center/cover;
      min-height: 100vh;
      color: #333;
      display: flex;
      flex-direction: column;
      align-items: center;
      opacity: 0;
      animation: fadeInBody 1s forwards;
    }

    @keyframes fadeInBody { to { opacity: 1; } }

    .main-content {
      width: 90%;
      max-width: 750px;
      background: rgba(255,255,255,0.95);
      border-radius: 20px;
      margin-top: 20px;
      padding: 40px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      animation: fadeInUp 1s ease forwards;
      transform: translateY(20px);
      opacity: 0;
      position: relative;
    }
    @keyframes fadeInUp { to { transform: translateY(0); opacity: 1; } }

    .back-button {
      position: absolute;
      top: 20px;
      left: 20px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 14px;
      font-size: 16px;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(135deg, #b8860b, #d4a017);
      border-radius: 30px;
      text-decoration: none;
      box-shadow: 0 4px 10px rgba(184, 134, 11, 0.3);
      transition: all 0.3s ease;
      border: none;
      outline: none;
      cursor: pointer;
    }
    .back-button:hover {
      transform: translateX(-3px) scale(1.05);
      box-shadow: 0 6px 15px rgba(184, 134, 11, 0.4);
      background: linear-gradient(135deg, #c89715, #e0b83f);
    }

    h2 {
      text-align: center;
      color: #b8860b;
      font-size: 28px;
      margin-bottom: 30px;
    }

    form { display: flex; flex-direction: column; gap: 20px; }

    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-weight: 600; color: #555; }
    .form-group input, .form-group select {
      padding: 10px 15px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 16px;
      transition: border-color 0.3s, box-shadow 0.3s;
    }
    .form-group input:focus, .form-group select:focus {
      border-color: #b8860b;
      box-shadow: 0 0 5px #b8860b;
      outline: none;
    }

    .form-check { display: inline-flex; align-items: center; gap: 5px; margin-right: 20px; }
    .form-check input { width: 18px; height: 18px; }

    .btn-submit {
      text-decoration: none;
      text-align: center;
      padding: 12px 20px;
      background: linear-gradient(135deg, #b8860b, #ffdb58);
      color: #fff;
      font-weight: 700;
      border-radius: 10px;
      transition: transform 0.3s, background 0.3s;
      cursor: pointer;
      display: inline-block;
      border: none;
      font-size: 16px;
    }
    .btn-submit:hover {
      transform: scale(1.05);
      background: linear-gradient(135deg, #ffdb58, #b8860b);
      color: #333;
    }

    .profile-info {
      background: rgba(255, 250, 240, 0.9);
      border: 1px solid #e0c97f;
      border-radius: 15px;
      padding: 20px 25px;
      margin-bottom: 30px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .profile-info h3 {
      color: #b8860b;
      margin-bottom: 10px;
      font-size: 22px;
      border-bottom: 1px solid #e0c97f;
      padding-bottom: 8px;
    }
    .profile-info p { margin: 6px 0; font-size: 16px; }

    .cart-summary {
      background: rgba(255, 250, 240, 0.9);
      border: 1px solid #e0c97f;
      border-radius: 15px;
      padding: 20px 25px;
      margin-bottom: 30px;
    }
    .cart-summary h3 {
      color: #b8860b;
      margin-bottom: 10px;
      font-size: 22px;
      border-bottom: 1px solid #e0c97f;
      padding-bottom: 8px;
    }
    .cart-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px dashed #e0c97f;
    }
    .cart-item:last-child { border-bottom: none; }
    .cart-item .item-name { flex: 1; }
    .cart-item .item-qty { margin: 0 12px; color: #666; }
    .cart-item .item-price { font-weight: 700; color: #b8860b; }
    .cart-total-row {
      display: flex;
      justify-content: space-between;
      margin-top: 12px;
      padding-top: 10px;
      border-top: 2px solid #e0c97f;
      font-size: 18px;
      font-weight: 700;
      color: #b8860b;
    }
    .empty-cart { text-align: center; color: #aaa; font-style: italic; padding: 12px 0; }

    .error-message {
      background: #ffebee;
      color: #c62828;
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      border: 1px solid #f5c2c2;
    }

    .footer { margin-top: 40px; text-align: center; font-size: 14px; color: #555; padding: 20px 0; }

    @media (max-width: 600px) {
      .main-content { padding: 25px; margin-top: 10px; }
    }
  </style>
</head>
<body>

<!-- Header -->
<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="<?= $link_home ?>" class="home-btn"><i class="fas fa-home"></i> Home</a>
    </div>
    <div class="center">
      <a href="<?= $link_home ?>">
        <img src="<?= IMG_URL ?>36-logo.png" alt="Jewelry Store Logo" class="header-logo">
      </a>
      <div class="search-box">
        <input type="text" id="search-input" placeholder="Search products...">
        <button onclick="doSearch()"><i class="fas fa-search"></i></button>
      </div>
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

  <?php if ($error): ?>
    <div class="error-message"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="profile-info">
    <h3>Customer Information</h3>
    <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name'] ?: 'Not set') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?: 'Not set') ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?: 'Not set') ?></p>
  </div>

  <div class="cart-summary">
    <h3>Order Summary</h3>
    <?php if (empty($cart_items)): ?>
      <p class="empty-cart">Your cart is empty.</p>
    <?php else: ?>
      <?php foreach ($cart_items as $item): ?>
        <div class="cart-item">
          <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
          <span class="item-qty">x<?= $item['quantity'] ?></span>
          <span class="item-price">$<?= number_format($item['total'], 2) ?></span>
        </div>
      <?php endforeach; ?>
      <div class="cart-total-row">
        <span>Total</span>
        <span>$<?= number_format($total_amount, 2) ?></span>
      </div>
    <?php endif; ?>
  </div>

  <form method="POST" action="" id="checkoutForm">
    <div class="form-group">
      <label for="fullname">Full Name <span style="color:red">*</span></label>
      <input type="text" id="fullname" name="fullname" required
             placeholder="Enter your full name"
             value="<?= htmlspecialchars($_POST['fullname'] ?? $user['full_name']) ?>">
    </div>

    <div class="form-group">
      <label for="phone">Phone Number <span style="color:red">*</span></label>
      <input type="tel" id="phone" name="phone" required
             placeholder="Enter your phone number"
             value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone']) ?>">
    </div>

    <div class="form-group">
      <label>Address Option</label>
      <div>
        <label class="form-check">
          <input type="radio" name="addressOption" value="saved"
            <?= ($_POST['addressOption'] ?? 'saved') === 'saved' ? 'checked' : '' ?>>
          Use Saved Address
        </label>
        <label class="form-check">
          <input type="radio" name="addressOption" value="new"
            <?= ($_POST['addressOption'] ?? '') === 'new' ? 'checked' : '' ?>>
          Enter New Address
        </label>
      </div>
    </div>

    <div class="form-group" id="savedAddressSection">
      <label for="saved_address">Saved Address</label>
      <select id="saved_address" name="saved_address">
        <?php if ($user['address']): ?>
          <option value="<?= htmlspecialchars($user['address']) ?>" selected>
            <?= htmlspecialchars($user['address']) ?>
          </option>
        <?php else: ?>
          <option value="">No saved address</option>
        <?php endif; ?>
      </select>
    </div>

    <div class="form-group" id="newAddressSection" style="display:none;">
      <label for="c_address">New Address <span style="color:red">*</span></label>
      <input type="text" id="c_address" name="c_address"
             placeholder="Enter your street address"
             value="<?= htmlspecialchars($_POST['c_address'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label for="payment">Payment Method</label>
      <select id="payment" name="payment">
        <option value="cod" <?= ($_POST['payment'] ?? 'cod') === 'cod' ? 'selected' : '' ?>>Cash on Delivery</option>
        <option value="bank" <?= ($_POST['payment'] ?? '') === 'bank' ? 'selected' : '' ?>>Bank Transfer</option>
        <option value="online" <?= ($_POST['payment'] ?? '') === 'online' ? 'selected' : '' ?>>Online Payment</option>
      </select>
    </div>

    <button type="submit" class="btn-submit" <?= empty($cart_items) ? 'disabled' : '' ?>>
      <?= empty($cart_items) ? 'Cart is Empty' : 'Review & Confirm Order' ?>
    </button>
  </form>
</main>

<footer class="footer">
  <p>&copy; 2025 36 Jewelry. All rights reserved.</p>
</footer>

<script>
  function doSearch() {
    const kw = document.getElementById('search-input').value.trim();
    if (kw) window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(kw);
  }

  document.addEventListener("DOMContentLoaded", () => {
    const savedSection = document.getElementById("savedAddressSection");
    const newSection   = document.getElementById("newAddressSection");
    const cAddr        = document.getElementById("c_address");

    function toggleAddress() {
      const val = document.querySelector('input[name="addressOption"]:checked')?.value;
      if (val === 'new') {
        savedSection.style.display = 'none';
        newSection.style.display   = 'block';
        cAddr.setAttribute('required', 'required');
      } else {
        savedSection.style.display = 'block';
        newSection.style.display   = 'none';
        cAddr.removeAttribute('required');
      }
    }

    document.querySelectorAll('input[name="addressOption"]')
      .forEach(r => r.addEventListener('change', toggleAddress));
    toggleAddress();

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
      const name  = document.getElementById('fullname').value.trim();
      const phone = document.getElementById('phone').value.trim();
      if (!name || !phone) {
        e.preventDefault();
        alert('Please fill in Name and Phone.');
        return;
      }

      const addrOpt = document.querySelector('input[name="addressOption"]:checked')?.value;
      if (addrOpt === 'new' && !cAddr.value.trim()) {
        e.preventDefault();
        alert('Please enter your new address.');
        return;
      }

      const total = '<?= number_format($total_amount, 2) ?>';
      if (!confirm(`Confirm order?\n\nName: ${name}\nPhone: ${phone}\nTotal: $${total}`)) {
        e.preventDefault();
      }
    });
  });
</script>
</body>
</html>