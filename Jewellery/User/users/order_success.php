<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/order_success.php
// Chức năng:
//   ✔ Kiểm tra đăng nhập
//   ✔ Hiển thị thông báo thành công + order number
//   ✔ Links về Home / View Orders
// ═══════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../../config/config.php';

// Định nghĩa hằng TRƯỚC khi dùng
if (!defined('BASE_URL')) define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))  define('IMG_URL',  BASE_URL . 'images/');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'User/users/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$link_home    = BASE_URL . 'User/indexprofile.php';
$link_history = BASE_URL . 'User/users/history.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_cart    = BASE_URL . 'User/users/cart.php';
$link_logout  = BASE_URL . 'User/users/logout.php';
$link_search  = BASE_URL . 'User/users/search.php';

// Lấy thông tin order vừa đặt
$order_id     = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order_number = $_SESSION['last_order_number'] ?? '';

// Nếu không có order_id hợp lệ trong session → redirect history
if (!$order_id && !$order_number) {
    header('Location: ' . $link_history);
    exit();
}

// Lấy order từ DB để hiển thị
$order = null;
if ($order_id) {
    $stmt = $conn->prepare("
        SELECT o.order_number, o.order_date, o.total_amount, o.status
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.id = ? AND c.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Xóa session sau khi lấy xong
unset($_SESSION['last_order_id'], $_SESSION['last_order_number']);

$order_num = $order['order_number'] ?? $order_number ?: 'N/A';
$order_date = $order ? date('d/m/Y', strtotime($order['order_date'])) : date('d/m/Y');
$order_total = $order ? '$' . number_format((float)$order['total_amount'], 2) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Placed | 36 Jewelry</title>
  <meta name="description" content="Your 36 Jewelry order has been placed successfully.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--gold:#b8860b;--gold-lt:#d4af37;--gold-pale:#f8ce86;--dark:#111;--bg:#fff;--accent:#f6f6f6;}
*{margin:0;padding:0;box-sizing:border-box;}
body{
  font-family:'DM Sans',sans-serif;min-height:100vh;
  background:linear-gradient(rgba(8,5,2,.55),rgba(5,3,1,.68)),
    url("<?= BASE_URL ?>images/profile background.jpg") no-repeat center center/cover fixed;
  display:flex;flex-direction:column;overflow-x:hidden;
}

/* HEADER */
.header-container{width:100%;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.08);}
.search-bar{max-width:1400px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;padding:12px 20px;gap:16px;}
.search-bar .left,.search-bar .center,.search-bar .right{display:flex;align-items:center;}
.search-bar .center{flex:1;justify-content:center;position:relative;}
.home-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:8px;text-decoration:none;color:var(--dark);font-weight:600;font-size:15px;transition:.2s;}
.home-btn:hover{background:var(--accent);color:var(--gold);}
.search-bar .center a{position:absolute;left:50%;transform:translateX(-50%);z-index:10;}
.header-logo{height:52px;max-width:170px;object-fit:contain;}
.search-box{flex:0 1 420px;margin-left:auto;display:flex;align-items:center;gap:8px;background:var(--accent);padding:4px 8px;border-radius:999px;border:1px solid rgba(0,0,0,.07);height:46px;}
.search-box input{flex:1;border:0;outline:0;background:transparent;padding:4px 8px;font-size:14px;}
.search-box button{display:inline-flex;align-items:center;justify-content:center;border:0;background:var(--gold);color:#fff;padding:8px 14px;border-radius:999px;cursor:pointer;font-size:13px;}
.icon-link{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:8px;text-decoration:none;color:var(--dark);font-size:18px;}
.icon-link:hover{background:rgba(184,134,11,.1);color:var(--gold);}
.search-bar .right{gap:4px;}

/* SUCCESS BOX */
.success-area{flex:1;display:flex;justify-content:center;align-items:center;padding:48px 20px;}
.success-box{
  background:rgba(255,255,255,.10);
  backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
  border:1px solid rgba(255,255,255,.22);
  border-radius:24px;width:100%;max-width:480px;
  padding:48px 40px;color:#fff;text-align:center;
  box-shadow:0 12px 48px rgba(0,0,0,.35);
  animation:popIn .6s cubic-bezier(.175,.885,.32,1.275) both;
}
@keyframes popIn{from{transform:scale(.8);opacity:0}to{transform:scale(1);opacity:1}}

/* Checkmark animation */
.check-circle{
  width:80px;height:80px;border-radius:50%;
  background:linear-gradient(135deg,#43a047,#2e7d32);
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 24px;
  box-shadow:0 0 0 0 rgba(76,175,80,.4);
  animation:pulse 1.5s ease-out 0.5s both;
}
.check-circle i{font-size:36px;color:#fff;}
@keyframes pulse{
  0%{box-shadow:0 0 0 0 rgba(76,175,80,.6);}
  70%{box-shadow:0 0 0 20px rgba(76,175,80,0);}
  100%{box-shadow:0 0 0 0 rgba(76,175,80,0);}
}

h1{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:#fff9e6;margin-bottom:10px;}
.subtitle{color:rgba(255,255,255,.65);font-size:15px;margin-bottom:28px;line-height:1.6;}

/* Order details table */
.order-details{background:rgba(255,255,255,.07);border-radius:12px;padding:18px 20px;margin-bottom:28px;text-align:left;}
.order-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.1);font-size:14px;}
.order-row:last-child{border-bottom:none;}
.order-row .label{color:rgba(255,255,255,.55);}
.order-row .value{font-weight:600;color:#fff9e6;}
.order-num-val{color:var(--gold-pale);font-family:monospace;font-size:15px;}

/* Buttons */
.btn-group{display:flex;gap:12px;flex-wrap:wrap;}
.btn-action{
  flex:1;min-width:140px;padding:13px 20px;border-radius:12px;
  font-weight:700;font-size:14px;text-decoration:none;
  display:flex;align-items:center;justify-content:center;gap:8px;
  transition:.25s;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;
}
.btn-home{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);}
.btn-home:hover{background:rgba(255,255,255,.2);}
.btn-orders{background:linear-gradient(135deg,#d4af37,#b8860b);color:#fff;box-shadow:0 4px 20px rgba(184,134,11,.35);}
.btn-orders:hover{background:linear-gradient(135deg,#e0c050,#c9972a);transform:translateY(-1px);}

.confetti-note{margin-top:20px;font-size:13px;color:rgba(255,255,255,.4);}
</style>
</head>
<body>

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
      <a href="<?= $link_profile ?>" class="icon-link" title="Profile"><i class="fas fa-user" style="color:var(--gold)"></i></a>
      <a href="<?= $link_logout ?>" class="icon-link" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </div>
</header>

<div class="success-area">
  <div class="success-box">
    <div class="check-circle"><i class="fas fa-check"></i></div>

    <h1>Order Placed!</h1>
    <p class="subtitle">
      Thank you for shopping with <strong>36 Jewelry</strong>.<br>
      Your order is confirmed and is being processed.
    </p>

    <div class="order-details">
      <div class="order-row">
        <span class="label"><i class="fas fa-hashtag" style="margin-right:5px;color:var(--gold-pale)"></i>Order Number</span>
        <span class="value order-num-val"><?= htmlspecialchars($order_num) ?></span>
      </div>
      <div class="order-row">
        <span class="label"><i class="fas fa-calendar" style="margin-right:5px;color:var(--gold-pale)"></i>Date</span>
        <span class="value"><?= $order_date ?></span>
      </div>
      <?php if ($order_total): ?>
      <div class="order-row">
        <span class="label"><i class="fas fa-receipt" style="margin-right:5px;color:var(--gold-pale)"></i>Total</span>
        <span class="value" style="color:var(--gold-pale)"><?= $order_total ?></span>
      </div>
      <?php endif; ?>
      <div class="order-row">
        <span class="label"><i class="fas fa-info-circle" style="margin-right:5px;color:var(--gold-pale)"></i>Status</span>
        <span class="value" style="color:#ffb74d">⏳ Pending</span>
      </div>
    </div>

    <div class="btn-group">
      <a href="<?= $link_home ?>" class="btn-action btn-home"><i class="fas fa-home"></i> Back to Home</a>
      <a href="<?= $link_history ?>" class="btn-action btn-orders"><i class="fas fa-history"></i> View Orders</a>
    </div>

    <p class="confetti-note"><i class="fas fa-envelope"></i> We'll notify you when your order is shipped.</p>
  </div>
</div>

<script>
function doSearch(){
  const kw=document.getElementById('search-input').value.trim();
  if(kw) window.location.href='<?= $link_search ?>?q='+encodeURIComponent(kw);
}
</script>

</body>
</html>