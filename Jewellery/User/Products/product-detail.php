<?php
session_start();
$logged_in_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
$link_logout = '../users/logout.php';

$conn = new mysqli("localhost", "root", "", "jewelry_db");
$conn->set_charset("utf8");

$id = $_GET['id'] ?? '';

// ===== LẤY PRODUCT =====
$sql = "SELECT * FROM products WHERE id = '$id'";
$product = $conn->query($sql)->fetch_assoc();

// ===== LẤY DETAILS =====
$sql_details = "SELECT * FROM product_details WHERE product_id = '$id'";
$details = $conn->query($sql_details)->fetch_assoc();

// ===== TÍNH GIÁ =====
$sale_price = 0;

if ($product) {
    $cost_price     = (float)$product['cost_price'];
    $profit_percent = (float)$product['profit_percent'];

    // Giá bán = giá nhập bình quân × (1 + tỷ lệ lợi nhuận%)
    $sale_price = round($cost_price * (1 + $profit_percent / 100), 2);
}

// ===== TỔNG SỐ LƯỢNG GIỎ HÀNG =====
$total_cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt_badge = $conn->query("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = $uid");
    if ($stmt_badge && $row_b = $stmt_badge->fetch_assoc()) {
        $total_cart_count = (int)$row_b['total_qty'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $product['name'] ?? 'Product'; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="pr_detail.css">
  <style>
    .stock-status {
      font-size: 14px;
      font-weight: 600;
      margin-top: 5px;
      display: inline-block;
      padding: 4px 12px;
      border-radius: 4px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .status-in { background: #e8f5e9; color: #2e7d32; }
    .status-out { background: #ffebee; color: #c62828; }
    
    .btn-out-of-stock {
      background: #d8d8d8 !important;
      color: #666 !important;
      cursor: not-allowed !important;
      border: 1px solid #ccc !important;
      padding: 12px 30px;
      font-weight: 700;
      border-radius: 5px;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      text-transform: uppercase;
    }
    
    .buy-now.disabled {
      background: #f0f0f0;
      color: #bbb;
      border-color: #ddd;
      pointer-events: none;
    }
    
    select:disabled {
      background-color: #f9f9f9;
      cursor: not-allowed;
      border-color: #eee;
    }
    
    /* ===== CART BADGE ===== */
    .icon-link { position: relative; }
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
    }
  </style>
</head>

<body>

<!-- 🔥 HEADER -->
<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="../indexprofile.php" class="home-btn"><i class="fas fa-home"></i> Home</a>
    </div>

    <div class="center">
      <a href="../indexprofile.php">
        <img src="../../images/36-logo.png" alt="Jewelry Store Logo" class="header-logo">
      </a>
      <div class="search-box">
        <input type="text" id="header-search" placeholder="Search products..."
               onkeydown="if(event.key==='Enter') applyHeaderSearch()">
        <button onclick="applyHeaderSearch()">
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
      <a href="../users/profile.php" class="icon-link" title="Profile">
        <i class="fas fa-user-circle user-icon"></i>
      </a>
      <a href="<?= htmlspecialchars($link_logout) ?>" class="icon-link" title="Logout" style="color:#111;">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </div>
  </div>
</header>

<?php if ($product): ?>

<div class="product-detail">

  <button class="back-btn" onclick="window.history.back()">←</button>

  <!-- IMAGE -->
  <div class="product-image">
    <img src="../../images/<?php echo $product['image']; ?>">
  </div>

  <!-- INFO -->
  <div class="product-info">

    <h1><?php echo $product['name']; ?></h1>

    <p class="price">
      $<?php echo number_format($sale_price, 2); ?>
      <br>
      <?php if ((int)$product['stock'] > 0): ?>
        <span class="stock-status status-in"><i class="fas fa-check"></i> In Stock</span>
      <?php else: ?>
        <span class="stock-status status-out"><i class="fas fa-times"></i> Out of Stock</span>
      <?php endif; ?>
    </p>

    <p class="description">
      <?php echo nl2br($details['description'] ?? 'No description'); ?>
    </p>

    <div class="size-selection">
  <h3>Select Ring Size</h3>
  <select id="ring-size" required <?php echo (int)$product['stock'] <= 0 ? 'disabled' : ''; ?>>
    <option value="" disabled selected>-- <?php echo (int)$product['stock'] > 0 ? 'Choose size' : 'Unavailable'; ?> --</option>
    <option value="5">Size 5</option>
    <option value="6">Size 6</option>
    <option value="7">Size 7</option>
    <option value="8">Size 8</option>
    <option value="9">Size 9</option>
  </select>
</div>

    <ul class="product-specs">
      <?php if (!empty($details['material'])): ?>
        <li><span>Material:</span> <span><?php echo $details['material']; ?></span></li>
      <?php endif; ?>

      <?php if (!empty($details['design'])): ?>
        <li><span>Design:</span> <span><?php echo $details['design']; ?></span></li>
      <?php endif; ?>

      <?php if (!empty($details['stone'])): ?>
        <li><span>Stone:</span> <span><?php echo $details['stone']; ?></span></li>
      <?php endif; ?>

      <?php if (!empty($details['color'])): ?>
        <li><span>Color:</span> <span><?php echo $details['color']; ?></span></li>
      <?php endif; ?>

      <?php if (!empty($details['brand'])): ?>
        <li><span>Brand:</span> <span><?php echo $details['brand']; ?></span></li>
      <?php endif; ?>
    </ul>

    <div class="action-buttons">
      <?php if ((int)$product['stock'] > 0): ?>
        <button class="add-to-cart"
          onclick="addToCart('<?php echo $product['id']; ?>','<?php echo addslashes($product['name']); ?>')">
          <i class="fas fa-shopping-cart"></i>
          Add to cart
        </button>

        <a href="#" class="buy-now" onclick="buyNow(event, '<?php echo $product['id']; ?>')">
          <i class="fas fa-bolt"></i>
          Buy now
        </a>
      <?php else: ?>
        <button class="btn-out-of-stock" disabled>
          <i class="fas fa-ban"></i>
          Currently Out of Stock
        </button>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php else: ?>
  <h2 style="text-align:center; margin-top:100px;">Product not found</h2>
<?php endif; ?>

<!-- 🔥 NOTIFICATION -->
<div class="cart-notification" id="cart-notification">
  <div class="notification-content">
    <div class="notification-icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <div class="notification-text">
      <h3>Added to cart!</h3>
      <p>The product has been added to your shopping cart.</p>
    </div>
    <button onclick="closeNotification()">×</button>
  </div>
</div>

<script>
function showNotification(name) {
  const noti = document.getElementById('cart-notification');
  noti.querySelector('h3').textContent = `"${name}" added to cart!`;
  noti.classList.add('show');

  setTimeout(() => {
    noti.classList.remove('show');
  }, 3000);
}

function closeNotification() {
  document.getElementById('cart-notification').classList.remove('show');
}

async function addToCart(id, name) {
  const sizeSelect = document.getElementById('ring-size');
  const selectedSize = sizeSelect.value;

  if (!selectedSize) {
    alert('Please select a ring size before adding to cart.');
    return;
  }

  const btn = document.querySelector('.add-to-cart');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

  try {
    const url = `../users/cart.php?action=add&id=${encodeURIComponent(id)}&size=${encodeURIComponent(selectedSize)}`;
    await fetch(url);
    
    showNotification(`${name} (Size ${selectedSize})`);
  } catch (err) {
    alert('Network error, please try again.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to cart';
  }
}

function applyHeaderSearch() {
  const kw = document.getElementById('header-search').value.trim();
  if (kw) {
    window.location.href = `products_sp.php?q=${encodeURIComponent(kw)}`;
  } else {
    window.location.href = `products_sp.php`;
  }
}

async function buyNow(event, id) {
  event.preventDefault();
  const sizeSelect = document.getElementById('ring-size');
  const selectedSize = sizeSelect.value;

  if (!selectedSize) {
    alert('Please select a ring size before buying.');
    return;
  }

  const btn = document.querySelector('.buy-now');
  btn.style.pointerEvents = 'none';
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

  try {
    const url = `../users/cart.php?action=add&id=${encodeURIComponent(id)}&size=${encodeURIComponent(selectedSize)}`;
    await fetch(url);
    
    window.location.href = '../users/order_confirm.php';
  } catch (err) {
    alert('Network error, please try again.');
    btn.style.pointerEvents = 'auto';
    btn.innerHTML = '<i class="fas fa-bolt"></i> Buy now';
  }
}
</script>

</body>
</html>