<?php
// ═══════════════════════════════════════════════════════════
// File: Jewellery/User/users/forgotpassword.php
// Function: Reset password using email (no email token needed)
//   ✔ Check if email exists in DB
//   ✔ Validate new password
//   ✔ Update hashed password in DB
//   ✔ Redirect to login after success
// ═══════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../../config/config.php';

if (!defined('BASE_URL')) define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))  define('IMG_URL', BASE_URL . 'images/');

// If already logged in → go home
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'User/indexprofile.php');
    exit();
}

$link_home  = BASE_URL . 'User/index.php';
$link_login = BASE_URL . 'User/users/login.php';
$link_search = BASE_URL . 'User/Search/search.html';

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']           ?? '');
    $new_pass = trim($_POST['password']        ?? '');
    $confirm  = trim($_POST['confirmPassword'] ?? '');

    // Validate
    if ($email === '')    $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';

    if ($new_pass === '') $errors[] = 'New password is required.';
    elseif (strlen($new_pass) < 6) $errors[] = 'Password must be at least 6 characters.';
    elseif ($new_pass !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Kiểm tra email tồn tại
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $errors[] = 'No account found with this email address.';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd    = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $upd->bind_param('ss', $hashed, $email);
            if ($upd->execute()) {
                $success = 'Password reset successful! Redirecting to login...';
                header('Refresh: 2; url=' . $link_login);
            } else {
                $errors[] = 'Database error. Please try again.';
            }
            $upd->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | 36 Jewelry</title>
  <meta name="description" content="Reset your 36 Jewelry account password.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--gold:#b8860b;--gold-lt:#d4af37;--gold-pale:#f8ce86;--dark:#111;--bg:#fff;--accent:#f6f6f6;}
*{margin:0;padding:0;box-sizing:border-box;}
body{
  font-family:'DM Sans',sans-serif;
  min-height:100vh;
  background:linear-gradient(rgba(8,5,2,.62),rgba(5,3,1,.72)),
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

/* FORM AREA */
.form-area{flex:1;display:flex;justify-content:center;align-items:center;padding:48px 20px;}
.reset-box{
  background:rgba(255,255,255,.10);
  backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);
  border:1px solid rgba(255,255,255,.2);
  border-radius:20px;width:100%;max-width:420px;
  padding:40px 36px;color:#fff;
  box-shadow:0 12px 40px rgba(0,0,0,.35);
  animation:slideUp .6s ease both;
}
@keyframes slideUp{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}

.reset-title{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:#fff9e6;margin-bottom:6px;display:flex;align-items:center;gap:10px;}
.reset-title i{color:var(--gold-lt);}
.reset-sub{font-size:14px;color:rgba(255,255,255,.5);margin-bottom:24px;}

.alert{padding:12px 16px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:600;}
.alert-error{background:rgba(244,67,54,.15);border:1px solid rgba(244,67,54,.3);color:#ef9a9a;}
.alert-error ul{margin:6px 0 0 16px;}
.alert-error li{margin-bottom:3px;}
.alert-success{background:rgba(76,175,80,.15);border:1px solid rgba(76,175,80,.3);color:#a5d6a7;text-align:center;}

.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:13px;font-weight:600;color:rgba(255,255,255,.7);margin-bottom:6px;}
.form-group label i{margin-right:5px;color:var(--gold-lt);}
.form-group input{
  width:100%;padding:11px 14px;border-radius:10px;font-size:14px;
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);
  color:#fff;outline:none;transition:.2s;font-family:'DM Sans',sans-serif;
}
.form-group input::placeholder{color:rgba(255,255,255,.4);}
.form-group input:focus{border-color:rgba(212,175,55,.6);background:rgba(255,255,255,.14);box-shadow:0 0 0 3px rgba(212,175,55,.15);}

.btn-submit{
  width:100%;padding:13px;border:none;border-radius:12px;cursor:pointer;
  font-weight:700;font-size:15px;margin-top:8px;
  background:linear-gradient(135deg,#d4af37,#b8860b);color:#fff;
  box-shadow:0 4px 20px rgba(184,134,11,.35);
  display:flex;align-items:center;justify-content:center;gap:9px;transition:.25s;
  font-family:'DM Sans',sans-serif;
}
.btn-submit:hover{background:linear-gradient(135deg,#e0c050,#c9972a);transform:translateY(-1px);}
.links{display:flex;justify-content:center;gap:20px;margin-top:16px;}
.links a{color:rgba(255,255,255,.5);font-size:13px;text-decoration:none;transition:.2s;}
.links a:hover{color:var(--gold-pale);}
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
      <a href="<?= $link_login ?>" class="icon-link" title="Login"><i class="fas fa-user"></i></a>
    </div>
  </div>
</header>

<div class="form-area">
  <div class="reset-box">
    <h1 class="reset-title"><i class="fas fa-key"></i> Reset Password</h1>
    <p class="reset-sub">Enter your email and choose a new password.</p>

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

    <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <form method="POST" id="reset-form">
      <div class="form-group">
        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
        <input type="email" id="email" name="email"
               placeholder="your@email.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="password"><i class="fas fa-lock"></i> New Password</label>
        <input type="password" id="password" name="password"
               placeholder="Min. 6 characters" required autocomplete="new-password">
      </div>
      <div class="form-group">
        <label for="confirmPassword"><i class="fas fa-check-double"></i> Confirm Password</label>
        <input type="password" id="confirmPassword" name="confirmPassword"
               placeholder="Repeat new password" required autocomplete="new-password">
      </div>
      <button type="submit" class="btn-submit" id="btn-reset">
        <i class="fas fa-lock-open"></i> Reset Password
      </button>
    </form>

    <div class="links">
      <a href="<?= $link_login ?>">← Back to Login</a>
      <a href="<?= $link_home ?>">Home</a>
    </div>
  </div>
</div>

<script>
function doSearch(){
  const kw=document.getElementById('search-input').value.trim();
  if(kw) window.location.href='<?= $link_search ?>?q='+encodeURIComponent(kw);
}
document.getElementById('reset-form').addEventListener('submit',function(e){
  const p=document.getElementById('password').value;
  const c=document.getElementById('confirmPassword').value;
  if(p && p!==c){ e.preventDefault(); alert('Passwords do not match!'); }
});
</script>

</body>
</html>
