<?php


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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $product['name'] ?? 'Product'; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="pr_detail.css">
</head>

<body>

<!-- 🔥 HEADER -->
<header class="header-container">
  <div class="search-bar">

    <div class="left">
      <a href="../indexprofile.php" class="home-btn">
        <i class="fas fa-home"></i> Home
      </a>
    </div>

    <div class="center">
      <a href="../indexprofile.php">
        <img src="../../images/36-logo.png" class="header-logo">
      </a>

      <div class="search-box">
        <input type="text" placeholder="Search products...">
        <button onclick="window.location.href='../search.html'">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>

    <div class="right">
      <a href="../users/cart.php" class="icon-link">
        <i class="fas fa-shopping-cart"></i>
      </a>
      <a href="../users/profile.php" class="icon-link">
        <i class="fas fa-user"></i>
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
    </p>

    <p class="description">
      <?php echo nl2br($details['description'] ?? 'No description'); ?>
    </p>

    <div class="size-selection">
  <h3>Select Ring Size</h3>
  <select id="ring-size" required>
    <option value="" disabled selected>-- Choose size --</option>
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
      <button class="add-to-cart"
        onclick="addToCart('<?php echo $product['id']; ?>','<?php echo addslashes($product['name']); ?>')">
        <i class="fas fa-shopping-cart"></i>
        Add to cart
      </button>

      <a href="../users/order_confirm.php" class="buy-now">
        <i class="fas fa-bolt"></i>
        Buy now
      </a>
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
</script>

</body>
</html>