<?php

$conn = new mysqli("localhost", "root", "", "jewelry_db");
$conn->set_charset("utf8");

$id   = $_GET['id']   ?? '';
$size = $_GET['size'] ?? '';

// ===== LẤY PRODUCT =====
$sql     = "SELECT * FROM products WHERE id = '" . $conn->real_escape_string($id) . "'";
$product = $conn->query($sql)->fetch_assoc();

// ===== LẤY DETAILS =====
$sql_details = "SELECT * FROM product_details WHERE product_id = '" . $conn->real_escape_string($id) . "'";
$details     = $conn->query($sql_details)->fetch_assoc();

// ===== TÍNH GIÁ =====
$sale_price = 0;

if ($product) {
    $product_id    = $product['id'];
    $current_cost  = (float)$product['price'];
    $profit_percent = (float)$product['profit_percent'];

    $total_quantity = 0;
    $total_cost     = 0;

    $sql_receipt = "SELECT quantity, unit_price 
                    FROM goods_receipt_items 
                    WHERE product_id = '" . $conn->real_escape_string($product_id) . "'";

    $result_receipt = $conn->query($sql_receipt);

    while ($row = $result_receipt->fetch_assoc()) {
        $total_cost     += $row['quantity'] * $row['unit_price'];
        $total_quantity += $row['quantity'];
    }

    $avg_cost   = ($total_quantity > 0) ? $total_cost / $total_quantity : $current_cost;
    $sale_price = round($avg_cost * (1 + $profit_percent / 100), 2);
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
      <h3>Selected Ring Size</h3>
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
</html>