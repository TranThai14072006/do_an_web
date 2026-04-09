<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/cart.php
// Full features:
//   ✔ Login check
//   ✔ Handle product addition to cart (GET action=add&id=)
//   ✔ AJAX: quantity update, item removal
//   ✔ Display cart with image & local price
//   ✔ Real-time total calculation
//   ✔ Checkout button → order_confirm.php
// ═══════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../../config/config.php';

if (!defined('BASE_URL'))
  define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))
  define('IMG_URL', BASE_URL . 'images/');

// Check login
if (!isset($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . 'User/users/login.php');
  exit();
}

$user_id = (int) $_SESSION['user_id'];

// ── Link helpers ──────────────────────────────────────────
$link_home = BASE_URL . 'User/indexprofile.php';
$link_cart = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_logout = BASE_URL . 'User/users/logout.php';
$link_search = BASE_URL . 'User/Products/products_sp.php';
$link_detail = BASE_URL . 'User/Cart/view.php';
$link_shop = BASE_URL . 'User/Products/products_sp.php';
$link_checkout = BASE_URL . 'User/users/order_confirm.php';
$logged_in_name = htmlspecialchars($_SESSION['username'] ?? 'User');

// ─────────────────────────────────────────────────────────
// SELLING PRICE CALCULATION FUNCTION
// ─────────────────────────────────────────────────────────
function sellingPrice(array $row): float
{
  $cost = (float) ($row['cost_price'] ?? 0);
  $profit = (int) ($row['profit_percent'] ?? 0);
  $price = (float) ($row['price'] ?? 0);
  return ($cost > 0) ? $cost * (1 + $profit / 100) : $price;
}

// ─────────────────────────────────────────────────────────
// AJAX HANDLING (update qty / remove)
// ─────────────────────────────────────────────────────────
if (isset($_POST['ajax_action'])) {
  header('Content-Type: application/json');
  $action = $_POST['ajax_action'];

  if ($action === 'update' && isset($_POST['product_id'], $_POST['quantity'])) {
    $pid = $_POST['product_id'];     // varchar – not cast to int
    $qty = max(1, (int) $_POST['quantity']);

    $st = $conn->prepare("
            INSERT INTO cart (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
        ");
    $st->bind_param('isi', $user_id, $pid, $qty);
    $st->execute();
    $st->close();
    echo json_encode(['success' => true, 'qty' => $qty]);

  } elseif ($action === 'remove' && isset($_POST['product_id'])) {
    $pid = $_POST['product_id'];
    $st = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $st->bind_param('is', $user_id, $pid);
    $st->execute();
    $st->close();
    echo json_encode(['success' => true]);

  } else {
    echo json_encode(['success' => false, 'msg' => 'Invalid action']);
  }
  exit();
}

// ─────────────────────────────────────────────────────────
// ADD TO CART (GET action=add&id=xxx&size=yyy)
// ─────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'add' && !empty($_GET['id'])) {
  $pid = trim($_GET['id']);
  $size = trim($_GET['size'] ?? '');

  // Size is required
  if (empty($size)) {
    header('Location: ' . $link_home . '?error=no_size');
    exit();
  }

  // Check product existence
  $chk = $conn->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
  $chk->bind_param('s', $pid);
  $chk->execute();
  if ($chk->get_result()->num_rows > 0) {
    $st = $conn->prepare("
            INSERT INTO cart (user_id, product_id, quantity, size)
            VALUES (?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + 1
        ");
    $st->bind_param('iss', $user_id, $pid, $size);
    $st->execute();
    $st->close();
  }
  $chk->close();
  header('Location: ' . $link_cart . '?added=1');
  exit();
}

// ─────────────────────────────────────────────────────────
// FETCH CART
// ─────────────────────────────────────────────────────────
$cart_rows = [];
$total = 0.0;
$item_count = 0;

$st = $conn->prepare("
    SELECT c.product_id, c.quantity, c.size,
           p.name, p.price, p.image, p.cost_price, p.profit_percent, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.product_id ASC
");
$st->bind_param('i', $user_id);
$st->execute();
$res = $st->get_result();
while ($row = $res->fetch_assoc()) {
  $sp = sellingPrice($row);
  $qty = (int) $row['quantity'];
  $row['selling_price'] = $sp;
  $row['item_total'] = $sp * $qty;
  $total += $row['item_total'];
  $item_count += $qty;
  $cart_rows[] = $row;
}
$st->close();

$added_flash = isset($_GET['added']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shopping Cart | 36 Jewelry</title>
  <meta name="description" content="Your 36 Jewelry shopping cart - review items and proceed to checkout.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap"
    rel="stylesheet">
  <style>
    /* ═══════════════════════════════ RESET ═══ */
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

    html {
      scrollbar-gutter: stable;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: #fafafa;
      min-height: 100vh;
      color: var(--dark);
      margin: 0;
    }

    /* ═══════════════════════════════ HEADER ═══ */
    .header-container {
      width: 100%;
      position: sticky;
      top: 0;
      z-index: 1000;
      background-color: var(--bg);
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .search-bar {
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      background: var(--bg);
      border-top: 1px solid rgba(0, 0, 0, 0.03);
      border-bottom: 1px solid rgba(0, 0, 0, 0.04);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
      position: sticky;
      top: 0;
      z-index: 999;
    }
    
    .search-bar .left { 
      width: auto; 
      justify-content: flex-start;
    }
    
    .search-bar .right { 
      justify-content: flex-end; 
      width: auto; 
      gap: 4px;
    }

    .search-bar .left,
    .search-bar .center,
    .search-bar .right {
      display: flex;
      align-items: center;
    }

    .search-bar .center {
      flex: 1 1 auto;
      justify-content: center;
      gap: 30px;
      position: relative;
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

    .search-bar .center a {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      z-index: 10;
    }

    .header-logo {
      height: 55px;
      max-width: 180px;
      object-fit: contain;
      transition: all 0.25s ease;
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
      box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.03);
      height: 50px;
    }

    .search-box input {
      flex: 1;
      border: 0;
      outline: 0;
      background: transparent;
      padding: 4px 8px;
      font-size: 15px;
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
      transition: .18s;
      font-size: 18px;
      position: relative;
    }

    .icon-link:hover {
      background: rgba(184, 134, 11, .1);
      color: var(--gold);
    }
    
    .icon-link i {
      color: inherit;
    }

    .cart-badge {
      position: absolute;
      top: 4px;
      right: 4px;
      background: var(--gold);
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

    .user-icon { font-size: 20px; }

    /* ═══════════════════════════════ FLASH ═══ */
    .flash {
      background: linear-gradient(135deg, #d4af37, #b8860b);
      color: #fff;
      text-align: center;
      padding: 12px;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    /* ═══════════════════════════════ PAGE ═══ */
    .page-wrapper {
      max-width: 1100px;
      margin: 36px auto;
      padding: 0 20px 60px;
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 28px;
      align-items: start;
    }

    /* ═══════════════════════════════ CART TABLE ═══ */
    .cart-box {
      background: #fff;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow-x: auto; /* Allow horizontal scroll if needed */
      position: relative;
    }

    .cart-box-header {
      padding: 20px 24px;
      border-bottom: 1px solid #f0eee8;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .cart-box-header h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 24px;
      font-weight: 700;
      color: var(--dark);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .cart-box-header h1 i {
      color: var(--gold);
    }

    .item-count-badge {
      background: var(--accent);
      color: var(--muted);
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
    }

    /* Empty state */
    .empty-cart {
      text-align: center;
      padding: 60px 20px;
    }

    .empty-cart i {
      font-size: 56px;
      color: #e0d5c0;
      display: block;
      margin-bottom: 16px;
    }

    .empty-cart p {
      font-size: 17px;
      color: var(--muted);
      margin-bottom: 24px;
    }

    .btn-shop {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      background: linear-gradient(135deg, var(--gold-lt), var(--gold));
      color: #fff;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 600;
      font-size: 15px;
      transition: .25s;
    }

    .btn-shop:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 16px rgba(184, 134, 11, .35);
    }

    /* Cart items */
    .cart-table {
      width: 100%;
      border-collapse: collapse;
    }

    .cart-table thead tr {
      background: #faf8f3;
      border-bottom: 2px solid #ede9df;
    }

    .cart-table th {
      padding: 12px 16px;
      text-align: left;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .5px;
      color: var(--muted);
    }

    .cart-table th:last-child {
      text-align: center;
      position: sticky;
      right: 0;
      background: #faf8f3; /* Match header bg */
      z-index: 10;
      min-width: 60px;
      box-shadow: -4px 0 8px rgba(0,0,0,0.02);
    }

    .cart-table tbody tr {
      border-bottom: 1px solid #f5f3ee;
      transition: .15s;
    }

    .cart-table tbody tr:hover {
      background: #fffdf7;
    }

    .cart-table tbody tr:last-child {
      border-bottom: none;
    }

    .cart-table td {
      padding: 16px;
    }

    /* Fixed/Sticky last column for trash icon */
    .cart-table td:last-child {
      position: sticky;
      right: 0;
      background: inherit;
      z-index: 5;
      min-width: 60px;
      text-align: center;
      box-shadow: -4px 0 8px rgba(0,0,0,0.02);
    }

    .cart-table tbody tr {
      background: #fff;
    }

    /* Product cell */
    .product-cell {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .product-thumb {
      width: 68px;
      height: 68px;
      object-fit: cover;
      border-radius: 10px;
      border: 1px solid #ece8df;
      flex-shrink: 0;
    }

    .product-name {
      font-weight: 600;
      font-size: 15px;
      color: var(--dark);
      text-decoration: none;
      display: block;
      margin-bottom: 3px;
    }

    .product-name:hover {
      color: var(--gold);
    }

    .product-id {
      font-size: 12px;
      color: var(--muted);
    }

    /* Qty input */
    .qty-wrapper {
      display: flex;
      align-items: center;
      gap: 0;
      border: 1px solid #ddd;
      border-radius: 8px;
      overflow: hidden;
      width: fit-content;
    }

    .qty-btn {
      width: 32px;
      height: 32px;
      border: none;
      background: var(--accent);
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      color: var(--dark);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: .15s;
    }

    .qty-btn:hover {
      background: var(--gold);
      color: #fff;
    }

    .qty-input {
      width: 44px;
      height: 32px;
      border: none;
      border-left: 1px solid #ddd;
      border-right: 1px solid #ddd;
      text-align: center;
      font-size: 14px;
      font-weight: 600;
      outline: none;
      color: var(--dark);
      background: #fff;
    }

    /* Price cells */
    .price-cell {
      font-size: 14px;
      color: var(--muted);
    }

    .total-cell {
      font-size: 15px;
      font-weight: 700;
      color: var(--gold);
      text-align: right;
    }

    /* Remove btn */
    .remove-btn {
      background: none;
      border: none;
      cursor: pointer;
      color: #ccc;
      font-size: 17px;
      padding: 6px;
      border-radius: 6px;
      transition: .2s;
      display: flex;
    }

    .remove-btn:hover {
      color: #e53935;
      background: #fff0f0;
    }

    /* ═══════════════════════════════ SUMMARY PANEL ═══ */
    .summary-box {
      background: #fff;
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 24px;
      position: sticky;
      top: 90px;
    }

    .summary-title {
      font-family: 'Cormorant Garamond', serif;
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 20px;
      color: var(--dark);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .summary-title i {
      color: var(--gold-lt);
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 9px 0;
      font-size: 14px;
      border-bottom: 1px solid #f5f3ee;
    }

    .summary-row:last-of-type {
      border-bottom: none;
    }

    .summary-row .label {
      color: var(--muted);
    }

    .summary-row .value {
      font-weight: 600;
      color: var(--dark);
    }

    .summary-divider {
      border: none;
      border-top: 2px solid #ede9df;
      margin: 12px 0;
    }

    .summary-total {
      display: flex;
      justify-content: space-between;
      font-size: 18px;
      font-weight: 700;
      margin: 4px 0 20px;
    }

    .summary-total .amt {
      color: var(--gold);
    }

    .btn-checkout {
      display: block;
      width: 100%;
      padding: 14px;
      text-align: center;
      background: linear-gradient(135deg, #d4af37, #b8860b);
      color: #fff;
      font-size: 16px;
      font-weight: 700;
      border-radius: 10px;
      text-decoration: none;
      border: none;
      cursor: pointer;
      box-shadow: 0 4px 20px rgba(184, 134, 11, .3);
      transition: .25s;
      font-family: 'DM Sans', sans-serif;
    }

    .btn-checkout:hover {
      background: linear-gradient(135deg, #e0c050, #c9972a);
      transform: translateY(-1px);
      box-shadow: 0 6px 24px rgba(184, 134, 11, .4);
    }

    .btn-checkout:disabled,
    .btn-checkout.disabled {
      background: #ccc !important;
      box-shadow: none !important;
      cursor: not-allowed !important;
      transform: none !important;
      pointer-events: none;
    }

    .btn-continue {
      display: block;
      text-align: center;
      margin-top: 12px;
      color: var(--muted);
      font-size: 13px;
      text-decoration: none;
      transition: .2s;
    }

    .btn-continue:hover {
      color: var(--gold);
    }

    /* Secure badge */
    .secure-note {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 16px;
      font-size: 12px;
      color: var(--muted);
    }

    .secure-note i {
      color: #4caf50;
    }

    /* Alert */
    .alert-toast {
      position: fixed;
      top: 80px;
      right: 20px;
      padding: 12px 20px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 14px;
      color: #fff;
      opacity: 0;
      transform: translateY(-10px);
      transition: .3s;
      z-index: 9999;
      min-width: 200px;
      text-align: center;
    }

    .alert-toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    .alert-toast.success {
      background: linear-gradient(135deg, #43a047, #2e7d32);
    }

    .alert-toast.error {
      background: linear-gradient(135deg, #e53935, #b71c1c);
    }

    /* ═══════════════════════════════ RESPONSIVE ═══ */
    @media(max-width:900px) {
      .page-wrapper {
        grid-template-columns: 1fr;
      }

      .summary-box {
        position: static;
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

      .cart-table th:nth-child(2),
      .cart-table td:nth-child(2) {
        display: none;
      }

      /* hide unit price on mobile */
      .product-thumb {
        width: 52px;
        height: 52px;
      }
    }
  </style>
</head>

<body>

  <!-- ══ HEADER ════════════════════════════════════════════ -->
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
          <input type="text" id="header-search" placeholder="Search products..."
            onkeydown="if(event.key==='Enter') applyHeaderSearch()">
          <button onclick="applyHeaderSearch()"><i class="fas fa-search"></i></button>
        </div>
      </div>
      <div class="right">
        <a href="<?= $link_cart ?>" class="icon-link" title="Cart">
          <i class="fas fa-shopping-cart"></i>
          <?php if ($item_count > 0): ?>
            <span class="cart-badge"><?= $item_count > 9 ? '9+' : $item_count ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= $link_profile ?>" class="icon-link" title="Profile">
          <i class="fas fa-user-circle user-icon"></i>
        </a>
        <a href="<?= $link_logout ?>" class="icon-link" title="Logout" style="color:#111;">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </div>
    </div>
  </header>

  <?php if ($added_flash): ?>
    <div class="flash"><i class="fas fa-check-circle"></i> Item added to your cart!</div>
  <?php endif; ?>

  <!-- ══ MAIN ══════════════════════════════════════════════ -->
  <div class="page-wrapper">

    <!-- LEFT: Cart items -->
    <div class="cart-box">
      <div class="cart-box-header">
        <h1><i class="fas fa-shopping-cart"></i> My Cart</h1>
        <span class="item-count-badge">
          <?= count($cart_rows) ?> <?= count($cart_rows) === 1 ? 'item' : 'items' ?>
        </span>
      </div>

      <?php if (empty($cart_rows)): ?>
        <div class="empty-cart">
          <i class="fas fa-shopping-bag"></i>
          <p>Your cart is empty. Start shopping!</p>
          <a href="<?= $link_shop ?>" class="btn-shop"><i class="fas fa-gem"></i> Browse Products</a>
        </div>
      <?php else: ?>
        <table class="cart-table" id="cart-table">
          <thead>
            <tr>
              <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all" checked></th>
              <th>Product</th>
              <th>Size</th>
              <th>Unit Price</th>
              <th>Quantity</th>
              <th style="text-align:right">Total</th>
              <th></th>
            </tr>
          </thead>

          <tbody id="cart-body">
            <?php foreach ($cart_rows as $item): ?>
              <tr id="row-<?= htmlspecialchars($item['product_id']) ?>"
                data-pid="<?= htmlspecialchars($item['product_id']) ?>" data-price="<?= $item['selling_price'] ?>">
                <td style="text-align: center;">
                  <input type="checkbox" class="item-checkbox" value="<?= htmlspecialchars($item['product_id']) ?>" checked>
                </td>
                <td>

                  <div class="product-cell">
                    <img src="<?= IMG_URL . htmlspecialchars($item['image'] ?? '') ?>"
                      alt="<?= htmlspecialchars($item['name']) ?>" class="product-thumb"
                      onerror="this.src='<?= IMG_URL ?>default-avatar.png'">
                    <div>
                      <a href="<?= $link_detail ?>?id=<?= urlencode($item['product_id']) ?>&size=<?= urlencode($item['size'] ?? '') ?>"
                        class="product-name"><?= htmlspecialchars($item['name']) ?></a>
                      <span class="product-id">SKU: <?= htmlspecialchars($item['product_id']) ?></span>
                    </div>
                  </div>
                </td>
                <td class="price-cell">
                  <?php if (!empty($item['size'])): ?>
                    <span
                      style="display:inline-flex;align-items:center;gap:5px;background:#f8f4ec;border:1px solid #d4b896;color:#7a5c2e;font-size:12px;font-weight:600;padding:4px 10px;border-radius:4px;letter-spacing:.05em;">
                      <i class="fas fa-ring" style="font-size:10px;color:#b8945f;"></i>
                      Size <?= htmlspecialchars($item['size']) ?>
                    </span>
                  <?php else: ?>
                    <span style="color:#ccc;font-size:12px;">—</span>
                  <?php endif; ?>
                </td>
                <td class="price-cell">$<?= number_format($item['selling_price'], 2) ?></td>
                <td>
                  <div class="qty-wrapper">
                    <button class="qty-btn qty-dec" type="button">−</button>
                    <input type="number" class="qty-input" value="<?= (int) $item['quantity'] ?>" min="1"
                      max="<?= (int) ($item['stock'] ?? 99) ?>" data-pid="<?= htmlspecialchars($item['product_id']) ?>">
                    <button class="qty-btn qty-inc" type="button">+</button>
                  </div>
                </td>
                <td class="total-cell item-total">$<?= number_format($item['item_total'], 2) ?></td>
                <td>
                  <button class="remove-btn" data-pid="<?= htmlspecialchars($item['product_id']) ?>" title="Remove item"><i
                      class="fas fa-trash-alt"></i></button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- RIGHT: Summary -->
    <div class="summary-box">
      <h2 class="summary-title"><i class="fas fa-receipt"></i> Order Summary</h2>

      <div class="summary-row">
        <span class="label">Subtotal (<?= $item_count ?> items)</span>
        <span class="value" id="subtotal">$<?= number_format($total, 2) ?></span>
      </div>
      <div class="summary-row">
        <span class="label">Shipping</span>
        <span class="value" style="color:#4caf50">Free</span>
      </div>

      <hr class="summary-divider">
      <div class="summary-total">
        <span>Total</span>
        <span class="amt" id="grand-total">$<?= number_format($total, 2) ?></span>
      </div>

      <?php if (!empty($cart_rows)): ?>
        <a href="<?= $link_checkout ?>" class="btn-checkout" id="btn-checkout">
          <i class="fas fa-lock"></i> Proceed to Checkout
        </a>
      <?php else: ?>
        <button class="btn-checkout" disabled>Cart is Empty</button>
      <?php endif; ?>

      <a href="<?= $link_shop ?>" class="btn-continue">← Continue Shopping</a>

      <div class="secure-note">
        <i class="fas fa-shield-alt"></i> Secure 256-bit SSL encrypted checkout
      </div>
    </div>

  </div><!-- /.page-wrapper -->

  <!-- Toast -->
  <div class="alert-toast" id="toast"></div>

  <script>
    // ── Toast ───────────────────────────────────────────────
    function showToast(msg, type = 'success') {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.className = 'alert-toast ' + type + ' show';
      clearTimeout(t._timer);
      t._timer = setTimeout(() => t.classList.remove('show'), 2500);
    }

    // ── Search ──────────────────────────────────────────────
    function applyHeaderSearch() {
      const kw = document.getElementById('header-search').value.trim();
      if (kw) window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(kw);
    }

    // ── Recalculate totals ──────────────────────────────────
    function recalcTotals() {
      let total = 0, count = 0, allCount = 0;
      let selectedIds = [];
      document.querySelectorAll('#cart-body tr[data-pid]').forEach(row => {
        const checkbox = row.querySelector('.item-checkbox');
        const price = parseFloat(row.dataset.price) || 0;
        const qty = parseInt(row.querySelector('.qty-input').value) || 1;
        const itemTotal = price * qty;
        row.querySelector('.item-total').textContent = '$' + itemTotal.toFixed(2);

        allCount += qty; // Total items for header badge

        if (checkbox && checkbox.checked) {
          total += itemTotal;
          count += qty;
          selectedIds.push(row.dataset.pid);
        }
      });

      // Update header cart badge
      const badge = document.querySelector('.cart-badge');
      if (badge) {
        if (allCount > 0) {
          badge.textContent = allCount > 9 ? '9+' : allCount;
          badge.style.display = 'flex';
        } else {
          badge.style.display = 'none';
        }
      } else if (allCount > 0) {
        // If badge didn't exist, create it inside the cart icon link
        const cartLink = document.querySelector('a[href="<?= $link_cart ?>"].icon-link');
        if (cartLink) {
          const newBadge = document.createElement('span');
          newBadge.className = 'cart-badge';
          newBadge.textContent = allCount > 9 ? '9+' : allCount;
          cartLink.appendChild(newBadge);
        }
      }

      document.getElementById('subtotal').textContent = '$' + total.toFixed(2);
      document.getElementById('grand-total').textContent = '$' + total.toFixed(2);

      const summaryItemsLabel = document.querySelector('.summary-row .label');
      if (summaryItemsLabel) {
        summaryItemsLabel.textContent = `Subtotal (${count} items)`;
      }

      const checkoutBtn = document.getElementById('btn-checkout');
      if (checkoutBtn && checkoutBtn.tagName === 'A') {
        if (selectedIds.length > 0) {
          checkoutBtn.classList.remove('disabled');
          checkoutBtn.href = '<?= $link_checkout ?>?items=' + encodeURIComponent(selectedIds.join(','));
        } else {
          checkoutBtn.classList.add('disabled');
          checkoutBtn.removeAttribute('href');
        }
      }
    }

    // ── Checkboxes ───────────────────────────────────────────
    document.getElementById('select-all')?.addEventListener('change', function () {
      const isChecked = this.checked;
      document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = isChecked;
      });
      recalcTotals();
    });

    document.querySelectorAll('.item-checkbox').forEach(cb => {
      cb.addEventListener('change', function () {
        const allChecked = document.querySelectorAll('.item-checkbox:not(:checked)').length === 0;
        const selectAll = document.getElementById('select-all');
        if (selectAll) selectAll.checked = allChecked;
        recalcTotals();
      });
    });

    document.addEventListener('DOMContentLoaded', recalcTotals);


    // ── AJAX helper ─────────────────────────────────────────
    function cartAjax(data) {
      return fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data).toString()
      }).then(r => r.json());
    }

    // ── Qty +/- buttons ─────────────────────────────────────
    document.querySelectorAll('.qty-dec').forEach(btn => {
      btn.addEventListener('click', () => {
        const input = btn.nextElementSibling;
        if (parseInt(input.value) > 1) {
          input.value = parseInt(input.value) - 1;
          input.dispatchEvent(new Event('change'));
        }
      });
    });
    document.querySelectorAll('.qty-inc').forEach(btn => {
      btn.addEventListener('click', () => {
        const input = btn.previousElementSibling;
        const max = parseInt(input.max) || 99;
        if (parseInt(input.value) < max) {
          input.value = parseInt(input.value) + 1;
          input.dispatchEvent(new Event('change'));
        }
      });
    });

    // ── Update quantity ──────────────────────────────────────
    document.querySelectorAll('.qty-input').forEach(input => {
      let debounce;
      input.addEventListener('change', function () {
        clearTimeout(debounce);
        const pid = this.dataset.pid;
        const qty = Math.max(1, parseInt(this.value) || 1);
        this.value = qty;
        recalcTotals();
        debounce = setTimeout(() => {
          cartAjax({ ajax_action: 'update', product_id: pid, quantity: qty })
            .then(d => showToast(d.success ? 'Quantity updated!' : 'Update failed', d.success ? 'success' : 'error'))
            .catch(() => showToast('Network error', 'error'));
        }, 600);
      });
    });

    // ── Remove item ──────────────────────────────────────────
    document.querySelectorAll('.remove-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const pid = this.dataset.pid;
        const row = document.getElementById('row-' + pid);
        cartAjax({ ajax_action: 'remove', product_id: pid })
          .then(d => {
            if (d.success) {
              row.style.transition = 'opacity .3s';
              row.style.opacity = '0';
              setTimeout(() => {
                row.remove();
                recalcTotals();
                // If cart is empty
                if (!document.querySelector('#cart-body tr[data-pid]')) {
                  document.getElementById('cart-body').innerHTML = '';
                  document.getElementById('cart-table').remove();
                  document.querySelector('.cart-box').insertAdjacentHTML('beforeend',
                    `<div class="empty-cart">
                   <i class="fas fa-shopping-bag"></i>
                   <p>Your cart is empty.</p>
                   <a href="<?= $link_shop ?>" class="btn-shop"><i class="fas fa-gem"></i> Browse Products</a>
                 </div>`);
                  document.getElementById('btn-checkout')?.setAttribute('disabled', 'disabled');
                }
              }, 300);
              showToast('Item removed!');
            }
          })
          .catch(() => showToast('Failed to remove', 'error'));
      });
    });
  </script>

</body>

</html>