<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/order_confirm.php
// Chức năng:
//   ✔ Kiểm tra đăng nhập (dùng session thực)
//   ✔ Kiểm tra giỏ hàng không rỗng
//   ✔ Form điền thông tin giao hàng
//   ✔ Lưu đơn hàng vào DB (orders + order_items)
//   ✔ Giảm stock sản phẩm
//   ✔ Xóa giỏ hàng sau khi đặt
//   ✔ Redirect → order_success.php
// ═══════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../../config/config.php';

if (!defined('BASE_URL')) define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))  define('IMG_URL',  BASE_URL . 'images/');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'User/users/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// ── Links ─────────────────────────────────────────────────
$link_home     = BASE_URL . 'User/indexprofile.php';
$link_cart     = BASE_URL . 'User/users/cart.php';
$link_profile  = BASE_URL . 'User/users/profile.php';
$link_logout   = BASE_URL . 'User/users/logout.php';
$link_search   = BASE_URL . 'User/users/search.php';
$logged_in_name = htmlspecialchars($_SESSION['username'] ?? 'User');

// ── Hàm tính giá bán ─────────────────────────────────────
function calcPrice(array $r): float {
    $cost   = (float)($r['cost_price']     ?? 0);
    $profit = (int)  ($r['profit_percent'] ?? 0);
    $price  = (float)($r['price']          ?? 0);
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
$cart_items   = [];
$total_amount = 0.0;

$cst = $conn->prepare("
    SELECT c.product_id, c.quantity,
           p.name, p.price, p.image, p.cost_price, p.profit_percent, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$cst->bind_param('i', $user_id);
$cst->execute();
$cres = $cst->get_result();
while ($row = $cres->fetch_assoc()) {
    $sp = calcPrice($row);
    $qty = (int)$row['quantity'];
    $cart_items[] = [
        'id'       => $row['product_id'],
        'name'     => $row['name'],
        'image'    => $row['image'],
        'price'    => $sp,
        'quantity' => $qty,
        'total'    => $sp * $qty,
        'stock'    => (int)($row['stock'] ?? 0),
    ];
    $total_amount += $sp * $qty;
}
$cst->close();

// ── Xử lý đặt hàng (POST) ────────────────────────────────
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($cart_items)) {

    $full_name      = trim($_POST['fullname']       ?? '');
    $phone          = trim($_POST['phone']          ?? '');
    $payment_method = trim($_POST['payment']        ?? 'cod');
    $addr_option    = trim($_POST['addressOption']  ?? 'saved');
    $address        = ($addr_option === 'new')
                        ? trim($_POST['c_address']    ?? '')
                        : trim($_POST['saved_address'] ?? $user['address'] ?? '');

    // Validation
    if (!$full_name) $error = 'Full name is required.';
    elseif (!$phone) $error = 'Phone number is required.';
    elseif (!$address) $error = 'Delivery address is required.';

    // Kiểm tra stock
    if (!$error) {
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock']) {
                $error = "\"" . htmlspecialchars($item['name']) . "\" is out of stock (only {$item['stock']} left).";
                break;
            }
        }
    }

    if (!$error) {
        $conn->begin_transaction();
        try {
            // Upsert customers
            $cid_st = $conn->prepare("SELECT id FROM customers WHERE user_id = ? LIMIT 1");
            $cid_st->bind_param('i', $user_id);
            $cid_st->execute();
            $cid_row = $cid_st->get_result()->fetch_assoc();
            $cid_st->close();

            if ($cid_row) {
                $customer_id = (int)$cid_row['id'];
                $upd = $conn->prepare("UPDATE customers SET full_name=?, phone=?, address=? WHERE id=?");
                $upd->bind_param('sssi', $full_name, $phone, $address, $customer_id);
                $upd->execute(); $upd->close();
            } else {
                $ins = $conn->prepare("INSERT INTO customers (user_id, full_name, phone, address) VALUES (?,?,?,?)");
                $ins->bind_param('isss', $user_id, $full_name, $phone, $address);
                $ins->execute();
                $customer_id = (int)$conn->insert_id;
                $ins->close();
            }

            // Tạo order
            $order_number = 'ORD' . date('Ymd') . rand(100, 999);
            $order_date   = date('Y-m-d');
            $status       = 'Pending';

            $oi = $conn->prepare("
                INSERT INTO orders (order_number, customer_id, order_date, total_amount, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $oi->bind_param('sisdss', $order_number, $customer_id, $order_date, $total_amount, $status);
            // fix: missing 1 param — remove extra s
            $oi2 = $conn->prepare("
                INSERT INTO orders (order_number, customer_id, order_date, total_amount, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $oi2->bind_param('sisds', $order_number, $customer_id, $order_date, $total_amount, $status);
            $oi2->execute();
            $order_id = (int)$conn->insert_id;
            $oi2->close();

            // Thêm order_items + giảm stock
            $ii = $conn->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($cart_items as $item) {
                $ii->bind_param('ississd',
                    $order_id, $item['id'], $item['name'],
                    $item['quantity'], $item['price'], $item['total']
                );
                // fix bind types
                $ii2 = $conn->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $ii2->bind_param('issidd',
                    $order_id, $item['id'], $item['name'],
                    $item['quantity'], $item['price'], $item['total']
                );
                $ii2->execute(); $ii2->close();

                // Giảm stock
                $us = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $us->bind_param('isi', $item['quantity'], $item['id'], $item['quantity']);
                $us->execute(); $us->close();
            }

            // Xóa giỏ hàng
            $del = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $del->bind_param('i', $user_id);
            $del->execute(); $del->close();

            $conn->commit();

            $_SESSION['last_order_id']     = $order_id;
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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--gold:#b8860b;--gold-lt:#d4af37;--gold-pale:#f8ce86;--dark:#111;--bg:#fff;--accent:#f6f6f6;--muted:#666;--radius:12px;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:#fafafa;min-height:100vh;color:var(--dark);}

/* HEADER */
.header-container{width:100%;position:sticky;top:0;z-index:1000;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.08);}
.search-bar{max-width:1400px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;padding:12px 20px;gap:16px;}
.search-bar .left,.search-bar .center,.search-bar .right{display:flex;align-items:center;}
.search-bar .center{flex:1;justify-content:center;gap:24px;position:relative;}
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
.user-name{font-size:13px;font-weight:600;color:var(--dark);text-decoration:none;display:flex;align-items:center;gap:6px;}
.user-name:hover{color:var(--gold);}

/* PAGE */
.page-wrapper{max-width:920px;margin:36px auto 60px;padding:0 20px;display:grid;grid-template-columns:1fr 320px;gap:28px;align-items:start;}

/* CHECKOUT BOX */
.checkout-box{background:#fff;border-radius:var(--radius);box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;}
.box-header{padding:20px 24px;border-bottom:1px solid #f0eee8;display:flex;align-items:center;gap:10px;}
.box-header h1{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:700;color:var(--dark);}
.box-header i{color:var(--gold);}
.box-body{padding:24px;}

.alert-error{background:#fff0f0;border:1px solid #f5c2c2;border-radius:10px;padding:12px 16px;color:#c62828;font-size:14px;margin-bottom:20px;font-weight:600;}

.section-label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--gold-lt);margin:20px 0 12px;display:flex;align-items:center;gap:8px;}
.section-label::after{content:'';flex:1;height:1px;background:#f0eee8;}
.section-label:first-child{margin-top:0;}

.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:13px;font-weight:600;color:var(--muted);margin-bottom:6px;}
.form-group input,.form-group select{
  width:100%;padding:11px 14px;border-radius:10px;font-size:14px;
  border:1px solid #e0ddd5;background:#fff;color:var(--dark);
  outline:none;transition:.2s;font-family:'DM Sans',sans-serif;
}
.form-group input:focus,.form-group select:focus{border-color:var(--gold-lt);box-shadow:0 0 0 3px rgba(212,175,55,.15);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}

.radio-group{display:flex;gap:16px;flex-wrap:wrap;}
.radio-group label{display:flex;align-items:center;gap:6px;cursor:pointer;font-size:14px;font-weight:500;}
.radio-group input[type=radio]{accent-color:var(--gold);}

.btn-order{
  width:100%;padding:14px;border:none;border-radius:12px;cursor:pointer;
  font-weight:700;font-size:16px;margin-top:8px;
  background:linear-gradient(135deg,#d4af37,#b8860b);color:#fff;
  box-shadow:0 4px 20px rgba(184,134,11,.3);
  display:flex;align-items:center;justify-content:center;gap:9px;transition:.25s;
  font-family:'DM Sans',sans-serif;
}
.btn-order:hover{background:linear-gradient(135deg,#e0c050,#c9972a);transform:translateY(-1px);}
.btn-order:disabled{background:#ccc;box-shadow:none;cursor:not-allowed;transform:none;}

/* SUMMARY */
.summary-box{background:#fff;border-radius:var(--radius);box-shadow:0 4px 24px rgba(0,0,0,.08);padding:24px;position:sticky;top:90px;}
.sum-title{font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;margin-bottom:18px;color:var(--dark);display:flex;align-items:center;gap:8px;}
.sum-title i{color:var(--gold-lt);}
.sum-item{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f5f3ee;}
.sum-item:last-of-type{border-bottom:none;}
.sum-thumb{width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #ece8df;flex-shrink:0;}
.sum-name{font-size:13px;font-weight:600;flex:1;line-height:1.3;}
.sum-qty{font-size:12px;color:var(--muted);}
.sum-price{font-size:13px;font-weight:700;color:var(--gold);white-space:nowrap;}
hr.sum-div{border:none;border-top:2px solid #ede9df;margin:14px 0;}
.sum-total-row{display:flex;justify-content:space-between;font-size:17px;font-weight:700;}
.sum-total-row .amt{color:var(--gold);}
.empty-cart-note{text-align:center;color:var(--muted);padding:20px 0;font-size:14px;}

@media(max-width:860px){
  .page-wrapper{grid-template-columns:1fr;}
  .summary-box{position:static;}
  .search-bar .center a{position:static;transform:none;}
  .search-box{margin-left:0;flex:1;}
}
@media(max-width:600px){
  .search-bar{flex-wrap:wrap;padding:10px;}
  .search-bar .center{order:2;width:100%;}
  .search-bar .left{order:1;}
  .search-bar .right{order:3;}
  .form-row{grid-template-columns:1fr;}
}
</style>
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
      <a href="<?= $link_profile ?>" class="user-name" title="Profile">
        <i class="fas fa-user-circle" style="font-size:20px;color:var(--gold)"></i>
        <span><?= $logged_in_name ?></span>
      </a>
      <a href="<?= $link_logout ?>" class="icon-link" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </div>
</header>

<div class="page-wrapper">

  <!-- LEFT: Checkout form -->
  <div class="checkout-box">
    <div class="box-header">
      <i class="fas fa-truck"></i>
      <h1>Shipping Information</h1>
    </div>
    <div class="box-body">

      <?php if ($error): ?>
        <div class="alert-error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (empty($cart_items)): ?>
        <div style="text-align:center;padding:40px 0;">
          <i class="fas fa-shopping-bag" style="font-size:48px;color:#e0d5c0;display:block;margin-bottom:16px;"></i>
          <p style="color:var(--muted);margin-bottom:20px;">Your cart is empty.</p>
          <a href="<?= $link_home ?>" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,var(--gold-lt),var(--gold));color:#fff;border-radius:10px;text-decoration:none;font-weight:600;">
            <i class="fas fa-gem"></i> Shop Now
          </a>
        </div>
      <?php else: ?>
      <form method="POST" id="checkout-form">

        <!-- Contact -->
        <div class="section-label"><i class="fas fa-user"></i> Contact Details</div>
        <div class="form-row">
          <div class="form-group">
            <label for="fullname">Full Name <span style="color:red">*</span></label>
            <input type="text" id="fullname" name="fullname" required
                   placeholder="Your full name"
                   value="<?= htmlspecialchars($_POST['fullname'] ?? $user['full_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="phone">Phone Number <span style="color:red">*</span></label>
            <input type="tel" id="phone" name="phone" required
                   placeholder="+84 xxx xxx xxx"
                   value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>">
          </div>
        </div>

        <!-- Address -->
        <div class="section-label"><i class="fas fa-map-marker-alt"></i> Delivery Address</div>
        <div class="form-group">
          <div class="radio-group">
            <label>
              <input type="radio" name="addressOption" value="saved"
                     <?= ($_POST['addressOption'] ?? 'saved') === 'saved' ? 'checked' : '' ?>>
              Use saved address
            </label>
            <label>
              <input type="radio" name="addressOption" value="new"
                     <?= ($_POST['addressOption'] ?? '') === 'new' ? 'checked' : '' ?>>
              Enter new address
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
              <option value="">No saved address — please enter one below</option>
            <?php endif; ?>
          </select>
        </div>

        <div class="form-group" id="new-addr-section" style="display:none;">
          <label for="c_address">New Address <span style="color:red">*</span></label>
          <input type="text" id="c_address" name="c_address"
                 placeholder="Street address, ward, district, city"
                 value="<?= htmlspecialchars($_POST['c_address'] ?? '') ?>">
        </div>

        <!-- Payment -->
        <div class="section-label"><i class="fas fa-credit-card"></i> Payment Method</div>
        <div class="form-group">
          <select id="payment" name="payment">
            <option value="cod"    <?= ($_POST['payment'] ?? 'cod') === 'cod'    ? 'selected' : '' ?>>💴 Cash on Delivery</option>
            <option value="bank"   <?= ($_POST['payment'] ?? '') === 'bank'   ? 'selected' : '' ?>>🏦 Bank Transfer</option>
            <option value="online" <?= ($_POST['payment'] ?? '') === 'online' ? 'selected' : '' ?>>💳 Online Payment</option>
          </select>
        </div>

        <button type="submit" class="btn-order" id="btn-place-order">
          <i class="fas fa-check-circle"></i> Place Order — $<?= number_format($total_amount, 2) ?>
        </button>

      </form>
      <?php endif; ?>

    </div>
  </div>

  <!-- RIGHT: Order Summary -->
  <div class="summary-box">
    <h2 class="sum-title"><i class="fas fa-receipt"></i> Order Summary</h2>

    <?php if (empty($cart_items)): ?>
      <p class="empty-cart-note">No items in cart.</p>
    <?php else: ?>
      <?php foreach ($cart_items as $item): ?>
      <div class="sum-item">
        <img src="<?= IMG_URL . htmlspecialchars($item['image'] ?? '') ?>"
             alt="<?= htmlspecialchars($item['name']) ?>" class="sum-thumb"
             onerror="this.src='<?= IMG_URL ?>default-avatar.png'">
        <div style="flex:1;min-width:0;">
          <div class="sum-name"><?= htmlspecialchars($item['name']) ?></div>
          <div class="sum-qty">Qty: <?= $item['quantity'] ?></div>
        </div>
        <span class="sum-price">$<?= number_format($item['total'], 2) ?></span>
      </div>
      <?php endforeach; ?>
      <hr class="sum-div">
      <div class="sum-total-row">
        <span>Total</span>
        <span class="amt">$<?= number_format($total_amount, 2) ?></span>
      </div>
      <div style="font-size:12px;color:var(--muted);margin-top:8px;text-align:center;">
        <i class="fas fa-shield-alt" style="color:#4caf50;"></i> Free shipping · Secure checkout
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
function doSearch(){
  const kw=document.getElementById('search-input').value.trim();
  if(kw) window.location.href='<?= $link_search ?>?q='+encodeURIComponent(kw);
}

// Toggle address sections
const savedSec = document.getElementById('saved-addr-section');
const newSec   = document.getElementById('new-addr-section');
const newInput = document.getElementById('c_address');

function toggleAddr(){
  const v = document.querySelector('input[name="addressOption"]:checked')?.value;
  if(v === 'new'){
    savedSec.style.display = 'none';
    newSec.style.display   = 'block';
    newInput?.setAttribute('required','required');
  } else {
    savedSec.style.display = 'block';
    newSec.style.display   = 'none';
    newInput?.removeAttribute('required');
  }
}
document.querySelectorAll('input[name="addressOption"]').forEach(r => r.addEventListener('change', toggleAddr));
toggleAddr();

// Confirm before submit
const form = document.getElementById('checkout-form');
if(form){
  form.addEventListener('submit', function(e){
    const name  = document.getElementById('fullname')?.value.trim();
    const phone = document.getElementById('phone')?.value.trim();
    if(!name || !phone){ e.preventDefault(); alert('Please fill in name and phone.'); return; }
    if(!confirm('Confirm your order?\n\nTotal: $<?= number_format($total_amount, 2) ?>\n\nClick OK to place order.')){ e.preventDefault(); }
  });
}
</script>

</body>
</html>