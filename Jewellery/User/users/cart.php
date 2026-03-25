<?php
session_start();
require __DIR__ . "/../../config/config.php";

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Kết nối đến database sản phẩm (jewelry_db)
$conn_products = new mysqli('localhost', 'root', '', 'jewelry_db');
if ($conn_products->connect_error) {
    die("Kết nối database sản phẩm thất bại: " . $conn_products->connect_error);
}
$conn_products->set_charset("utf8mb4");

// Lấy giỏ hàng từ user_db
$sql_cart = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
$stmt = $conn_user->prepare($sql_cart);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$product_ids = [];
while ($row = $result->fetch_assoc()) {
    $cart_items[$row['product_id']] = $row['quantity'];
    $product_ids[] = $row['product_id'];
}

// Nếu có sản phẩm, lấy thông tin từ jewelry_db
if (!empty($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('s', count($product_ids)); // product_id là string
    $sql_products = "SELECT id, name, price, image FROM products WHERE id IN ($placeholders)";
    $stmt_prod = $conn_products->prepare($sql_products);
    $stmt_prod->bind_param($types, ...$product_ids);
    $stmt_prod->execute();
    $result_prod = $stmt_prod->get_result();

    $products_info = [];
    while ($prod = $result_prod->fetch_assoc()) {
        $products_info[$prod['id']] = $prod;
    }

    // Tạo mảng cart_items đầy đủ
    $full_cart = [];
    $total = 0;
    foreach ($cart_items as $pid => $qty) {
        if (isset($products_info[$pid])) {
            $item = $products_info[$pid];
            $item['quantity'] = $qty;
            $item['item_total'] = $item['price'] * $qty;
            $total += $item['item_total'];
            $full_cart[] = $item;
        }
    }
    $cart_items = $full_cart;
} else {
    $cart_items = [];
    $total = 0;
}

$conn_products->close();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart | 36 Jewelry</title>

<link rel="stylesheet" href="jewelry-cart.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* ===== GIỮ NGUYÊN CSS CỦA BẠN ===== */
<?php echo file_get_contents("jewelry-cart.css"); ?>
</style>

</head>
<body>

<!-- HEADER -->
<header class="header-container">
  <div class="search-bar">
    <div class="left">
      <a href="../index_profile.php" class="home-btn"><i class="fas fa-home"></i> Home</a>
    </div>

    <div class="center">
      <a href="../index.php">
        <img src="../images/36-logo.png" class="header-logo">
      </a>

      <div class="search-box">
        <input type="text" placeholder="Search products...">
        <button onclick="window.location.href='search.php'">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>

    <div class="right">
      <a href="Jewelry-cart.php" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
      <a href="profile.php" class="icon-link"><i class="fas fa-user"></i></a>
    </div>
  </div>
</header>

<main class="main-content">
  <button class="back-button" onclick="window.history.back()">←</button>

  <h2>Your Shopping Cart</h2>

  <div id="alert-message" class="alert-message"></div>

  <table class="cart-table">
    <thead>
      <tr>
        <th>Product</th>
        <th>Price</th>
        <th>Quantity</th>
        <th>Total</th>
        <th>Action</th>
      </tr>
    </thead>

    <tbody id="cart-body">
    <?php if (count($cart_items) > 0): ?>
        <?php foreach ($cart_items as $item): ?>
          <tr>
          <td class="product-info">
            <img src="../images/<?php echo htmlspecialchars($item['image']); ?>" class="product-thumb">
            <span><?php echo htmlspecialchars($item['name']); ?></span>
          </td>
          <td class="price">$<?php echo number_format($item['price'],2); ?></td>
          <td class="quantity-cell">
            <input type="number" 
                   value="<?php echo $item['quantity']; ?>" 
                   min="1" 
                   class="quantity-input"
                   data-id="<?php echo $item['id']; ?>">
          </td>
          <td class="item-total">$<?php echo number_format($item['item_total'],2); ?></td>
          <td class="action-cell">
            <button class="view-details-btn"
              onclick="location.href='product.php?id=<?php echo $item['id']; ?>'">
              View
            </button>
            <button class="remove-btn" data-id="<?php echo $item['id']; ?>">
              Remove
            </button>
          </td>
          </tr>
        <?php endforeach; ?>
    <?php else: ?>
          <tr><td colspan="5">Cart is empty</td></tr>
    <?php endif; ?>
    </tbody>
  </table>

  <div class="cart-summary">
    <p class="total">
      Total: <strong id="cart-total">$<?php echo number_format($total,2); ?></strong>
    </p>
    <a href="order_confirm.php" class="checkout-btn">Proceed to Checkout</a>
  </div>
</main>

<footer class="footer">
  <p>&copy; 2025 36 Jewelry. All rights reserved.</p>
</footer>

<script>
function updateTotals() {
  let total = 0;
  document.querySelectorAll('#cart-body tr').forEach(row => {
    const price = parseFloat(row.querySelector('.price').textContent.replace('$','').replace(',',''));
    const qty = parseInt(row.querySelector('.quantity-input').value);
    const itemTotal = price * qty;
    row.querySelector('.item-total').textContent = '$' + itemTotal.toFixed(2);
    total += itemTotal;
  });
  document.getElementById('cart-total').textContent = '$' + total.toFixed(2);
}

function showAlert(message) {
  const alertBox = document.getElementById('alert-message');
  alertBox.textContent = message;
  alertBox.style.opacity = '1';
  alertBox.style.transform = 'translateY(0)';
  setTimeout(() => {
    alertBox.style.opacity = '0';
    alertBox.style.transform = 'translateY(-20px)';
  }, 2000);
}

/* UPDATE QUANTITY */
document.querySelectorAll('.quantity-input').forEach(input => {
  input.addEventListener('change', function() {
    const id = this.dataset.id;
    const qty = this.value;
    fetch('update_cart.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: `id=${id}&quantity=${qty}`
    });
    updateTotals();
    showAlert("Updated quantity!");
  });
});

/* REMOVE ITEM */
document.querySelectorAll('.remove-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const id = this.dataset.id;
    fetch('remove_cart.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: `id=${id}`
    });
    this.closest('tr').remove();
    updateTotals();
    showAlert("Removed!");
  });
});

updateTotals();
</script>

</body>
</html>