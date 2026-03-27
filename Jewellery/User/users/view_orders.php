<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: " . BASE_URL . "User/Login.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Lấy thông tin khách hàng
$stmt = $conn->prepare("SELECT u.email, c.full_name, c.phone, c.address, c.id as customer_id FROM users u LEFT JOIN customers c ON u.id = c.user_id WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
if (!$user_info) $user_info = ['email' => '', 'full_name' => '', 'phone' => '', 'address' => '', 'customer_id' => null];

// Lấy danh sách đơn hàng
$orders = [];
if ($user_info['customer_id']) {
    $order_stmt = $conn->prepare("SELECT id, order_number, order_date, total_amount, status FROM orders WHERE customer_id = ? ORDER BY order_date DESC");
    $order_stmt->bind_param("i", $user_info['customer_id']);
    $order_stmt->execute();
    $orders_result = $order_stmt->get_result();
    while ($order = $orders_result->fetch_assoc()) {
        $items_stmt = $conn->prepare("SELECT product_name, quantity, unit_price, total_price FROM order_items WHERE order_id = ?");
        $items_stmt->bind_param("i", $order['id']);
        $items_stmt->execute();
        $order['items'] = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $orders[] = $order;
    }
}

define('BASE_URL', '/do_an_web/Jewellery/');
define('IMG_URL', BASE_URL . 'images/');
$link_home = BASE_URL . 'User/index.php';
$link_cart = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_search = BASE_URL . 'User/users/search.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Your Orders | 36 Jewelry</title>
  <link rel="stylesheet" href="view_orders.css"><link rel="stylesheet" href="search.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .back-btn { position: fixed; top: 90px; left: 20px; z-index: 9999; background: rgba(0,0,0,0.35); border: 1px solid #f8ce86; color: #f8ce86; width: 40px; height: 40px; border-radius: 50%; font-size: 20px; cursor: pointer; transition: all 0.3s ease; backdrop-filter: blur(6px); }
    .back-btn:hover { background: #f8ce86; color: #000; box-shadow: 0 0 10px rgba(248,206,134,0.6); transform: scale(1.05); }
    @media (max-width: 768px) { .back-btn { top: 140px; } }
  </style>
</head>
<body>
<header class="header-container">
  <div class="search-bar">
    <div class="left"><a href="<?= $link_home ?>" class="home-btn"><i class="fas fa-home"></i> Home</a></div>
    <div class="center"><a href="<?= $link_home ?>"><img src="<?= IMG_URL ?>36-logo.png" class="header-logo"></a><div class="search-box"><input type="text" id="search-input" placeholder="Search products..."><button onclick="doSearch()"><i class="fas fa-search"></i></button></div></div>
    <div class="right"><a href="<?= $link_cart ?>" class="icon-link"><i class="fas fa-shopping-cart"></i></a><a href="<?= $link_profile ?>" class="icon-link"><i class="fas fa-user"></i></a></div>
  </div>
</header>
<main class="orders-container">
  <button class="back-btn" onclick="window.location.href='<?= $link_home ?>'">←</button>
  <h2>Your Recent Orders</h2><p class="subtitle">Here are the items you've recently purchased with 36 Jewelry.</p>
  <section class="customer-info"><h3>Customer Information</h3><div class="info-grid"><p><strong>Name:</strong> <?= htmlspecialchars($user_info['full_name'] ?: 'Not set') ?></p><p><strong>Email:</strong> <?= htmlspecialchars($user_info['email'] ?: 'Not set') ?></p><p><strong>Phone:</strong> <?= htmlspecialchars($user_info['phone'] ?: 'Not set') ?></p><p><strong>Address:</strong> <?= htmlspecialchars($user_info['address'] ?: 'Not set') ?></p></div></section>
  <?php if (empty($orders)): ?><p style="text-align:center;color:white;">You have no orders yet.</p><?php else: foreach ($orders as $order): ?>
    <div class="order-card"><div class="order-header"><span>Order ID: <strong><?= htmlspecialchars($order['order_number']) ?></strong></span><span>Date: <strong><?= date('M j, Y', strtotime($order['order_date'])) ?></strong></span><span>Status: <strong class="status <?= strtolower($order['status']) === 'pending' ? 'pending' : 'success' ?>"><?= htmlspecialchars($order['status']) ?></strong></span></div>
    <table class="order-table"><thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead><tbody><?php foreach ($order['items'] as $item): ?><tr><td class="product-info"><img src="<?= IMG_URL ?>placeholder.jpg" onerror="this.src='<?= IMG_URL ?>placeholder.jpg'"><span><?= htmlspecialchars($item['product_name']) ?></span></td><td>$<?= number_format($item['unit_price'], 2) ?></td><td><?= $item['quantity'] ?></td><td>$<?= number_format($item['total_price'], 2) ?></td></tr><?php endforeach; ?></tbody></table>
    <p class="order-total">Order Total: <strong>$<?= number_format($order['total_amount'], 2) ?></strong></p></div>
  <?php endforeach; endif; ?>
</main>
<footer class="footer"><p>&copy; 2025 36 Jewelry. All rights reserved.</p></footer>
<script>function doSearch() { const keyword = document.getElementById('search-input').value.trim(); if (keyword !== '') window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(keyword); }</script>
</body>
</html>