<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Define constants BEFORE use
if (!defined('BASE_URL'))
  define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))
  define('IMG_URL', BASE_URL . 'images/');

if (!isset($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . 'User/users/login.php');
  exit();
}
$user_id = (int) $_SESSION['user_id'];

// ── Links ──────────────────────────────────────────────────
$link_home = BASE_URL . 'User/indexprofile.php';
$link_cart = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_logout = BASE_URL . 'User/users/logout.php';
$link_search = BASE_URL . 'User/Search/search.html';
$link_shop = BASE_URL . 'User/Products/products_sp.php';

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
  } else {
    $cancel_error = 'Unable to cancel this order (only Pending orders can be cancelled).';
  }
}

// ── Handle Receive Order (POST) ─────────────────────────────
$receive_msg = '';
$receive_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_order_id'])) {
  $receive_id = (int) $_POST['receive_order_id'];

  // Verify order belongs to this customer and is Processed, Shipping, or Shipped.
  $chk = $conn->prepare("
        SELECT o.id, o.status
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.id = ? AND c.user_id = ? AND o.status IN ('Processed', 'Shipping', 'Shipped')
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
  } else {
    $receive_error = 'Unable to mark this order as received.';
  }
}

// ── Fetch customer info ────────────────────────────────────
$stmt = $conn->prepare("
    SELECT u.email, COALESCE(c.full_name, u.username, '') AS full_name,
           COALESCE(c.phone,'') AS phone, COALESCE(c.address,'') AS address,
           c.id AS customer_id
    FROM users u LEFT JOIN customers c ON u.id = c.user_id
    WHERE u.id = ? LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_info = $stmt->get_result()->fetch_assoc();
if (!$user_info)
  $user_info = ['email' => '', 'full_name' => '', 'phone' => '', 'address' => '', 'customer_id' => null];
$stmt->close();

// ── Fetch orders ───────────────────────────────────────────
$orders = [];
if ($user_info['customer_id']) {
  $order_stmt = $conn->prepare("
        SELECT id, order_number, order_date, total_amount, status
        FROM orders WHERE customer_id = ? ORDER BY order_date DESC
    ");
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Orders | 36 Jewelry</title>
  <meta name="description"
    content="View and manage all your orders at 36 Jewelry. Track status, cancel pending orders, and review your purchase history.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Cormorant Garamond', serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background: linear-gradient(rgba(0, 0, 0, .45), rgba(0, 0, 0, .65)),
        url("<?= IMG_URL ?>chocolat/pfb10.jpg") no-repeat center/cover fixed;
      color: #fff;
    }

    /* ── HEADER ── */
    .header-container {
      position: sticky;
      top: 0;
      z-index: 1000;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
    }

    .search-bar {
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 20px;
      gap: 12px;
    }

    .search-bar .left,
    .search-bar .right {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .search-bar .center {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      gap: 20px;
    }

    .home-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      border-radius: 8px;
      text-decoration: none;
      color: #111;
      font-weight: 600;
      font-size: 15px;
      transition: .2s;
    }

    .home-btn:hover {
      background: #f9f6ee;
      color: #b8860b;
    }

    .header-logo {
      height: 52px;
      object-fit: contain;
    }

    .search-box {
      display: flex;
      align-items: center;
      gap: 6px;
      background: #f5f5f5;
      border-radius: 999px;
      padding: 6px 10px;
      border: 1px solid #e0e0e0;
      min-width: 260px;
      flex: 0 1 420px;
    }

    .search-box input {
      flex: 1;
      border: 0;
      outline: 0;
      background: transparent;
      font-size: 14px;
    }

    .search-box button {
      background: #b8860b;
      color: #fff;
      border: 0;
      border-radius: 999px;
      padding: 7px 13px;
      cursor: pointer;
      font-size: 13px;
      transition: .2s;
    }

    .search-box button:hover {
      background: #8b6408;
    }

    .icon-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 8px;
      text-decoration: none;
      color: #111;
      font-size: 18px;
      transition: .18s;
    }

    .icon-link:hover {
      background: rgba(184, 134, 11, .1);
      color: #b8860b;
    }

    /* ── WRAPPER ── */
    .page-wrapper {
      width: 92%;
      max-width: 960px;
      margin: 36px auto 60px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    /* ── PAGE TITLE ── */
    .page-title {
      text-align: center;
      font-size: 32px;
      font-weight: 700;
      letter-spacing: .5px;
      color: #f8ce86;
      text-shadow: 0 2px 12px rgba(248, 206, 134, .35);
      margin-bottom: 4px;
    }

    .page-sub {
      text-align: center;
      font-size: 15px;
      color: rgba(255, 255, 255, .6);
    }

    /* ── ALERT MESSAGES ── */
    .alert-success,
    .alert-error {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 18px;
      border-radius: 12px;
      font-size: 15px;
      font-weight: 600;
      animation: slideDown .3s ease;
    }

    .alert-success {
      background: rgba(76, 175, 80, .2);
      border: 1px solid #4caf50;
      color: #a5d6a7;
    }

    .alert-error {
      background: rgba(244, 67, 54, .2);
      border: 1px solid #f44336;
      color: #ef9a9a;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-8px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ── ORDER CARD ── */
    .order-card {
      background: rgba(255, 255, 255, .1);
      border: 1px solid rgba(248, 206, 134, .25);
      border-radius: 16px;
      backdrop-filter: blur(10px);
      overflow: hidden;
      transition: transform .2s, box-shadow .2s;
    }

    .order-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 32px rgba(248, 206, 134, .15);
    }

    .order-header {
      background: rgba(248, 206, 134, .12);
      padding: 14px 20px;
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      border-bottom: 1px solid rgba(248, 206, 134, .2);
    }

    .order-header-item {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .order-header-item .lbl {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .8px;
      color: rgba(255, 255, 255, .5);
    }

    .order-header-item .val {
      font-size: 14px;
      font-weight: 700;
      color: #f8ce86;
    }

    .order-header-actions {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* Status badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .6px;
    }

    .status-pending {
      background: rgba(255, 152, 0, .25);
      border: 1px solid #ff9800;
      color: #ffcc80;
    }

    .status-processed {
      background: rgba(33, 150, 243, .25);
      border: 1px solid #2196f3;
      color: #90caf9;
    }

    .status-shipping {
      background: rgba(103, 58, 183, .25);
      border: 1px solid #7e57c2;
      color: #ce93d8;
    }

    .status-delivered {
      background: rgba(76, 175, 80, .25);
      border: 1px solid #4caf50;
      color: #a5d6a7;
    }

    .status-cancelled {
      background: rgba(244, 67, 54, .25);
      border: 1px solid #f44336;
      color: #ef9a9a;
    }

    /* Cancel button */
    .btn-cancel {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 16px;
      border-radius: 8px;
      border: 1.5px solid #f44336;
      background: rgba(244, 67, 54, .18);
      color: #ef9a9a;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: .2s;
      letter-spacing: .3px;
      font-family: inherit;
      white-space: nowrap;
    }

    .btn-cancel:hover {
      background: rgba(244, 67, 54, .42);
      color: #fff;
      border-color: #e53935;
      transform: scale(1.03);
    }

    /* Receive button */
    .btn-receive {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 16px;
      border-radius: 8px;
      border: 1.5px solid #4caf50;
      background: rgba(76, 175, 80, .18);
      color: #81c784;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: .2s;
      letter-spacing: .3px;
      font-family: inherit;
      white-space: nowrap;
    }

    .btn-receive:hover {
      background: rgba(76, 175, 80, .42);
      color: #fff;
      border-color: #388e3c;
      transform: scale(1.03);
    }

    /* Info note for shipping */
    .delivered-note {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 12px;
      color: #a5d6a7;
      font-style: italic;
    }

    /* ── TABLE ── */
    .order-body {
      padding: 16px 20px;
    }

    .order-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }

    .order-table th {
      padding: 10px 12px;
      text-align: left;
      background: rgba(248, 206, 134, .08);
      color: rgba(255, 255, 255, .55);
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .8px;
      border-bottom: 1px solid rgba(248, 206, 134, .15);
    }

    .order-table td {
      padding: 10px 12px;
      border-bottom: 1px solid rgba(255, 255, 255, .06);
      color: rgba(255, 255, 255, .88);
      vertical-align: middle;
    }

    .order-table tr:last-child td {
      border-bottom: none;
    }

    .order-table td.price {
      color: #f8ce86;
      font-weight: 700;
    }

    /* ── TOTAL ROW ── */
    .order-footer {
      padding: 12px 20px 16px;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 6px;
      border-top: 1px solid rgba(248, 206, 134, .15);
      font-size: 16px;
      font-weight: 700;
      color: #f8ce86;
    }

    /* ── EMPTY STATE ── */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: rgba(255, 255, 255, .08);
      border-radius: 16px;
      border: 1px dashed rgba(248, 206, 134, .3);
    }

    .empty-state i {
      font-size: 56px;
      color: rgba(248, 206, 134, .3);
      display: block;
      margin-bottom: 16px;
    }

    .empty-state p {
      color: rgba(255, 255, 255, .5);
      font-size: 16px;
      margin-bottom: 20px;
    }

    .empty-state a {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 28px;
      background: linear-gradient(135deg, #f8ce86, #b8860b);
      color: #3b2f10;
      border-radius: 999px;
      text-decoration: none;
      font-weight: 700;
      transition: .2s;
    }

    .empty-state a:hover {
      transform: scale(1.03);
    }

    /* ── MODAL ── */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .65);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-box {
      background: #1e1a12;
      border: 1px solid rgba(248, 206, 134, .35);
      border-radius: 16px;
      padding: 32px 28px;
      max-width: 420px;
      width: 90%;
      text-align: center;
      animation: popIn .25s ease;
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

    .modal-box i {
      font-size: 48px;
      color: #f44336;
      margin-bottom: 14px;
    }

    .modal-box h3 {
      font-size: 22px;
      color: #f8ce86;
      margin-bottom: 8px;
    }

    .modal-box p {
      font-size: 14px;
      color: rgba(255, 255, 255, .6);
      margin-bottom: 24px;
      line-height: 1.6;
    }

    .modal-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
    }

    .btn-modal-back {
      padding: 10px 22px;
      border-radius: 8px;
      border: 1.5px solid rgba(255, 255, 255, .2);
      background: transparent;
      color: #fff;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: .2s;
      font-family: inherit;
    }

    .btn-modal-back:hover {
      border-color: rgba(255, 255, 255, .5);
      background: rgba(255, 255, 255, .07);
    }

    .btn-modal-confirm {
      padding: 10px 22px;
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

    /* ── FOOTER ── */
    footer {
      text-align: center;
      padding: 20px;
      color: rgba(255, 255, 255, .4);
      font-size: 13px;
      margin-top: auto;
    }

    @media(max-width:640px) {
      .order-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .order-header-actions {
        margin-left: 0;
      }

      .order-table th:nth-child(2),
      .order-table td:nth-child(2) {
        display: none;
      }
    }
  </style>
<link rel="stylesheet" href="/do_an_web/Jewellery/User/page-transition.css">
  <script src="/do_an_web/Jewellery/User/page-transition.js"></script>
</head>

<body>

  <!-- HEADER -->
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

  <div class="page-wrapper">
    <h1 class="page-title"><i class="fas fa-box-open" style="margin-right:10px;"></i>Your Orders</h1>
    <p class="page-sub">View and manage all your orders</p>

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
      <div class="empty-state">
        <i class="fas fa-shopping-bag"></i>
        <p>You have no orders yet.</p>
        <a href="<?= $link_shop ?>"><i class="fas fa-gem"></i> Explore Products</a>
      </div>
    <?php else: ?>
      <?php foreach ($orders as $order):
        $status = $order['status'];

        // Map status → badge class + label + icon
        switch ($status) {
          case 'Pending':
            $badge_class = 'status-pending';
            $badge_icon = 'fa-clock';
            $badge_label = 'Pending';
            break;
          case 'Processed':
          case 'Processing':
            $badge_class = 'status-processed';
            $badge_icon = 'fa-cogs';
            $badge_label = 'Processing';
            break;
          case 'Shipping':
          case 'Shipped':
            $badge_class = 'status-shipping';
            $badge_icon = 'fa-truck';
            $badge_label = 'Shipping';
            break;
          case 'Delivered':
            $badge_class = 'status-delivered';
            $badge_icon = 'fa-check-circle';
            $badge_label = 'Delivered';
            break;
          case 'Cancelled':
            $badge_class = 'status-cancelled';
            $badge_icon = 'fa-times-circle';
            $badge_label = 'Cancelled';
            break;
          default:
            $badge_class = 'status-pending';
            $badge_icon = 'fa-question-circle';
            $badge_label = htmlspecialchars($status);
        }

        // Only allow cancellation when status is Pending
        $can_cancel = ($status === 'Pending');
        $can_receive = in_array($status, ['Processed', 'Shipping', 'Shipped']);
        ?>
        <div class="order-card">
          <div class="order-header">
            <div class="order-header-item">
              <span class="lbl">Order Number</span>
              <span class="val"><?= htmlspecialchars($order['order_number']) ?></span>
            </div>
            <div class="order-header-item">
              <span class="lbl">Order Date</span>
              <span class="val"><?= date('M d, Y', strtotime($order['order_date'])) ?></span>
            </div>
            <?php if (!empty($order['payment_method'])): ?>
              <div class="order-header-item">
                <span class="lbl">Payment</span>
                <span class="val"><?= htmlspecialchars($order['payment_method']) ?></span>
              </div>
            <?php endif; ?>

            <div class="order-header-actions">
              <span class="status-badge <?= $badge_class ?>">
                <i class="fas <?= $badge_icon ?>"></i><?= $badge_label ?>
              </span>

              <?php if ($can_cancel): ?>
                <button id="cancel-btn-<?= $order['id'] ?>" class="btn-cancel"
                  onclick="openCancelModal(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number'], ENT_QUOTES) ?>')">
                  <i class="fas fa-times"></i> Cancel Order
                </button>
              <?php elseif ($can_receive): ?>
                <button class="btn-receive"
                  onclick="openReceiveModal(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number'], ENT_QUOTES) ?>')">
                  <i class="fas fa-check"></i> Đã nhận hàng
                </button>
              <?php endif; ?>
            </div>
          </div>

          <div class="order-body">
            <table class="order-table">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Unit Price</th>
                  <th>Qty</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($order['items'] as $item): ?>
                  <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="price">$<?= number_format($item['unit_price'], 2) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td class="price">$<?= number_format($item['total_price'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="order-footer">
            <i class="fas fa-receipt" style="color:rgba(248,206,134,.5);"></i>
            Order Total: $<?= number_format($order['total_amount'], 2) ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- CANCEL CONFIRMATION MODAL -->
  <div class="modal-overlay" id="cancel-modal">
    <div class="modal-box">
      <i class="fas fa-exclamation-triangle"></i>
      <h3>Cancel Order?</h3>
      <p>Are you sure you want to cancel order <strong id="modal-order-num" style="color:#f8ce86;"></strong>?<br>
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
      <i class="fas fa-box-open" style="color: #4caf50;"></i>
      <h3>Confirm Received?</h3>
      <p>Are you sure you have received order <strong id="modal-receive-num" style="color:#f8ce86;"></strong>?</p>
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

  <footer>
    <p>&copy; 2025 36 Jewelry. All rights reserved.</p>
  </footer>

  <script>
    function doSearch() {
      const kw = document.getElementById('search-input').value.trim();
      if (kw) window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(kw);
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