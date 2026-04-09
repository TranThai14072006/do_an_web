<?php
session_start();

$logged_in_name = $_SESSION['username'] ?? ($_SESSION['user_name'] ?? '');
$link_logout = '../users/logout.php'; // Thay đổi theo đúng ngữ cảnh thực tế

$host = 'localhost';
$db   = 'jewelry_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Lấy toàn bộ sản phẩm từ DB
$stmt = $pdo->query("
    SELECT id, name, cost_price, profit_percent, image, gender, stock 
    FROM products
    ORDER BY id ASC
");
$rows = $stmt->fetchAll();

$products = [];
foreach ($rows as $p) {
    $cost_price     = (float)$p['cost_price'];
    $profit_percent = (float)$p['profit_percent'];
    $sale_price = round($cost_price * (1 + $profit_percent / 100), 2);

    $products[] = [
        'id'         => $p['id'],
        'name'       => $p['name'],
        'gender'     => $p['gender'],
        'sale_price' => $sale_price,
        'image'      => $p['image'] ?: 'placeholder.png',
        'in_stock'   => (int)$p['stock'] > 0,
    ];
}

// Convert sang JSON để truyền cho Javascript bên dưới
$all_products_json = json_encode($products, JSON_UNESCAPED_UNICODE);

// ===== TỔNG SỐ LƯỢNG GIỎ HÀNG =====
$total_cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    // Dùng $pdo vì file này đang dùng PDO cho phần sản phẩm
    $stmt_badge = $pdo->prepare("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = ?");
    $stmt_badge->execute([$uid]);
    $row_b = $stmt_badge->fetch();
    if ($row_b) {
        $total_cart_count = (int)$row_b['total_qty'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Products</title>
  
  <!-- Giữ nguyên các file CSS -->
  <link rel="stylesheet" href="../../style.css">
  <link rel="stylesheet" href="Product.css">
  <link rel="stylesheet" href="../Search/search.css">
  <link rel="stylesheet" type="text/css" href="../fonts/icomoon.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* ===== SIZE MODAL CSS (từ bản cũ) ===== */
    .size-modal-overlay {
      display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45);
      z-index: 9999; align-items: center; justify-content: center;
    }
    .size-modal-overlay.open { display: flex; }
    .size-modal {
      background: #fff; border-radius: 10px; padding: 32px 28px 24px;
      width: 340px; max-width: 92vw; box-shadow: 0 8px 32px rgba(0,0,0,0.18);
      position: relative; animation: modalIn .18s ease;
    }
    @keyframes modalIn {
      from { transform: translateY(30px); opacity: 0; }
      to   { transform: translateY(0);   opacity: 1; }
    }
    .size-modal h3 { margin: 0 0 6px; font-size: 17px; font-weight: 700; color: #2d1a0e; }
    .size-modal .modal-product-name { font-size: 13px; color: #888; margin-bottom: 20px; }
    .size-modal .size-label {
      font-size: 12px; font-weight: 600; letter-spacing: .1em;
      text-transform: uppercase; color: #999; margin-bottom: 10px;
    }
    .size-options { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 22px; }
    .size-options button {
      width: 46px; height: 46px; border: 1.5px solid #ddd; background: #fafafa;
      border-radius: 6px; font-size: 14px; font-weight: 600;
      color: #444; cursor: pointer; transition: all .15s;
    }
    .size-options button:hover { border-color: #c9a96e; color: #8a5e2d; background: #fff8f0; }
    .size-options button.selected { border-color: #c9a96e; background: #c9a96e; color: #fff; }
    .size-modal-actions { display: flex; gap: 10px; }
    .size-modal-actions .btn-cancel {
      flex: 1; padding: 11px; border: 1.5px solid #ddd;
      background: #fff; border-radius: 6px; font-size: 14px; cursor: pointer; color: #666;
    }
    .size-modal-actions .btn-confirm {
      flex: 2; padding: 11px; border: none; background: #c9a96e;
      color: #fff; border-radius: 6px; font-size: 14px; font-weight: 700;
      cursor: pointer; transition: background .15s;
    }
    .size-modal-actions .btn-confirm:hover { background: #b8945f; }
    .size-modal-actions .btn-confirm:disabled { background: #ddd; cursor: not-allowed; }
    .size-modal .close-modal {
      position: absolute; top: 12px; right: 14px;
      background: none; border: none; font-size: 18px; color: #aaa; cursor: pointer;
    }
    .no-size-option {
      font-size: 12px; color: #aaa; margin-bottom: 18px;
      cursor: pointer; text-decoration: underline;
    }
    /* ===== OUT OF STOCK ===== */
    .product-card.is-out-of-stock .image-holder {
      position: relative;
    }
    .product-card.is-out-of-stock .image-holder::after {
      content: 'Out of Stock';
      position: absolute; inset: 0;
      background: rgba(0,0,0,0.42);
      color: #fff;
      font-family: 'Cormorant Garamond', serif;
      font-size: 15px; font-weight: 700;
      letter-spacing: .14em; text-transform: uppercase;
      display: flex; align-items: center; justify-content: center;
    }
    .btn-add.out-of-stock {
      background: #d8d8d8 !important;
      color: #666 !important;
      cursor: not-allowed !important;
      border: 1px solid #ccc !important;
      font-weight: 600;
    }
    .out-of-stock-toast {
      position: fixed;
      bottom: 20px; right: 20px;
      z-index: 10000;
      opacity: 0; visibility: hidden;
      transform: translateY(100%);
      transition: opacity .3s ease, transform .3s ease, visibility .3s;
      max-width: 350px; width: 100%;
      pointer-events: none;
    }
    .out-of-stock-toast.show {
      opacity: 1; visibility: visible;
      transform: translateY(0);
      pointer-events: auto;
    }
    .out-of-stock-toast .oos-inner {
      background: #fff;
      border: 1px solid #ddd;
      border-left: 4px solid #b8860b;
      border-radius: 10px;
      padding: 15px 20px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.2);
      display: flex; align-items: center; gap: 14px;
    }
    .out-of-stock-toast .oos-icon i {
      color: #b8860b; font-size: 1.8em;
    }
    .out-of-stock-toast .oos-text h3 {
      margin: 0 0 3px; font-size: 1.05em; font-weight: 600;
      color: #b8860b; font-family: 'Cormorant Garamond', serif;
    }
    .out-of-stock-toast .oos-text p {
      margin: 0; font-size: 0.88em; color: #666;
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

    /* Hiệu ứng cho ô nhập giá tùy chỉnh */
    #custom-price-inputs {
      max-width: 0;
      opacity: 0;
      overflow: hidden;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    #custom-price-inputs.show {
      max-width: 300px;
      opacity: 1;
      margin-left: 5px;
    }
  </style>
</head>
<body class="homepage bg-accent-light">

<!-- ===== HEADER TỪ Products.html CÓ KẾT HỢP BIẾN PHP ===== -->
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
        <!-- Chuyển ID thành header-search để tương thích bộ lọc dưới -->
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

<!-- ===== MAIN CONTENT ===== -->
<div class="my-5" style="width: 90%; max-width: 1500px; margin: 0 auto;">

  <!-- ===== PHÂN LOẠI TABS TỪ Products.html ===== -->
  <div class="category-and-filter-bar" style="margin-bottom: 20px; display: flex; justify-content: center;">
    <nav class="filter-nav">
      <a href="#" class="filter-tab active" data-gender="all">All</a>
      <a href="#" class="filter-tab" data-gender="Male">Male</a>
      <a href="#" class="filter-tab" data-gender="Female">Female</a>
      <a href="#" class="filter-tab" data-gender="Unisex">Unisex</a>
    </nav>
  </div>

  <!-- ===== ADVANCED FILTER BAR TỪ search.html ===== -->
  <div class="filter-bar" style="display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin-bottom: 40px; align-items: flex-end;">
    <div class="filter-group">
      <label for="price">Price Range:</label>
      <select id="price" onchange="toggleCustomPrice()">
        <option value="all">All</option>
        <option value="0-500">$0 – $500</option>
        <option value="500-1000">$500 – $1,000</option>
        <option value="1000-2000">$1,000 – $2,000</option>
        <option value="custom">Custom Range...</option>
      </select>
    </div>
    
    <!-- Phần nhập giá thủ công, sử dụng class 'show' để điều khiển hiệu ứng -->
    <div id="custom-price-inputs">
      <input type="number" id="min-price" placeholder="From" 
             onkeydown="if(event.key==='Enter') applyFilter()"
             style="width: 80px; padding: 10px; border-radius: 6px; border: 1px solid #ddd; background-color: #f6f6f6;">
      <span style="color: #666;">–</span>
      <input type="number" id="max-price" placeholder="To" 
             onkeydown="if(event.key==='Enter') applyFilter()"
             style="width: 80px; padding: 10px; border-radius: 6px; border: 1px solid #ddd; background-color: #f6f6f6;">
    </div>
    <div class="filter-group">
      <label for="sort">Sort by:</label>
      <select id="sort">
        <option value="default">Default</option>
        <option value="price-low-high">Price: Low → High</option>
        <option value="price-high-low">Price: High → Low</option>
        <option value="name">Name (A → Z)</option>
      </select>
    </div>
    <div class="filter-group search-group">
      <label for="search">Keyword:</label>
      <input type="text" id="search" placeholder="Search product name..."
             onkeydown="if(event.key==='Enter') applyFilter()">
    </div>
    <button class="filter-btn" onclick="applyFilter()" style="background-color: #b8860b; border: none; color: #fff; padding: 10px 25px; border-radius: 6px; font-weight: 800; cursor: pointer; height: 42px;">
      <i class="fas fa-search"></i> Find
    </button>
  </div>

  <!-- ===== LƯỚI SẢN PHẨM ===== -->
  <div class="products-grid" id="products-grid"></div>

  <!-- ===== PHÂN TRANG ===== -->
  <div class="pagination" id="pagination"></div>
</div>

<!-- ===== FOOTER ===== -->
<footer id="footer" class="overflow-hidden">
  <div class="container mt-5">
    <div class="footer-top-area">
      <div class="row d-flex flex-wrap justify-content-between">
        <div class="col-lg-3 col-sm-6">
          <div class="footer-menu menu-001">
            <img src="../../images/36-logo.png" alt="logo">
            <p>Questions? Contact us at <span class="disabled-link">yourinfo@gmail.com</span></p>
          </div>
        </div>
        <div class="col-lg-2 col-sm-6">
          <div class="footer-menu menu-002">
            <h5 class="widget-title">Quick Links</h5>
            <ul class="menu-list list-unstyled text-uppercase">
              <li class="menu-item"><a href="../indexprofile.php">Home</a></li>
              
              <li class="menu-item"><span class="disabled-link">Contact</span></li> <!-- disabled -->
            </ul>
          </div>
        </div>
        <div class="col-lg-3 col-sm-6">
          <div class="footer-menu text-uppercase menu-003">
            <h5 class="widget-title">Help & Info</h5>
            <ul class="menu-list list-unstyled">
              <li class="menu-item"><span class="disabled-link">Shipping</span></li> <!-- disabled -->
              <li class="menu-item"><span class="disabled-link">Returns</span></li> <!-- disabled -->
              <li class="menu-item"><span class="disabled-link">Faqs</span></li>   <!-- disabled -->
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

<!-- ===== SIZE MODAL ===== -->
<div class="size-modal-overlay" id="size-modal-overlay">
  <div class="size-modal">
    <button class="close-modal" onclick="closeModal()"><i class="fas fa-times"></i></button>
    <h3>Select Ring Size</h3>
    <p class="modal-product-name" id="modal-product-name"></p>
    <p class="size-label">Ring Size (US)</p>
    <div class="size-options" id="size-options"></div>
    <p class="no-size-option" onclick="confirmNoSize()">Skip — add without size</p>
    <div class="size-modal-actions">
      <button class="btn-cancel" onclick="closeModal()">Cancel</button>
      <button class="btn-confirm" id="btn-confirm-size" disabled onclick="confirmSize()">Add to Cart</button>
    </div>
  </div>
</div>

<!-- ===== CART NOTIFICATION ===== -->
<div class="cart-notification" id="cart-notification">
  <div class="notification-content">
    <div class="notification-icon"><i class="fas fa-check-circle"></i></div>
    <div class="notification-text">
      <h3>Added to cart!</h3>
      <p>The product has been added to your shopping cart.</p>
    </div>
    <button class="close-notification" onclick="closeNotification()">
      <i class="fas fa-times"></i>
    </button>
  </div>
</div>

<!-- ===== OUT OF STOCK TOAST ===== -->
<div class="out-of-stock-toast" id="oos-toast">
  <div class="oos-inner">
    <div class="oos-icon"><i class="fas fa-ban"></i></div>
    <div class="oos-text">
      <h3>Out of Stock</h3>
      <p id="oos-toast-msg">This product is currently unavailable.</p>
    </div>
  </div>
</div>

<!-- ===== JAVASCRIPT GỘP ===== -->
<script>
// ----- NHẬN DỮ LIỆU TỪ PHP THÔNG QUA JSON ENCODE -----
let allProducts = <?= $all_products_json ?>;

const ITEMS_PER_PAGE = 8;
let filteredProducts = [...allProducts];
let currentPage = 1;
let currentGender = 'all';

// ===== THIẾT LẬP SIZE MODAL =====
const RING_SIZES = [5, 6, 7 ,8 ,9];
let pendingProductId   = null;
let pendingProductName = null;
let pendingBtn         = null;
let selectedSize       = null;

function openSizeModal(productId, productName, btn) {
  pendingProductId   = productId;
  pendingProductName = productName;
  pendingBtn         = btn;
  selectedSize       = null;

  document.getElementById('modal-product-name').textContent = productName;
  document.getElementById('btn-confirm-size').disabled = true;

  const container = document.getElementById('size-options');
  container.innerHTML = '';
  RING_SIZES.forEach(size => {
    const b = document.createElement('button');
    b.textContent = size;
    b.addEventListener('click', () => {
      container.querySelectorAll('button').forEach(x => x.classList.remove('selected'));
      b.classList.add('selected');
      selectedSize = String(size);
      document.getElementById('btn-confirm-size').disabled = false;
    });
    container.appendChild(b);
  });

  document.getElementById('size-modal-overlay').classList.add('open');
}

function closeModal() {
  document.getElementById('size-modal-overlay').classList.remove('open');
  if (pendingBtn) {
    pendingBtn.disabled = false;
    pendingBtn.textContent = 'Add to Cart';
  }
  pendingProductId = pendingProductName = pendingBtn = selectedSize = null;
}

function confirmSize() {
  if (!selectedSize) return;
  doAddToCart(pendingProductId, pendingProductName, selectedSize, pendingBtn);
  document.getElementById('size-modal-overlay').classList.remove('open');
}

function confirmNoSize() {
  doAddToCart(pendingProductId, pendingProductName, '', pendingBtn);
  document.getElementById('size-modal-overlay').classList.remove('open');
}

document.getElementById('size-modal-overlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// ===== ADD TO CART XỬ LÝ API =====
function addToCart(productId, productName, btn) {
  btn.disabled = true;
  btn.textContent = 'Select size...';
  openSizeModal(productId, productName, btn);
}

async function doAddToCart(productId, productName, size, btn) {
  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Adding...';
  }
  
  if (!size || size.trim() === '') {
    size = 'Standard';
  }

  try {
    const url = `../users/cart.php?action=add&id=${encodeURIComponent(productId)}&size=${encodeURIComponent(size)}`;
    await fetch(url);
    
    // ----- CẬP NHẬT BADGE GIỎ HÀNG NGAY LẬP TỨC -----
    const cartLink = document.querySelector('a[href="../users/cart.php"]');
    if (cartLink) {
      let badge = cartLink.querySelector('.cart-badge');
      if (!badge) {
        // Nếu chưa có badge, tạo mới
        badge = document.createElement('span');
        badge.className = 'cart-badge';
        badge.textContent = '1';
        cartLink.appendChild(badge);
      } else {
        // Nếu đã có badge, tăng số lượng
        let currentCount = badge.textContent.includes('+') ? 10 : parseInt(badge.textContent);
        let newCount = currentCount + 1;
        badge.textContent = newCount > 9 ? '9+' : newCount;
      }
    }

    showNotification(productName, size);
  } catch (error) {
    alert('Network error, please try again.');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Add to Cart';
    }
  }
}

// ===== NOTIFICATION =====
function showNotification(productName, size) {
  const n = document.getElementById('cart-notification');
  n.querySelector('.notification-text h3').textContent = size
    ? `"${productName}" (Size ${size}) added!`
    : `"${productName}" added to cart!`;
  n.classList.add('show');
  clearTimeout(n._t);
  n._t = setTimeout(() => n.classList.remove('show'), 3000);
}

function closeNotification() {
  const n = document.getElementById('cart-notification');
  n.classList.remove('show');
  clearTimeout(n._t);
}

// ===== OUT OF STOCK NOTIFICATION =====
function showOutOfStockToast(productName) {
  const t = document.getElementById('oos-toast');
  const msg = document.getElementById('oos-toast-msg');
  msg.textContent = `"${productName}" is currently unavailable. Please check back later.`;
  t.classList.add('show');
  clearTimeout(t._t);
  t._t = setTimeout(() => t.classList.remove('show'), 3500);
}

// ===== XỬ LÝ KHỞI TẠO PAGE =====
window.addEventListener('DOMContentLoaded', () => {
  // Lấy parameter q từ URL khi redirect tới trang này (vd: products_test.php?q=abc)
  const urlParams = new URLSearchParams(window.location.search);
  const qParam = urlParams.get('q');
  const genderParam = urlParams.get('gender');
  
  if (qParam && qParam.trim()) {
    document.getElementById('search').value = qParam.trim();
    document.getElementById('header-search').value = qParam.trim();
  }
  
  if (genderParam) {
    const tabMatch = Array.from(document.querySelectorAll('.filter-tab')).find(t => t.dataset.gender.toLowerCase() === genderParam.toLowerCase());
    if (tabMatch) {
      document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
      tabMatch.classList.add('active');
      currentGender = tabMatch.dataset.gender;
    }
  }
  
  // Lúc này allProducts đã có sẵn dữ liệu JSON do PHP truyền thẳng xuống
  applyFilter();
});

// Xử lý sự kiện click trên Tabs phân loại
document.querySelectorAll('.filter-tab').forEach(tab => {
  tab.addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');
    currentGender = this.dataset.gender;
    applyFilter();
  });
});

// Đã tắt đồng bộ 2 ô tìm kiếm (header và advanced bar)
/*
document.getElementById('header-search').addEventListener('input', function() {
  document.getElementById('search').value = this.value;
});
document.getElementById('search').addEventListener('input', function() {
  document.getElementById('header-search').value = this.value;
});
*/

function applyHeaderSearch() {
  const kw = document.getElementById('header-search').value.trim().toLowerCase();
  applyFilter(kw);
}

function applyFilter(customKeyword = null) {
  const category = currentGender;
  const dropPrice = document.getElementById('price').value;
  let manualMin  = document.getElementById('min-price').value;
  let manualMax  = document.getElementById('max-price').value;
  const sort      = document.getElementById('sort').value;
  const search    = customKeyword !== null ? customKeyword : document.getElementById('search').value.trim().toLowerCase();

  // Xử lý logic Min > Max & Swap trên giao diện (Vấn đề #3 cập nhật)
  if (manualMin !== '' && manualMax !== '') {
    const minVal = parseFloat(manualMin);
    const maxVal = parseFloat(manualMax);
    if (minVal > maxVal) {
      // Đổi chỗ trực tiếp trên ô nhập để người dùng thấy
      document.getElementById('min-price').value = maxVal;
      document.getElementById('max-price').value = minVal;
      manualMin = String(maxVal);
      manualMax = String(minVal);
    }
  }

  filteredProducts = allProducts.filter(p => {
    if (category !== 'all' && p.gender !== category) return false;
    
    // Logic lọc giá mới
    if (dropPrice === 'custom') {
      // Nếu chọn Custom: Dùng giá trị thủ công
      if (manualMin !== '' && p.sale_price < parseFloat(manualMin)) return false;
      if (manualMax !== '' && p.sale_price > parseFloat(manualMax)) return false;
    } else if (dropPrice !== 'all') {
      // Nếu chọn các khoảng sẵn có
      const [dMin, dMax] = dropPrice.split('-').map(Number);
      if (p.sale_price < dMin || p.sale_price > dMax) return false;
    }

    if (search && !p.name.toLowerCase().includes(search)) return false;
    return true;
  });

  if (sort === 'price-low-high') filteredProducts.sort((a, b) => a.sale_price - b.sale_price);
  else if (sort === 'price-high-low') filteredProducts.sort((a, b) => b.sale_price - a.sale_price);
  else if (sort === 'name') filteredProducts.sort((a, b) => a.name.localeCompare(b.name));

  currentPage = 1;
  renderPage(currentPage);
  renderPagination();
}

// Hàm ẩn/hiện ô nhập giá tùy chỉnh
function toggleCustomPrice() {
  const val = document.getElementById('price').value;
  const customDiv = document.getElementById('custom-price-inputs');
  if (val === 'custom') {
    customDiv.classList.add('show');
    // Tự động focus vào ô Min khi hiện ra
    setTimeout(() => document.getElementById('min-price').focus(), 400);
  } else {
    customDiv.classList.remove('show');
    // Xóa giá trị cũ khi ẩn đi để tránh gây nhầm lẫn khi lọc lại
    document.getElementById('min-price').value = '';
    document.getElementById('max-price').value = '';
    applyFilter(); // Kích hoạt lại bộ lọc theo dropdown
  }
}

function renderPage(page) {
  const grid = document.getElementById('products-grid');
  grid.innerHTML = '';
  const start = (page - 1) * ITEMS_PER_PAGE;
  const pageProducts = filteredProducts.slice(start, start + ITEMS_PER_PAGE);

  if (pageProducts.length === 0) {
    grid.innerHTML = `<p style="grid-column:1/-1;text-align:center;padding:40px;color:black;font-size:24px;font-weight:600;">
                        No products match your filters.
                      </p>`;
    return;
  }

  pageProducts.forEach(p => {
    const card = document.createElement('article');
    card.className = 'product-card' + (p.in_stock ? '' : ' is-out-of-stock');
    
    card.innerHTML = `
      <a href="product-detail.php?id=${p.id}" class="product-card-link">
        <div class="image-holder">
          <img src="../../images/${p.image}" alt="${p.name}" class="product-image">
        </div>
        <h3 class="product-title">${p.name}</h3>
        <span class="product-price">$${Number(p.sale_price).toFixed(2)}</span>
      </a>
      <button class="btn-add ${p.in_stock ? '' : 'out-of-stock'}" 
              data-id="${p.id}" 
              data-name="${p.name}" 
              data-instock="${p.in_stock}">
        ${p.in_stock ? 'Add to Cart' : 'Out of Stock'}
      </button>
    `;
    grid.appendChild(card);
  });

  document.querySelectorAll('.btn-add').forEach(btn => {
    btn.addEventListener('click', function () {
      const isStock = this.dataset.instock === 'true';
      if (!isStock) {
        showOutOfStockToast(this.dataset.name);
        return;
      }
      addToCart(this.dataset.id, this.dataset.name, this);
    });
  });
}

function renderPagination() {
  const totalPages = Math.ceil(filteredProducts.length / ITEMS_PER_PAGE);
  const container = document.getElementById('pagination');
  container.innerHTML = '';

  if (totalPages <= 1) return;

  const createPageLink = (text, pageNum, disabled = false, active = false) => {
    const a = document.createElement('a');
    a.href = '#';
    a.textContent = text;
    if (disabled) a.classList.add('disabled');
    if (active) a.classList.add('active');
    a.addEventListener('click', e => {
      e.preventDefault();
      if (!disabled && currentPage !== pageNum) {
        currentPage = pageNum;
        renderPage(currentPage);
        renderPagination();
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
    return a;
  };

  container.appendChild(createPageLink('<', currentPage - 1, currentPage === 1));
  for (let i = 1; i <= totalPages; i++) {
    container.appendChild(createPageLink(i, i, false, i === currentPage));
  }
  container.appendChild(createPageLink('>', currentPage + 1, currentPage === totalPages));
}
</script>

</body>
</html>