<?php
// ═══════════════════════════════════════════════════════════════
// File: Jewellery/User/users/order_confirm.php
// Fixes:
//   ✔ Bỏ define() trùng lặp
//   ✔ Kiểm tra đăng nhập đúng cách
//   ✔ Lấy giỏ hàng từ bảng cart trong DB
//   ✔ Lưu đơn hàng với transaction an toàn
//   ✔ Redirect về order_success.php sau khi đặt thành công
// ═══════════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../../config/config.php';

// ── Khai báo constants (chỉ 1 lần) ──────────────────────────
if (!defined('BASE_URL')) define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))  define('IMG_URL',  BASE_URL . 'images/');

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

// ── Kiểm tra đăng nhập ───────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'User/Login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}
$user_id = (int)$_SESSION['user_id'];

// ── Lấy thông tin user từ DB ─────────────────────────────────
$user = ['email' => '', 'full_name' => '', 'phone' => '', 'address' => ''];
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

// ── Lấy giỏ hàng từ bảng cart ───────────────────────────────
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
        // Tính giá bán
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

// ── Xử lý POST: Đặt hàng ─────────────────────────────────────
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
        $error = 'Please fill in all required fields.';
    } elseif (empty($cart_items)) {
        $error = 'Your cart is empty.';
    } else {
        $conn->begin_transaction();
        try {
            // ── Upsert bảng customers ─────────────────────
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

            // ── Tạo đơn hàng ──────────────────────────────
            $order_number = 'ORD' . date('Ymd') . '-' . rand(1000, 9999);
            $order_date   = date('Y-m-d');
            $status       = 'Pending';

            $ins_order = $conn->prepare("
                INSERT INTO orders (order_number, customer_id, order_date, total_amount, payment_method, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $ins_order->bind_param("sisdss",
                $order_number, $customer_id,
                $order_date, $total_amount,
                $payment_method, $status
            );
            $ins_order->execute();
            $order_id = $conn->insert_id;
            $ins_order->close();

            // ── Thêm chi tiết đơn hàng ────────────────────
            $ins_item = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($cart_items as $item) {
                $ins_item->bind_param("sissdd",
                    $order_id,
                    $item['id'],
                    $item['name'],
                    $item['quantity'],
                    $item['price'],
                    $item['total']
                );
                $ins_item->execute();

                // Giảm stock
                $upd_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $upd_stock->bind_param("isi", $item['quantity'], $item['id'], $item['quantity']);
                $upd_stock->execute();
                $upd_stock->close();
            }
            $ins_item->close();

            // ── Xoá giỏ hàng ──────────────────────────────
            $del = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $del->bind_param("i", $user_id);
            $del->execute();
            $del->close();

            $conn->commit();

            // Lưu vào session để trang success dùng
            $_SESSION['last_order_id']     = $order_id;
            $_SESSION['last_order_number'] = $order_number;

            header('Location: order_success.php?order_id=' . $order_id);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error placing order: ' . $e->getMessage() . '. Please try again.';
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
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cormorant Garamond', serif; }

    body {
      background: linear-gradient(rgba(255,255,255,0.8), rgba(255,255,255,0.8)),
                  url("<?= BASE_URL ?>images/chocolat/pfb10.jpg") no-repeat center center/cover;
      min-height: 100vh; color: #333;
      display: flex; flex-direction: column; align-items: center;
      opacity: 0; animation: fadeInBody 1s forwards;
    }
    @keyframes fadeInBody { to { opacity: 1; } }

    .main-content {
      width: 90%; max-width: 750px;
      background: rgba(255,255,255,0.95);
      border-radius: 20px; margin-top: 20px; padding: 40px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      animation: fadeInUp 1s ease forwards;
      transform: translateY(20px); opacity: 0; position: relative;
    }
    @keyframes fadeInUp { to { transform: translateY(0); opacity: 1; } }

    .back-button {
      position: absolute; top: 20px; left: 20px;
      display: inline-flex; align-items: center; gap: 8px;
      padding: 8px 14px; font-size: 16px; font-weight: 600; color: #fff;
      background: linear-gradient(135deg, #b8860b, #d4a017);
      border-radius: 30px; text-decoration: none;
      box-shadow: 0 4px 10px rgba(184,134,11,0.3);
      transition: all 0.3s ease; border: none; outline: none; cursor: pointer;
    }
    .back-button:hover {
      transform: translateX(-3px) scale(1.05);
      box-shadow: 0 6px 15px rgba(184,134,11,0.4);
      background: linear-gradient(135deg, #c89715, #e0b83f);
    }

    h2 { text-align: center; color: #b8860b; font-size: 28px; margin-bottom: 30px; }

    /* Alert */
    .error-message {
      background: #ffebee; color: #c62828;
      padding: 12px 16px; border-radius: 8px;
      margin-bottom: 20px; text-align: center;
      border: 1px solid #f5c2c2;
    }

    /* Profile info */
    .profile-info {
      background: rgba(255,250,240,0.9);
      border: 1px solid #e0c97f; border-radius: 15px;
      padding: 20px 25px; margin-bottom: 24px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }
    .profile-info h3 {
      color: #b8860b; margin-bottom: 10px; font-size: 22px;
      border-bottom: 1px solid #e0c97f; padding-bottom: 8px;
    }
    .profile-info p { margin: 6px 0; font-size: 16px; }

    /* Cart summary */
    .cart-summary {
      background: #fffdf5; border: 1px solid #e0c97f;
      border-radius: 12px; padding: 18px 22px; margin-bottom: 24px;
    }
    .cart-summary h3 {
      color: #b8860b; font-size: 20px; margin-bottom: 12px;
      border-bottom: 1px solid #f0dea0; padding-bottom: 8px;
    }
    .cart-item {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 0; border-bottom: 1px dashed #f0dea0; font-size: 15px;
    }
    .cart-item:last-child { border-bottom: none; }
    .cart-item .item-name  { color: #444; flex: 1; }
    .cart-item .item-qty   { color: #888; font-size: 13px; margin: 0 12px; white-space: nowrap; }
    .cart-item .item-price { font-weight: 700; color: #b8860b; white-space: nowrap; }
    .cart-total-row {
      display: flex; justify-content: space-between;
      margin-top: 12px; padding-top: 10px;
      border-top: 2px solid #e0c97f;
      font-size: 18px; font-weight: 700; color: #b8860b;
    }
    .empty-cart { text-align: center; color: #aaa; font-style: italic; padding: 12px 0; }

    /* Form */
    form { display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-weight: 600; color: #555; }
    .form-group input, .form-group select {
      padding: 10px 15px; border-radius: 8px;
      border: 1px solid #ccc; font-size: 16px;
      transition: border-color 0.3s, box-shadow 0.3s;
    }
    .form-group input:focus, .form-group select:focus {
      border-color: #b8860b; box-shadow: 0 0 5px #b8860b; outline: none;
    }
    .form-check { display: inline-flex; align-items: center; gap: 5px; margin-right: 20px; }
    .form-check input { width: 18px; height: 18px; }

    .btn-submit {
      text-align: center; padding: 14px 20px;
      background: linear-gradient(135deg, #b8860b, #ffdb58);
      color: #fff; font-weight: 700; font-size: 16px;
      border-radius: 10px; border: none; cursor: pointer; width: 100%;
      transition: transform 0.3s, background 0.3s;
    }
    .btn-submit:hover {
      transform: scale(1.02);
      background: linear-gradient(135deg, #ffdb58, #b8860b); color: #333;
    }
    .btn-submit:disabled { background: #ccc; cursor: not-allowed; transform: none; }

    .footer { margin-top: 40px; text-align: center; font-size: 14px; color: #555; padding: 20px 0; }

    @media (max-width: 600px) { .main-content { padding: 25px; margin-top: 10px; } }
  </style>
</head>
<body>

<!-- ══ HEADER ════════════════════════════════════════════════════ -->
<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="<?= $link_home ?>" class="home-btn"><i class="fas fa-home"></i> Home</a>
    </div>
    <div class="center">
      <a href="<?= $link_home ?>">
        <img src="<?= IMG_URL ?>36-logo.png" alt="36 Jewelry" class="header-logo">
      </a>
      <div class="search-box">
        <input type="text" id="search-input" placeholder="Search products..."
               onkeydown="if(event.key==='Enter') doSearch()">
        <button onclick="doSearch()"><i class="fas fa-search"></i></button>
      </div>
    </div>
    <div class="right">
      <a href="<?= $link_cart ?>" class="icon-link" title="Cart">
        <i class="fas fa-shopping-cart"></i>
        <?php if (!empty($cart_items)): ?>
          <span style="font-size:11px;background:#b8860b;color:#fff;border-radius:50%;padding:1px 5px;vertical-align:top;">
            <?= count($cart_items) ?>
          </span>
        <?php endif; ?>
      </a>
      <a href="<?= $link_profile ?>" class="icon-link" title="Profile">
        <i class="fas fa-user"></i>
      </a>
    </div>
  </div>
</header>

<!-- ══ MAIN ══════════════════════════════════════════════════════ -->
<main class="main-content">
  <button class="back-button" onclick="window.history.back();">&#8592; Back</button>

  <h2>Shipping Information</h2>

  <?php if ($error): ?>
    <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Thông tin khách hàng -->
  <div class="profile-info">
    <h3><i class="fas fa-user-circle"></i> Customer Information</h3>
    <p><strong>Name:</strong>  <?= htmlspecialchars($user['full_name'] ?: 'Not set') ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']     ?: 'Not set') ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']     ?: 'Not set') ?></p>
  </div>

  <!-- Tóm tắt giỏ hàng -->
  <div class="cart-summary">
    <h3><i class="fas fa-shopping-basket"></i> Order Summary</h3>
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

  <!-- Form đặt hàng -->
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
          <input type="radio" name="addressOption" id="savedAddressOption" value="saved"
            <?= ($_POST['addressOption'] ?? 'saved') === 'saved' ? 'checked' : '' ?>>
          Use Saved Address
        </label>
        <label class="form-check">
          <input type="radio" name="addressOption" id="newAddressOption" value="new"
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
        <option value="cod"    <?= ($_POST['payment'] ?? 'cod') === 'cod'    ? 'selected' : '' ?>>Cash on Delivery</option>
        <option value="bank"   <?= ($_POST['payment'] ?? '') === 'bank'   ? 'selected' : '' ?>>Bank Transfer</option>
        <option value="online" <?= ($_POST['payment'] ?? '') === 'online' ? 'selected' : '' ?>>Online Payment</option>
      </select>
    </div>

    <button type="submit" class="btn-submit" <?= empty($cart_items) ? 'disabled' : '' ?>>
      <i class="fas fa-check-circle"></i>
      <?= empty($cart_items) ? 'Cart is Empty' : 'Review & Confirm Order' ?>
    </button>

  </form>
</main>

<footer class="footer">
  <p>&copy; 2025 36 Jewelry. All rights reserved.</p>
</footer>

<script>
  // ── Tìm kiếm ────────────────────────────────────────────────
  function doSearch() {
    const kw = document.getElementById('search-input').value.trim();
    if (kw) window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(kw);
  }

  // ── Toggle địa chỉ ──────────────────────────────────────────
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

    // Khởi tạo trạng thái ban đầu (sau POST lỗi)
    toggleAddress();

    // ── Confirm trước khi submit ───────────────────────────────
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
      const name  = document.getElementById('fullname').value.trim();
      const phone = document.getElementById('phone').value.trim();
      if (!name || !phone) { e.preventDefault(); alert('Please fill in Name and Phone.'); return; }

      const addrOpt = document.querySelector('input[name="addressOption"]:checked')?.value;
      if (addrOpt === 'new' && !cAddr.value.trim()) {
        e.preventDefault(); alert('Please enter your new address.'); return;
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