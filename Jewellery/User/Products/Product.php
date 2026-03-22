<?php
// -------------------------
// product.php (Full & Giao diện giống HTML tĩnh)
// -------------------------

// Kết nối database
$host = "localhost";
$user = "root";
$pass = "";
$db = "shop_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// -------------------------
// Lấy sản phẩm và tính giá nhập bình quân
// -------------------------

$sql_products = "SELECT p.id, p.name, p.price AS cost_price, p.profit_percent, p.stock, p.image
                 FROM products p";
$result_products = $conn->query($sql_products);

$products = [];
if ($result_products->num_rows > 0) {
    while ($product = $result_products->fetch_assoc()) {
        $current_stock = (int)$product['stock'];
        $current_cost = (float)$product['cost_price'];

        $sql_receipt = "SELECT quantity, unit_price FROM goods_receipt_items WHERE product_id = '".$product['id']."'";
        $result_receipt = $conn->query($sql_receipt);

        $total_quantity = $current_stock;
        $total_cost = $current_cost * $current_stock;

        if ($result_receipt->num_rows > 0) {
            while ($row = $result_receipt->fetch_assoc()) {
                $qty_new = (int)$row['quantity'];
                $price_new = (float)$row['unit_price'];
                $total_cost += $qty_new * $price_new;
                $total_quantity += $qty_new;
            }
        }

        $avg_cost = $total_quantity > 0 ? $total_cost / $total_quantity : 0;
        $sale_price = round($avg_cost * (1 + $product['profit_percent'] / 100), 2);

        $product['sale_price'] = $sale_price;
        // Nếu ảnh rỗng thì hiển thị placeholder
        if(empty($product['image'])) $product['image'] = 'placeholder.png';

        $products[] = $product;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Products</title>
  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="../Product.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="homepage bg-accent-light">

<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="../../index_profile.html" class="home-btn"><i class="fas fa-home"></i> Home</a>
    </div>
    <div class="center">
      <a href="../../index.html">
        <img src="../../images/36-logo.png" alt="Jewelry Store Logo" class="header-logo">
      </a>
      <div class="search-box">
        <input type="text" placeholder="Search products...">
        <button onclick="window.location.href='../search.html'">
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

<section id="page-banner" class="padding-large no-padding-top position-relative">
  <div class="page-container">
    
    <div class="category-and-filter-bar">
        <nav class="filter-nav">
          <a href="product.php" class="active">All</a>
          <a href="product_male.php">Male</a>
          <a href="product_female.php">Female</a>
          <a href="product_unisex.php">Unisex</a>
        </nav>
    </div>

    <div class="products-grid">
      <?php foreach($products as $product): ?>
      <article class="product-card">
        <a href="R01.php?id=<?= $product['id'] ?>" class="product-card-link">
          <div class="image-holder">
            <img src="../../images/<?= $product['image'] ?>" alt="<?= $product['name'] ?>" class="product-image">
          </div>
          <h3 class="product-title"><?= $product['name'] ?></h3>
          <span class="product-price">$<?= number_format($product['sale_price'], 2) ?></span>
        </a>
        <button class="btn-add" onclick="showNotification('<?= addslashes($product['name']) ?>')">Add to Cart</button>
      </article>
      <?php endforeach; ?>
    </div>

    <div class="pagination">
      <a href="#" class="disabled"><</a>
      <a href="#" class="active">1</a>
      <a href="#">2</a>
      <a href="#">3</a>
      <a href="#">></a>
    </div>
  </div>
</section>

<footer id="footer" class="overflow-hidden">
  <div class="container mt-5">
    <div class="footer-top-area">
      <div class="row d-flex flex-wrap justify-content-between">
        <div class="col-lg-3 col-sm-6">
          <div class="footer-menu menu-001">
            <img src="../../images/36-logo.png" alt="logo">
            <p>Questions? Contact us at <a href="mailto:yourinfo@gmail.com">yourinfo@gmail.com</a></p>
          </div>
        </div>
        <div class="col-lg-2 col-sm-6">
          <div class="footer-menu menu-002">
            <h5 class="widget-title">Quick Links</h5>
            <ul class="menu-list list-unstyled text-uppercase">
              <li class="menu-item"><a href="index.html">Home</a></li>
              <li class="menu-item"><a href="product.php">Products</a></li>
              <li class="menu-item"><a href="#">Contact</a></li>
            </ul>
          </div>
        </div>
        <div class="col-lg-3 col-sm-6">
          <div class="footer-menu text-uppercase menu-003">
            <h5 class="widget-title">Help & Info</h5>
            <ul class="menu-list list-unstyled">
              <li class="menu-item"><a href="#">Shipping</a></li>
              <li class="menu-item"><a href="#">Returns</a></li>
              <li class="menu-item"><a href="#">Faqs</a></li>
            </ul>
          </div>
        </div>
        <div class="col-lg-3 col-sm-6">
          <div class="footer-menu contact-item menu-004">
            <h5 class="widget-title">Contact Us</h5>
            <p>If you need support? Call +55 111 222 333</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</footer>

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

<script>
function showNotification(productName) {
  const notification = document.getElementById('cart-notification');
  const textEl = notification.querySelector('.notification-text h3');
  textEl.textContent = `"${productName}" added to cart!`;
  notification.classList.add('show');
  clearTimeout(notification.timeout);
  notification.timeout = setTimeout(() => {
    notification.classList.remove('show');
  }, 3000);
}
function closeNotification() {
  const notification = document.getElementById('cart-notification');
  notification.classList.remove('show');
  clearTimeout(notification.timeout);
}
document.querySelectorAll('.pagination a').forEach(link => {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    if (this.classList.contains('disabled')) return;
    document.querySelectorAll('.pagination a').forEach(a => a.classList.remove('active'));
    this.classList.add('active');
  });
});
</script>

</body>
</html>