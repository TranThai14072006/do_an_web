<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/order_success.php
// Functions:
//   ✔ Check login
//   ✔ Fetch order from DB by order_id (GET) + verify owner
//   ✔ Fetch order_items list for display
//   ✔ Display success message with beautiful animation
//   ✔ Clear last_order session after display
//   ✔ Links to Home / View Orders
// ═══════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../../config/config.php';

if (!defined('BASE_URL')) define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))  define('IMG_URL',  BASE_URL . 'images/');

// ── Check login ────────────────────────────────────
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
$link_search  = BASE_URL . 'User/Search/search.html';
$logged_in_name = htmlspecialchars($_SESSION['username'] ?? 'User');

// ── Get order_id from GET ───────────────────────────────────
$order_id     = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order_number = $_SESSION['last_order_number'] ?? '';

// If nothing valid → redirect
if (!$order_id && !$order_number) {
    header('Location: ' . $link_history);
    exit();
}

// ── Fetch order info from DB ─────────────────────────────
$order = null;
if ($order_id) {
    $stmt = $conn->prepare("
        SELECT o.id, o.order_number, o.order_date, o.total_amount, o.status,
               c.full_name, c.phone, c.address
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

// ── Fetch order items ───────────────────────────────────────
$order_items = [];
if ($order) {
    $ist = $conn->prepare("
        SELECT oi.product_id, oi.product_name, oi.quantity, oi.unit_price, oi.total_price,
               p.image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $ist->bind_param('i', $order_id);
    $ist->execute();
    $r = $ist->get_result();
    while ($row = $r->fetch_assoc()) {
        $order_items[] = $row;
    }
    $ist->close();
}

// ── Clear session after fetching ─────────────────────────
unset($_SESSION['last_order_id'], $_SESSION['last_order_number']);

// ── Prep display variables ───────────────────────────────
$order_num   = $order['order_number'] ?? $order_number ?: 'N/A';
$order_date  = $order ? date('d/m/Y', strtotime($order['order_date'])) : date('d/m/Y');
$order_total = $order ? number_format((float)$order['total_amount'], 2) : '0.00';
$customer_name = $order['full_name']  ?? $logged_in_name;
$customer_addr = $order['address']    ?? '';
$order_status  = $order['status']     ?? 'Pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmed | 36 Jewelry</title>
  <meta name="description" content="Your 36 Jewelry order has been placed successfully.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ══ ROOT ══ */
:root{
  --gold:#b8860b;--gold-lt:#d4af37;--gold-pale:#f8ce86;
  --dark:#111;--bg:#fff;--accent:#f6f6f6;--muted:#666;
  --radius:14px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{
  font-family:'DM Sans',sans-serif;min-height:100vh;
  background:linear-gradient(rgba(8,5,2,.58),rgba(4,2,0,.72)),
    url("<?= BASE_URL ?>images/profile background.jpg") no-repeat center center/cover fixed;
  display:flex;flex-direction:column;overflow-x:hidden;
  color:var(--dark);
}

/* ══ HEADER ══ */
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
.icon-link{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:8px;text-decoration:none;color:var(--dark);font-size:18px;transition:.18s;}
.icon-link:hover{background:rgba(184,134,11,.1);color:var(--gold);}
.search-bar .right{gap:4px;}
.user-name{font-size:13px;font-weight:600;color:var(--dark);text-decoration:none;display:flex;align-items:center;gap:6px;}
.user-name:hover{color:var(--gold);}

/* ══ MAIN AREA ══ */
.success-area{flex:1;display:flex;justify-content:center;align-items:flex-start;padding:40px 20px 60px;}
.success-wrapper{width:100%;max-width:580px;}

/* ══ SUCCESS CARD ══ */
.success-card{
  background:rgba(255,255,255,.10);
  backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);
  border:1px solid rgba(255,255,255,.22);
  border-radius:24px;width:100%;
  padding:44px 40px 36px;color:#fff;text-align:center;
  box-shadow:0 16px 60px rgba(0,0,0,.4);
  animation:popIn .6s cubic-bezier(.175,.885,.32,1.275) both;
  margin-bottom:20px;
}
@keyframes popIn{from{transform:scale(.82);opacity:0}to{transform:scale(1);opacity:1}}

/* Checkmark */
.check-circle{
  width:84px;height:84px;border-radius:50%;
  background:linear-gradient(135deg,#43a047,#2e7d32);
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 24px;
  animation:pulse 1.6s ease-out .5s both;
  box-shadow:0 0 0 0 rgba(76,175,80,.5);
}
.check-circle i{font-size:38px;color:#fff;}
@keyframes pulse{
  0%{box-shadow:0 0 0 0 rgba(76,175,80,.6);}
  60%{box-shadow:0 0 0 22px rgba(76,175,80,0);}
  100%{box-shadow:0 0 0 0 rgba(76,175,80,0);}
}

h1{font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:700;color:#fff9e6;margin-bottom:8px;}
.subtitle{color:rgba(255,255,255,.65);font-size:15px;margin-bottom:28px;line-height:1.6;}

/* Order info box */
.order-info{background:rgba(255,255,255,.08);border-radius:14px;padding:18px 22px;margin-bottom:24px;text-align:left;}
.order-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.1);font-size:14px;}
.order-row:last-child{border-bottom:none;}
.order-row .lbl{color:rgba(255,255,255,.55);display:flex;align-items:center;gap:7px;}
.order-row .lbl i{width:16px;text-align:center;color:var(--gold-pale);}
.order-row .val{font-weight:600;color:#fff9e6;text-align:right;max-width:60%;word-break:break-word;}
.order-num-val{color:var(--gold-pale);font-family:monospace;font-size:15px;letter-spacing:.5px;}
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;}
.status-pending{background:rgba(255,183,77,.2);color:#ffb74d;border:1px solid rgba(255,183,77,.3);}

/* Action buttons */
.btn-group{display:flex;gap:12px;flex-wrap:wrap;}
.btn-action{
  flex:1;min-width:130px;padding:13px 16px;border-radius:12px;
  font-weight:700;font-size:14px;text-decoration:none;
  display:flex;align-items:center;justify-content:center;gap:8px;
  transition:.25s;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;
}
.btn-home{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.22);}
.btn-home:hover{background:rgba(255,255,255,.22);}
.btn-orders{background:linear-gradient(135deg,#d4af37,#b8860b);color:#fff;box-shadow:0 4px 20px rgba(184,134,11,.4);}
.btn-orders:hover{background:linear-gradient(135deg,#e0c050,#c9972a);transform:translateY(-1px);}

.confetti-note{margin-top:18px;font-size:13px;color:rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;gap:6px;}

/* ══ ORDER ITEMS CARD ══ */
.items-card{
  background:rgba(255,255,255,.09);
  backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
  border:1px solid rgba(255,255,255,.18);
  border-radius:20px;padding:24px 28px;
  animation:popIn .7s cubic-bezier(.175,.885,.32,1.275) .15s both;
}
.items-card-title{
  font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:700;
  color:#fff9e6;margin-bottom:16px;display:flex;align-items:center;gap:8px;
}
.items-card-title i{color:var(--gold-pale);}

.item-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.1);}
.item-row:last-child{border-bottom:none;}
.item-thumb{width:52px;height:52px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,.2);flex-shrink:0;}
.item-info{flex:1;min-width:0;}
.item-name{font-size:14px;font-weight:600;color:#fff9e6;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.item-meta{font-size:12px;color:rgba(255,255,255,.5);margin-top:2px;}
.item-price{font-size:14px;font-weight:700;color:var(--gold-pale);white-space:nowrap;}
.items-total{display:flex;justify-content:space-between;font-size:16px;font-weight:700;padding-top:14px;margin-top:4px;color:#fff9e6;border-top:2px solid rgba(255,255,255,.15);}
.items-total .amt{color:var(--gold-pale);}

/* ══ RESPONSIVE ══ */
@media(max-width:600px){
  .success-card{padding:32px 20px 28px;}
  .items-card{padding:20px;}
  .search-bar{flex-wrap:wrap;padding:10px;}
  .search-bar .center{order:2;width:100%;}
  .search-bar .left{order:1;}
  .search-bar .right{order:3;}
  .search-bar .center a{position:static;transform:none;}
  .search-box{margin-left:0;flex:1;}
  h1{font-size:24px;}
}
</style>
</head>
<body>

<!-- ══ HEADER ══ -->
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

<!-- ══ MAIN ══ -->
<div class="success-area">
  <div class="success-wrapper">

    <!-- ── Success Card ── -->
    <div class="success-card">
      <div class="check-circle"><i class="fas fa-check"></i></div>

      <h1>Order Confirmed! 🎉</h1>
      <p class="subtitle">
        Thank you, <strong><?= htmlspecialchars($customer_name) ?></strong>!<br>
        Your order has been placed and is being processed.
      </p>

      <div class="order-info">
        <div class="order-row">
          <span class="lbl"><i class="fas fa-hashtag"></i> Order Number</span>
          <span class="val order-num-val"><?= htmlspecialchars($order_num) ?></span>
        </div>
        <div class="order-row">
          <span class="lbl"><i class="fas fa-calendar-alt"></i> Order Date</span>
          <span class="val"><?= $order_date ?></span>
        </div>
        <?php if ($customer_addr): ?>
        <div class="order-row">
          <span class="lbl"><i class="fas fa-map-marker-alt"></i> Ship to</span>
          <span class="val"><?= htmlspecialchars($customer_addr) ?></span>
        </div>
        <?php endif; ?>
        <div class="order-row">
          <span class="lbl"><i class="fas fa-receipt"></i> Total Amount</span>
          <span class="val" style="color:var(--gold-pale);font-size:16px;">$<?= $order_total ?></span>
        </div>
        <div class="order-row">
          <span class="lbl"><i class="fas fa-info-circle"></i> Status</span>
          <span class="val">
            <span class="status-badge status-pending">⏳ <?= htmlspecialchars($order_status) ?></span>
          </span>
        </div>
      </div>

      <div class="btn-group">
        <a href="<?= $link_home ?>" class="btn-action btn-home">
          <i class="fas fa-home"></i> Back to Home
        </a>
        <a href="<?= $link_history ?>" class="btn-action btn-orders">
          <i class="fas fa-history"></i> View My Orders
        </a>
      </div>

      <p class="confetti-note">
        <i class="fas fa-envelope"></i> We'll notify you when your order is shipped.
      </p>
    </div>

    <!-- ── Order Items Card ── -->
    <?php if (!empty($order_items)): ?>
    <div class="items-card">
      <div class="items-card-title">
        <i class="fas fa-box-open"></i> Items in Your Order
      </div>

      <?php foreach ($order_items as $item): ?>
      <div class="item-row">
        <img src="<?= IMG_URL . htmlspecialchars($item['image'] ?? '') ?>"
             alt="<?= htmlspecialchars($item['product_name']) ?>" class="item-thumb"
             onerror="this.src='<?= IMG_URL ?>default-avatar.png'">
        <div class="item-info">
          <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
          <div class="item-meta">SKU: <?= htmlspecialchars($item['product_id']) ?> · Qty: <?= (int)$item['quantity'] ?></div>
        </div>
        <span class="item-price">$<?= number_format((float)$item['total_price'], 2) ?></span>
      </div>
      <?php endforeach; ?>

      <div class="items-total">
        <span>Order Total</span>
        <span class="amt">$<?= $order_total ?></span>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- Confetti canvas -->
<canvas id="confetti-canvas" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;"></canvas>

<script>
// ── Search ──────────────────────────────────────────────
function doSearch() {
  const kw = document.getElementById('search-input').value.trim();
  if (kw) window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(kw);
}

// ── Confetti ─────────────────────────────────────────────
(function() {
  const canvas = document.getElementById('confetti-canvas');
  const ctx    = canvas.getContext('2d');
  canvas.width  = window.innerWidth;
  canvas.height = window.innerHeight;

  const COLORS = ['#d4af37','#b8860b','#f8ce86','#fff','#4caf50','#ff7043'];
  const pieces = Array.from({length: 80}, () => ({
    x:   Math.random() * canvas.width,
    y:   Math.random() * -canvas.height,
    r:   Math.random() * 6 + 3,
    color: COLORS[Math.floor(Math.random() * COLORS.length)],
    speed: Math.random() * 2.5 + 1.5,
    angle: Math.random() * Math.PI * 2,
    spin:  (Math.random() - .5) * .12,
    drift: (Math.random() - .5) * 1.2,
    opacity: Math.random() * .6 + .4,
  }));

  let frame = 0;
  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    pieces.forEach(p => {
      p.y     += p.speed;
      p.x     += p.drift;
      p.angle += p.spin;
      if (p.y > canvas.height) {
        p.y = -10;
        p.x = Math.random() * canvas.width;
      }
      ctx.save();
      ctx.globalAlpha = p.opacity;
      ctx.translate(p.x, p.y);
      ctx.rotate(p.angle);
      ctx.fillStyle = p.color;
      ctx.fillRect(-p.r, -p.r / 2, p.r * 2, p.r);
      ctx.restore();
    });
    frame++;
    if (frame < 300) requestAnimationFrame(draw); // ~5s
    else ctx.clearRect(0, 0, canvas.width, canvas.height);
  }
  draw();

  window.addEventListener('resize', () => {
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;
  });
})();
</script>

</body>
</html>
