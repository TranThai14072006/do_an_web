<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/order_confirm.php
// Chức năng:
//   ✔ Kiểm tra đăng nhập
//   ✔ Kiểm tra giỏ hàng không rỗng
//   ✔ Lấy thông tin user/customer điền sẵn vào form
//   ✔ Xử lý POST: validate → transaction → insert orders + order_items
//   ✔ Giảm stock sản phẩm
//   ✔ Xóa giỏ hàng sau khi đặt thành công
//   ✔ Redirect → order_success.php?order_id=xxx
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

// ── Links ─────────────────────────────────────────────────
$link_home = BASE_URL . 'User/indexprofile.php';
$link_cart = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_logout = BASE_URL . 'User/users/logout.php';
$link_search = BASE_URL . 'User/Search/search.html';
$link_shop = BASE_URL . 'User/Products/products_sp.php';
$logged_in_name = htmlspecialchars($_SESSION['username'] ?? 'User');

// ── AJAX: Remove item from cart (must run before any output) ──
// product_id is VARCHAR in the cart table → use 's' bind type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product_id'])) {
  // Define calcPrice here so it is available for the total re-calc below
  if (!function_exists('calcPrice')) {
    function calcPrice(array $r): float {
      $cost   = (float)($r['cost_price']    ?? 0);
      $profit = (int)  ($r['profit_percent'] ?? 0);
      $price  = (float)($r['price']          ?? 0);
      return $cost > 0 ? $cost * (1 + $profit / 100) : $price;
    }
  }

  $remove_pid = trim($_POST['remove_product_id']); // keep as string
  $del = $conn->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
  $del->bind_param('is', $user_id, $remove_pid);
  $del->execute();
  $affected = $del->affected_rows;
  $del->close();
  
  file_put_contents('debug_remove.txt', "AJAX FIRED. User: $user_id, PID: $remove_pid, Affected: $affected\n", FILE_APPEND);

  // Re-calculate totals from remaining cart rows
  $new_total = 0.0;
  $new_count = 0;

  if (isset($_POST['selected_items']) && is_array($_POST['selected_items']) && !empty($_POST['selected_items'])) {
      $safe_items = array_map(function($item) use ($conn) {
          return "'" . $conn->real_escape_string($item) . "'";
      }, $_POST['selected_items']);
      
      $query2 = "
        SELECT c.quantity, p.price, p.cost_price, p.profit_percent
        FROM cart c JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND c.product_id IN (" . implode(",", $safe_items) . ")
      ";
      
      $cst2 = $conn->prepare($query2);
      $cst2->bind_param('i', $user_id);
      $cst2->execute();
      $res2 = $cst2->get_result();
      while ($r2 = $res2->fetch_assoc()) {
        $new_total += calcPrice($r2) * (int)$r2['quantity'];
        $new_count++;
      }
      $cst2->close();
  }

  header('Content-Type: application/json');
  echo json_encode(['success' => true, 'total' => round($new_total, 2), 'count' => $new_count]);
  exit();
}

// ── Hàm tính giá bán ─────────────────────────────────────
function calcPrice(array $r): float
{
  $cost = (float) ($r['cost_price'] ?? 0);
  $profit = (int) ($r['profit_percent'] ?? 0);
  $price = (float) ($r['price'] ?? 0);
  return $cost > 0 ? $cost * (1 + $profit / 100) : $price;
}

// ── Lấy thông tin user + customer ────────────────────────
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

// ── Lấy giỏ hàng ─────────────────────────────────────────
$requested_items = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_items'])) {
  $requested_items = $_POST['selected_items'];
} elseif (isset($_GET['items'])) {
  $requested_items = array_map('trim', explode(',', $_GET['items']));
}

$cart_items = [];
$total_amount = 0.0;

$query = "
    SELECT c.product_id, c.quantity,
           p.name, p.price, p.image, p.cost_price, p.profit_percent, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
";

if (!empty($requested_items)) {
  $safe_items = array_map(function($item) use ($conn) {
    return "'" . $conn->real_escape_string($item) . "'";
  }, $requested_items);
  $query .= " AND c.product_id IN (" . implode(",", $safe_items) . ")";
}
$query .= " ORDER BY c.product_id ASC";

$cst = $conn->prepare($query);
$cst->bind_param('i', $user_id);
$cst->execute();
$cres = $cst->get_result();
while ($row = $cres->fetch_assoc()) {
  $sp = calcPrice($row);
  $qty = (int) $row['quantity'];
  $cart_items[] = [
    'id' => $row['product_id'],
    'name' => $row['name'],
    'image' => $row['image'],
    'price' => $sp,
    'quantity' => $qty,
    'total' => $sp * $qty,
    'stock' => (int) ($row['stock'] ?? 0),
  ];
  $total_amount += $sp * $qty;
}
$cst->close();

// ── Handle Place Order (POST) ─────────────────────────────

// ── Handle Place Order (POST) ─────────────────────────────
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart_items)) {

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

  // Kiểm tra stock
  if (!$error) {
    foreach ($cart_items as $item) {
      if ($item['quantity'] > $item['stock']) {
        $error = '"' . htmlspecialchars($item['name']) . '" only has ' . $item['stock'] . ' units left in stock.';
        break;
      }
    }
  }

  // Thực hiện đặt hàng
  if (!$error) {
    $conn->begin_transaction();
    try {
      // ── 1. Upsert customer ────────────────────────
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

      // ── 2. Tạo đơn hàng ──────────────────────────
      $order_number = 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -4));
      $order_date = date('Y-m-d');
      $status = 'Pending';

      $ord = $conn->prepare("
                INSERT INTO orders (order_number, customer_id, order_date, total_amount, status)
                VALUES (?, ?, ?, ?, ?)
            ");
      // types: s=order_number, i=customer_id, s=order_date, d=total_amount, s=status
      $ord->bind_param('sisds', $order_number, $customer_id, $order_date, $total_amount, $status);
      $ord->execute();
      $order_id = (int) $conn->insert_id;
      $ord->close();

      // ── 3. Thêm order_items + giảm stock ─────────
      $ii = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
      $us = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

      foreach ($cart_items as $item) {
        // types: i=order_id, s=product_id, s=product_name, i=quantity, d=unit_price, d=total_price
        $ii->bind_param(
          'issidd',
          $order_id,
          $item['id'],
          $item['name'],
          $item['quantity'],
          $item['price'],
          $item['total']
        );
        $ii->execute();

        // Giảm stock
        $us->bind_param('isi', $item['quantity'], $item['id'], $item['quantity']);
        $us->execute();
      }
      $ii->close();
      $us->close();

      // ── 4. Xóa giỏ hàng ──────────────────────────
      if (!empty($requested_items)) {
        $safe_items = array_map(function($item) use ($conn) {
          return "'" . $conn->real_escape_string($item) . "'";
        }, $requested_items);
        $conn->query("DELETE FROM cart WHERE user_id = " . $user_id . " AND product_id IN (" . implode(",", $safe_items) . ")");
      } else {
        $del = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $del->bind_param('i', $user_id);
        $del->execute();
        $del->close();
      }

      $conn->commit();

      // Lưu session để trang success đọc
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
  <title>Checkout | 36 Jewelry</title>
  <meta name="description" content="Complete your 36 Jewelry order - enter shipping details and confirm.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap"
    rel="stylesheet">
  <style>
    /* ══ ROOT ══ */
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
      min-height: 100vh;
      color: var(--dark);
    }

    /* ══ HEADER ══ */
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
      padding: 12px 20px;
      gap: 16px;
    }

    .search-bar .left,
    .search-bar .center,
    .search-bar .right {
      display: flex;
      align-items: center;
    }

    .search-bar .center {
      flex: 1;
      justify-content: center;
      position: relative;
    }

    .home-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 9px 14px;
      border-radius: 8px;
      text-decoration: none;
      color: var(--dark);
      font-weight: 600;
      font-size: 15px;
      transition: .2s;
    }

    .home-btn:hover {
      background: var(--accent);
      color: var(--gold);
    }

    .search-bar .center a {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      z-index: 10;
    }

    .header-logo {
      height: 52px;
      max-width: 170px;
      object-fit: contain;
    }

    .search-box {
      flex: 0 1 420px;
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--accent);
      padding: 4px 8px;
      border-radius: 999px;
      border: 1px solid rgba(0, 0, 0, .07);
      height: 46px;
    }

    .search-box input {
      flex: 1;
      border: 0;
      outline: 0;
      background: transparent;
      padding: 4px 8px;
      font-size: 14px;
      color: var(--dark);
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
      font-size: 13px;
      transition: .2s;
    }

    .search-box button:hover {
      background: var(--gold-lt);
    }

    .icon-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      border-radius: 8px;
      text-decoration: none;
      color: var(--dark);
      font-size: 18px;
      transition: .18s;
    }

    .icon-link:hover {
      background: rgba(184, 134, 11, .1);
      color: var(--gold);
    }

    .search-bar .right {
      gap: 4px;
    }

    .user-name {
      font-size: 13px;
      font-weight: 600;
      color: var(--dark);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .user-name:hover {
      color: var(--gold);
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

    /* ══ PAGE ══ */
    .page-wrapper {
      max-width: 1100px;
      margin: 24px auto 60px;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 28px;
      align-items: start;
    }

    /* ══ CHECKOUT BOX ══ */
    .checkout-box {
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
      color: var(--dark);
    }

    .box-header i {
      color: var(--gold);
      font-size: 18px;
    }

    .box-body {
      padding: 24px;
    }

    /* Alert */
    .alert-error {
      background: #fff0f0;
      border: 1px solid #f5c2c2;
      border-radius: 10px;
      padding: 14px 16px;
      color: #c62828;
      font-size: 14px;
      margin-bottom: 20px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-error i {
      font-size: 16px;
    }

    /* Section labels */
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

    .section-label:first-child {
      margin-top: 0;
    }

    /* Form */
    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
      margin-bottom: 6px;
      letter-spacing: .2px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 11px 14px;
      border-radius: 10px;
      font-size: 14px;
      border: 1.5px solid #e0ddd5;
      background: #fff;
      color: var(--dark);
      outline: none;
      transition: .2s;
      font-family: 'DM Sans', sans-serif;
    }

    .form-group input:focus,
    .form-group select:focus {
      border-color: var(--gold-lt);
      box-shadow: 0 0 0 3px rgba(212, 175, 55, .12);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    /* Radio cards */
    .radio-cards {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    .radio-card {
      position: relative;
      cursor: pointer;
    }

    .radio-card input[type=radio] {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
    }

    .radio-card-inner {
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

    .radio-card-inner i {
      width: 20px;
      text-align: center;
      color: var(--muted);
    }

    .radio-card input:checked+.radio-card-inner {
      border-color: var(--gold-lt);
      background: #fffdf5;
      color: var(--dark);
    }

    .radio-card input:checked+.radio-card-inner i {
      color: var(--gold);
    }

    /* Payment radio cards */
    .payment-cards {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }

    .p-card {
      position: relative;
      cursor: pointer;
    }

    .p-card input[type=radio] {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
    }

    .p-card-inner {
      border: 2px solid #e0ddd5;
      border-radius: 10px;
      padding: 14px 10px;
      text-align: center;
      transition: .2s;
      font-size: 13px;
      font-weight: 600;
      color: var(--muted);
    }

    .p-card-inner .p-icon {
      font-size: 22px;
      margin-bottom: 6px;
      display: block;
    }

    .p-card input:checked+.p-card-inner {
      border-color: var(--gold-lt);
      background: #fffdf5;
      color: var(--dark);
    }

    .p-card input:checked+.p-card-inner .p-icon {
      filter: none;
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

    /* Place order btn */
    .btn-order {
      width: 100%;
      padding: 15px;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      font-weight: 700;
      font-size: 16px;
      margin-top: 12px;
      background: linear-gradient(135deg, #d4af37, #b8860b);
      color: #fff;
      box-shadow: 0 4px 20px rgba(184, 134, 11, .3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      transition: .25s;
      font-family: 'DM Sans', sans-serif;
    }

    .btn-order:hover {
      background: linear-gradient(135deg, #e0c050, #c9972a);
      transform: translateY(-1px);
      box-shadow: 0 6px 28px rgba(184, 134, 11, .4);
    }

    .btn-order:disabled {
      background: #ccc;
      box-shadow: none;
      cursor: not-allowed;
      transform: none;
    }

    .btn-back {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 12px;
      color: var(--muted);
      font-size: 13px;
      text-decoration: none;
      transition: .2s;
    }

    .btn-back:hover {
      color: var(--gold);
    }

    /* ══ SUMMARY PANEL ══ */
    .summary-box {
      background: #fff;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 24px;
      position: sticky;
      top: 90px;
    }

    .sum-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 18px;
      color: var(--dark);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .sum-title i {
      color: var(--gold-lt);
    }

    .sum-items-list {
      max-height: 320px;
      overflow-y: auto;
      margin-bottom: 4px;
    }

    .sum-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 0;
      border-bottom: 1px solid #f5f3ee;
    }

    .sum-item:last-child {
      border-bottom: none;
    }

    .sum-thumb {
      width: 52px;
      height: 52px;
      object-fit: cover;
      border-radius: 8px;
      border: 1px solid #ece8df;
      flex-shrink: 0;
    }

    .sum-info {
      flex: 1;
      min-width: 0;
    }

    .sum-name {
      font-size: 13px;
      font-weight: 600;
      line-height: 1.3;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .sum-qty {
      font-size: 12px;
      color: var(--muted);
      margin-top: 2px;
    }

    .sum-price {
      font-size: 13px;
      font-weight: 700;
      color: var(--gold);
      white-space: nowrap;
    }

    .sum-item-right {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 5px;
      flex-shrink: 0;
    }

    .btn-remove-item {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      font-weight: 600;
      color: #e53935;
      background: rgba(244, 67, 54, .08);
      border: 1px solid rgba(244, 67, 54, .25);
      border-radius: 6px;
      padding: 3px 8px;
      cursor: pointer;
      transition: .18s;
      font-family: 'DM Sans', sans-serif;
      white-space: nowrap;
    }

    .btn-remove-item:hover {
      background: #e53935;
      color: #fff;
      border-color: #e53935;
    }

    .sum-item.removing {
      opacity: .4;
      pointer-events: none;
      transition: opacity .3s;
    }

    hr.sum-div {
      border: none;
      border-top: 2px solid #ede9df;
      margin: 14px 0;
    }

    .sum-row {
      display: flex;
      justify-content: space-between;
      font-size: 14px;
      padding: 5px 0;
    }

    .sum-row .lbl {
      color: var(--muted);
    }

    .sum-row .val {
      font-weight: 600;
    }

    .sum-total-row {
      display: flex;
      justify-content: space-between;
      font-size: 18px;
      font-weight: 700;
      margin-top: 4px;
    }

    .sum-total-row .amt {
      color: var(--gold);
    }

    .sum-note {
      font-size: 12px;
      color: var(--muted);
      text-align: center;
      margin-top: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
    }

    .sum-note i {
      color: #4caf50;
    }

    .empty-note {
      text-align: center;
      color: var(--muted);
      padding: 20px 0;
      font-size: 14px;
    }

    /* ══ RESPONSIVE ══ */
    @media(max-width:860px) {
      .page-wrapper {
        grid-template-columns: 1fr;
      }

      .summary-box {
        position: static;
        order: -1;
      }

      .steps {
        overflow-x: auto;
      }

      .payment-cards {
        grid-template-columns: 1fr 1fr 1fr;
      }

      .search-bar .center a {
        position: static;
        transform: none;
      }

      .search-box {
        margin-left: 0;
        flex: 1;
      }
    }

    @media(max-width:600px) {
      .search-bar {
        flex-wrap: wrap;
        padding: 10px;
      }

      .search-bar .center {
        order: 2;
        width: 100%;
      }

      .search-bar .left {
        order: 1;
      }

      .search-bar .right {
        order: 3;
      }

      .form-row {
        grid-template-columns: 1fr;
      }

      .radio-cards {
        grid-template-columns: 1fr;
      }

      .payment-cards {
        grid-template-columns: 1fr 1fr;
      }
    }
  </style>
<link rel="stylesheet" href="/do_an_web/Jewellery/User/page-transition.css">
  <script src="/do_an_web/Jewellery/User/page-transition.js"></script>
</head>

<body>

  <!-- ══ HEADER ══════════════════════════════════════════════ -->
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
        <a href="<?= $link_cart ?>" class="icon-link" title="Cart"><i class="fas fa-shopping-cart"></i></a>
        <a href="<?= $link_profile ?>" class="icon-link" title="Profile"><i class="fas fa-user"></i></a>
        <a href="<?= $link_logout ?>" class="icon-link" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
      </div>
    </div>
  </header>

  <!-- ══ BREADCRUMB ══ -->
  <div class="breadcrumb">
    <a href="<?= $link_home ?>">Home</a>
    <span class="sep"><i class="fas fa-chevron-right" style="font-size:10px"></i></span>
    <a href="<?= $link_cart ?>">Cart</a>
    <span class="sep"><i class="fas fa-chevron-right" style="font-size:10px"></i></span>
    <span class="current">Checkout</span>
  </div>

  <!-- ══ PROGRESS STEPS ══ -->
  <div class="steps">
    <div class="step done">
      <span class="step-num"><i class="fas fa-check" style="font-size:11px"></i></span>
      <span class="step-label">Cart</span>
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

  <!-- ══ MAIN ══════════════════════════════════════════════ -->
  <div class="page-wrapper">

    <!-- LEFT: Checkout form -->
    <div class="checkout-box">
      <div class="box-header">
        <i class="fas fa-truck"></i>
        <h1>Shipping Information</h1>
      </div>
      <div class="box-body">

        <?php if ($error): ?>
          <div class="alert-error"><i class="fas fa-exclamation-triangle"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
          <div style="text-align:center;padding:48px 0;">
            <i class="fas fa-shopping-bag" style="font-size:52px;color:#e0d5c0;display:block;margin-bottom:16px;"></i>
            <p style="color:var(--muted);margin-bottom:20px;font-size:16px;">Your cart is empty.</p>
            <a href="<?= $link_shop ?>"
              style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,var(--gold-lt),var(--gold));color:#fff;border-radius:10px;text-decoration:none;font-weight:600;">
              <i class="fas fa-gem"></i> Browse Products
            </a>
          </div>

        <?php else: ?>
          <form method="POST" id="checkout-form" novalidate>
            <?php foreach ($cart_items as $item): ?>
              <input type="hidden" name="selected_items[]" class="selected-item-input" value="<?= htmlspecialchars($item['id']) ?>">
            <?php endforeach; ?>

            <!-- ── Contact Details ── -->
            <div class="section-label"><i class="fas fa-user"></i> Contact Details</div>
            <div class="form-row">
              <div class="form-group">
                <label for="fullname">Full Name <span style="color:red">*</span></label>
                <input type="text" id="fullname" name="fullname" required placeholder="Your full name"
                  value="<?= htmlspecialchars($_POST['fullname'] ?? $user['full_name'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label for="phone">Phone Number <span style="color:red">*</span></label>
                <input type="tel" id="phone" name="phone" required placeholder="+84 xxx xxx xxx"
                  value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label for="email_display">Email</label>
              <input type="email" id="email_display" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled
                style="background:#f5f5f5;color:var(--muted);">
            </div>

            <!-- ── Delivery Address ── -->
            <div class="section-label"><i class="fas fa-map-marker-alt"></i> Delivery Address</div>

            <div class="form-group">
              <div class="radio-cards">
                <label class="radio-card">
                  <input type="radio" name="addressOption" value="saved" <?= (($_POST['addressOption'] ?? 'saved') === 'saved') ? 'checked' : '' ?>>
                  <span class="radio-card-inner">
                    <i class="fas fa-bookmark"></i> Use Saved Address
                  </span>
                </label>
                <label class="radio-card">
                  <input type="radio" name="addressOption" value="new" <?= (($_POST['addressOption'] ?? '') === 'new') ? 'checked' : '' ?>>
                  <span class="radio-card-inner">
                    <i class="fas fa-edit"></i> New Address
                  </span>
                </label>
              </div>
            </div>

            <div class="form-group" id="saved-addr-section">
              <label for="saved_address">Saved Address</label>
              <select id="saved_address" name="saved_address">
                <?php if (!empty($user['address'])): ?>
                  <option value="<?= htmlspecialchars($user['address']) ?>" selected>
                    <?= htmlspecialchars($user['address']) ?>
                  </option>
                <?php else: ?>
                  <option value="">— No saved address, please enter one below —</option>
                <?php endif; ?>
              </select>
            </div>

            <div class="form-group" id="new-addr-section" style="display:none;">
              <label for="c_address">New Address <span style="color:red">*</span></label>
              <input type="text" id="c_address" name="c_address" placeholder="Street address, ward, district, city"
                value="<?= htmlspecialchars($_POST['c_address'] ?? '') ?>">
            </div>

            <!-- ── Payment Method ── -->
            <div class="section-label"><i class="fas fa-credit-card"></i> Payment Method</div>
            <div class="form-group">
              <input type="hidden" name="payment" id="payment-hidden"
                value="<?= htmlspecialchars($_POST['payment'] ?? 'cod') ?>">
              <div class="payment-cards">
                <label class="p-card">
                  <input type="radio" name="payment_radio" value="cod" <?= (($_POST['payment'] ?? 'cod') === 'cod') ? 'checked' : '' ?>>
                  <span class="p-card-inner">
                    <span class="p-icon">💴</span>
                    Cash on Delivery
                  </span>
                </label>
                <label class="p-card">
                  <input type="radio" name="payment_radio" value="bank" <?= (($_POST['payment'] ?? '') === 'bank') ? 'checked' : '' ?>>
                  <span class="p-card-inner">
                    <span class="p-icon">🏦</span>
                    Bank Transfer
                  </span>
                </label>
                <label class="p-card">
                  <input type="radio" name="payment_radio" value="online" <?= (($_POST['payment'] ?? '') === 'online') ? 'checked' : '' ?>>
                  <span class="p-card-inner">
                    <span class="p-icon">💳</span>
                    Online Payment
                  </span>
                </label>
              </div>
            </div>

            <!-- ── Bank Transfer Info Panel ── -->
            <div class="bank-panel" id="bank-panel">
              <div class="bank-panel-header">
                <i class="fas fa-university"></i> Bank Transfer Information
              </div>
              <div class="bank-panel-body">

                <!-- Thông tin ngân hàng cố định của cửa hàng -->
                <div class="bank-row">
                  <span class="b-label"><i class="fas fa-landmark"
                      style="width:14px;text-align:center;color:#d4af37;"></i> Bank Name:</span>
                  <span class="b-value">Vietcombank (VCB)</span>
                </div>
                <div class="bank-row">
                  <span class="b-label"><i class="fas fa-hashtag" style="width:14px;text-align:center;color:#d4af37;"></i>
                    Account No.:</span>
                  <span class="b-value">1234 5678 9012 3456</span>
                </div>
                <div class="bank-row">
                  <span class="b-label"><i class="fas fa-user-tie"
                      style="width:14px;text-align:center;color:#d4af37;"></i> Account Name:</span>
                  <span class="b-value">CONG TY TNHH TRANG SUC 36</span>
                </div>
                <div class="bank-row">
                  <span class="b-label"><i class="fas fa-map-pin" style="width:14px;text-align:center;color:#d4af37;"></i>
                    Branch:</span>
                  <span class="b-value">Ho Chi Minh City</span>
                </div>

                <hr class="bank-divider">

                <!-- Thông tin người dùng (tự động điền) -->
                <div class="bank-row">
                  <span class="b-label"><i class="fas fa-user" style="width:14px;text-align:center;color:#d4af37;"></i>
                    Sender Name:</span>
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
                  <span class="b-value" id="bp-content">36JW <?= htmlspecialchars($user['full_name'] ?? 'CUSTOMER') ?>
                    <?= htmlspecialchars($user['phone'] ?? '') ?></span>
                </div>

                <p class="bank-note">
                  <i class="fas fa-info-circle"></i>
                  Please note exactly as above so we can verify your payment quickly.
                </p>
              </div>
            </div>

            <button type="submit" class="btn-order" id="btn-place-order">
              <i class="fas fa-check-circle"></i>
              Place Order — $<?= number_format($total_amount, 2) ?>
            </button>

            <a href="<?= $link_cart ?>" class="btn-back">
              <i class="fas fa-arrow-left"></i> Back to Cart
            </a>

          </form>
        <?php endif; ?>

      </div>
    </div>

    <!-- RIGHT: Order Summary -->
    <div class="summary-box">
      <h2 class="sum-title"><i class="fas fa-receipt"></i> Order Summary</h2>

      <?php if (empty($cart_items)): ?>
        <p class="empty-note">No items in cart.</p>
      <?php else: ?>
        <div class="sum-items-list" id="sum-items-list">
          <?php foreach ($cart_items as $item): ?>
            <div class="sum-item" data-product-id="<?= $item['id'] ?>" id="sum-item-<?= $item['id'] ?>">
              <img src="<?= IMG_URL . htmlspecialchars($item['image'] ?? '') ?>"
                alt="<?= htmlspecialchars($item['name']) ?>" class="sum-thumb"
                onerror="this.src='<?= IMG_URL ?>default-avatar.png'">
              <div class="sum-info">
                <div class="sum-name"><?= htmlspecialchars($item['name']) ?></div>
                <div class="sum-qty">SKU: <?= htmlspecialchars($item['id']) ?> · Qty: <?= $item['quantity'] ?></div>
              </div>
              <div class="sum-item-right">
                <span class="sum-price">$<?= number_format($item['total'], 2) ?></span>
                <button type="button" class="btn-remove-item" onclick="removeItem('<?= htmlspecialchars($item['id']) ?>', this)">
                  <i class="fas fa-times"></i> Remove
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <hr class="sum-div">

        <div class="sum-row">
          <span class="lbl">Subtotal (<span id="sum-count"><?= count($cart_items) ?></span> items)</span>
          <span class="val" id="sum-subtotal">$<?= number_format($total_amount, 2) ?></span>
        </div>
        <div class="sum-row">
          <span class="lbl">Shipping</span>
          <span class="val" style="color:#4caf50">Free</span>
        </div>
        <div class="sum-row">
          <span class="lbl">Tax</span>
          <span class="val">Included</span>
        </div>

        <hr class="sum-div">

        <div class="sum-total-row">
          <span>Total</span>
          <span class="amt" id="sum-total">$<?= number_format($total_amount, 2) ?></span>
        </div>

        <p class="sum-note"><i class="fas fa-shield-alt"></i> Free shipping · Secure checkout</p>
      <?php endif; ?>
    </div>

  </div><!-- /.page-wrapper -->

  <script>
    // ── Search ──────────────────────────────────────────────
    function doSearch() {
      const kw = document.getElementById('search-input').value.trim();
      if (kw) window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(kw);
    }

    // ── Remove item from cart ────────────────────────────────
    function removeItem(productId, btn) {
      const itemEl = document.getElementById('sum-item-' + productId);
      if (!itemEl) return;

      itemEl.classList.add('removing');

      const formData = new FormData();
      formData.append('remove_product_id', productId);
      
      document.querySelectorAll('input.selected-item-input').forEach(input => {
          if (input.value !== productId) {
              formData.append('selected_items[]', input.value);
          }
      });

      fetch(window.location.href, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            itemEl.remove();
            
            // Remove the hidden input field so it won't be submitted later
            const hiddenInp = document.querySelector('input.selected-item-input[value="' + productId + '"]');
            if (hiddenInp) hiddenInp.remove();

            const fmt = n => '$' + parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

            const countEl = document.getElementById('sum-count');
            const subtotalEl = document.getElementById('sum-subtotal');
            const totalEl = document.getElementById('sum-total');
            const btnOrder = document.getElementById('btn-place-order');

            if (countEl) countEl.textContent = data.count;
            if (subtotalEl) subtotalEl.textContent = fmt(data.total);
            if (totalEl) totalEl.textContent = fmt(data.total);

            // Update Place Order button total
            if (btnOrder) {
              btnOrder.innerHTML = '<i class="fas fa-check-circle"></i> Place Order — ' + fmt(data.total);
            }

            // If no items left, reload to show empty state
            if (data.count === 0) {
              window.location.reload();
            }
          }
        })
        .catch(() => {
          itemEl.classList.remove('removing');
          alert('Failed to remove item. Please try again.');
        });
    }

    // ── Toggle bank panel ────────────────────────────────────
    const bankPanel = document.getElementById('bank-panel');
    function toggleBankPanel() {
      const val = document.querySelector('input[name="payment_radio"]:checked')?.value;
      if (bankPanel) bankPanel.style.display = (val === 'bank') ? 'block' : 'none';
    }
    document.querySelectorAll('input[name="payment_radio"]').forEach(r => r.addEventListener('change', function () {
      document.getElementById('payment-hidden').value = this.value;
      toggleBankPanel();
    }));
    // Sync sender/phone in bank panel when fullname/phone fields change
    const fullnameInput = document.getElementById('fullname');
    const phoneInput = document.getElementById('phone');
    const bpSender = document.getElementById('bp-sender');
    const bpPhone = document.getElementById('bp-phone');
    const bpContent = document.getElementById('bp-content');
    function syncBankInfo() {
      const name = fullnameInput ? fullnameInput.value.trim() : '';
      const phone = phoneInput ? phoneInput.value.trim() : '';
      if (bpSender) bpSender.textContent = name || '—';
      if (bpPhone) bpPhone.textContent = phone || '—';
      if (bpContent) bpContent.textContent = '36JW ' + (name || 'CUSTOMER') + ' ' + phone;
    }
    if (fullnameInput) fullnameInput.addEventListener('input', syncBankInfo);
    if (phoneInput) phoneInput.addEventListener('input', syncBankInfo);
    toggleBankPanel(); // init on page load

    // ── Toggle address sections ──────────────────────────────
    const savedSec = document.getElementById('saved-addr-section');
    const newSec = document.getElementById('new-addr-section');
    const newInput = document.getElementById('c_address');

    function toggleAddr() {
      const v = document.querySelector('input[name="addressOption"]:checked')?.value;
      if (v === 'new') {
        savedSec.style.display = 'none';
        newSec.style.display = 'block';
        if (newInput) newInput.required = true;
      } else {
        savedSec.style.display = 'block';
        newSec.style.display = 'none';
        if (newInput) newInput.required = false;
      }
    }
    document.querySelectorAll('input[name="addressOption"]').forEach(r => r.addEventListener('change', toggleAddr));
    toggleAddr();

    // ── Form validation ──────────────────────────────────────
    const form = document.getElementById('checkout-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        const name = document.getElementById('fullname')?.value.trim();
        const phone = document.getElementById('phone')?.value.trim();
        const addrOpt = document.querySelector('input[name="addressOption"]:checked')?.value;
        const newAddr = document.getElementById('c_address')?.value.trim();

        if (!name || !phone) {
          e.preventDefault();
          alert('Please fill in your full name and phone number.');
          return;
        }
        if (addrOpt === 'new' && !newAddr) {
          e.preventDefault();
          alert('Please enter your delivery address.');
          return;
        }
        if (addrOpt === 'saved') {
          const savedAddr = document.getElementById('saved_address')?.value.trim();
          if (!savedAddr) {
            e.preventDefault();
            alert('You do not have a saved address. Please select New Address and enter your address.');
            return;
          }
        }

        if (!confirm('Confirm your order?\n\nTotal: $<?= number_format($total_amount, 2) ?>\n\nClick OK to place order.')) {
          e.preventDefault();
          return;
        }

        // Disable button AFTER submit event is queued
        const btn = document.getElementById('btn-place-order');
        if (btn) {
          setTimeout(() => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Placing Order...';
          }, 0);
        }
      });
    }
  </script>

</body>

</html>
