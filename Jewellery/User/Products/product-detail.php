<?php
$conn = new mysqli("localhost", "root", "", "shop_db");
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
    $product_id = $product['id'];
    $current_stock = (int)$product['stock'];
    $current_cost = (float)$product['price'];
    $profit_percent = (float)$product['profit_percent'];

    $total_quantity = $current_stock;
    $total_cost = $current_cost * $current_stock;

    $sql_receipt = "SELECT quantity, unit_price 
                    FROM goods_receipt_items 
                    WHERE product_id = '$product_id'";

    $result_receipt = $conn->query($sql_receipt);

    while ($row = $result_receipt->fetch_assoc()) {
        $total_cost += $row['quantity'] * $row['unit_price'];
        $total_quantity += $row['quantity'];
    }

    $avg_cost = ($total_quantity > 0) ? $total_cost / $total_quantity : $current_cost;
    $sale_price = round($avg_cost * (1 + $profit_percent / 100), 2);
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
      <a href="../../index_profile.html" class="home-btn">
        <i class="fas fa-home"></i> Home
      </a>
    </div>

    <div class="center">
      <a href="../../index.html">
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
      <a href="../Jewelry-cart.html" class="icon-link">
        <i class="fas fa-shopping-cart"></i>
      </a>
      <a href="../profile.html" class="icon-link">
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
      <select>
        <option value="5">Size 5</option>
        <option value="6">Size 6</option>
        <option value="7">Size 7</option>
        <option value="8">Size 8</option>
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
        onclick="addToCart('<?php echo $product['id']; ?>','<?php echo $product['name']; ?>')">
        <i class="fas fa-shopping-cart"></i>
        Add to cart
      </button>

      <a href="order_confirm.html" class="buy-now">
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

function addToCart(id, name) {
  showNotification(name);
}
</script>

</body>
</html>