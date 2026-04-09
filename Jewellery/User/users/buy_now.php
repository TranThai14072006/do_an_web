<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/buy_now.php
// Chức năng:
//   ✔ Xử lý mua nhanh một sản phẩm (Buy Now)
//   ✔ Không ảnh hưởng đến giỏ hàng hiện tại
//   ✔ Giao diện đồng nhất với order_confirm.php
// ═══════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../../config/config.php';

if (!defined('BASE_URL'))
  define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))
  define('IMG_URL', BASE_URL . 'images/');

// ── Kiểm tra đăng nhập ────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . 'User/users/login.php');
  exit();
}

$user_id = (int) $_SESSION['user_id'];

// ── Lấy thông số từ URL hoặc POST ──────────────────────────
$product_id = $_GET['id'] ?? $_POST['buy_now_id'] ?? '';
$size = $_GET['size'] ?? $_POST['buy_now_size'] ?? '';
$quantity = 1; // Mặc định cho Mua ngay là 1

if (!$product_id || !$size) {
  header('Location: ' . BASE_URL . 'User/Products/products_sp.php');
  exit();
}

// ── Links ─────────────────────────────────────────────────
$link_home = BASE_URL . 'User/indexprofile.php';
$link_cart = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_logout = BASE_URL . 'User/users/logout.php';
$link_search = BASE_URL . 'User/Products/products_sp.php';
$logged_in_name = htmlspecialchars($_SESSION['username'] ?? 'User');

// ── Hàm tính giá bán ─────────────────────────────────────
function calcPrice(array $r): float
{
  $cost = (float) ($r['cost_price'] ?? 0);
  $profit = (int) ($r['profit_percent'] ?? 0);
  $price = (float) ($r['price'] ?? 0);
  return $cost > 0 ? $cost * (1 + $profit / 100) : $price;
}

// ── Lấy thông tin sản phẩm ───────────────────────────────
$stmt_p = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt_p->bind_param('s', $product_id);
$stmt_p->execute();
$product = $stmt_p->get_result()->fetch_assoc();
$stmt_p->close();

if (!$product) {
  header('Location: ' . BASE_URL . 'User/Products/products_sp.php?error=product_not_found');
  exit();
}

$sale_price = calcPrice($product);
$total_amount = $sale_price * $quantity;

// Giả lập danh sách item để dùng chung UI với order_confirm.php
$cart_items = [
  [
    'id' => $product['id'],
    'name' => $product['name'],
    'image' => $product['image'],
    'price' => $sale_price,
    'quantity' => $quantity,
    'total' => $total_amount,
    'stock' => (int) ($product['stock'] ?? 0),
    'size' => $size
  ]
];

// ── Lấy thông tin người dùng ─────────────────────────────
$stmt = $conn->prepare("
    SELECT u.email,
           COALESCE(c.full_name, u.username, '') AS full_name,
           COALESCE(c.phone, '')   AS phone,
           COALESCE(c.address, '') AS address
    FROM users u
    LEFT JOIN customers c ON u.id = c.user_id
    WHERE u.id = ? LIMIT 1
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?? [];
$stmt->close();

// ===== TỔNG SỐ LƯỢNG GIỎ HÀNG (cho header) =====
$total_cart_count = 0;
if (isset($_SESSION['user_id'])) {
  $uid = (int) $_SESSION['user_id'];
  $stmt_badge = $conn->query("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = $uid");
  if ($stmt_badge && $row_b = $stmt_badge->fetch_assoc()) {
    $total_cart_count = (int) $row_b['total_qty'];
  }
}

// ── Xử lý Đặt hàng (POST) ───────────────────────────────
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_now_submit'])) {

  $full_name = trim($_POST['fullname'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $payment_method = trim($_POST['payment'] ?? 'cod');
  $addr_option = trim($_POST['addressOption'] ?? 'saved');
  $address = ($addr_option === 'new')
    ? trim($_POST['c_address'] ?? '')
    : trim($_POST['saved_address'] ?? $user['address'] ?? '');

  // Validation
  if (!$full_name) {
    $error = 'Full name is required.';
  } elseif (!$phone) {
    $error = 'Phone number is required.';
  } elseif (!$address) {
    $error = 'Delivery address is required.';
  }

  // Kiểm tra tồn kho
  if (!$error) {
    if ($quantity > $product['stock']) {
      $error = '"' . htmlspecialchars($product['name']) . '" only has ' . $product['stock'] . ' units left in stock.';
    }
  }

  // Thực hiện lưu đơn hàng
  if (!$error) {
    $conn->begin_transaction();
    try {
      // 1. Cập nhật/Tạo khách hàng
      $cid_st = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
      $cid_st->bind_param('i', $user_id);
      $cid_st->execute();
      $cid_row = $cid_st->get_result()->fetch_assoc();
      $cid_st->close();

      if ($cid_row) {
        $customer_id = (int) $cid_row['id'];
        $upd = $conn->prepare("UPDATE customers SET full_name=?, phone=?, address=? WHERE id=?");
        $upd->bind_param('sssi', $full_name, $phone, $address, $customer_id);
        $upd->execute();
        $upd->close();
      } else {
        $ins = $conn->prepare("INSERT INTO customers (user_id, full_name, phone, address) VALUES (?,?,?,?)");
        $ins->bind_param('isss', $user_id, $full_name, $phone, $address);
        $ins->execute();
        $customer_id = (int) $conn->insert_id;
        $ins->close();
      }

      // 2. Tạo đơn hàng
      $order_number = 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -4));
      $order_date = date('Y-m-d');
      $status = 'Pending';

      $ord = $conn->prepare("
                INSERT INTO orders (order_number, customer_id, order_date, total_amount, status)
                VALUES (?, ?, ?, ?, ?)
            ");
      $ord->bind_param('sisds', $order_number, $customer_id, $order_date, $total_amount, $status);
      $ord->execute();
      $order_id = (int) $conn->insert_id;
      $ord->close();

      // 3. Thêm order_items + giảm stock
      $ii = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
      $ii->bind_param('issidd', $order_id, $product_id, $product['name'], $quantity, $sale_price, $total_amount);
      $ii->execute();
      $ii->close();

      $us = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
      $us->bind_param('isi', $quantity, $product_id, $quantity);
      $us->execute();
      $us->close();

      // QUAN TRỌNG: KHÔNG xóa giỏ hàng vì đây là Mua ngay

      $conn->commit();

      $_SESSION['last_order_id'] = $order_id;
      $_SESSION['last_order_number'] = $order_number;

      header('Location: ' . BASE_URL . 'User/users/order_success.php?order_id=' . $order_id);
      exit();

    } catch (Exception $e) {
      $conn->rollback();
      $error = 'Order failed: ' . $e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buy Now Checkout | 36 Jewelry</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap"
    rel="stylesheet">
  <style>
    /* CSS đồng bộ với order_confirm.php & product-detail.php */
    :root {
      --gold: #b8860b;
      --gold-lt: #d4af37;
      --gold-pale: #f8ce86;
      --dark: #111;
      --bg: #fff;
      --accent: #f6f6f6;
      --muted: #666;
      --radius: 12px;
      --shadow: 0 4px 24px rgba(0, 0, 0, .08);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: #fafafa;
      color: var(--dark);
      min-height: 100vh;
    }

    /* ══ BREADCRUMB ══ */
    .breadcrumb {
      max-width: 1100px;
      margin: 20px auto 0;
      padding: 0 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: var(--muted);
    }

    .breadcrumb a {
      color: var(--muted);
      text-decoration: none;
      transition: .15s;
    }

    .breadcrumb a:hover {
      color: var(--gold);
    }

    .breadcrumb .sep {
      color: #ccc;
    }

    .breadcrumb .current {
      color: var(--dark);
      font-weight: 600;
    }

    /* ══ PROGRESS STEPS ══ */
    .steps {
      max-width: 1100px;
      margin: 20px auto 0;
      padding: 0 20px;
      display: flex;
      align-items: center;
    }

    .step {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      font-weight: 600;
    }

    .step-num {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
      flex-shrink: 0;
    }

    .step.done .step-num {
      background: var(--gold);
      color: #fff;
    }

    .step.active .step-num {
      background: var(--gold);
      color: #fff;
      box-shadow: 0 0 0 4px rgba(212, 175, 55, .2);
    }

    .step.inactive .step-num {
      background: #e0e0e0;
      color: #999;
    }

    .step.active .step-label {
      color: var(--dark);
    }

    .step.done .step-label {
      color: var(--muted);
    }

    .step.inactive .step-label {
      color: #bbb;
    }

    .step-line {
      flex: 1;
      height: 2px;
      background: #e0e0e0;
      margin: 0 10px;
    }

    .step-line.done {
      background: var(--gold);
    }

    /* HEADER (từ product-detail.php) */
    .header-container {
      width: 100%;
      position: sticky;
      top: 0;
      z-index: 1000;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    }

    .search-bar {
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      gap: 16px;
      background: #fff;
    }

    .search-bar .left,
    .search-bar .right {
      display: flex;
      align-items: center;
    }

    .search-bar .right {
      gap: 10px;
    }

    .search-bar .center {
      flex: 1 1 auto;
      justify-content: center;
      gap: 30px;
      position: relative;
      display: flex;
      align-items: center;
    }

    .search-bar .center a {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
    }

    .header-logo {
      height: 55px;
      max-width: 180px;
      object-fit: contain;
      transition: all 0.25s ease;
    }

    .home-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 10px 15px;
      border-radius: 8px;
      text-decoration: none;
      color: var(--dark);
      font-weight: 600;
      font-size: 16px;
      transition: .2s;
    }

    .home-btn:hover {
      background: var(--accent);
      color: var(--gold);
    }

    .search-box {
      flex: 0 1 450px;
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--accent);
      padding: 4px 8px;
      border-radius: 999px;
      border: 1px solid rgba(0, 0, 0, 0.06);
      height: 50px;
    }

    .search-box input {
      flex: 1;
      border: 0;
      outline: 0;
      background: transparent;
      padding: 4px 8px;
      font-size: 14px;
    }

    .search-box button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 0;
      background: var(--gold);
      color: #fff;
      padding: 8px 14px;
      border-radius: 999px;
      cursor: pointer;
    }

    .icon-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      text-decoration: none;
      color: var(--dark);
      font-size: 18px;
      position: relative;
      border-radius: 8px;
      transition: .18s;
    }

    .icon-link:hover {
      background: rgba(184, 134, 11, .1);
      color: var(--gold);
    }

    .cart-badge {
      position: absolute;
      top: 4px;
      right: 4px;
      background: #b8860b;
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      border-radius: 50%;
      width: 16px;
      height: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      line-height: 1;
    }

    /* LAYOUT */
    .page-wrapper {
      max-width: 1100px;
      margin: 24px auto 60px;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 28px;
    }

    /* BOXES */
    .checkout-box,
    .summary-box {
      background: #fff;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .box-header {
      padding: 20px 24px;
      border-bottom: 1px solid #f0eee8;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .box-header h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 22px;
      font-weight: 700;
    }

    .box-body {
      padding: 24px;
    }

    /* FORM */
    .section-label {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      color: var(--gold-lt);
      margin: 24px 0 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .section-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #f0eee8;
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      margin-bottom: 6px;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 11px 14px;
      border-radius: 10px;
      border: 1.5px solid #e0ddd5;
      font-size: 14px;
      outline: none;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    /* RADIO CARDS */
    .radio-cards,
    .payment-cards {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    .radio-card,
    .p-card {
      position: relative;
      cursor: pointer;
    }

    .radio-card input,
    .p-card input {
      position: absolute;
      opacity: 0;
    }

    .radio-card-inner,
    .p-card-inner {
      border: 2px solid #e0ddd5;
      border-radius: 10px;
      padding: 12px 14px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      font-weight: 500;
      transition: .2s;
    }

    .radio-card input:checked+.radio-card-inner,
    .p-card input:checked+.p-card-inner {
      border-color: var(--gold-lt);
      background: #fffdf5;
    }

    /* PAYMENT SPECIFIC */
    .payment-cards {
      grid-template-columns: repeat(3, 1fr);
    }

    .p-card-inner {
      flex-direction: column;
      text-align: center;
      font-size: 12px;
      padding: 14px 10px;
    }

    .p-icon {
      font-size: 22px;
      margin-bottom: 4px;
    }

    /* ── Bank Transfer Panel ── */
    .bank-panel {
      display: none;
      margin-top: 14px;
      border-radius: 12px;
      overflow: hidden;
      border: 1.5px solid #d4af37;
      animation: fadeSlide .25s ease;
    }

    @keyframes fadeSlide {
      from {
        opacity: 0;
        transform: translateY(-6px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .bank-panel-header {
      background: linear-gradient(135deg, #d4af37, #b8860b);
      color: #fff;
      padding: 12px 16px;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: .5px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .bank-panel-body {
      background: #fffdf5;
      padding: 14px 16px;
      display: grid;
      gap: 8px;
    }

    .bank-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-size: 13.5px;
    }

    .bank-row .b-label {
      min-width: 130px;
      color: var(--muted);
      font-weight: 600;
      flex-shrink: 0;
    }

    .bank-row .b-value {
      color: var(--dark);
      font-weight: 700;
      word-break: break-all;
    }

    .bank-row .b-value.highlight {
      color: #d4af37;
      font-size: 15px;
    }

    .bank-divider {
      border: none;
      border-top: 1px dashed #e0d8c0;
      margin: 4px 0;
    }

    .bank-note {
      font-size: 12px;
      color: #b8860b;
      display: flex;
      align-items: center;
      gap: 5px;
      margin-top: 2px;
    }

    /* SUMMARY */
    .sum-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .sum-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 0;
      border-bottom: 1px solid #f5f3ee;
    }

    .sum-thumb {
      width: 56px;
      height: 56px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid #eee;
    }

    .sum-info {
      flex: 1;
    }

    .sum-name {
      font-size: 13px;
      font-weight: 600;
      line-height: 1.4;
    }

    .sum-meta {
      font-size: 12px;
      color: var(--muted);
      margin-top: 2px;
    }

    .sum-price {
      font-size: 14px;
      font-weight: 700;
      color: var(--gold);
    }

    .sum-row {
      display: flex;
      justify-content: space-between;
      font-size: 14px;
      padding: 6px 0;
    }

    .sum-total {
      display: flex;
      justify-content: space-between;
      font-size: 18px;
      font-weight: 700;
      margin-top: 10px;
      border-top: 2px solid #ede9df;
      padding-top: 10px;
    }

    .sum-total .amt {
      color: var(--gold);
    }

    /* BUTTONS */
    .btn-order {
      width: 100%;
      padding: 15px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, #d4af37, #b8860b);
      color: #fff;
      font-weight: 700;
      font-size: 16px;
      cursor: pointer;
      margin-top: 15px;
      transition: .2s;
    }

    .btn-order:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 15px rgba(184, 134, 11, .3);
    }

    .btn-back {
      display: block;
      text-align: center;
      margin-top: 14px;
      font-size: 13px;
      color: var(--muted);
      text-decoration: none;
    }

    .alert-error {
      background: #fff0f0;
      border: 1px solid #f5c2c2;
      padding: 12px;
      color: #c62828;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    @media(max-width: 860px) {
      .page-wrapper {
        grid-template-columns: 1fr;
      }

      .summary-box {
        order: -1;
      }
    }
  </style>
  <link rel="stylesheet" href="/do_an_web/Jewellery/User/page-transition.css">
  <script src="/do_an_web/Jewellery/User/page-transition.js"></script>
</head>

<body>

  <header class="header-container">
    <div class="search-bar">
      <div class="left">
        <a href="../indexprofile.php" class="home-btn"><i class="fas fa-home"></i> Home</a>
      </div>

      <div class="center">
        <a href="../indexprofile.php">
          <img src="../../images/36-logo.png" alt="Jewelry Store Logo" class="header-logo">
        </a>
        <div class="search-box">
          <input type="text" id="header-search" placeholder="Search products..."
            onkeydown="if(event.key==='Enter') applyHeaderSearch()">
          <button onclick="applyHeaderSearch()">
            <i class="fas fa-search"></i>
          </button>
        </div>
      </div>

      <div class="right">
        <a href="../users/cart.php" class="icon-link" title="Cart">
          <i class="fas fa-shopping-cart"></i>
          <?php if ($total_cart_count > 0): ?>
            <span class="cart-badge"><?= $total_cart_count > 9 ? '9+' : $total_cart_count ?></span>
          <?php endif; ?>
        </a>
        <a href="../users/profile.php" class="icon-link" title="Profile">
          <i class="fas fa-user-circle user-icon"></i>
        </a>
        <a href="../users/logout.php" class="icon-link" title="Logout" style="color:#111;">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </div>
    </div>
  </header>

  <!-- ══ BREADCRUMB ══ -->
  <div class="breadcrumb">
    <a href="<?= $link_home ?>">Home</a>
    <span class="sep"><i class="fas fa-chevron-right" style="font-size:10px"></i></span>
    <span class="current">Checkout</span>
  </div>

  <!-- ══ PROGRESS STEPS ══ -->
  <div class="steps">
    <div class="step done">
      <span class="step-num"><i class="fas fa-check" style="font-size:11px"></i></span>
      <span class="step-label">Product</span>
    </div>
    <div class="step-line done"></div>
    <div class="step active">
      <span class="step-num">2</span>
      <span class="step-label">Checkout</span>
    </div>
    <div class="step-line"></div>
    <div class="step inactive">
      <span class="step-num">3</span>
      <span class="step-label">Confirmed</span>
    </div>
  </div>

  <div class="page-wrapper">
    <!-- Form -->
    <div class="checkout-box">
      <div class="box-header"><i class="fas fa-truck"></i>
        <h1>Shipping Information</h1>
      </div>
      <div class="box-body">
        <?php if ($error): ?>
          <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="checkout-form">
          <input type="hidden" name="buy_now_id" value="<?= htmlspecialchars($product_id) ?>">
          <input type="hidden" name="buy_now_size" value="<?= htmlspecialchars($size) ?>">

          <div class="section-label"><i class="fas fa-user"></i> Contact Details</div>
          <div class="form-row">
            <div class="form-group">
              <label>Full Name *</label>
              <input type="text" name="fullname" required placeholder="Your full name"
                value="<?= htmlspecialchars($_POST['fullname'] ?? $user['full_name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Phone Number *</label>
              <input type="tel" name="phone" required placeholder="+84 xxx xxx xxx"
                value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled
              style="background:#f5f5f5;color:var(--muted);">
          </div>

          <div class="section-label"><i class="fas fa-map-marker-alt"></i> Delivery Address</div>
          <div class="form-group">
            <div class="radio-cards">
              <label class="radio-card"><input type="radio" name="addressOption" value="saved" checked><span
                  class="radio-card-inner">Saved Address</span></label>
              <label class="radio-card"><input type="radio" name="addressOption" value="new"><span
                  class="radio-card-inner">New Address</span></label>
            </div>
          </div>

          <div id="saved-addr-section" class="form-group">
            <label>Saved Address</label>
            <select name="saved_address">
              <?php if (!empty($user['address'])): ?>
                <option value="<?= htmlspecialchars($user['address']) ?>"><?= htmlspecialchars($user['address']) ?>
                </option>
              <?php else: ?>
                <option value="">— No saved address —</option><?php endif; ?>
            </select>
          </div>
          <div id="new-addr-section" class="form-group" style="display:none;">
            <label>New Address *</label>
            <input type="text" name="c_address" placeholder="Street address, ward, district, city">
          </div>

          <div class="section-label"><i class="fas fa-credit-card"></i> Payment Method</div>
          <div class="payment-cards">
            <label class="p-card"><input type="radio" name="payment_radio" value="cod" checked><span
                class="p-card-inner"><span class="p-icon">💴</span> COD</span></label>
            <label class="p-card"><input type="radio" name="payment_radio" value="bank"><span class="p-card-inner"><span
                  class="p-icon">🏦</span> Bank</span></label>
            <label class="p-card"><input type="radio" name="payment_radio" value="online"><span
                class="p-card-inner"><span class="p-icon">💳</span> Online</span></label>
          </div>
          <input type="hidden" name="payment" id="payment-hidden" value="cod">

          <!-- ── Bank Transfer Info Panel ── -->
          <div class="bank-panel" id="bank-panel">
            <div class="bank-panel-header">
              <i class="fas fa-university"></i> Bank Transfer Information
            </div>
            <div class="bank-panel-body">
              <div class="bank-row">
                <span class="b-label"><i class="fas fa-landmark"
                    style="width:14px;text-align:center;color:#d4af37;"></i> Bank Name:</span>
                <span class="b-value">Vietcombank (VCB)</span>
              </div>
              <div class="bank-row">
                <span class="b-label"><i class="fas fa-hashtag" style="width:14px;text-align:center;color:#d4af37;"></i>
                  Account Number:</span>
                <span class="b-value">1234 5678 9012 3456</span>
              </div>
              <div class="bank-row">
                <span class="b-label"><i class="fas fa-user-tie"
                    style="width:14px;text-align:center;color:#d4af37;"></i> Account Owner:</span>
                <span class="b-value">CONG TY TNHH TRANG SUC 36</span>
              </div>
              <div class="bank-row">
                <span class="b-label"><i class="fas fa-map-pin" style="width:14px;text-align:center;color:#d4af37;"></i>
                  Branch:</span>
                <span class="b-value">Ho Chi Minh City</span>
              </div>

              <hr class="bank-divider">

              <div class="bank-row">
                <span class="b-label"><i class="fas fa-user" style="width:14px;text-align:center;color:#d4af37;"></i>
                  Sender:</span>
                <span class="b-value" id="bp-sender"><?= htmlspecialchars($user['full_name'] ?? '') ?></span>
              </div>
              <div class="bank-row">
                <span class="b-label"><i class="fas fa-phone" style="width:14px;text-align:center;color:#d4af37;"></i>
                  Phone Number:</span>
                <span class="b-value" id="bp-phone"><?= htmlspecialchars($user['phone'] ?? '') ?></span>
              </div>
              <div class="bank-row">
                <span class="b-label"><i class="fas fa-money-bill-wave"
                    style="width:14px;text-align:center;color:#d4af37;"></i> Amount:</span>
                <span class="b-value highlight" id="bp-amount">$<?= number_format($total_amount, 2) ?></span>
              </div>
              <div class="bank-row">
                <span class="b-label"><i class="fas fa-comment-alt"
                    style="width:14px;text-align:center;color:#d4af37;"></i> Transfer Note:</span>
                <span class="b-value" id="bp-content">36BN <?= htmlspecialchars($product_id) ?></span>
              </div>

              <p class="bank-note"><i class="fas fa-info-circle"></i> Please enter the correct transfer note to ensure
                your order is confirmed quickly.</p>
            </div>
          </div>

          <button type="submit" name="buy_now_submit" class="btn-order">
            <i class="fas fa-check-circle"></i> Place Order — $<?= number_format($total_amount, 2) ?>
          </button>
          <a href="#" onclick="history.back()" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Product
          </a>
        </form>
      </div>
    </div>

    <!-- Summary -->
    <div class="summary-box">
      <div class="box-body">
        <h2 class="sum-title"><i class="fas fa-receipt"></i> Order Summary</h2>
        <div class="sum-item">
          <img src="<?= IMG_URL . htmlspecialchars($product['image']) ?>" class="sum-thumb">
          <div class="sum-info">
            <div class="sum-name"><?= htmlspecialchars($product['name']) ?></div>
            <div class="sum-meta">Size: <?= htmlspecialchars($size) ?> · Qty: 1</div>
          </div>
          <div class="sum-price">$<?= number_format($sale_price, 2) ?></div>
        </div>

        <div class="sum-row" style="margin-top:15px;"><span class="label">Subtotal</span><span
            class="value">$<?= number_format($total_amount, 2) ?></span></div>
        <div class="sum-row"><span class="label">Shipping</span><span class="value" style="color:#4caf50">Free</span>
        </div>
        <div class="sum-total"><span>Total</span><span class="amt">$<?= number_format($total_amount, 2) ?></span></div>
      </div>
    </div>
  </div>

  <script>
    // Search logic (từ product-detail.php)
    function applyHeaderSearch() {
      const kw = document.getElementById('header-search').value.trim();
      if (kw) {
        window.location.href = `../Products/products_sp.php?q=${encodeURIComponent(kw)}`;
      } else {
        window.location.href = `../Products/products_sp.php`;
      }
    }

    // Toggle address
    const savedSec = document.getElementById('saved-addr-section');
    const newSec = document.getElementById('new-addr-section');
    document.querySelectorAll('input[name="addressOption"]').forEach(r => r.addEventListener('change', (e) => {
      savedSec.style.display = (e.target.value === 'saved') ? 'block' : 'none';
      newSec.style.display = (e.target.value === 'new') ? 'block' : 'none';
    }));

    // Toggle bank
    const bankPanel = document.getElementById('bank-panel');
    function toggleBankPanel() {
      const val = document.querySelector('input[name="payment_radio"]:checked')?.value;
      if (bankPanel) bankPanel.style.display = (val === 'bank') ? 'block' : 'none';
    }
    document.querySelectorAll('input[name="payment_radio"]').forEach(r => r.addEventListener('change', (e) => {
      document.getElementById('payment-hidden').value = e.target.value;
      toggleBankPanel();
    }));

    // Sync bank info logic
    const fullnameInput = document.querySelector('input[name="fullname"]');
    const phoneInput = document.querySelector('input[name="phone"]');
    const bpSender = document.getElementById('bp-sender');
    const bpPhone = document.getElementById('bp-phone');
    const bpContent = document.getElementById('bp-content');

    function syncBankInfo() {
      const name = fullnameInput ? fullnameInput.value.trim() : '';
      const phone = phoneInput ? phoneInput.value.trim() : '';
      if (bpSender) bpSender.textContent = name || '—';
      if (bpPhone) bpPhone.textContent = phone || '—';
      if (bpContent) bpContent.textContent = '36BN ' + '<?= htmlspecialchars($product_id) ?>' + ' ' + (name || 'CUSTOMER');
    }

    if (fullnameInput) fullnameInput.addEventListener('input', syncBankInfo);
    if (phoneInput) phoneInput.addEventListener('input', syncBankInfo);

    toggleBankPanel(); // Initial call
  </script>
</body>

</html>