<?php
session_start();

$logged_in_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
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
    SELECT id, name, cost_price, profit_percent, image, gender 
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
    ];
}

// Convert sang JSON để truyền cho Javascript bên dưới
$all_products_json = json_encode($products, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Products (Test Version)</title>
  
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
               onkeydown="if(event.key==='Enter') applyFilter()">
        <button onclick="applyFilter()">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>

    <div class="right">
      <a href="../users/cart.php" class="icon-link" title="Giỏ hàng">
        <i class="fas fa-shopping-cart"></i>
      </a>
      <a href="../users/profile.php" class="icon-link" title="Trang cá nhân">
        <i class="fas fa-user-circle user-icon"></i>
        <?php if ($logged_in_name): ?><span><?= htmlspecialchars($logged_in_name) ?></span><?php endif; ?>
      </a>
      <a href="<?= htmlspecialchars($link_logout) ?>" class="icon-link" title="Đăng xuất" style="color:#c0392b;">
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
      <select id="price">
        <option value="all">All</option>
        <option value="0-500">$0 – $500</option>
        <option value="500-1000">$500 – $1,000</option>
        <option value="1000-2000">$1,000 – $2,000</option>
      </select>
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
            <p>Questions? Contact us at <a href="mailto:yourinfo@gmail.com">yourinfo@gmail.com</a></p>
          </div>
        </div>
        <div class="col-lg-2 col-sm-6">
          <div class="footer-menu menu-002">
            <h5 class="widget-title">Quick Links</h5>
            <ul class="menu-list list-unstyled text-uppercase">
              <li class="menu-item"><a href="../indexprofile.php">Home</a></li>
              <li class="menu-item"><a href="Products.html">Products</a></li> <!-- Giữ tạm href cũ -->
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
  try {
    const res = await fetch('../Cart/jewelry_cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'add', product_id: productId, quantity: 1, size: size }),
    });
    const data = await res.json();
    if (data.success) showNotification(productName, size);
    else alert(data.message || 'Failed to add to cart');
  } catch {
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

// ===== XỬ LÝ KHỞI TẠO PAGE =====
window.addEventListener('DOMContentLoaded', () => {
  // Lấy parameter q từ URL khi redirect tới trang này (vd: products_test.php?q=abc)
  const urlParams = new URLSearchParams(window.location.search);
  const qParam = urlParams.get('q');
  
  if (qParam && qParam.trim()) {
    document.getElementById('search').value = qParam.trim();
    document.getElementById('header-search').value = qParam.trim();
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

function applyFilter() {
  const headerSearch = document.getElementById('header-search').value.trim();
  if (headerSearch) document.getElementById('search').value = headerSearch;

  const category = currentGender;
  const price    = document.getElementById('price').value;
  const sort     = document.getElementById('sort').value;
  const search   = document.getElementById('search').value.trim().toLowerCase();

  filteredProducts = allProducts.filter(p => {
    if (category !== 'all' && p.gender !== category) return false;
    if (price !== 'all') {
      const [min, max] = price.split('-').map(Number);
      if (p.sale_price < min || p.sale_price > max) return false;
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
    card.className = 'product-card';
    card.innerHTML = `
      <a href="product-detail.php?id=${p.id}" class="product-card-link">
        <div class="image-holder">
          <img src="../../images/${p.image}" alt="${p.name}" class="product-image">
        </div>
        <h3 class="product-title">${p.name}</h3>
        <span class="product-price">$${Number(p.sale_price).toFixed(2)}</span>
      </a>
      <button class="btn-add" data-id="${p.id}" data-name="${p.name}">Add to Cart</button>
    `;
    grid.appendChild(card);
  });

  document.querySelectorAll('.btn-add').forEach(btn => {
    btn.addEventListener('click', function () {
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
