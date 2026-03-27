<?php
session_start();
require_once "../../config/config.php";
define('BASE_URL', '/do_an_web/Jewellery/');
define('IMG_URL', BASE_URL . 'images/');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "User/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Xử lý AJAX
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action'];

    if ($_POST['ajax_action'] === 'update' && isset($_POST['product_id'], $_POST['quantity'])) {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        if ($product_id > 0 && $quantity >= 1) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $product_id);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                // Chưa có thì thêm mới
                $stmt2 = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt2->bind_param("iii", $user_id, $product_id, $quantity);
                $stmt2->execute();
                $stmt2->close();
            }
            $stmt->close();
            $response = ['success' => true, 'message' => 'Updated'];
        } else {
            $response = ['success' => false, 'message' => 'Invalid data'];
        }
    } elseif ($_POST['ajax_action'] === 'remove' && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        if ($product_id > 0) {
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $stmt->close();
            $response = ['success' => true, 'message' => 'Removed'];
        } else {
            $response = ['success' => false, 'message' => 'Invalid product'];
        }
    }

    echo json_encode($response);
    exit;
}

// Lấy giỏ hàng
$sql_cart = "SELECT product_id, quantity FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($sql_cart);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->execute();

$stmt->bind_result($product_id, $quantity);

$cart_items = [];
$product_ids = [];

while ($stmt->fetch()) {
    $cart_items[$product_id] = $quantity;
    $product_ids[] = $product_id;
}

if (!empty($product_ids)) {
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('i', count($product_ids));
    $sql_products = "SELECT id, name, price, image FROM products WHERE id IN ($placeholders)";
    $stmt_prod = $conn->prepare($sql_products);
    $stmt_prod->bind_param($types, ...$product_ids);
    $stmt_prod->execute();
    $result_prod = $stmt_prod->get_result();

    $products_info = [];
    while ($prod = $result_prod->fetch_assoc()) {
        $products_info[$prod['id']] = $prod;
    }

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

$stmt->close();
$conn->close();


// Link helpers
$link_home    = BASE_URL . 'User/index.php';
$link_login   = BASE_URL . 'User/users/Login.php';
$link_cart    = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_logout  = BASE_URL . 'User/users/logout.php';
$link_detail  = BASE_URL . 'User/users/product_detail.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | 36 Jewelry</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/normalize.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>fonts/icomoon.css">
    <link href="<?= BASE_URL ?>bootstrap-5.3.0-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content { max-width: 1200px; margin: 40px auto; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        .back-button { background: none; border: none; font-size: 1.5rem; cursor: pointer; margin-bottom: 20px; }
        .cart-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .cart-table th, .cart-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .product-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; margin-right: 10px; vertical-align: middle; }
        .quantity-input { width: 60px; padding: 5px; text-align: center; }
        .remove-btn, .view-details-btn { padding: 5px 10px; margin: 0 5px; border: none; border-radius: 4px; cursor: pointer; }
        .remove-btn { background: #dc3545; color: white; }
        .view-details-btn { background: #007bff; color: white; }
        .cart-summary { text-align: right; margin-top: 20px; }
        .total { font-size: 1.2rem; margin-bottom: 15px; }
        .checkout-btn { display: inline-block; background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; }
        .alert-message { position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; opacity: 0; transition: opacity 0.3s, transform 0.3s; transform: translateY(-20px); z-index: 1000; }
    </style>
</head>
<body>

<header class="header-container">
    <div class="search-bar">
        <div class="left"><a href="<?= $link_home ?>" class="home-btn"><i class="fas fa-home"></i> Home</a></div>
        <div class="center">
            <a href="<?= $link_home ?>"><img src="<?= IMG_URL ?>36-logo.png" class="header-logo" style="width:80px;height:auto;"></a>
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Search products..." onkeydown="if(event.key==='Enter') doSearch()">
                <button onclick="doSearch()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <div class="right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="<?= $link_cart ?>" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
                <a href="<?= $link_profile ?>" class="icon-link"><i class="fas fa-user"></i><span class="ms-1 d-none d-md-inline"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span></a>
                <a href="<?= $link_logout ?>" class="icon-link"><i class="fas fa-sign-out-alt"></i></a>
            <?php else: ?>
                <a href="<?= $link_login ?>" class="icon-link"><i class="fas fa-shopping-cart"></i></a>
                <a href="<?= $link_login ?>" class="icon-link"><i class="fas fa-user"></i></a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="main-content">
    <button class="back-button" onclick="window.history.back()">←</button>
    <h2>Your Shopping Cart</h2>

    <div id="alert-message" class="alert-message"></div>

    <table class="cart-table">
        <thead>
            <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr>
        </thead>
        <tbody id="cart-body">
        <?php if (count($cart_items) > 0): ?>
            <?php foreach ($cart_items as $item): ?>
            <tr data-product-id="<?= $item['id'] ?>">
                <td class="product-info">
                    <img src="<?= IMG_URL . $item['image'] ?>" class="product-thumb" onerror="this.src='<?= IMG_URL ?>placeholder.jpg'">
                    <span><?= htmlspecialchars($item['name']) ?></span>
                </td>
                <td class="price">$<?= number_format($item['price'],2) ?></td>
                <td class="quantity-cell">
                    <input type="number" value="<?= $item['quantity'] ?>" min="1" class="quantity-input" data-id="<?= $item['id'] ?>">
                </td>
                <td class="item-total">$<?= number_format($item['item_total'],2) ?></td>
                <td class="action-cell">
                    <button class="view-details-btn" onclick="location.href='<?= $link_detail ?>?id=<?= $item['id'] ?>'">View</button>
                    <button class="remove-btn" data-id="<?= $item['id'] ?>">Remove</button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">Cart is empty</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="cart-summary">
        <p class="total">Total: <strong id="cart-total">$<?= number_format($total,2) ?></strong></p>
        <a href="order_confirm.php" class="checkout-btn">Proceed to Checkout</a>
    </div>
</main>

<footer class="footer"><p>&copy; 2025 36 Jewelry. All rights reserved.</p></footer>

<script src="<?= BASE_URL ?>js/jquery-1.11.0.min.js"></script>
<script>
function doSearch() {
    let kw = document.getElementById('search-input').value.trim();
    if (kw) window.location.href = '<?= BASE_URL ?>User/users/search.php?q=' + encodeURIComponent(kw);
}

function showAlert(message, isError = false) {
    const alertBox = document.getElementById('alert-message');
    alertBox.textContent = message;
    alertBox.style.backgroundColor = isError ? '#dc3545' : '#28a745';
    alertBox.style.opacity = '1';
    alertBox.style.transform = 'translateY(0)';
    setTimeout(() => {
        alertBox.style.opacity = '0';
        alertBox.style.transform = 'translateY(-20px)';
    }, 2000);
}

function updateTotals() {
    let total = 0;
    document.querySelectorAll('#cart-body tr[data-product-id]').forEach(row => {
        const price = parseFloat(row.querySelector('.price').textContent.replace('$','').replace(',',''));
        const qty = parseInt(row.querySelector('.quantity-input').value);
        const itemTotal = price * qty;
        row.querySelector('.item-total').textContent = '$' + itemTotal.toFixed(2);
        total += itemTotal;
    });
    document.getElementById('cart-total').textContent = '$' + total.toFixed(2);
}

// Cập nhật số lượng (AJAX)
document.querySelectorAll('.quantity-input').forEach(input => {
    input.addEventListener('change', function() {
        const productId = this.dataset.id;
        const quantity = this.value;
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `ajax_action=update&product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTotals();
                showAlert('Updated quantity!');
            } else {
                showAlert(data.message, true);
            }
        })
        .catch(() => showAlert('Error updating quantity', true));
    });
});

// Xóa sản phẩm (AJAX)
document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.id;
        fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `ajax_action=remove&product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = this.closest('tr');
                row.remove();
                updateTotals();
                showAlert('Removed!');
                if (document.querySelectorAll('#cart-body tr').length === 0) {
                    document.getElementById('cart-body').innerHTML = '<tr><td colspan="5">Cart is empty</td></tr>';
                }
            } else {
                showAlert(data.message, true);
            }
        })
        .catch(() => showAlert('Error removing item', true));
    });
});

updateTotals();
</script>
</body>
</html>