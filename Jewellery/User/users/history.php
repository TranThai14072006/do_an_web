<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Define constants BEFORE use
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

// Get customer_id from customers table
$stmt = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
  // If no customer information found
  $orders = [];
} else {
  $customer_id = $customer['id'];
  // Get orders list for the customer
  $stmt = $conn->prepare("SELECT id, order_number, order_date, total_amount, status FROM orders WHERE customer_id = ? ORDER BY order_date DESC");
  $stmt->bind_param("i", $customer_id);
  $stmt->execute();
  $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// ── Handle Cancel Order (POST) ─────────────────────────────
$cancel_msg = '';
$cancel_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
  $cancel_id = (int) $_POST['cancel_order_id'];

  // Verify order belongs to this customer and is Pending
  $chk = $conn->prepare("
        SELECT o.id, o.status
        FROM orders o
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
    $cancel_msg = 'Your order has been cancelled successfully.';
    
    // Refresh orders list
    $stmt = $conn->prepare("SELECT id, order_number, order_date, total_amount, status FROM orders WHERE customer_id = ? ORDER BY order_date DESC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
  } else {
    $cancel_error = 'Unable to cancel this order (only Pending orders can be cancelled).';
  }
}

// ── Handle Receive Order (POST) ─────────────────────────────
$receive_msg = '';
$receive_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_order_id'])) {
  $receive_id = (int) $_POST['receive_order_id'];

  // Verify order belongs to this customer and is Shipping or Shipped.
  $chk = $conn->prepare("
        SELECT o.id, o.status
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.id = ? AND c.user_id = ? AND o.status IN ('Shipping', 'Shipped')
        LIMIT 1
    ");
  $chk->bind_param('ii', $receive_id, $user_id);
  $chk->execute();
  $can_receive = $chk->get_result()->fetch_assoc();
  $chk->close();

  if ($can_receive) {
    $upd_order = $conn->prepare("UPDATE orders SET status = 'Delivered' WHERE id = ?");
    $upd_order->bind_param('i', $receive_id);
    $upd_order->execute();
    $upd_order->close();
    $receive_msg = 'Thank you! Your order has been marked as received.';
    
    // Refresh orders list
    $stmt = $conn->prepare("SELECT id, order_number, order_date, total_amount, status FROM orders WHERE customer_id = ? ORDER BY order_date DESC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
  } else {
    $receive_error = 'Unable to mark this order as received.';
  }
}

// Link helpers
$link_home = BASE_URL . 'User/indexprofile.php';
$link_login = BASE_URL . 'User/users/login.php';
$link_cart = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_logout = BASE_URL . 'User/users/logout.php';
$link_search = BASE_URL . 'User/Products/products_sp.php';
$link_history = BASE_URL . 'User/users/history.php';
$link_view = BASE_URL . 'User/users/view_details.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Orders | 36 Jewelry</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Cormorant Garamond", serif;
    }

    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: linear-gradient(rgba(255, 255, 255, 0.1), rgba(0, 0, 0, 0.6)),
        url("<?= IMG_URL ?>chocolat/pfb10.jpg") no-repeat center center/cover;
      overflow-x: hidden;
    }

    /* ===== HEADER STYLING ===== */
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
      position: sticky;
      top: 0;
      z-index: 999;
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
      background: #f6f6f6;
      color: #b8860b;
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
      background: #f6f6f6;
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
      color: #111111;
      min-width: 100px;
    }

    .search-box input::placeholder {
      color: #666666;
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
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
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
      color: #111111;
      transition: all 0.18s;
      font-size: 18px;
      position: relative;
    }

    .icon-link:hover {
      background: rgba(186, 134, 11, 0.08);
      color: #b8860b;
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
      z-index: 10;
    }

    .user-icon {
      font-size: 20px;
    }

    /* ===== BOX ===== */
    .main-content {
      position: relative;
      width: 90%;
      max-width: 800px;
      background: rgba(255, 255, 255, 0.15);
      padding: 50px 40px 40px 40px;
      border-radius: 20px;
      backdrop-filter: blur(8px);
      box-shadow: 0 0 35px rgba(255, 215, 0, 0.25);
      margin: 40px auto;
    }

    /* ===== BACK BUTTON ===== */
    .back-button {
      position: absolute;
      top: 20px;
      left: 20px;
      background: rgba(0, 0, 0, 0.35);
      border: 1px solid #f8ce86;
      color: #f8ce86;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      font-size: 20px;
      cursor: pointer;
      transition: all 0.3s ease;
      backdrop-filter: blur(6px);
    }

    .back-button:hover {
      background: #f8ce86;
      color: #000;
      box-shadow: 0 0 10px rgba(248, 206, 134, 0.6);
      transform: scale(1.05);
    }

    /* ===== TITLE ===== */
    .orders-section h2 {
      text-align: center;
      color: #ede9e3;
      font-size: 26px;
      margin-bottom: 25px;
      text-shadow: 0 0 12px rgba(255, 215, 0, 0.4);
    }

    /* ===== TABLE ===== */
    .orders-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 16px;
    }

    .orders-table th,
    .orders-table td {
      padding: 12px;
      border-bottom: 1px solid rgba(255, 215, 0, 0.3);
      text-align: center;
      color: #fff;
    }

    .orders-table th {
      background: rgba(218, 165, 32, 0.15);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-weight: 700;
      font-size: 12px;
      padding: 5px 12px;
      border-radius: 999px;
      letter-spacing: .4px;
      text-transform: uppercase;
    }

    .status.pending {
      background: rgba(255, 152, 0, .25);
      border: 1px solid #ff9800;
      color: #ffcc80;
    }

    .status.processed {
      background: rgba(33, 150, 243, .25);
      border: 1px solid #2196f3;
      color: #90caf9;
    }

    .status.shipping {
      background: rgba(103, 58, 183, .25);
      border: 1px solid #7e57c2;
      color: #ce93d8;
    }

    .status.delivered {
      background: rgba(76, 175, 80, .25);
      border: 1px solid #4caf50;
      color: #a5d6a7;
    }

    .status.cancelled {
      background: rgba(244, 67, 54, .25);
      border: 1px solid #f44336;
      color: #ef9a9a;
    }

    /* ===== VIEW BUTTON ===== */
    .view-details-btn {
      padding: 6px 12px;
      background: linear-gradient(120deg, #f8ce86, #d4af37, #b8860b);
      color: #3b2f10;
      border: none;
      border-radius: 50px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
    }

    .view-details-btn:hover {
      transform: scale(1.05);
      box-shadow: 0 0 25px rgba(255, 215, 0, 0.5);
    }

    /* ===== ACTION BUTTONS ===== */
    .action-group {
      display: flex;
      gap: 6px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn-cancel, .btn-receive {
      padding: 6px 12px;
      border-radius: 50px;
      font-weight: bold;
      cursor: pointer;
      border: none;
      transition: all 0.3s ease;
      font-size: 14px;
    }
    .btn-cancel {
      background: rgba(244, 67, 54, 0.15);
      color: #f44336;
      border: 1px solid rgba(244, 67, 54, 0.4);
    }
    .btn-cancel:hover {
      background: #f44336;
      color: #fff;
    }
    .btn-receive {
      background: rgba(76, 175, 80, 0.15);
      color: #72bf76;
      border: 1px solid rgba(76, 175, 80, 0.4);
    }
    .btn-receive:hover {
      background: #4caf50;
      color: #fff;
    }

    /* ===== ALERTS ===== */
    .alert-success, .alert-error {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 15px;
      margin-bottom: 20px;
      border-radius: 8px;
      font-weight: bold;
    }
    .alert-success {
      background: rgba(76, 175, 80, 0.2);
      border: 1px solid #4caf50;
      color: #a5d6a7;
    }
    .alert-error {
      background: rgba(244, 67, 54, 0.2);
      border: 1px solid #f44336;
      color: #ef9a9a;
    }

    /* ===== MODAL ===== */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(4px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
    }
    .modal-overlay.active {
      display: flex;
    }
    .modal-box {
      background: #1a1a1a;
      border: 1px solid rgba(255, 215, 0, 0.3);
      padding: 25px 30px;
      border-radius: 12px;
      text-align: center;
      max-width: 380px;
      width: 90%;
      color: #fff;
    }
    .modal-box h3 {
      color: #f8ce86;
      margin-bottom: 10px;
    }
    .modal-box p {
      color: #ccc;
      margin-bottom: 20px;
    }
    .modal-actions {
      display: flex;
      justify-content: center;
      gap: 10px;
    }
    .btn-modal-back {
      padding: 8px 16px;
      background: transparent;
      border: 1px solid #888;
      color: #ccc;
      border-radius: 6px;
      cursor: pointer;
    }
    .btn-modal-back:hover { background: rgba(255,255,255,0.1); }
    .btn-modal-confirm {
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      color: #fff;
      font-weight: bold;
      cursor: pointer;
    }

    /* ===== FOOTER ===== */
    .footer {
      text-align: center;
      margin-top: 30px;
      color: #f0e6b2;
      font-size: 14px;
      padding: 20px;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      .search-bar {
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 10px;
      }

      .search-bar .left,
      .search-bar .right,
      .search-bar .center {
        flex: unset;
      }

      .search-box {
        flex: 1;
        margin-left: 0;
        max-width: 100%;
      }

      .main-content {
        width: 95%;
        padding: 40px 20px 30px 20px;
        margin: 20px auto;
      }

      .orders-table {
        font-size: 14px;
      }

      .orders-table th,
      .orders-table td {
        padding: 8px 6px;
      }
    }
  </style>
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
          <img src="<?= IMG_URL ?>36-logo.png" alt="Jewelry Store Logo" class="header-logo">
        </a>
        <div class="search-box">
          <!-- Chuyển ID thành header-search để tương thích bộ lọc dưới -->
          <input type="text" id="header-search" placeholder="Search products..."
                 onkeydown="if(event.key==='Enter') applyHeaderSearch()">
          <button onclick="applyHeaderSearch()">
            <i class="fas fa-search"></i>
          </button>
        </div>
      </div>

      <div class="right">
        <a href="<?= $link_cart ?>" class="icon-link" title="Cart">
          <i class="fas fa-shopping-cart"></i>
          <?php
          // Lấy số lượng giỏ hàng thực tế cho badge
          $uid = (int)$_SESSION['user_id'];
          $st_b = $conn->query("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = $uid");
          $total_cart_count = 0;
          if ($st_b && $row_b = $st_b->fetch_assoc()) {
            $total_cart_count = (int)$row_b['total_qty'];
          }
          if ($total_cart_count > 0):
          ?>
            <span class="cart-badge"><?= $total_cart_count > 9 ? '9+' : $total_cart_count ?></span>
          <?php endif; ?>
        </a>
        <a href="<?= $link_profile ?>" class="icon-link" title="Profile">
          <i class="fas fa-user-circle user-icon"></i>
        </a>
        <a href="<?= htmlspecialchars($link_logout) ?>" class="icon-link" title="Logout" style="color:#111;">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </div>
    </div>
  </header>

  <main class="main-content">

    <!-- BACK BUTTON -->
    <a href="<?= $link_profile ?>" class="back-button" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;"><i class="fas fa-arrow-left"></i></a>

    <section class="orders-section">
      <h2>Orders History</h2>

      <?php if ($cancel_msg): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($cancel_msg) ?></div>
      <?php endif; ?>
      <?php if ($cancel_error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-triangle"></i><?= htmlspecialchars($cancel_error) ?></div>
      <?php endif; ?>
      <?php if ($receive_msg): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i><?= htmlspecialchars($receive_msg) ?></div>
      <?php endif; ?>
      <?php if ($receive_error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-triangle"></i><?= htmlspecialchars($receive_error) ?></div>
      <?php endif; ?>

      <?php if (empty($orders)): ?>
        <p style="color:white; text-align:center;">You have no orders yet.</p>
      <?php else: ?>
        <table class="orders-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Date</th>
              <th>Total</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order):
              $order_date = date('M d, Y', strtotime($order['order_date']));
              switch ($order['status']) {
                case 'Pending':
                  $sc = 'pending';
                  $si = 'fa-clock';
                  $sl = 'Pending';
                  break;
                case 'Processed':
                case 'Processing':
                  $sc = 'processed';
                  $si = 'fa-cogs';
                  $sl = 'Processing';
                  break;
                case 'Shipping':
                case 'Shipped':
                  $sc = 'shipping';
                  $si = 'fa-truck';
                  $sl = 'Shipping';
                  break;
                case 'Delivered':
                  $sc = 'delivered';
                  $si = 'fa-check-circle';
                  $sl = 'Delivered';
                  break;
                case 'Cancelled':
                  $sc = 'cancelled';
                  $si = 'fa-times-circle';
                  $sl = 'Cancelled';
                  break;
                default:
                  $sc = 'pending';
                  $si = 'fa-question-circle';
                  $sl = htmlspecialchars($order['status']);
              }
              ?>
              <tr>
                <td><?= htmlspecialchars($order['order_number']) ?></td>
                <td><?= $order_date ?></td>
                <td>$<?= number_format($order['total_amount'], 2) ?></td>
                <td><span class="status <?= $sc ?>"><i class="fas <?= $si ?>"></i><?= $sl ?></span></td>
                <td>
                  <a href="<?= $link_view ?>?order_id=<?= $order['id'] ?>" class="view-details-btn" style="text-decoration:none; display:inline-block;">Details</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

  </main>

  <footer class="footer">
    <p>&copy; 2025 36 Jewelry. All rights reserved.</p>
  </footer>

  <!-- CANCEL CONFIRMATION MODAL -->
  <div class="modal-overlay" id="cancel-modal">
    <div class="modal-box">
      <div class="modal-icon"><i class="fas fa-exclamation-triangle" style="font-size:48px;color:#f44336;margin-bottom:14px;"></i></div>
      <h3>Cancel Order?</h3>
      <p>Are you sure you want to cancel order <strong id="modal-order-num" style="color:#b8860b;"></strong>?<br>
        This action cannot be undone.</p>
      <div class="modal-actions">
        <button class="btn-modal-back" onclick="closeCancelModal()">
          <i class="fas fa-arrow-left"></i> Go Back
        </button>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="cancel_order_id" id="modal-order-id">
          <button type="submit" class="btn-modal-confirm" style="background:#f44336;">
            <i class="fas fa-times-circle"></i> Confirm Cancel
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- RECEIVE CONFIRMATION MODAL -->
  <div class="modal-overlay" id="receive-modal">
    <div class="modal-box">
      <div class="modal-icon"><i class="fas fa-box-open" style="font-size:48px;color:#4caf50;margin-bottom:14px;"></i></div>
      <h3>Confirm Received?</h3>
      <p>Are you sure you have received order <strong id="modal-receive-num" style="color:#b8860b;"></strong>?</p>
      <div class="modal-actions">
        <button class="btn-modal-back" onclick="closeReceiveModal()">
          <i class="fas fa-arrow-left"></i> Go Back
        </button>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="receive_order_id" id="modal-receive-id">
          <button type="submit" class="btn-modal-confirm" style="background:#4caf50;">
            <i class="fas fa-check-circle"></i> Confirm
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    function applyHeaderSearch() {
      const keyword = document.getElementById('header-search').value.trim();
      if (keyword !== '') {
        window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(keyword);
      }
    }

    // Modals
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

    document.getElementById('cancel-modal').addEventListener('click', function (e) {
      if (e.target === this) closeCancelModal();
    });

    document.getElementById('receive-modal').addEventListener('click', function (e) {
      if (e.target === this) closeReceiveModal();
    });
  </script>

</body>

</html>
