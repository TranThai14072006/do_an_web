<?php
// ═══════════════════════════════════════════════════════════════
// File: Jewellery/User/indexprofile.php  (logged-in homepage)
// Config: chỉ dùng $conn → jewelry_db (1 DB duy nhất)
// ═══════════════════════════════════════════════════════════════

session_start();
require_once __DIR__ . '/../config/config.php';
// $conn đã có từ config.php (jewelry_db)

// ── Đường dẫn gốc web ────────────────────────────────────────
if (!defined('BASE_URL')) define('BASE_URL', '/do_an_web/Jewellery/');
if (!defined('IMG_URL'))  define('IMG_URL',  BASE_URL . 'images/');

// ── Nhận diện user từ session ────────────────────────────────
// Sau đăng nhập, session lưu user_id (hoặc id) + username / full_name
$session_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

$logged_in_name = 'Tài khoản';

if ($session_user_id) {
    // Ưu tiên lấy full_name từ customers, fallback về username của users
    $stmt_name = $conn->prepare("
        SELECT COALESCE(c.full_name, u.username) AS display_name
        FROM users u
        LEFT JOIN customers c ON c.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt_name->bind_param('i', $session_user_id);
    $stmt_name->execute();
    $row_name = $stmt_name->get_result()->fetch_assoc();
    $stmt_name->close();
    if ($row_name) {
        $logged_in_name = htmlspecialchars($row_name['display_name']);
    }
} else {
    // Fallback: đọc từ session nếu login đã lưu sẵn tên
    $session_keys_name = ['full_name', 'user_name', 'username', 'name'];
    foreach ($session_keys_name as $key) {
        if (!empty($_SESSION[$key])) {
            $logged_in_name = htmlspecialchars($_SESSION[$key]);
            break;
        }
    }
}

// ── Hàm tính giá bán ─────────────────────────────────────────
function calcSellingPrice($row) {
    $cost   = isset($row['cost_price'])     ? (float)$row['cost_price']   : 0;
    $profit = isset($row['profit_percent']) ? (int)$row['profit_percent'] : 0;
    $price  = isset($row['price'])          ? (float)$row['price']        : 0;
    return ($cost > 0) ? $cost * (1 + $profit / 100) : $price;
}

// ── Kiểm tra cột tồn tại ────────────────────────────────────
$col_result = $conn->query("SHOW COLUMNS FROM products");
$columns = [];
while ($col = $col_result->fetch_assoc()) { $columns[] = $col['Field']; }

$has_cost     = in_array('cost_price',     $columns);
$has_profit   = in_array('profit_percent', $columns);
$has_stock    = in_array('stock',          $columns);
$has_category = in_array('category',       $columns);

$select = "id, name, price, image"
        . ($has_cost     ? ", cost_price"     : "")
        . ($has_profit   ? ", profit_percent" : "")
        . ($has_stock    ? ", stock"          : "")
        . ($has_category ? ", category"       : "");

// ── Trending products ────────────────────────────────────────
$order_t = $has_category
    ? "ORDER BY FIELD(category,'Ring') DESC, id ASC"
    : "ORDER BY id ASC";
$trending_products = [];
$res = $conn->query("SELECT $select FROM products $order_t LIMIT 5");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sp = calcSellingPrice($row);
        $trending_products[] = [
            'id'    => $row['id'],
            'name'  => !empty($row['name']) ? $row['name'] : $row['id'],
            'image' => IMG_URL . $row['image'],
            'price' => '$' . number_format($sp, 2),
            'stock' => $has_stock ? (int)$row['stock'] : 1,
        ];
    }
}

// ── New arrivals ─────────────────────────────────────────────
$new_arrivals = [];
$res2 = $conn->query("SELECT $select FROM products ORDER BY id DESC LIMIT 3");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        $sp = calcSellingPrice($row);
        $new_arrivals[] = [
            'id'    => $row['id'],
            'name'  => !empty($row['name']) ? $row['name'] : $row['id'],
            'image' => IMG_URL . $row['image'],
            'price' => '$' . number_format($sp, 2),
            'stock' => $has_stock ? (int)$row['stock'] : 1,
        ];
    }
}

// ── Static data ───────────────────────────────────────────────
$page_title   = "ThirtySix Jewellery Store";
$banner_title = "Make your fashion look ThirtySix.";

$testimonials = [
    ['quote' => "The craftsmanship is absolutely beautiful! My 18K gold necklace from 36 Jewelry exceeded all expectations — elegant, shiny, and luxurious.", 'author' => "Emma Chamberlin", 'stars' => 5],
    ['quote' => "I ordered a silver ring as a gift, and it came beautifully packaged. The shine and detail are just perfect — will definitely shop again!", 'author' => "Sophia Nguyen", 'stars' => 4],
    ['quote' => "I'm in love with my new pearl earrings! The design is timeless and the service from 36 Jewelry was outstanding.", 'author' => "Joey Kimberland", 'stars' => 5],
];

$categories = [
    ['name' => 'Male',   'image' => IMG_URL . 'R002.jpg'],
    ['name' => 'Female', 'image' => IMG_URL . 'R003.jpg'],
    ['name' => 'Unisex', 'image' => IMG_URL . 'R006.jpg'],
];

// ── Link helpers ──────────────────────────────────────────────
$link_home    = BASE_URL . 'User/indexprofile.php';
$link_cart    = BASE_URL . 'User/users/cart.php';
$link_profile = BASE_URL . 'User/users/profile.php';
$link_logout  = BASE_URL . 'User/users/logout.php';
$link_search  = BASE_URL . 'User/Search/search.html';
$link_detail  = BASE_URL . 'User/users/product_detail.php';
$link_shop    = BASE_URL . 'User/indexprofile.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="format-detection" content="telephone=no">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <link rel="stylesheet" type="text/css" href="<?= BASE_URL ?>css/normalize.css">
  <link rel="stylesheet" type="text/css" href="<?= BASE_URL ?>fonts/icomoon.css">
  <link rel="stylesheet" type="text/css" href="<?= BASE_URL ?>css/vendor.css">
  <link href="<?= BASE_URL ?>bootstrap-5.3.0-dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="<?= BASE_URL ?>style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
  <style>
    /* Tên user hiển thị bên trái icon profile */
    .user-name-label {
      font-size: 13px;
      font-weight: 600;
      margin-right: 6px;
      white-space: nowrap;
      vertical-align: middle;
    }
    .icon-link {
      display: inline-flex;
      align-items: center;
    }

    .search-bar .right {
  display: flex !important;
  align-items: center;
  gap: 20px;
  padding-right: 16px;
}

.search-bar .right .icon-link {
  display: flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  color: #333;
  transition: color 0.2s;
}

.search-bar .right .icon-link:hover {
  color: #b8972e;
}

.search-bar .right .icon-link i {
  font-size: 18px;
}

.search-bar .right .icon-link span {
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
}

.search-bar .right {
  display: flex;
  align-items: center;
  gap: 28px; /* tăng khoảng cách giữa các icon */
  padding-right: 20px;
}

.icon-link {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  color: #333;
  transition: all 0.25s ease;
}

.icon-link:hover {
  color: #b8972e;
  transform: translateY(-1px);
}

/* ICON USER TO HƠN */
.user-icon {
  font-size: 26px; /* 🔥 tăng size */
  color: #b8972e;
}

/* ICON GIỎ HÀNG + LOGOUT */
.icon-link i {
  font-size: 20px;
}

/* TEXT USER */
.icon-link span {
  font-size: 14px;
  font-weight: 600;
}

.user-icon {
  font-size: 24px;
  color: white;
  background: #b8972e;
  padding: 6px;
  border-radius: 50%;
}

  </style>
</head>

<body class="hompage bg-accent-light">

<!-- ══ HEADER ══════════════════════════════════════════════════ -->
<header class="header-container">
  <div class="search-bar">

    <div class="left">
      <a href="<?= $link_home ?>" class="home-btn">
        <i class="fas fa-home"></i> Home
      </a>
    </div>

    <div class="center">
      <a href="<?= $link_home ?>">
        <img src="<?= IMG_URL ?>36-logo.png" alt="Jewelry Store Logo" class="header-logo">
      </a>
      <div class="search-box">
        <input type="text" id="search-input" placeholder="Search products..."
               onkeydown="if(event.key==='Enter') doSearch()">
        <button onclick="doSearch()">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>

    <!-- Header phải: luôn hiển thị trạng thái đã đăng nhập -->
    <div class="right" style="display:flex; align-items:center; gap:20px;">

  <!-- Giỏ hàng -->
  <a href="<?= $link_cart ?>" class="icon-link" title="Giỏ hàng"
     style="display:flex; align-items:center; gap:6px; text-decoration:none; color:inherit;">
    <i class="fas fa-shopping-cart" style="font-size:18px;"></i>
  </a>

  <!-- Tên tài khoản + icon profile -->
  <a href="<?= $link_profile ?>" class="icon-link" title="Trang cá nhân"
     style="display:flex; align-items:center; gap:6px; text-decoration:none; color:inherit;">
      <i class="fas fa-user-circle user-icon"></i>
    <span style="font-size:13px; font-weight:600; white-space:nowrap; color:#333;">
      <?= $logged_in_name ?>
    </span>
  </a>

  <!-- Đăng xuất -->
  <a href="<?= $link_logout ?>" class="icon-link" title="Đăng xuất"
     style="display:flex; align-items:center; gap:6px; text-decoration:none; color:#c0392b;">
    <i class="fas fa-sign-out-alt" style="font-size:18px;"></i>
    <span style="font-size:12px; font-weight:500;"></span>
  </a>

</div>
  </div><!-- /.search-bar -->
</header>

<!-- ══ BANNER ═══════════════════════════════════════════════════ -->
<section id="billboard" class="padding-large no-padding-top position-relative">
  <div class="image-holder">
    <img src="<?= IMG_URL ?>Banner3-2.jpg" alt="banner" class="banner-image">
  </div>
  <div class="banner-content content-light style1 text-center col-md-6">
    <h2 class="banner-title"><?= htmlspecialchars($banner_title) ?></h2>
    <div class="btn-center">
      <a href="<?= $link_shop ?>" class="btn btn-medium btn-light">Shop Now</a>
    </div>
  </div>
</section>

<!-- ══ COMPANY SERVICES ══════════════════════════════════════════ -->
<section id="company-services">
  <div class="container my-5">
    <div class="row">
      <div class="icon-box-wrapper d-flex flex-wrap justify-content-between">
        <div class="icon-box text-center col-md-3 col-sm-12">
          <div class="content-box border-top border-bottom">
            <div class="icon-box-icon"><i class="icon icon-shipping"></i></div>
            <div class="icon-content">
              <h3 class="no-margin">Quick delivery</h3>
              <p>Inside City delivery within 5 days</p>
            </div>
          </div>
        </div>
        <div class="icon-box text-center col-md-3 col-sm-12">
          <div class="content-box border-top border-bottom">
            <div class="icon-box-icon"><i class="icon icon-store"></i></div>
            <div class="icon-content">
              <h3 class="no-margin">Pick up in store</h3>
              <p>We have option of pick up in store.</p>
            </div>
          </div>
        </div>
        <div class="icon-box text-center col-md-3 col-sm-12">
          <div class="content-box border-top border-bottom">
            <div class="icon-box-icon"><i class="icon icon-package"></i></div>
            <div class="icon-content">
              <h3 class="no-margin">Special packaging</h3>
              <p>Our packaging is best for products.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ TRENDING PRODUCTS ════════════════════════════════════════ -->
<section id="fashion-trending" class="product-store padding-large position-relative overflow-hidden">
  <div class="container mb-5">
    <div class="section-header text-center">
      <h2 class="section-title">What's trending</h2>
      <p>These are the products that are trending now.</p>
    </div>
    <div class="row">
      <div class="swiper product-swiper">
        <div class="swiper-wrapper">
          <?php foreach ($trending_products as $p): ?>
          <div class="swiper-slide">
            <div class="product-item position-relative">
              <div class="image-holder">
                <img src="<?= htmlspecialchars($p['image']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>"
                     class="product-image"
                     onerror="this.src='<?= IMG_URL ?>placeholder.jpg'">
              </div>
              <?php if ($p['stock'] <= 0): ?>
                <span class="badge bg-danger position-absolute top-0 start-0 m-2">Out of Stock</span>
              <?php endif; ?>
              <div class="cart-concern">
                <div class="cart-button d-flex flex-wrap">
                  <div class="btn-left">
                    <a href="<?= $link_cart ?>?action=add&id=<?= urlencode($p['id']) ?>"
                       class="btn btn-medium btn-light">Add to Cart</a>
                  </div>
                  <button type="button" class="btn btn-light view-btn d-flex"
                    onclick="window.location.href='<?= $link_detail ?>?id=<?= urlencode($p['id']) ?>'">
                    <i class="icon icon-crop"></i>
                  </button>
                </div>
              </div>
              <div class="product-detail text-center">
                <h3 class="product-title">
                  <a href="<?= $link_detail ?>?id=<?= urlencode($p['id']) ?>">
                    <?= htmlspecialchars($p['name']) ?>
                  </a>
                </h3>
                <span class="item-price text-primary"><?= htmlspecialchars($p['price']) ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="swiper-button swiper-button-next"></div>
      <div class="swiper-button swiper-button-prev"></div>
      <div class="btn-center">
        <a href="<?= $link_shop ?>" class="btn btn-medium btn-black">Shop All</a>
      </div>
    </div>
  </div>
  <div class="swiper-pagination"></div>
</section>

<!-- ══ TESTIMONIALS ══════════════════════════════════════════════ -->
<section id="testimonials" class="position-relative padding-large">
  <div class="container mt-5">
    <div class="row pt-5">
      <div class="review-content">
        <i class="icon-arrow icon-arrow-left"></i>
        <div class="swiper testimonial-swiper">
          <div class="swiper-wrapper">
            <?php foreach ($testimonials as $review): ?>
            <div class="swiper-slide text-center d-flex justify-content-center">
              <div class="review-item col-md-8">
                <i class="icon icon-review"></i>
                <blockquote>"<?= htmlspecialchars($review['quote']) ?>"</blockquote>
                <div class="rating-star">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="icon <?= ($i <= $review['stars']) ? 'icon-star' : 'icon-star-line' ?>"></i>
                  <?php endfor; ?>
                </div>
                <div class="author-detail">
                  <div class="name text-primary"><?= htmlspecialchars($review['author']) ?></div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="swiper-pagination"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="swiper-pagination"></div>
</section>

<!-- ══ NEW ARRIVALS ══════════════════════════════════════════════ -->
<section id="new-arrivals" class="product-store padding-large position-relative overflow-hidden">
  <div class="container mb-5">
    <div class="section-header text-center">
      <h2 class="section-title">New Arrivals</h2>
      <p>These are the products that are new.</p>
    </div>
    <div class="row">
      <div class="swiper product-swiper">
        <div class="swiper-wrapper">
          <?php foreach ($new_arrivals as $p): ?>
          <div class="swiper-slide">
            <div class="product-item position-relative">
              <div class="image-holder">
                <img src="<?= htmlspecialchars($p['image']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>"
                     class="product-image"
                     onerror="this.src='<?= IMG_URL ?>placeholder.jpg'">
              </div>
              <?php if ($p['stock'] <= 0): ?>
                <span class="badge bg-danger position-absolute top-0 start-0 m-2">Out of Stock</span>
              <?php endif; ?>
              <div class="cart-concern">
                <div class="cart-button d-flex flex-wrap">
                  <div class="btn-left">
                    <a href="<?= $link_cart ?>?action=add&id=<?= urlencode($p['id']) ?>"
                       class="btn btn-medium btn-light">Add to Cart</a>
                  </div>
                  <button type="button" class="btn btn-light view-btn d-flex"
                    onclick="window.location.href='<?= $link_detail ?>?id=<?= urlencode($p['id']) ?>'">
                    <i class="icon icon-crop"></i>
                  </button>
                </div>
              </div>
              <div class="product-detail text-center">
                <h3 class="product-title">
                  <a href="<?= $link_detail ?>?id=<?= urlencode($p['id']) ?>">
                    <?= htmlspecialchars($p['name']) ?>
                  </a>
                </h3>
                <span class="item-price text-primary"><?= htmlspecialchars($p['price']) ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="swiper-button swiper-button-next"></div>
      <div class="swiper-button swiper-button-prev"></div>
      <div class="btn-center">
        <a href="<?= $link_shop ?>" class="btn btn-medium btn-black">Shop All</a>
      </div>
    </div>
  </div>
  <div class="swiper-pagination1"></div>
</section>

<!-- ══ CATEGORIES ═══════════════════════════════════════════════ -->
<section id="categories" class="overflow-hidden">
  <div class="d-flex flex-wrap">
    <?php
    $btn_classes = ['btn-light', 'btn-outline-light', 'btn-outline-light'];
    foreach ($categories as $idx => $cat):
      $bc = $btn_classes[$idx] ?? 'btn-outline-light';
    ?>
    <div class="category-item col-md-4 col-sm-12 position-relative">
      <div class="image-holder">
        <img src="<?= htmlspecialchars($cat['image']) ?>" alt="fashion">
      </div>
      <div class="category-content content-light">
        <h3 class="category-title"><?= htmlspecialchars($cat['name']) ?></h3>
        <div class="btn-left">
          <a href="<?= $link_shop ?>" class="btn btn-medium <?= $bc ?>">Shop it now</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ══ FOOTER ═══════════════════════════════════════════════════ -->
<footer id="footer" class="overflow-hidden">
  <div class="container mt-5">
    <div class="row">
      <div class="footer-top-area">
        <div class="row d-flex flex-wrap justify-content-between">
          <div class="col-lg-3 col-sm-6">
            <div class="footer-menu menu-001">
              <img src="<?= IMG_URL ?>36-logo.png" alt="logo">
              <p>Nisi, purus vitae, ultrices nunc. Sit ac sit suscipit hendrerit. Gravida massa volutpat aenean odio erat nullam fringilla.</p>
              <div class="newsletter-button d-flex flex-wrap align-items-center justify-content-between border-bottom widget-menu">
                <input type="text" name="Subscribe" placeholder="Enter your email address...">
                <a href="#"><i class="icon icon-send"></i></a>
              </div>
              <div class="social-links">
                <ul class="d-flex list-unstyled">
                  <li><a href="#"><i class="icon icon-facebook"></i></a></li>
                  <li><a href="#"><i class="icon icon-twitter"></i></a></li>
                  <li><a href="#"><i class="icon icon-instagram1"></i></a></li>
                  <li><a href="#"><i class="icon icon-youtube"></i></a></li>
                  <li><a href="#"><i class="icon icon-behance"></i></a></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-lg-2 col-sm-6">
            <div class="footer-menu menu-002">
              <h5 class="widget-title">Quick Links</h5>
              <ul class="menu-list list-unstyled text-uppercase">
                <li class="menu-item"><a href="<?= $link_home ?>">Home</a></li>
                <li class="menu-item"><a href="<?= $link_shop ?>">Shop</a></li>
                <li class="menu-item"><a href="<?= $link_profile ?>">My Profile</a></li>
              </ul>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6">
            <div class="footer-menu text-uppercase menu-003">
              <h5 class="widget-title">Help & Info</h5>
              <ul class="menu-list list-unstyled">
                <li class="menu-item"><a href="#">Track Your Order</a></li>
                <li class="menu-item"><a href="#">Returns Policies</a></li>
                <li class="menu-item"><a href="#">Shipping + Delivery</a></li>
                <li class="menu-item"><a href="#">Contact Us</a></li>
                <li class="menu-item"><a href="#">Faqs</a></li>
              </ul>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6">
            <div class="footer-menu contact-item menu-004">
              <h5 class="widget-title">Contact Us</h5>
              <p>Do you have any queries or suggestions?
                <a href="mailto:ThirtySixJewellery@gmail.com">ThirtySixJewellery@gmail.com</a></p>
              <p>If you need support? Just give us a call.
                <a href="tel:+5511122233344">+55 111 222 333 44</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <hr>
</footer>

<div id="footer-bottom">
  <div class="container">
    <div class="row d-flex flex-wrap justify-content-between mb-3">
      <div class="col-md-4 col-sm-6">
        <div class="Shipping d-flex">
          <p>We ship with:</p>
          <div class="card-wrap">
            <img src="<?= IMG_URL ?>dhl.png" alt="dhl">
            <img src="<?= IMG_URL ?>shippingcard.png" alt="shipping">
          </div>
        </div>
      </div>
      <div class="col-md-4 col-sm-6">
        <div class="payment-method d-flex justify-content-md-center">
          <p>Payment options:</p>
          <div class="card-wrap">
            <img src="<?= IMG_URL ?>visa.jpg" alt="visa">
            <img src="<?= IMG_URL ?>mastercard.jpg" alt="mastercard">
            <img src="<?= IMG_URL ?>paypal.jpg" alt="paypal">
          </div>
        </div>
      </div>
      <div class="col-md-4 col-sm-6">
        <div class="copyright text-md-end">
          <p>Freebies by <a href="https://templatesjungle.com/"><u>Templates Jungle</u></a><br>
             Distributed by <a href="https://themewagon.com"><u>ThemeWagon</u></a></p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ══ SCRIPTS ══════════════════════════════════════════════════ -->
<script src="<?= BASE_URL ?>js/jquery-1.11.0.min.js"></script>
<script src="<?= BASE_URL ?>js/plugins.js"></script>
<script src="<?= BASE_URL ?>js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
  crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<script>

  const productSwiper = new Swiper('.product-swiper', {
    slidesPerView: 3,
    spaceBetween: 30,
    loop: true,
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    breakpoints: {
      768: { slidesPerView: 2 },
      480: { slidesPerView: 1 }
    }
  });

  const testimonialSwiper = new Swiper('.testimonial-swiper', {
    slidesPerView: 1,
    loop: true,
    pagination: { el: '.swiper-pagination', clickable: true }
  });

  function doSearch() {
    const keyword = document.getElementById('search-input').value.trim();
    if (keyword) {
      window.location.href = '<?= $link_search ?>?q=' + encodeURIComponent(keyword);
    }
  }

</script>

</body>
</html>