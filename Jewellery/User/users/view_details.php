<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Định nghĩa hằng TRƯỚC khi dùng
if (!defined('BASE_URL')) define('BASE_URL', '/Jewellery/');
if (!defined('IMG_URL'))  define('IMG_URL', BASE_URL . 'images/');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'User/users/login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Lấy order_id từ URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    die("Invalid order ID.");
}

// Lấy thông tin đơn hàng, kiểm tra quyền sở hữu
$stmt = $conn->prepare("
    SELECT o.id, o.order_number, o.order_date, o.total_amount, o.status,
           c.full_name, c.address, c.phone
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = ? AND c.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found or you don't have permission to view it.");
}

// Lấy danh sách sản phẩm trong đơn hàng
$stmt = $conn->prepare("
    SELECT oi.product_id, oi.product_name, oi.quantity, oi.unit_price, oi.total_price,
           p.image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Link helpers
$link_home    = BASE_URL . 'User/indexprofile.php';
$link_cart    = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_logout  = BASE_URL . 'User/users/logout.php';
$link_search  = BASE_URL . 'User/Search/search.html';
$link_history = BASE_URL . 'User/users/history.php';

// Định dạng ngày
$order_date = date('Y-m-d', strtotime($order['order_date']));

// Xác định class cho status
$status_class = '';
$status_text = '';
switch ($order['status']) {
    case 'Pending':   $status_class = 'pending';   $status_text = 'Pending'; break;
    case 'Processed': $status_class = 'completed'; $status_text = 'Processed'; break;
    case 'Delivered': $status_class = 'completed'; $status_text = 'Delivered'; break;
    case 'Cancelled': $status_class = 'cancelled'; $status_text = 'Cancelled'; break;
    default:          $status_class = 'pending';   $status_text = $order['status'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Details | 36 Jewelry</title>
<meta name="description" content="View details of your 36 Jewelry order.">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  * { margin:0; padding:0; box-sizing:border-box; font-family:"Cormorant Garamond", serif; }

  body {
    background: linear-gradient(rgba(255,255,255,0.7), rgba(255,255,255,0.7)),
                url("<?= IMG_URL ?>profile background.jpg") no-repeat center center/cover;
    min-height:100vh;
    display:flex;
    flex-direction:column;
    align-items:center;
    color:#333;
    opacity:0;
    animation: fadeInBody 1s forwards;
  }

  @keyframes fadeInBody { to { opacity:1; } }

  .main-content {
    width:90%; max-width:900px;
    background: rgba(255,255,255,0.95);
    border-radius:20px;
    margin-top:60px;
    padding:30px 40px;
    box-shadow:0 8px 25px rgba(0,0,0,0.15);
    animation: fadeInUp 1s ease forwards;
    transform: translateY(20px);
    opacity:0;
    position: relative;
  }

  @keyframes fadeInUp { to { transform:translateY(0); opacity:1; } }

  .back-button {
    position: absolute;
    top: 20px;
    left: 20px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #b8860b, #d4a017);
    border-radius: 30px;
    text-decoration: none;
    box-shadow: 0 4px 10px rgba(184, 134, 11, 0.3);
    transition: all 0.3s ease;
    border: none;
    outline: none;
    cursor: pointer;
  }

  .back-button i { font-size: 16px; }

  .back-button:hover {
    transform: translateX(-3px) scale(1.05);
    box-shadow: 0 6px 15px rgba(184, 134, 11, 0.4);
    background: linear-gradient(135deg, #c89715, #e0b83f);
  }

  h2 { text-align:center; color:#b8860b; font-size:28px; margin-bottom:30px; }

  .order-info p { font-size:16px; margin-bottom:8px; }
  .order-info span { font-weight:600; }

  .product-item { display:flex; gap:20px; margin-bottom:25px; padding-bottom:15px; border-bottom:1px solid #ddd; }
  .product-thumb { width:100px; height:100px; object-fit:cover; border-radius:10px; }
  .product-details { flex:1; }
  .product-details h3 { color:#b8860b; font-size:20px; margin-bottom:8px; }
  .product-details p { font-size:15px; margin-bottom:5px; }
  .product-details span { font-weight:600; }

  .status { font-weight:600; padding:5px 10px; border-radius:8px; color:#fff; }
  .status.pending { background-color:#ff9800; }
  .status.completed { background-color:#4caf50; }
  .status.cancelled { background-color:#f44336; }

  .total { text-align:right; font-size:18px; font-weight:600; color:#222; }
  .total strong { color:#b8860b; }

  .footer { margin-top:40px; text-align:center; font-size:14px; color:#555; padding:20px 0; }

  /* Header styling - giống với các file khác */
  .header-container {
    width: 100%;
    position: sticky;
    top: 0;
    z-index: 1000;
    background-color: #ffffff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  }
  .search-bar {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    background: #ffffff;
    border-top: 1px solid rgba(0,0,0,0.03);
    border-bottom: 1px solid rgba(0,0,0,0.04);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
  }
  .search-bar .left, .search-bar .center, .search-bar .right { display: flex; align-items: center; }
  .search-bar .left { width: auto; justify-content: flex-start; }
  .search-bar .right { justify-content: flex-end; width: auto; }
  .search-bar .center { flex: 1 1 auto; justify-content: center; gap: 30px; position: relative; }
  .home-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    color: #111111;
    font-weight: 600;
    font-size: 16px;
    transition: background-color 0.2s;
  }
  .home-btn i { margin-right: 5px; font-size: 18px; }
  .home-btn:hover { background: #f9f9f9; color: #b8860b; }
  .search-bar .center a {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
  }
  .search-bar .center .header-logo { height: 55px; max-width: 180px; object-fit: contain; }
  .search-box {
    flex: 0 1 450px;
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f9f9f9;
    padding: 4px 8px;
    border-radius: 999px;
    border: 1px solid rgba(0,0,0,0.06);
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.03);
    height: 50px;
  }
  .search-box input { flex: 1; border: 0; outline: 0; background: transparent; padding: 2px 6px; font-size: 15px; color: #111; }
  .search-box button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    background: #b8860b;
    color: #fff;
    padding: 8px 12px;
    border-radius: 999px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s;
  }
  .search-box button:hover { background: #996600; }
  .icon-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 8px;
    text-decoration: none;
    color: #111;
    transition: all 0.18s;
    font-size: 18px;
  }
  .icon-link:hover { background: rgba(186,134,11,0.08); color: #b8860b; }
  @media (max-width: 768px) {
    .search-bar { flex-direction: column; align-items: center; gap: 10px; }
    .search-box { width: 100%; margin-left: 0; }
  }
</style>
</head>
<body class="homepage bg-accent-light">

<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="<?= $link_home ?>" class="home-btn"><i class="fas fa-home"></i> Home</a>
    </div>
    <div class="center">
      <a href="<?= $link_home ?>">
        <img src="<?= IMG_URL ?>36-logo.png" alt="Jewelry Store Logo" class="header-logo">
      </a>
      <div class="search-box">
        <input type="text" id="search-input" placeholder="Search products...">
        <button onclick="doSearch()"><i class="fas fa-search"></i></button>
      </div>
    </div>
    <div class="right">
      <a href="<?= $link_cart ?>" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
      <a href="<?= $link_profile ?>" class="icon-link"><i class="fas fa-user"></i></a>
    </div>
  </div>
</header>

<main class="main-content">
  <button class="back-button" onclick="window.history.back();">&#8592;</button>
  
  <h2>Order #<?= htmlspecialchars($order['order_number']) ?> Details</h2>

  <div class="order-info">
    <p>Order Date: <span><?= $order_date ?></span></p>
    <p>Status: <span class="status <?= $status_class ?>"><?= $status_text ?></span></p>
    <p>Shipping To: <span><?= htmlspecialchars($order['address']) ?></span></p>
    <?php if (!empty($order['phone'])): ?>
      <p>Phone: <span><?= htmlspecialchars($order['phone']) ?></span></p>
    <?php endif; ?>
  </div>

  <?php foreach ($items as $item): ?>
  <div class="product-item">
    <?php
      $image_src = !empty($item['image']) ? IMG_URL . $item['image'] : IMG_URL . 'placeholder.jpg';
    ?>
    <img src="<?= $image_src ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="product-thumb">
    <div class="product-details">
      <h3><?= htmlspecialchars($item['product_name']) ?></h3>
      <p>Price: <span>$<?= number_format($item['unit_price'], 2) ?></span></p>
      <p>Quantity: <span><?= $item['quantity'] ?></span></p>
      <p>Total: <span>$<?= number_format($item['total_price'], 2) ?></span></p>
      <p>Description: A beautiful piece from 36 Jewelry collection, crafted with care.</p>
    </div>
  </div>
  <?php endforeach; ?>

  <p class="total">Order Total: <strong>$<?= number_format($order['total_amount'], 2) ?></strong></p>
</main>

<footer class="footer">
  <p>&copy; 2025 36 Jewelry. All rights reserved.</p>
</footer>

<script>
  function doSearch() {
    const keyword = document.getElementById('search-input').value.trim();
    if (keyword !== '') {
      window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(keyword);
    }
  }
</script>

</body>
</html>
