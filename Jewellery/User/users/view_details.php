<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Định nghĩa hằng TRƯỚC khi dùng
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

// Link helpers
$link_home = BASE_URL . 'User/indexprofile.php';
$link_cart = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_logout = BASE_URL . 'User/users/logout.php';
$link_search = BASE_URL . 'User/Search/search.html';
$link_history = BASE_URL . 'User/users/history.php';

// Get order_id from URL
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($order_id <= 0) {
  die("Invalid order ID.");
}

// ── Handle Actions (POST) ─────────────────────────────
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['cancel_order_id'])) {
    $cancel_id = (int) $_POST['cancel_order_id'];

    // Verify order belongs to this customer and is still Pending
    $chk = $conn->prepare("
          SELECT o.id FROM orders o
          JOIN customers c ON o.customer_id = c.id
          WHERE o.id = ? AND c.user_id = ? AND o.status = 'Pending'
          LIMIT 1
      ");
    $chk->bind_param('ii', $cancel_id, $user_id);
    $chk->execute();
    $can = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($can) {
      // Restore stock before cancelling
      $items_q = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
      $items_q->bind_param('i', $cancel_id);
      $items_q->execute();
      $items_res = $items_q->get_result();
      while ($it = $items_res->fetch_assoc()) {
        $upd = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $upd->bind_param('ii', $it['quantity'], $it['product_id']);
        $upd->execute();
        $upd->close();
      }
      $items_q->close();

      // Update order status → Cancelled
      $upd_order = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?");
      $upd_order->bind_param('i', $cancel_id);
      $upd_order->execute();
      $upd_order->close();
      $success_msg = 'Your order has been cancelled successfully.';
    } else {
      $error_msg = 'Unable to cancel this order (only Pending orders can be cancelled).';
    }
  } elseif (isset($_POST['receive_order_id'])) {
    $receive_id = (int) $_POST['receive_order_id'];

    // Verify order belongs to this customer and is Shipped/Shipping
    $chk = $conn->prepare("
          SELECT o.id FROM orders o
          JOIN customers c ON o.customer_id = c.id
          WHERE o.id = ? AND c.user_id = ? AND o.status IN ('Shipped', 'Shipping')
          LIMIT 1
      ");
    $chk->bind_param('ii', $receive_id, $user_id);
    $chk->execute();
    $can = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($can) {
      // Update order status → Delivered
      $upd_order = $conn->prepare("UPDATE orders SET status = 'Delivered' WHERE id = ?");
      $upd_order->bind_param('i', $receive_id);
      $upd_order->execute();
      $upd_order->close();
      $success_msg = 'Cảm ơn! Bạn đã xác nhận nhận hàng thành công.';
    } else {
      $error_msg = 'Unable to mark this order as received (only Shipped orders).';
    }
  }
}

// Fetch order info and verify ownership
$stmt = $conn->prepare("
    SELECT o.id, o.order_number, o.order_date, o.total_amount, o.status,
           c.full_name, c.address, c.phone
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ? AND c.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
  die("Order not found or you don't have permission to view it.");
}

// Fetch order items
$stmt = $conn->prepare("
    SELECT oi.product_id, oi.product_name, oi.quantity, oi.unit_price, oi.total_price,
           p.image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Format date
$order_date = date('M d, Y', strtotime($order['order_date']));

// Determine status class and label
$can_cancel = ($order['status'] === 'Pending');
$can_receive = ($order['status'] === 'Shipped' || $order['status'] === 'Shipping');
switch ($order['status']) {
  case 'Pending':
    $status_class = 'pending';
    $status_text = 'Pending';
    break;
  case 'Processed':
  case 'Processing':
    $status_class = 'completed';
    $status_text = 'Processing';
    break;
  case 'Shipping':
  case 'Shipped':
    $status_class = 'completed';
    $status_text = 'Shipping';
    break;
  case 'Delivered':
    $status_class = 'completed';
    $status_text = 'Delivered';
    break;
  case 'Cancelled':
    $status_class = 'cancelled';
    $status_text = 'Cancelled';
    break;
  default:
    $status_class = 'pending';
    $status_text = $order['status'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Details | 36 Jewelry</title>
  <meta name="description" content="View details of your 36 Jewelry order.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Cormorant Garamond", serif;
    }

    body {
      background: linear-gradient(rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0.7)),
        url("<?= IMG_URL ?>profile background.jpg") no-repeat center center/cover;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      color: #333;
      opacity: 0;
      animation: fadeInBody 1s forwards;
    }

    @keyframes fadeInBody {
      to {
        opacity: 1;
      }
    }

    .main-content {
      width: 90%;
      max-width: 900px;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 20px;
      margin-top: 60px;
      padding: 30px 40px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      animation: fadeInUp 1s ease forwards;
      transform: translateY(20px);
      opacity: 0;
      position: relative;
    }

    @keyframes fadeInUp {
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

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

    .back-button i {
      font-size: 16px;
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

    .order-info p {
      font-size: 16px;
      margin-bottom: 8px;
    }

    .order-info span {
      font-weight: 600;
    }

    .product-item {
      display: flex;
      gap: 20px;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid #ddd;
    }

    .product-thumb {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 10px;
    }

    .product-details {
      flex: 1;
    }

    .product-details h3 {
      color: #b8860b;
      font-size: 20px;
      margin-bottom: 8px;
    }

    .product-details p {
      font-size: 15px;
      margin-bottom: 5px;
    }

    .product-details span {
      font-weight: 600;
    }

    .status {
      font-weight: 600;
      padding: 5px 10px;
      border-radius: 8px;
      color: #fff;
    }

    .status.pending {
      background-color: #ff9800;
    }

    .status.completed {
      background-color: #4caf50;
    }

    .status.cancelled {
      background-color: #f44336;
    }

    /* Alert messages */
    .alert-success,
    .alert-error {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 18px;
      animation: slideDown .3s ease;
    }

    .alert-success {
      background: rgba(76, 175, 80, .12);
      border: 1px solid #4caf50;
      color: #2e7d32;
    }

    .alert-error {
      background: rgba(244, 67, 54, .10);
      border: 1px solid #f44336;
      color: #c62828;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-6px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Status row with cancel button */
    .status-row {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    /* Action buttons */
    .btn-cancel, .btn-receive {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 14px;
      border-radius: 30px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: all .2s;
      font-family: inherit;
    }

    .btn-cancel {
      border: 1.5px solid #f44336;
      background: rgba(244, 67, 54, .1);
      color: #c62828;
    }
    .btn-cancel:hover {
      background: #f44336;
      color: #fff;
      transform: scale(1.04);
    }

    .btn-receive {
      border: 1.5px solid #4CAF50;
      background: rgba(76, 175, 80, .1);
      color: #2E7D32;
    }
    .btn-receive:hover {
      background: #4CAF50;
      color: #fff;
      transform: scale(1.04);
    }

    /* Modal */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .55);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-box {
      background: #fff;
      border-radius: 16px;
      padding: 32px 28px;
      max-width: 400px;
      width: 90%;
      text-align: center;
      animation: popIn .25s ease;
      box-shadow: 0 8px 32px rgba(0, 0, 0, .18);
    }

    @keyframes popIn {
      from {
        opacity: 0;
        transform: scale(.9);
      }

      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    .modal-box .modal-icon {
      font-size: 44px;
      color: #f44336;
      margin-bottom: 12px;
    }

    .modal-box h3 {
      font-size: 20px;
      color: #333;
      margin-bottom: 8px;
    }

    .modal-box p {
      font-size: 14px;
      color: #666;
      margin-bottom: 22px;
      line-height: 1.6;
    }

    .modal-actions {
      display: flex;
      gap: 10px;
      justify-content: center;
    }

    .btn-modal-back {
      padding: 9px 20px;
      border-radius: 8px;
      border: 1.5px solid #ccc;
      background: #f5f5f5;
      color: #333;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: .2s;
      font-family: inherit;
    }

    .btn-modal-back:hover {
      border-color: #999;
      background: #ebebeb;
    }

    .btn-modal-confirm {
      padding: 9px 20px;
      border-radius: 8px;
      border: none;
      background: #f44336;
      color: #fff;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: .2s;
      font-family: inherit;
    }

    .btn-modal-confirm:hover {
      background: #d32f2f;
    }

    .total {
      text-align: right;
      font-size: 18px;
      font-weight: 600;
      color: #222;
      margin-top: 16px;
    }

    .total strong {
      color: #b8860b;
    }

    .footer {
      margin-top: 40px;
      text-align: center;
      font-size: 14px;
      color: #555;
      padding: 20px 0;
    }

    /* Header styling */
    .header-container {
      width: 100%;
      position: sticky;
      top: 0;
      z-index: 1000;
      background-color: #ffffff;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .search-bar {
      width: 100%;
      max-width: 1400px;
      margin: 0 auto;
      background: #ffffff;
      border-top: 1px solid rgba(0, 0, 0, 0.03);
      border-bottom: 1px solid rgba(0, 0, 0, 0.04);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    }

    .search-bar .left,
    .search-bar .center,
    .search-bar .right {
      display: flex;
      align-items: center;
    }

    .search-bar .left {
      width: auto;
      justify-content: flex-start;
    }

    .search-bar .right {
      justify-content: flex-end;
      width: auto;
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
      justify-content: center;
      padding: 10px 15px;
      border-radius: 8px;
      text-decoration: none;
      color: #111111;
      font-weight: 600;
      font-size: 16px;
      transition: background-color 0.2s;
    }

    .home-btn i {
      margin-right: 5px;
      font-size: 18px;
    }

    .home-btn:hover {
      background: #f9f9f9;
      color: #b8860b;
    }

    .search-bar .center a {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      z-index: 10;
    }

    .search-bar .center .header-logo {
      height: 55px;
      max-width: 180px;
      object-fit: contain;
    }

    .search-box {
      flex: 0 1 450px;
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 8px;
      background: #f9f9f9;
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
      padding: 2px 6px;
      font-size: 15px;
      color: #111;
    }

    .search-box button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border: 0;
      background: #b8860b;
      color: #fff;
      padding: 8px 12px;
      border-radius: 999px;
      cursor: pointer;
      font-size: 14px;
      transition: background 0.3s;
    }

    .search-box button:hover {
      background: #996600;
    }

    .icon-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      border-radius: 8px;
      text-decoration: none;
      color: #111;
      transition: all 0.18s;
      font-size: 18px;
    }

    .icon-link:hover {
      background: rgba(186, 134, 11, 0.08);
      color: #b8860b;
    }

    @media (max-width: 768px) {
      .search-bar {
        flex-direction: column;
        align-items: center;
        gap: 10px;
      }

      .search-box {
        width: 100%;
        margin-left: 0;
      }
    }
  </style>
</head>

<body class="homepage bg-accent-light">

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
    <button class="back-button" onclick="window.history.back();"><i class="fas fa-arrow-left"></i> Back</button>

    <h2>Order #<?= htmlspecialchars($order['order_number']) ?> Details</h2>

    <?php if ($success_msg): ?>
      <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
      <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <div class="order-info">
      <p>Order Date: <span><?= $order_date ?></span></p>
      <p>Status:
        <span class="status-row">
          <span class="status <?= $status_class ?>"><?= $status_text ?></span>
          <?php if ($can_cancel): ?>
            <button class="btn-cancel"
              onclick="openCancelModal(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number'], ENT_QUOTES) ?>')"
              id="cancel-btn">
              <i class="fas fa-times"></i> Cancel Order
            </button>
          <?php endif; ?>
          <?php if ($can_receive): ?>
            <button class="btn-receive"
              onclick="openReceiveModal(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number'], ENT_QUOTES) ?>')"
              id="receive-btn">
              <i class="fas fa-check-double"></i> Đã nhận hàng
            </button>
          <?php endif; ?>
        </span>
      </p>
      <p>Customer: <span><?= htmlspecialchars($order['full_name']) ?></span></p>
      <p>Shipping To: <span><?= htmlspecialchars($order['address']) ?></span></p>
      <?php if (!empty($order['phone'])): ?>
        <p>Phone: <span><?= htmlspecialchars($order['phone']) ?></span></p>
      <?php endif; ?>
    </div>

    <?php foreach ($items as $item): ?>
      <div class="product-item">
        <?php
        $image_src = !empty($item['image']) ? IMG_URL . $item['image'] : IMG_URL . 'placeholder.jpg';
        ?>
        <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="product-thumb">
        <div class="product-details">
          <h3><?= htmlspecialchars($item['product_name']) ?></h3>
          <p>Price: <span>$<?= number_format($item['unit_price'], 2) ?></span></p>
          <p>Quantity: <span><?= $item['quantity'] ?></span></p>
          <p>Total: <span>$<?= number_format($item['total_price'], 2) ?></span></p>
          <p>Description: A beautiful piece from 36 Jewelry collection, crafted with care.</p>
        </div>
      </div>
    <?php endforeach; ?>

    <p class="total">Order Total: <strong>$<?= number_format($order['total_amount'], 2) ?></strong></p>
  </main>

  <!-- CANCEL CONFIRMATION MODAL -->
  <div class="modal-overlay" id="cancel-modal">
    <div class="modal-box">
      <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
      <h3>Cancel Order?</h3>
      <p>Are you sure you want to cancel order <strong id="modal-order-num" style="color:#b8860b;"></strong>?<br>
        This action cannot be undone.</p>
      <div class="modal-actions">
        <button class="btn-modal-back" onclick="closeCancelModal()">
          <i class="fas fa-arrow-left"></i> Go Back
        </button>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="cancel_order_id" id="modal-order-id">
          <button type="submit" class="btn-modal-confirm">
            <i class="fas fa-times-circle"></i> Confirm Cancel
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- RECEIVE CONFIRMATION MODAL -->
  <div class="modal-overlay" id="receive-modal">
    <div class="modal-box">
      <div class="modal-icon" style="color: #4CAF50;"><i class="fas fa-box-open"></i></div>
      <h3>Xác nhận nhận hàng?</h3>
      <p>Bạn có chắc chắn đã nhận được đơn <strong id="modal-receive-num" style="color:#b8860b;"></strong>?<br>
        Hành động này không thể hoàn tác.</p>
      <div class="modal-actions">
        <button class="btn-modal-back" onclick="closeReceiveModal()">
          <i class="fas fa-arrow-left"></i> Quay lại
        </button>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="receive_order_id" id="modal-receive-id">
          <button type="submit" class="btn-modal-confirm" style="background:#4CAF50;">
            <i class="fas fa-check-double"></i> Xác nhận
          </button>
        </form>
      </div>
    </div>
  </div>

  <footer class="footer">
    <p>&copy; 2025 36 Jewelry. All rights reserved.</p>
  </footer>

  <script>
    function doSearch() {
      const keyword = document.getElementById('search-input').value.trim();
      if (keyword !== '') {
        window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(keyword);
      }
    }

    function openCancelModal(orderId, orderNum) {
      document.getElementById('modal-order-id').value = orderId;
      document.getElementById('modal-order-num').textContent = orderNum;
      document.getElementById('cancel-modal').classList.add('active');
    }

    function closeCancelModal() {
      document.getElementById('cancel-modal').classList.remove('active');
    }

    function openReceiveModal(orderId, orderNum) {
      document.getElementById('modal-receive-id').value = orderId;
      document.getElementById('modal-receive-num').textContent = orderNum;
      document.getElementById('receive-modal').classList.add('active');
    }

    function closeReceiveModal() {
      document.getElementById('receive-modal').classList.remove('active');
    }

    // Close modal when clicking the backdrop
    document.getElementById('cancel-modal').addEventListener('click', function (e) {
      if (e.target === this) closeCancelModal();
    });
    document.getElementById('receive-modal').addEventListener('click', function (e) {
      if (e.target === this) closeReceiveModal();
    });
  </script>

</body>

</html>
