<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/profile.php
// Full functions:
//   ✔ Check login
//   ✔ Fetch user + customer info from DB
//   ✔ Avatar upload (file storage)
//   ✔ Display recent orders
//   ✔ Stats: total orders / total spent
//   ✔ Edit Profile / Log Out / Order History buttons
// ═══════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../../config/config.php';

// --- Define constants ---
if (!defined('BASE_URL')) define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))  define('IMG_URL', BASE_URL . 'images/');

// --- Check login ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'User/users/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// ── Link helpers ──────────────────────────────────────────
$link_home        = BASE_URL . 'User/indexprofile.php';
$link_cart        = BASE_URL . 'User/users/cart.php';
$link_profile     = BASE_URL . 'User/users/profile.php';
$link_logout      = BASE_URL . 'User/users/logout.php';
$link_search      = BASE_URL . 'User/Search/search.html';
$link_edit        = BASE_URL . 'User/users/edit_profile.php';
$link_history     = BASE_URL . 'User/users/history.php';

// ─────────────────────────────────────────────────────────
// AVATAR UPLOAD HANDLING
// ─────────────────────────────────────────────────────────
$upload_msg  = '';
$upload_err  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file   = $_FILES['avatar'];
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        if (!in_array($file['type'], $allowed)) {
            $upload_err = 'Only JPG, PNG, GIF, WEBP allowed.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $upload_err = 'File size must be under 2 MB.';
        } else {
            $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fname   = 'avatar_' . $user_id . '.' . strtolower($ext);
            $dir     = __DIR__ . '/../../images/avatars/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $dest    = $dir . $fname;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Save path to DB (customers table, avatar column)
                // Add column if not exists (idempotent)
                $conn->query("ALTER TABLE customers ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL");
                $stmt_av = $conn->prepare("UPDATE customers SET avatar = ? WHERE user_id = ?");
                $stmt_av->bind_param('si', $fname, $user_id);
                $stmt_av->execute();
                $stmt_av->close();
                $upload_msg = 'Avatar updated successfully!';
            } else {
                $upload_err = 'Upload failed. Check folder permissions.';
            }
        }
    }
}

// ─────────────────────────────────────────────────────────
// FETCH USER + CUSTOMER INFO
// ─────────────────────────────────────────────────────────
$sql_user = "
    SELECT u.username, u.email, u.created_at,
           c.full_name, c.phone, c.address, c.birthday, c.gender,
           c.avatar
    FROM users u
    LEFT JOIN customers c ON c.user_id = u.id
    WHERE u.id = ?
    LIMIT 1
";
$stmt_u = $conn->prepare($sql_user);
$stmt_u->bind_param('i', $user_id);
$stmt_u->execute();
$user = $stmt_u->get_result()->fetch_assoc();
$stmt_u->close();

if (!$user) {
    // Account doesn't exist → logout
    header('Location: ' . $link_logout);
    exit();
}

$display_name = htmlspecialchars($user['full_name'] ?: $user['username']);
$email        = htmlspecialchars($user['email']);
$phone        = htmlspecialchars($user['phone']   ?: 'Not set');
$address      = htmlspecialchars($user['address'] ?: 'Not set');
$birthday     = $user['birthday'] ? date('d/m/Y', strtotime($user['birthday'])) : 'Not set';
$gender       = htmlspecialchars($user['gender']  ?: 'Not set');
$member_since = $user['created_at'] ? date('F Y', strtotime($user['created_at'])) : '';

// Avatar path
if (!empty($user['avatar'])) {
    $avatar_src = BASE_URL . 'images/avatars/' . htmlspecialchars($user['avatar']);
} else {
    $avatar_src = BASE_URL . 'images/default-avatar.png';
}

// ─────────────────────────────────────────────────────────
// FETCH ORDER STATS
// ─────────────────────────────────────────────────────────
$stats = ['total_orders' => 0, 'total_spent' => 0, 'pending' => 0, 'delivered' => 0];
$stmt_cid = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
$stmt_cid->bind_param('i', $user_id);
$stmt_cid->execute();
$row_c = $stmt_cid->get_result()->fetch_assoc();
$stmt_cid->close();

$customer_id = $row_c['id'] ?? null;
$orders = [];

if ($customer_id) {
    // General stats
    $stmt_stat = $conn->prepare("
        SELECT COUNT(*) AS total_orders,
               COALESCE(SUM(total_amount),0) AS total_spent,
               SUM(status = 'Pending')   AS pending,
               SUM(status = 'Delivered') AS delivered
        FROM orders WHERE customer_id = ?
    ");
    $stmt_stat->bind_param('i', $customer_id);
    $stmt_stat->execute();
    $stats = $stmt_stat->get_result()->fetch_assoc();
    $stmt_stat->close();

    // 3 most recent orders
    $stmt_o = $conn->prepare("
        SELECT id, order_number, order_date, total_amount, status
        FROM orders
        WHERE customer_id = ?
        ORDER BY order_date DESC
        LIMIT 3
    ");
    $stmt_o->bind_param('i', $customer_id);
    $stmt_o->execute();
    $orders = $stmt_o->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_o->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile | 36 Jewelry</title>
  <meta name="description" content="View and manage your 36 Jewelry account profile, personal information, and order history.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════════ */
:root {
  --gold:      #b8860b;
  --gold-lt:   #d4af37;
  --gold-pale: #f8ce86;
  --dark:      #111;
  --bg:        #fff;
  --accent:    #f6f6f6;
  --radius:    12px;
  --shadow:    0 8px 30px rgba(0,0,0,.12);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{
  font-family:'DM Sans',sans-serif;
  min-height:100vh;
  background:
    linear-gradient(rgba(15,10,5,.55), rgba(10,5,0,.65)),
    url("<?= BASE_URL ?>images/profile background.jpg") no-repeat center center/cover fixed;
  overflow-x:hidden;
}

/* ═══════════════════════════════════════════
   HEADER
═══════════════════════════════════════════ */
.header-container{
  width:100%;position:sticky;top:0;z-index:1000;
  background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.1);
}
.search-bar{
  max-width:1400px;margin:0 auto;
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 20px;gap:16px;
}
.search-bar .left,.search-bar .center,.search-bar .right{display:flex;align-items:center;}
.search-bar .center{flex:1;justify-content:center;gap:24px;position:relative;}
.home-btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:9px 14px;border-radius:8px;text-decoration:none;
  color:var(--dark);font-weight:600;font-size:15px;transition:.2s;
}
.home-btn:hover{background:var(--accent);color:var(--gold);}
.search-bar .center a{position:absolute;left:50%;transform:translateX(-50%);z-index:10;}
.header-logo{height:52px;max-width:170px;object-fit:contain;}
.search-box{
  flex:0 1 420px;margin-left:auto;display:flex;align-items:center;gap:8px;
  background:var(--accent);padding:4px 8px;border-radius:999px;
  border:1px solid rgba(0,0,0,.07);height:46px;
}
.search-box input{flex:1;border:0;outline:0;background:transparent;padding:4px 8px;font-size:14px;color:var(--dark);}
.search-box button{
  display:inline-flex;align-items:center;justify-content:center;
  border:0;background:var(--gold);color:#fff;
  padding:8px 14px;border-radius:999px;cursor:pointer;font-size:13px;
  transition:.2s;
}
.search-box button:hover{background:var(--gold-lt);}
.icon-link{
  display:inline-flex;align-items:center;gap:6px;
  width:44px;height:44px;border-radius:8px;text-decoration:none;
  color:var(--dark);transition:.18s;font-size:18px;justify-content:center;
}
.icon-link:hover{background:rgba(184,134,11,.1);color:var(--gold);}
.search-bar .right{gap:4px;}

/* ═══════════════════════════════════════════
   PAGE WRAPPER
═══════════════════════════════════════════ */
.page-wrapper{
  display:flex;justify-content:center;align-items:flex-start;
  min-height:calc(100vh - 76px);
  padding:48px 20px 60px;
  gap:28px;flex-wrap:wrap;
}

/* ═══════════════════════════════════════════
   PROFILE CARD (LEFT)
═══════════════════════════════════════════ */
.profile-card{
  background:rgba(255,255,255,.10);
  backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border:1px solid rgba(255,255,255,.2);
  border-radius:20px;width:320px;flex-shrink:0;
  padding:36px 28px;color:#fff;
  box-shadow:0 12px 40px rgba(0,0,0,.35);
  text-align:center;
  animation:slideUp .7s ease both;
}
@keyframes slideUp{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}

/* Avatar */
.avatar-wrapper{position:relative;display:inline-block;margin-bottom:6px;}
.avatar{
  width:120px;height:120px;border-radius:50%;object-fit:cover;
  border:3px solid rgba(212,175,55,.7);
  box-shadow:0 0 20px rgba(212,175,55,.25);
  transition:.3s;
}
.avatar-overlay{
  position:absolute;bottom:0;left:50%;transform:translateX(-50%);
  background:rgba(0,0,0,.65);color:var(--gold-pale);
  font-size:11px;font-weight:600;padding:4px 10px;border-radius:12px;
  white-space:nowrap;cursor:pointer;transition:.2s;
  opacity:0;pointer-events:none;
}
.avatar-wrapper:hover .avatar-overlay{opacity:1;pointer-events:auto;}
.avatar-wrapper:hover .avatar{filter:brightness(.75);}

/* Upload form hidden */
#avatar-file{display:none;}

.profile-name{font-family:'Cormorant Garamond',serif;font-size:23px;font-weight:700;color:#fff9e6;margin:12px 0 4px;}
.profile-role{font-size:13px;color:rgba(255,249,230,.65);margin-bottom:8px;}
.member-since{font-size:12px;color:rgba(255,255,255,.45);margin-bottom:20px;}

/* Upload feedback */
.upload-msg{font-size:13px;font-weight:600;margin-bottom:8px;}
.upload-msg.success{color:#4caf50;}
.upload-msg.error{color:#f44336;}

/* Stats row */
.stats-row{display:flex;justify-content:space-around;gap:8px;margin:20px 0;border-top:1px solid rgba(255,255,255,.12);border-bottom:1px solid rgba(255,255,255,.12);padding:16px 0;}
.stat-box{text-align:center;}
.stat-num{font-size:22px;font-weight:700;color:var(--gold-pale);}
.stat-lbl{font-size:11px;color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:.5px;}

/* Info rows */
.info-section{text-align:left;margin:16px 0;}
.info-row{display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.08);font-size:14px;}
.info-row:last-child{border-bottom:none;}
.info-row i{color:var(--gold-lt);width:18px;flex-shrink:0;margin-top:2px;}
.info-label{color:rgba(255,255,255,.5);font-size:12px;min-width:60px;}
.info-val{color:#fff9e6;word-break:break-word;}

/* Action buttons */
.btn-group{display:flex;flex-direction:column;gap:10px;margin-top:20px;}
.btn-row{display:flex;gap:10px;}
.btn-action{
  flex:1;padding:11px 16px;border:none;border-radius:10px;
  cursor:pointer;font-weight:600;font-size:14px;transition:.25s;
  display:flex;align-items:center;justify-content:center;gap:7px;
  text-decoration:none;
}
.btn-edit{background:linear-gradient(135deg,#c9972a,#b8860b);color:#fff;}
.btn-edit:hover{background:linear-gradient(135deg,#d4af37,#c9972a);transform:translateY(-1px);box-shadow:0 4px 16px rgba(184,134,11,.4);}
.btn-logout{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2);}
.btn-logout:hover{background:rgba(220,50,50,.3);border-color:rgba(220,50,50,.5);transform:translateY(-1px);}
.btn-history{
  width:100%;padding:11px 16px;border:none;border-radius:10px;
  cursor:pointer;font-weight:600;font-size:14px;transition:.25s;
  background:rgba(212,175,55,.15);border:1px solid rgba(212,175,55,.3);
  color:var(--gold-pale);text-decoration:none;
  display:flex;align-items:center;justify-content:center;gap:7px;
}
.btn-history:hover{background:rgba(212,175,55,.3);transform:translateY(-1px);}

/* ═══════════════════════════════════════════
   ORDERS PANEL (RIGHT)
═══════════════════════════════════════════ */
.orders-panel{
  flex:1;min-width:300px;max-width:560px;
  animation:slideUp .7s ease .15s both;
}
.panel-box{
  background:rgba(255,255,255,.10);
  backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
  border:1px solid rgba(255,255,255,.18);
  border-radius:18px;padding:28px;margin-bottom:24px;
  box-shadow:0 8px 30px rgba(0,0,0,.25);
}
.panel-title{
  font-family:'Cormorant Garamond',serif;
  font-size:20px;font-weight:700;color:var(--gold-pale);
  margin-bottom:18px;display:flex;align-items:center;gap:9px;
}
.panel-title i{font-size:18px;}

/* Order item */
.order-item{
  display:flex;align-items:center;justify-content:space-between;
  padding:12px 0;border-bottom:1px solid rgba(255,255,255,.1);
  gap:12px;flex-wrap:wrap;
}
.order-item:last-child{border-bottom:none;}
.order-num{font-weight:600;color:#fff9e6;font-size:14px;}
.order-date{font-size:12px;color:rgba(255,255,255,.5);margin-top:2px;}
.order-amount{font-size:15px;font-weight:700;color:var(--gold-pale);}
.badge-status{
  padding:4px 10px;border-radius:8px;font-size:12px;font-weight:600;
  white-space:nowrap;
}
.badge-Pending{background:rgba(255,152,0,.2);color:#ffb74d;border:1px solid rgba(255,152,0,.3);}
.badge-Processed{background:rgba(33,150,243,.2);color:#64b5f6;border:1px solid rgba(33,150,243,.3);}
.badge-Delivered{background:rgba(76,175,80,.2);color:#81c784;border:1px solid rgba(76,175,80,.3);}
.badge-Cancelled{background:rgba(244,67,54,.2);color:#e57373;border:1px solid rgba(244,67,54,.3);}

.no-orders{text-align:center;color:rgba(255,255,255,.45);padding:32px 0;font-size:15px;}
.no-orders i{font-size:36px;display:block;margin-bottom:12px;color:rgba(212,175,55,.4);}

.see-all-link{
  display:block;text-align:center;margin-top:16px;
  color:var(--gold-lt);font-size:14px;font-weight:600;
  text-decoration:none;transition:.2s;
}
.see-all-link:hover{color:var(--gold-pale);text-shadow:0 0 8px rgba(212,175,55,.3);}

/* Quick actions */
.quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.qa-btn{
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;
  padding:20px 12px;border-radius:14px;text-decoration:none;
  background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
  color:rgba(255,255,255,.8);font-size:13px;font-weight:600;transition:.25s;
}
.qa-btn i{font-size:22px;color:var(--gold-lt);}
.qa-btn:hover{background:rgba(212,175,55,.15);border-color:rgba(212,175,55,.3);color:#fff;transform:translateY(-2px);}

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media(max-width:900px){
  .search-bar .center a{position:static;transform:none;}
  .search-box{margin-left:0;flex:1;}
  .page-wrapper{padding:28px 12px 40px;flex-direction:column;align-items:center;}
  .profile-card{width:100%;max-width:420px;}
  .orders-panel{max-width:100%;width:100%;}
}
@media(max-width:480px){
  .search-bar{flex-wrap:wrap;padding:10px;}
  .search-bar .center{order:2;width:100%;}
  .search-bar .left{order:1;}
  .search-bar .right{order:3;}
  .quick-actions{grid-template-columns:1fr;}
  .stats-row{gap:4px;}
  .stat-num{font-size:18px;}
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

<!-- ══ PAGE WRAPPER ════════════════════════════════════════ -->
<div class="page-wrapper">

  <!-- ── LEFT: PROFILE CARD ────────────────────────────── -->
  <div class="profile-card">

    <!-- Avatar + Upload -->
    <form method="POST" enctype="multipart/form-data" id="avatar-form">
      <label for="avatar-file" style="cursor:pointer;">
        <div class="avatar-wrapper">
          <img src="<?= $avatar_src ?>"
               alt="Avatar"
               class="avatar"
               id="avatar-preview"
               onerror="this.src='<?= BASE_URL ?>images/default-avatar.png'">
          <div class="avatar-overlay"><i class="fas fa-camera"></i> Change</div>
        </div>
      </label>
      <input type="file" id="avatar-file" name="avatar" accept="image/*"
             onchange="previewAndSubmit(this)">
    </form>

    <?php if ($upload_msg): ?>
      <p class="upload-msg success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($upload_msg) ?></p>
    <?php elseif ($upload_err): ?>
      <p class="upload-msg error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($upload_err) ?></p>
    <?php endif; ?>

    <h2 class="profile-name"><?= $display_name ?></h2>
    <p class="profile-role">Member of 36 Jewelry</p>
    <?php if ($member_since): ?>
      <p class="member-since"><i class="fas fa-calendar-alt" style="margin-right:4px;"></i>Since <?= $member_since ?></p>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-box">
        <div class="stat-num"><?= (int)($stats['total_orders'] ?? 0) ?></div>
        <div class="stat-lbl">Orders</div>
      </div>
      <div class="stat-box">
        <div class="stat-num">$<?= number_format((float)($stats['total_spent'] ?? 0), 0) ?></div>
        <div class="stat-lbl">Spent</div>
      </div>
      <div class="stat-box">
        <div class="stat-num"><?= (int)($stats['delivered'] ?? 0) ?></div>
        <div class="stat-lbl">Delivered</div>
      </div>
    </div>

    <!-- Personal Info -->
    <div class="info-section">
      <div class="info-row">
        <i class="fas fa-envelope"></i>
        <div>
          <div class="info-label">Email</div>
          <div class="info-val"><?= $email ?></div>
        </div>
      </div>
      <div class="info-row">
        <i class="fas fa-phone"></i>
        <div>
          <div class="info-label">Phone</div>
          <div class="info-val"><?= $phone ?></div>
        </div>
      </div>
      <div class="info-row">
        <i class="fas fa-map-marker-alt"></i>
        <div>
          <div class="info-label">Address</div>
          <div class="info-val"><?= $address ?></div>
        </div>
      </div>
      <div class="info-row">
        <i class="fas fa-birthday-cake"></i>
        <div>
          <div class="info-label">Birthday</div>
          <div class="info-val"><?= $birthday ?></div>
        </div>
      </div>
      <div class="info-row">
        <i class="fas fa-venus-mars"></i>
        <div>
          <div class="info-label">Gender</div>
          <div class="info-val"><?= $gender ?></div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="btn-group">
      <div class="btn-row">
        <a href="<?= $link_edit ?>" class="btn-action btn-edit" id="btn-edit-profile">
          <i class="fas fa-user-edit"></i> Edit Profile
        </a>
        <a href="<?= $link_logout ?>" class="btn-action btn-logout" id="btn-logout">
          <i class="fas fa-sign-out-alt"></i> Log Out
        </a>
      </div>
      <a href="<?= $link_history ?>" class="btn-history" id="btn-history">
        <i class="fas fa-history"></i> View All Orders
      </a>
    </div>

  </div><!-- /.profile-card -->

  <!-- ── RIGHT PANEL ────────────────────────────────────── -->
  <div class="orders-panel">

    <!-- Recent Orders -->
    <div class="panel-box">
      <h3 class="panel-title"><i class="fas fa-shopping-bag"></i> Recent Orders</h3>

      <?php if (empty($orders)): ?>
        <div class="no-orders">
          <i class="fas fa-box-open"></i>
          You haven't placed any orders yet.
        </div>
      <?php else: ?>
        <?php foreach ($orders as $o): ?>
          <?php
            $status_cls = 'badge-' . htmlspecialchars($o['status']);
            $odate = date('d M Y', strtotime($o['order_date']));
          ?>
          <div class="order-item">
            <div>
              <div class="order-num"><?= htmlspecialchars($o['order_number']) ?></div>
              <div class="order-date"><?= $odate ?></div>
            </div>
            <div class="order-amount">$<?= number_format((float)$o['total_amount'], 2) ?></div>
            <span class="badge-status <?= $status_cls ?>"><?= htmlspecialchars($o['status']) ?></span>
          </div>
        <?php endforeach; ?>
        <a href="<?= $link_history ?>" class="see-all-link">See all orders &rarr;</a>
      <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="panel-box">
      <h3 class="panel-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
      <div class="quick-actions">
        <a href="<?= $link_cart ?>" class="qa-btn" id="qa-cart">
          <i class="fas fa-shopping-cart"></i> My Cart
        </a>
        <a href="<?= $link_history ?>" class="qa-btn" id="qa-history">
          <i class="fas fa-history"></i> Order History
        </a>
        <a href="<?= $link_edit ?>" class="qa-btn" id="qa-edit">
          <i class="fas fa-user-edit"></i> Edit Profile
        </a>
        <a href="<?= $link_home ?>" class="qa-btn" id="qa-shop">
          <i class="fas fa-gem"></i> Shop Now
        </a>
      </div>
    </div>

  </div><!-- /.orders-panel -->

</div><!-- /.page-wrapper -->

<script>
// Search
function doSearch() {
  const kw = document.getElementById('search-input').value.trim();
  if (kw) window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(kw);
}

// Preview avatar before submit
function previewAndSubmit(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    document.getElementById('avatar-preview').src = e.target.result;
  };
  reader.readAsDataURL(input.files[0]);
  // Auto submit form
  document.getElementById('avatar-form').submit();
}
</script>

</body>
</html>
