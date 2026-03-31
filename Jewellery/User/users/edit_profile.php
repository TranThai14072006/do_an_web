<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/edit_profile.php
// Chức năng:
//   ✔ Lấy thông tin hiện tại của user từ DB
//   ✔ Form sửa: full_name, phone, address, birthday, gender
//   ✔ Đổi password (optional, có xác nhận)
//   ✔ Validate server-side
//   ✔ Ghi DB → redirect về profile.php
// ═══════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../../config/config.php';

if (!defined('BASE_URL')) define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))  define('IMG_URL', BASE_URL . 'images/');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'User/users/login.php');
    exit();
}

$user_id       = (int)$_SESSION['user_id'];
$link_home     = BASE_URL . 'User/indexprofile.php';
$link_profile  = BASE_URL . 'User/users/profile.php';
$link_cart     = BASE_URL . 'User/users/cart.php';
$link_logout   = BASE_URL . 'User/users/logout.php';
$link_search   = BASE_URL . 'User/users/search.php';

$errors  = [];
$success = '';

// ─────────────────────────────────────────────────────────
// LẤY DỮ LIỆU HIỆN TẠI
// ─────────────────────────────────────────────────────────
$stmt_get = $conn->prepare("
    SELECT u.username, u.email,
           c.full_name, c.phone, c.address, c.birthday, c.gender
    FROM users u
    LEFT JOIN customers c ON c.user_id = u.id
    WHERE u.id = ? LIMIT 1
");
$stmt_get->bind_param('i', $user_id);
$stmt_get->execute();
$user = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$user) {
    header('Location: ' . $link_profile);
    exit();
}

// ─────────────────────────────────────────────────────────
// XỬ LÝ SUBMIT
// ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $address   = trim($_POST['address']   ?? '');
    $birthday  = trim($_POST['birthday']  ?? '');
    $gender    = trim($_POST['gender']    ?? '');

    $new_pass  = trim($_POST['new_password']     ?? '');
    $conf_pass = trim($_POST['confirm_password'] ?? '');

    // Validate
    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($phone !== '' && !preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $phone))
        $errors[] = 'Phone number is invalid.';
    if ($birthday !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday))
        $errors[] = 'Birthday format is invalid (YYYY-MM-DD).';
    if (!in_array($gender, ['Male','Female','Other','']))
        $errors[] = 'Invalid gender value.';

    // Password change
    $change_pass = false;
    if ($new_pass !== '') {
        if (strlen($new_pass) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new_pass !== $conf_pass) {
            $errors[] = 'Passwords do not match.';
        } else {
            $change_pass = true;
        }
    }

    if (empty($errors)) {
        // Cập nhật bảng customers
        // Kiểm tra row customer đã tồn tại chưa
        $stmt_check = $conn->prepare("SELECT id FROM customers WHERE user_id = ?");
        $stmt_check->bind_param('i', $user_id);
        $stmt_check->execute();
        $existing = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        $bd_val = ($birthday !== '') ? $birthday : null;
        $gd_val = ($gender   !== '') ? $gender   : null;
        $ph_val = ($phone    !== '') ? $phone    : null;
        $ad_val = ($address  !== '') ? $address  : null;

        if ($existing) {
            $stmt_upd = $conn->prepare("
                UPDATE customers SET full_name=?, phone=?, address=?, birthday=?, gender=?
                WHERE user_id=?
            ");
            $stmt_upd->bind_param('sssssi', $full_name, $ph_val, $ad_val, $bd_val, $gd_val, $user_id);
            $stmt_upd->execute();
            $stmt_upd->close();
        } else {
            $stmt_ins = $conn->prepare("
                INSERT INTO customers (user_id, full_name, phone, address, birthday, gender)
                VALUES (?,?,?,?,?,?)
            ");
            $stmt_ins->bind_param('isssss', $user_id, $full_name, $ph_val, $ad_val, $bd_val, $gd_val);
            $stmt_ins->execute();
            $stmt_ins->close();
        }

        // Đổi password
        if ($change_pass) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt_pw = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt_pw->bind_param('si', $hashed, $user_id);
            $stmt_pw->execute();
            $stmt_pw->close();
        }

        // Redirect với thông báo thành công
        header('Location: ' . $link_profile . '?updated=1');
        exit();
    }

    // Nếu lỗi → giữ giá trị đã nhập
    $user['full_name'] = $full_name;
    $user['phone']     = $phone;
    $user['address']   = $address;
    $user['birthday']  = $birthday;
    $user['gender']    = $gender;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile | 36 Jewelry</title>
  <meta name="description" content="Edit your 36 Jewelry account profile and personal information.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --gold:#b8860b;--gold-lt:#d4af37;--gold-pale:#f8ce86;
  --dark:#111;--bg:#fff;--accent:#f6f6f6;--radius:12px;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{
  font-family:'DM Sans',sans-serif;
  min-height:100vh;
  background:
    linear-gradient(rgba(12,8,4,.6),rgba(8,5,2,.7)),
    url("<?= BASE_URL ?>images/profile background.jpg") no-repeat center center/cover fixed;
  overflow-x:hidden;
}

/* HEADER */
.header-container{width:100%;position:sticky;top:0;z-index:1000;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.1);}
.search-bar{max-width:1400px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;padding:12px 20px;gap:16px;}
.search-bar .left,.search-bar .center,.search-bar .right{display:flex;align-items:center;}
.search-bar .center{flex:1;justify-content:center;gap:24px;position:relative;}
.home-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:8px;text-decoration:none;color:var(--dark);font-weight:600;font-size:15px;transition:.2s;}
.home-btn:hover{background:var(--accent);color:var(--gold);}
.search-bar .center a{position:absolute;left:50%;transform:translateX(-50%);z-index:10;}
.header-logo{height:52px;max-width:170px;object-fit:contain;}
.search-box{flex:0 1 420px;margin-left:auto;display:flex;align-items:center;gap:8px;background:var(--accent);padding:4px 8px;border-radius:999px;border:1px solid rgba(0,0,0,.07);height:46px;}
.search-box input{flex:1;border:0;outline:0;background:transparent;padding:4px 8px;font-size:14px;color:var(--dark);}
.search-box button{display:inline-flex;align-items:center;justify-content:center;border:0;background:var(--gold);color:#fff;padding:8px 14px;border-radius:999px;cursor:pointer;font-size:13px;transition:.2s;}
.search-box button:hover{background:var(--gold-lt);}
.icon-link{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:8px;text-decoration:none;color:var(--dark);transition:.18s;font-size:18px;}
.icon-link:hover{background:rgba(184,134,11,.1);color:var(--gold);}
.search-bar .right{gap:4px;}

/* EDIT CONTAINER */
.edit-container{
  display:flex;justify-content:center;align-items:flex-start;
  min-height:calc(100vh - 76px);padding:48px 20px 60px;
}
.edit-box{
  background:rgba(255,255,255,.10);
  backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border:1px solid rgba(255,255,255,.2);
  border-radius:20px;width:100%;max-width:520px;
  padding:40px 36px;color:#fff;
  box-shadow:0 12px 40px rgba(0,0,0,.35);
  animation:slideUp .6s ease both;
}
@keyframes slideUp{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}

/* Back button */
.back-btn{
  display:inline-flex;align-items:center;gap:7px;
  color:rgba(255,255,255,.6);text-decoration:none;font-size:14px;
  margin-bottom:20px;transition:.2s;
}
.back-btn:hover{color:var(--gold-pale);}

/* Title */
.edit-title{
  font-family:'Cormorant Garamond',serif;
  font-size:28px;font-weight:700;color:#fff9e6;
  margin-bottom:28px;display:flex;align-items:center;gap:10px;
}
.edit-title i{color:var(--gold-lt);}

/* Section label */
.section-label{
  font-size:12px;font-weight:700;text-transform:uppercase;
  letter-spacing:1px;color:var(--gold-lt);
  margin:20px 0 12px;display:flex;align-items:center;gap:8px;
}
.section-label::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.12);}

/* Form groups */
.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:13px;font-weight:600;color:rgba(255,255,255,.7);margin-bottom:6px;}
.form-group label i{margin-right:5px;color:var(--gold-lt);}
.form-group input,
.form-group select,
.form-group textarea{
  width:100%;padding:11px 14px;border-radius:10px;font-size:14px;
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);
  color:#fff;outline:none;transition:.2s;font-family:'DM Sans',sans-serif;
}
.form-group input::placeholder{color:rgba(255,255,255,.4);}
.form-group input:focus,
.form-group select:focus{
  border-color:rgba(212,175,55,.6);
  background:rgba(255,255,255,.14);
  box-shadow:0 0 0 3px rgba(212,175,55,.15);
}
.form-group select option{background:#2a1e0a;color:#fff;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}

/* Error / Success */
.alert{
  padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:14px;font-weight:600;
}
.alert-error{background:rgba(244,67,54,.15);border:1px solid rgba(244,67,54,.3);color:#ef9a9a;}
.alert-error ul{margin:6px 0 0 16px;}
.alert-error li{margin-bottom:3px;}
.alert-success{background:rgba(76,175,80,.15);border:1px solid rgba(76,175,80,.3);color:#a5d6a7;}

/* Hint */
.pass-hint{font-size:12px;color:rgba(255,255,255,.4);margin-top:4px;}

/* Submit */
.btn-save{
  width:100%;padding:14px;border:none;border-radius:12px;cursor:pointer;
  font-weight:700;font-size:16px;margin-top:24px;
  background:linear-gradient(135deg,#d4af37,#b8860b);color:#fff;
  box-shadow:0 4px 20px rgba(184,134,11,.35);
  display:flex;align-items:center;justify-content:center;gap:9px;
  transition:.25s;
}
.btn-save:hover{background:linear-gradient(135deg,#e0c050,#c9972a);transform:translateY(-1px);box-shadow:0 6px 24px rgba(184,134,11,.45);}
.btn-save:active{transform:translateY(0);}
.btn-cancel{
  display:block;text-align:center;margin-top:14px;
  color:rgba(255,255,255,.5);font-size:14px;text-decoration:none;transition:.2s;
}
.btn-cancel:hover{color:rgba(255,255,255,.8);}

/* Responsive */
@media(max-width:600px){
  .edit-box{padding:28px 20px;}
  .form-row{grid-template-columns:1fr;}
  .search-bar{flex-wrap:wrap;padding:10px;}
  .search-bar .center{order:2;width:100%;}
  .search-bar .left{order:1;}
  .search-bar .right{order:3;}
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
      <a href="<?= $link_profile ?>" class="icon-link" title="Profile"><i class="fas fa-user" style="color:var(--gold)"></i></a>
      <a href="<?= $link_logout ?>" class="icon-link" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </div>
</header>

<!-- EDIT BOX -->
<div class="edit-container">
  <div class="edit-box">

    <a href="<?= $link_profile ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Profile</a>

    <h1 class="edit-title"><i class="fas fa-user-edit"></i> Edit Profile</h1>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> Please fix the following errors:
        <ul>
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="" id="edit-form" novalidate>

      <!-- ── PERSONAL INFO ────────────────── -->
      <div class="section-label"><i class="fas fa-user"></i> Personal Information</div>

      <div class="form-group">
        <label for="full_name"><i class="fas fa-id-card"></i> Full Name <span style="color:#f44336">*</span></label>
        <input type="text" id="full_name" name="full_name"
               placeholder="Your full name"
               value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="phone"><i class="fas fa-phone"></i> Phone</label>
          <input type="tel" id="phone" name="phone"
                 placeholder="+84 xxx xxx xxx"
                 value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="gender"><i class="fas fa-venus-mars"></i> Gender</label>
          <select id="gender" name="gender">
            <option value="">— Select —</option>
            <?php foreach (['Male','Female','Other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($user['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="birthday"><i class="fas fa-birthday-cake"></i> Birthday</label>
        <input type="date" id="birthday" name="birthday"
               value="<?= htmlspecialchars($user['birthday'] ?? '') ?>"
               max="<?= date('Y-m-d') ?>">
      </div>

      <div class="form-group">
        <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
        <input type="text" id="address" name="address"
               placeholder="Street, District, City"
               value="<?= htmlspecialchars($user['address'] ?? '') ?>">
      </div>

      <!-- ── CHANGE PASSWORD ──────────────── -->
      <div class="section-label"><i class="fas fa-lock"></i> Change Password</div>
      <p class="pass-hint" style="margin-bottom:12px;">Leave blank to keep your current password.</p>

      <div class="form-group">
        <label for="new_password"><i class="fas fa-key"></i> New Password</label>
        <input type="password" id="new_password" name="new_password"
               placeholder="Min. 6 characters" autocomplete="new-password">
        <p class="pass-hint">At least 6 characters.</p>
      </div>

      <div class="form-group">
        <label for="confirm_password"><i class="fas fa-check-double"></i> Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password"
               placeholder="Repeat new password" autocomplete="new-password">
      </div>

      <button type="submit" class="btn-save" id="btn-save-changes">
        <i class="fas fa-save"></i> Save Changes
      </button>

    </form>

    <a href="<?= $link_profile ?>" class="btn-cancel">Cancel &amp; go back</a>

  </div>
</div>

<script>
function doSearch() {
  const kw = document.getElementById('search-input').value.trim();
  if (kw) window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(kw);
}

// Client-side password match check
document.getElementById('edit-form').addEventListener('submit', function(e) {
  const np = document.getElementById('new_password').value;
  const cp = document.getElementById('confirm_password').value;
  if (np && np !== cp) {
    e.preventDefault();
    alert('Passwords do not match. Please try again.');
    document.getElementById('confirm_password').focus();
  }
});
</script>

</body>
</html>
