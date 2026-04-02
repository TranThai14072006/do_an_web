<?php
session_start();

$conn = new mysqli("localhost", "root", "", "jewelry_db");
$conn->set_charset("utf8");

// Handle Size Update natively in this file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_size') {
    $product_id = $_POST['product_id'] ?? '';
    $old_size = $_POST['old_size'] ?? '';
    $new_size = $_POST['new_size'] ?? '';
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    
    if ($user_id > 0 && $product_id && $new_size && $old_size !== $new_size) {
        $pid_safe = $conn->real_escape_string($product_id);
        $old_s_safe = $conn->real_escape_string($old_size);
        $new_s_safe = $conn->real_escape_string($new_size);
        
        // Merge identical sizes if they exist
        $check = $conn->query("SELECT quantity FROM cart WHERE user_id = $user_id AND product_id = '$pid_safe' AND size = '$new_s_safe'");
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE cart SET quantity = quantity + (SELECT quantity FROM (SELECT quantity FROM cart WHERE user_id=$user_id AND product_id='$pid_safe' AND size='$old_s_safe') as old_qty) WHERE user_id = $user_id AND product_id = '$pid_safe' AND size = '$new_s_safe'");
            $conn->query("DELETE FROM cart WHERE user_id = $user_id AND product_id = '$pid_safe' AND size = '$old_s_safe'");
        } else {
            // otherwise just rename the size
            $conn->query("UPDATE cart SET size = '$new_s_safe' WHERE user_id = $user_id AND product_id = '$pid_safe' AND size = '$old_s_safe'");
        }
    }
    // Refresh page with new size
    header("Location: view.php?id=" . urlencode($product_id) . "&size=" . urlencode($new_size));
    exit;
}

$id   = $_GET['id']   ?? '';
$size = $_GET['size'] ?? '';

// ===== LẤY PRODUCT =====
$sql     = "SELECT * FROM products WHERE id = '" . $conn->real_escape_string($id) . "'";
$product = $conn->query($sql)->fetch_assoc();

// ===== LẤY DETAILS =====
$sql_details = "SELECT * FROM product_details WHERE product_id = '" . $conn->real_escape_string($id) . "'";
$details     = $conn->query($sql_details)->fetch_assoc();

// ===== TÍNH GIÁ VÀ SỐ LƯỢNG =====
$sale_price = 0;
$qty_in_cart = 0;

if ($product) {
    $cost_price     = (float)$product['cost_price'];
    $profit_percent = (float)$product['profit_percent'];

    // Giá bán = giá nhập bình quân × (1 + tỷ lệ lợi nhuận%)
    $sale_price = round($cost_price * (1 + $profit_percent / 100), 2);

    // Tính số lượng một sản phẩm riêng lẽ này trong giỏ (để hiện bên dưới)
    $user_id_cart = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    if ($user_id_cart > 0) {
        $q_sql = "SELECT quantity FROM cart WHERE user_id = $user_id_cart AND product_id = '" . $conn->real_escape_string($id) . "' AND size = '" . $conn->real_escape_string($size) . "'";
        $q_res = $conn->query($q_sql);
        if ($q_res && $q_res->num_rows > 0) {
            $qty_in_cart = (int)$q_res->fetch_assoc()['quantity'];
        }
    }
}

// ===== TỔNG SỐ LƯỢNG CART & USER NAME CHO HEADER =====
$total_cart_count = 0;
$logged_in_name = htmlspecialchars($_SESSION['username'] ?? 'User');
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $badge_res = $conn->query("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = $uid");
    if ($badge_res && $row_b = $badge_res->fetch_assoc()) {
        $total_cart_count = (int)$row_b['total_qty'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($product['name'] ?? 'Product Detail'); ?> | 36 Jewelry</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../Products/pr_detail.css">

  <style>
    /* ── VIEW-ONLY BADGE ── */
    .view-only-badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: #f5f0ea;
      border: 1px solid #d4b896;
      color: #8a6a3e;
      font-size: 11px;
      font-weight: 500;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 6px 14px;
      border-radius: 2px;
      margin-bottom: 16px;
    }
    .view-only-badge i { color: #b8945f; }

    /* ── SELECTED SIZE CHIP ── */
    .size-display { margin: 20px 0 16px; }
    .size-display h3 {
      font-size: 11px;
      font-weight: 500;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: #999;
      margin-bottom: 10px;
    }
    .size-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1.5px solid #c9a96e;
      background: #fffdf9;
      padding: 9px 20px;
      border-radius: 3px;
      font-size: 14px;
      font-weight: 500;
      color: #5a3e28;
      letter-spacing: 0.05em;
    }
    .size-chip i { color: #b8945f; font-size: 13px; }
    .size-chip .size-value { font-size: 16px; font-weight: 700; color: #3d2710; }
    .size-chip.no-size { border-color: #e0e0e0; color: #aaa; }

    /* ── HIDE action buttons block ── */
    .action-buttons,
    .size-selection { display: none !important; }

    /* ── THIN DIVIDER ── */
    .view-divider {
      width: 40px;
      height: 1px;
      background: linear-gradient(to right, #c9a96e, transparent);
      margin: 22px 0;
    }

    /* ── EDIT MODAL ── */
    .edit-modal-overlay {
      display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45);
      z-index: 9999; align-items: center; justify-content: center;
    }
    .edit-modal-overlay.open { display: flex; }
    .edit-modal {
      background: #fff; border-radius: 10px; padding: 24px;
      width: 320px; box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    }
    .edit-modal h3 { margin: 0 0 15px; font-size: 16px; color: #2d1a0e; }
    .edit-modal select { width: 100%; padding: 10px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #ddd; outline: none; }
    .edit-modal .modal-actions { display: flex; gap: 10px; }
    .edit-modal button { flex: 1; padding: 10px; border-radius: 6px; cursor: pointer; font-weight: 600; border: none; }
    .btn-cancel { background: #f5f5f5; color: #666; }
    .btn-save { background: #c9a96e; color: #fff; text-transform: uppercase; }

    .qty-badge {
      display: inline-block;
      margin-top: 12px; font-size: 14px; color: #888;
    }
    .qty-badge strong { color: #3d2710; }
    
    .edit-size-btn {
      background: none; border: none; color: #c9a96e; margin-left: 10px;
      cursor: pointer; font-size: 13px; text-decoration: underline; text-transform: none; letter-spacing: 0;
    }
    
    /* ── CUSTOM HEADER MATCHING CART ── */
    .icon-link { position: relative; }
    .cart-badge { position: absolute; top: 4px; right: 4px; background: #b8860b; color: #fff; font-size: 10px; font-weight: 700; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; line-height: 1; }
    .user-name { font-size: 13px; font-weight: 600; color: #111; text-decoration: none; display: flex; align-items: center; gap: 6px; }
    .user-name:hover { color: #b8860b; }
    .search-bar .right { gap: 4px; }
  </style>
</head>

<body>

<!-- HEADER -->
<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="../indexprofile.php" class="home-btn">
        <i class="fas fa-home"></i> Home
      </a>
    </div>

    <div class="center">
      <a href="../indexprofile.php">
        <img src="../../images/36-logo.png" class="header-logo" alt="36 Jewelry">
      </a>
      <div class="search-box">
        <input type="text" id="view-search-input" placeholder="Search products..." onkeydown="if(event.key==='Enter') doSearchView()">
        <button onclick="doSearchView()">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>

    <div class="right">
      <a href="../users/cart.php" class="icon-link" title="Cart">
        <i class="fas fa-shopping-cart"></i>
        <?php if ($total_cart_count > 0): ?>
          <span class="cart-badge"><?= $total_cart_count > 9 ? '9+' : $total_cart_count ?></span>
        <?php endif; ?>
      </a>
      <a href="../users/profile.php" class="user-name" title="Profile">
        <i class="fas fa-user-circle" style="font-size:20px;color:#b8860b"></i>
        <span><?= $logged_in_name ?></span>
      </a>
      <a href="../users/logout.php" class="icon-link" title="Logout">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>
</header>

<?php if ($product): ?>

<div class="product-detail">

  <!-- IMAGE -->
  <div class="product-image">
    <img src="../../images/<?php echo htmlspecialchars($product['image']); ?>"
         alt="<?php echo htmlspecialchars($product['name']); ?>">
  </div>

  <!-- INFO -->
  <div class="product-info">

    <div class="view-only-badge">
      <i class="fas fa-eye"></i>
      Viewing from cart
    </div>

    <h1><?php echo htmlspecialchars($product['name']); ?></h1>

    <p class="price">$<?php echo number_format($sale_price, 2); ?></p>

    <p class="description">
      <?php echo nl2br(htmlspecialchars($details['description'] ?? 'No description available.')); ?>
    </p>

    <div class="view-divider"></div>

    <!-- Size đã chọn -->
    <div class="size-display">
      <h3>
        Selected Ring Size 
        <button type="button" class="edit-size-btn" onclick="document.getElementById('editModal').classList.add('open')">
          <i class="fas fa-pencil-alt"></i> Edit
        </button>
      </h3>
      <?php if (!empty($size)): ?>
        <div class="size-chip">
          <i class="fas fa-ring"></i>
          Size <span class="size-value"><?php echo htmlspecialchars($size); ?></span>
        </div>
      <?php else: ?>
        <div class="size-chip no-size">
          <i class="fas fa-circle-question"></i>
          No size selected
        </div>
      <?php endif; ?>
      <br>
      <?php if ($qty_in_cart > 0): ?>
        <div class="qty-badge">Quantity in cart: <strong><?php echo $qty_in_cart; ?></strong></div>
      <?php endif; ?>
    </div>

    <!-- Edit Modal -->
    <div class="edit-modal-overlay" id="editModal">
      <div class="edit-modal">
        <h3>Change Ring Size</h3>
        <form method="POST" action="">
          <input type="hidden" name="action" value="update_size">
          <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
          <input type="hidden" name="old_size" value="<?php echo htmlspecialchars($size); ?>">
          <select name="new_size" required>
            <?php foreach([5,6,7,8,9] as $s): ?>
              <option value="<?php echo $s; ?>" <?php echo $size == $s ? 'selected' : ''; ?>>Size <?php echo $s; ?></option>
            <?php endforeach; ?>
          </select>
          <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="document.getElementById('editModal').classList.remove('open')">Cancel</button>
            <button type="submit" class="btn-save">Save</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Specs -->
    <ul class="product-specs">
      <?php if (!empty($details['material'])): ?>
        <li><span>Material:</span> <span><?php echo htmlspecialchars($details['material']); ?></span></li>
      <?php endif; ?>
      <?php if (!empty($details['design'])): ?>
        <li><span>Design:</span> <span><?php echo htmlspecialchars($details['design']); ?></span></li>
      <?php endif; ?>
      <?php if (!empty($details['stone'])): ?>
        <li><span>Stone:</span> <span><?php echo htmlspecialchars($details['stone']); ?></span></li>
      <?php endif; ?>
      <?php if (!empty($details['color'])): ?>
        <li><span>Color:</span> <span><?php echo htmlspecialchars($details['color']); ?></span></li>
      <?php endif; ?>
      <?php if (!empty($details['brand'])): ?>
        <li><span>Brand:</span> <span><?php echo htmlspecialchars($details['brand']); ?></span></li>
      <?php endif; ?>
    </ul>



  </div>
</div>

<?php else: ?>
  <h2 style="text-align:center; margin-top:100px;">Product not found</h2>
<?php endif; ?>

</body>
<script>
function doSearchView() {
  const kw = document.getElementById('view-search-input').value.trim();
  if (kw) window.location.href = '../Search/search.html?q=' + encodeURIComponent(kw);
}
</script>
</html>