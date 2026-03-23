<?php
// Kết nối database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "shop_db";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Lấy id từ URL
$id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

if (empty($id)) {
    header("Location: product.html");
    exit;
}

// Query sản phẩm
$sql = "SELECT id, name, price AS cost_price, profit_percent, stock, image,
               description, material, design, stone, color, brand, gender
        FROM products
        WHERE id = '$id'
        LIMIT 1";

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    header("Location: product.html");
    exit;
}

$p = $result->fetch_assoc();

// Tính giá bán (giống products.php)
$product_id    = $p['id'];
$current_stock = (int)$p['stock'];
$current_cost  = (float)$p['cost_price'];
$profit_percent = isset($p['profit_percent']) ? (float)$p['profit_percent'] : 0;

$total_quantity = $current_stock;
$total_cost     = $current_cost * $current_stock;

$sql_receipt = "SELECT quantity, unit_price FROM goods_receipt_items WHERE product_id = '$product_id'";
$result_receipt = $conn->query($sql_receipt);

if ($result_receipt && $result_receipt->num_rows > 0) {
    while ($row = $result_receipt->fetch_assoc()) {
        $total_cost     += (int)$row['quantity'] * (float)$row['unit_price'];
        $total_quantity += (int)$row['quantity'];
    }
}

$avg_cost   = $total_quantity > 0 ? $total_cost / $total_quantity : $current_cost;
$sale_price = round($avg_cost * (1 + $profit_percent / 100), 2);

// Dữ liệu hiển thị
$name        = htmlspecialchars($p['name']);
$image       = !empty($p['image']) ? htmlspecialchars($p['image']) : 'placeholder.png';
$description = htmlspecialchars($p['description'] ?? '');
$material    = htmlspecialchars($p['material'] ?? '');
$design      = htmlspecialchars($p['design'] ?? '');
$stone       = htmlspecialchars($p['stone'] ?? '');
$color       = htmlspecialchars($p['color'] ?? '');
$brand       = htmlspecialchars($p['brand'] ?? 'ThirtySix Jewellery');

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $name ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="pr_detail.css">
</head>

<body>

<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="../index_profile.html" class="home-btn"><i class="fas fa-home"></i> Home</a>
    </div>
    <div class="center">
      <a href="../index.html">
        <img src="../images/36-logo.png" alt="Jewelry Store Logo" class="header-logo">
      </a>
      <div class="search-box">
        <input type="text" placeholder="Search products...">
        <button onclick="window.location.href='search.html'">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>
    <div class="right">
      <a href="Jewelry-cart.html" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
      <a href="profile.html" class="icon-link"><i class="fas fa-user"></i></a>
    </div>
  </div>
</header>

<!-- THÔNG BÁO THÊM VÀO GIỎ HÀNG -->
<div class="cart-notification" id="cart-notification">
  <div class="notification-content">
    <div class="notification-icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <div class="notification-text">
      <h3>Added to cart!</h3>
      <p>The product has been added to your shopping cart.</p>
    </div>
    <button class="close-notification" onclick="closeNotification()">
      <i class="fas fa-times"></i>
    </button>
  </div>
</div>

<div class="product-detail">

  <button class="back-btn" onclick="window.history.back()">←</button>

  <div class="product-image">
    <img src="../images/<?= $image ?>" alt="<?= $name ?>">
  </div>

  <div class="product-info">
    <h1><?= $name ?></h1>
    <p class="price">$<?= number_format($sale_price, 2) ?></p>

    <?php if (!empty($description)): ?>
    <p class="description"><?= $description ?></p>
    <?php endif; ?>

    <!-- CHỌN SIZE -->
    <div class="size-selection">
      <h3>Select Ring Size</h3>
      <select name="ring-size" id="ring-size">
        <option value="5">Size 5 (12cm - 15cm)</option>
        <option value="6">Size 6 (15cm - 18cm)</option>
        <option value="7">Size 7 (18cm - 21cm)</option>
        <option value="8">Size 8 (21cm - 24cm)</option>
      </select>
    </div>

    <ul class="product-specs">
      <?php if (!empty($material)): ?>
      <li><span>Material:</span> <span><?= $material ?></span></li>
      <?php endif; ?>
      <?php if (!empty($design)): ?>
      <li><span>Design:</span> <span><?= $design ?></span></li>
      <?php endif; ?>
      <?php if (!empty($stone)): ?>
      <li><span>Stone:</span> <span><?= $stone ?></span></li>
      <?php endif; ?>
      <?php if (!empty($color)): ?>
      <li><span>Color:</span> <span><?= $color ?></span></li>
      <?php endif; ?>
      <li><span>Brand:</span> <span><?= $brand ?></span></li>
    </ul>

    <div class="action-buttons">
      <button class="add-to-cart" onclick="showNotification()">
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

<script>
  function showNotification() {
    const notification = document.getElementById('cart-notification');
    notification.classList.add('show');
    setTimeout(() => {
      notification.classList.remove('show');
    }, 3000);
  }

  function closeNotification() {
    const notification = document.getElementById('cart-notification');
    notification.classList.remove('show');
  }
</script>

</body>
</html>
