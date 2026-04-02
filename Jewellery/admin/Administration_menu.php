<?php
session_start();
require_once '../config/config.php';

// Guard: only logged-in admins can access this page
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// ──────────────────────────────────────────────
// JEWELRY LIST — pagination per tab
// ──────────────────────────────────────────────
$prod_limit = 6;

// Tab-aware page params (male_page, female_page, unisex_page)
$male_page   = isset($_GET['male_page'])   && is_numeric($_GET['male_page'])   ? max(1,(int)$_GET['male_page'])   : 1;
$female_page = isset($_GET['female_page']) && is_numeric($_GET['female_page']) ? max(1,(int)$_GET['female_page']) : 1;
$unisex_page = isset($_GET['unisex_page']) && is_numeric($_GET['unisex_page']) ? max(1,(int)$_GET['unisex_page']) : 1;

// Search params per tab
$male_name   = isset($_GET['male_name'])   ? trim($_GET['male_name'])   : '';
$female_name = isset($_GET['female_name']) ? trim($_GET['female_name']) : '';
$unisex_name = isset($_GET['unisex_name']) ? trim($_GET['unisex_name']) : '';

// Customer search/pagination params (used in hidden inputs)
$cust_page   = isset($_GET['cust_page'])   && is_numeric($_GET['cust_page'])   ? max(1,(int)$_GET['cust_page'])   : 1;
$cust_name   = isset($_GET['cust_name'])   ? trim($_GET['cust_name'])   : '';
$cust_status = isset($_GET['cust_status']) ? trim($_GET['cust_status']) : '';

function fetchProducts($conn, $category, $search_name, $page, $limit) {
    $offset = ($page - 1) * $limit;
    $where  = "WHERE category = '" . $conn->real_escape_string($category) . "'";
    if (!empty($search_name)) {
        $where .= " AND name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
    }
    $rows  = [];
    $r     = $conn->query("SELECT id, name, image, category FROM products $where ORDER BY id ASC LIMIT $offset, $limit");
    if ($r) while ($row = $r->fetch_assoc()) $rows[] = $row;
    $cnt   = $conn->query("SELECT COUNT(id) AS total FROM products $where");
    $total = $cnt ? (int)$cnt->fetch_assoc()['total'] : 0;
    $pages = max(1, (int)ceil($total / $limit));
    return [$rows, $total, $pages];
}

[$male_products,   $male_total,   $male_pages]   = fetchProducts($conn, 'Male',   $male_name,   $male_page,   $prod_limit);
[$female_products, $female_total, $female_pages] = fetchProducts($conn, 'Female', $female_name, $female_page, $prod_limit);
[$unisex_products, $unisex_total, $unisex_pages] = fetchProducts($conn, 'Unisex', $unisex_name, $unisex_page, $prod_limit);

// Helper: build query string keeping all GET params, overriding specific keys
function pageUrl($overrides = []) {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Luxury Jewelry Admin Panel</title>
  <link rel="stylesheet" href="admin_function.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    .product-img { border-radius:6px; object-fit:cover; width:60px; height:60px; }
    .no-image { width:60px; height:60px; border-radius:6px; background:#f0f0f0;
                display:inline-flex; align-items:center; justify-content:center;
                color:#aaa; font-size:10px; text-align:center; }
    .pagination-info { text-align:center; color:#888; font-size:13px; margin-top:4px; }
    .logout-btn { display:inline-flex; align-items:center; gap:10px; padding:12px 24px;
      background:linear-gradient(135deg,#d9534f,#c9302c); color:white; text-decoration:none;
      border-radius:8px; font-weight:600; transition:all .3s;
      box-shadow:0 4px 10px rgba(217,83,79,.3); border:none; cursor:pointer; }
    .logout-btn:hover { transform:translateY(-2px); background:linear-gradient(135deg,#c9302c,#ac2925);
      box-shadow:0 6px 15px rgba(217,83,79,.4); }
    .logout-btn i { font-size:18px; }
    .tab-search { display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;
                  background:#fdf7ee; border:1px solid #e8d5b0; border-radius:8px;
                  padding:12px 16px; margin-bottom:14px; }
    .tab-search input { padding:8px 12px; border:1px solid #ccc; border-radius:6px; font-size:14px; }
    .tab-search button { padding:8px 16px; border:none; border-radius:6px; cursor:pointer;
                         font-size:14px; font-weight:600; background:#8e4b00; color:#fff; }
    .tab-search button:hover { background:#a3670b; }
    .tab-search a.reset-btn { padding:8px 14px; border-radius:6px; font-size:14px;
                               background:#e0e0e0; color:#333; text-decoration:none; font-weight:600; }
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="logo">
      <img src="../images/Admin_login.jpg" alt="Admin Logo">
      <h2>Luxury Jewelry Admin</h2>
    </div>
    <div class="menu">
      <a href="#products">Jewelry List</a>
      <a href="product_management.php">Product Management</a>
      <a href="Price Manage/pricing.php">Pricing Management</a>
      <a href="customer_management.php">Customers</a>
      <a href="Order Manage/order_management.php">Order Management</a>
      <a href="Import product manage/import_management.php">Import Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking Management</a>
      <a href="#settings">Settings</a>
    </div>
  </div>

  <div class="content">

    <!-- ======= Jewelry List ======= -->
    <section id="products" class="section">
      <header><h1>Jewelry Inventory</h1></header>

      <div class="tabs">
        <div class="tab active" data-tab="tab1">Male (<?php echo $male_total; ?>)</div>
        <div class="tab" data-tab="tab2">Female (<?php echo $female_total; ?>)</div>
        <div class="tab" data-tab="tab3">Unisex (<?php echo $unisex_total; ?>)</div>
      </div>

      <div class="product-list">

        <!-- TAB1 – Male -->
        <div class="tab-content active" id="tab1">
          <form class="tab-search" method="GET" action="Administration_menu.php">
            <input type="hidden" name="female_name"  value="<?php echo htmlspecialchars($female_name); ?>">
            <input type="hidden" name="unisex_name"  value="<?php echo htmlspecialchars($unisex_name); ?>">
            <input type="hidden" name="female_page"  value="<?php echo $female_page; ?>">
            <input type="hidden" name="unisex_page"  value="<?php echo $unisex_page; ?>">
            <input type="hidden" name="cust_page"    value="<?php echo $cust_page; ?>">
            <input type="hidden" name="cust_name"    value="<?php echo htmlspecialchars($cust_name); ?>">
            <input type="hidden" name="cust_status"  value="<?php echo htmlspecialchars($cust_status); ?>">
            <input type="text" name="male_name" placeholder="Search product name…" value="<?php echo htmlspecialchars($male_name); ?>">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
            <?php if (!empty($male_name)): ?>
              <a class="reset-btn" href="<?php echo pageUrl(['male_name'=>'','male_page'=>1]); ?>">Reset</a>
            <?php endif; ?>
          </form>
          <table>
            <thead><tr><th>#</th><th>Image</th><th>Product Name</th><th>Category</th></tr></thead>
            <tbody>
              <?php if (empty($male_products)): ?>
              <tr><td colspan="4" style="text-align:center;color:#888;padding:20px;">No products found.</td></tr>
              <?php else: ?>
              <?php foreach ($male_products as $i => $p): ?>
              <tr>
                <td><?php echo ($male_page-1)*$prod_limit + $i + 1; ?></td>
                <td>
                  <?php if (!empty($p['image'])): ?>
                    <img src="../images/<?php echo htmlspecialchars($p['image']); ?>" class="product-img"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                    <span class="no-image" style="display:none;">No img</span>
                  <?php else: ?><span class="no-image">No img</span><?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['category']); ?></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
          <?php if ($male_pages > 1): ?>
          <div class="pagination">
            <?php if ($male_page > 1): ?>
              <button class="pagination-btn" onclick="window.location.href='<?php echo pageUrl(['male_page'=>$male_page-1]); ?>#products'">&#10094;</button>
            <?php endif; ?>
            <?php for ($i=1; $i<=$male_pages; $i++): ?>
              <button class="pagination-btn <?php echo ($i==$male_page)?'active':''; ?>"
                      onclick="window.location.href='<?php echo pageUrl(['male_page'=>$i]); ?>#products'"><?php echo $i; ?></button>
            <?php endfor; ?>
            <?php if ($male_page < $male_pages): ?>
              <button class="pagination-btn" onclick="window.location.href='<?php echo pageUrl(['male_page'=>$male_page+1]); ?>#products'">&#10095;</button>
            <?php endif; ?>
          </div>
          <div class="pagination-info">Page <?php echo $male_page; ?>/<?php echo $male_pages; ?> — <?php echo $male_total; ?> product(s)</div>
          <?php endif; ?>
        </div>

        <!-- TAB2 – Female -->
        <div class="tab-content" id="tab2">
          <form class="tab-search" method="GET" action="Administration_menu.php">
            <input type="hidden" name="male_name"    value="<?php echo htmlspecialchars($male_name); ?>">
            <input type="hidden" name="unisex_name"  value="<?php echo htmlspecialchars($unisex_name); ?>">
            <input type="hidden" name="male_page"    value="<?php echo $male_page; ?>">
            <input type="hidden" name="unisex_page"  value="<?php echo $unisex_page; ?>">
            <input type="hidden" name="cust_page"    value="<?php echo $cust_page; ?>">
            <input type="hidden" name="cust_name"    value="<?php echo htmlspecialchars($cust_name); ?>">
            <input type="hidden" name="cust_status"  value="<?php echo htmlspecialchars($cust_status); ?>">
            <input type="text" name="female_name" placeholder="Search product name…" value="<?php echo htmlspecialchars($female_name); ?>">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
            <?php if (!empty($female_name)): ?>
              <a class="reset-btn" href="<?php echo pageUrl(['female_name'=>'','female_page'=>1]); ?>">Reset</a>
            <?php endif; ?>
          </form>
          <table>
            <thead><tr><th>#</th><th>Image</th><th>Product Name</th><th>Category</th></tr></thead>
            <tbody>
              <?php if (empty($female_products)): ?>
              <tr><td colspan="4" style="text-align:center;color:#888;padding:20px;">No products found.</td></tr>
              <?php else: ?>
              <?php foreach ($female_products as $i => $p): ?>
              <tr>
                <td><?php echo ($female_page-1)*$prod_limit + $i + 1; ?></td>
                <td>
                  <?php if (!empty($p['image'])): ?>
                    <img src="../images/<?php echo htmlspecialchars($p['image']); ?>" class="product-img"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                    <span class="no-image" style="display:none;">No img</span>
                  <?php else: ?><span class="no-image">No img</span><?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['category']); ?></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
          <?php if ($female_pages > 1): ?>
          <div class="pagination">
            <?php if ($female_page > 1): ?>
              <button class="pagination-btn" onclick="window.location.href='<?php echo pageUrl(['female_page'=>$female_page-1]); ?>#products'">&#10094;</button>
            <?php endif; ?>
            <?php for ($i=1; $i<=$female_pages; $i++): ?>
              <button class="pagination-btn <?php echo ($i==$female_page)?'active':''; ?>"
                      onclick="window.location.href='<?php echo pageUrl(['female_page'=>$i]); ?>#products'"><?php echo $i; ?></button>
            <?php endfor; ?>
            <?php if ($female_page < $female_pages): ?>
              <button class="pagination-btn" onclick="window.location.href='<?php echo pageUrl(['female_page'=>$female_page+1]); ?>#products'">&#10095;</button>
            <?php endif; ?>
          </div>
          <div class="pagination-info">Page <?php echo $female_page; ?>/<?php echo $female_pages; ?> — <?php echo $female_total; ?> product(s)</div>
          <?php endif; ?>
        </div>

        <!-- TAB3 – Unisex -->
        <div class="tab-content" id="tab3">
          <form class="tab-search" method="GET" action="Administration_menu.php">
            <input type="hidden" name="male_name"    value="<?php echo htmlspecialchars($male_name); ?>">
            <input type="hidden" name="female_name"  value="<?php echo htmlspecialchars($female_name); ?>">
            <input type="hidden" name="male_page"    value="<?php echo $male_page; ?>">
            <input type="hidden" name="female_page"  value="<?php echo $female_page; ?>">
            <input type="hidden" name="cust_page"    value="<?php echo $cust_page; ?>">
            <input type="hidden" name="cust_name"    value="<?php echo htmlspecialchars($cust_name); ?>">
            <input type="hidden" name="cust_status"  value="<?php echo htmlspecialchars($cust_status); ?>">
            <input type="text" name="unisex_name" placeholder="Search product name…" value="<?php echo htmlspecialchars($unisex_name); ?>">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
            <?php if (!empty($unisex_name)): ?>
              <a class="reset-btn" href="<?php echo pageUrl(['unisex_name'=>'','unisex_page'=>1]); ?>">Reset</a>
            <?php endif; ?>
          </form>
          <table>
            <thead><tr><th>#</th><th>Image</th><th>Product Name</th><th>Category</th></tr></thead>
            <tbody>
              <?php if (empty($unisex_products)): ?>
              <tr><td colspan="4" style="text-align:center;color:#888;padding:20px;">No products found.</td></tr>
              <?php else: ?>
              <?php foreach ($unisex_products as $i => $p): ?>
              <tr>
                <td><?php echo ($unisex_page-1)*$prod_limit + $i + 1; ?></td>
                <td>
                  <?php if (!empty($p['image'])): ?>
                    <img src="../images/<?php echo htmlspecialchars($p['image']); ?>" class="product-img"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                    <span class="no-image" style="display:none;">No img</span>
                  <?php else: ?><span class="no-image">No img</span><?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['category']); ?></td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
          <?php if ($unisex_pages > 1): ?>
          <div class="pagination">
            <?php if ($unisex_page > 1): ?>
              <button class="pagination-btn" onclick="window.location.href='<?php echo pageUrl(['unisex_page'=>$unisex_page-1]); ?>#products'">&#10094;</button>
            <?php endif; ?>
            <?php for ($i=1; $i<=$unisex_pages; $i++): ?>
              <button class="pagination-btn <?php echo ($i==$unisex_page)?'active':''; ?>"
                      onclick="window.location.href='<?php echo pageUrl(['unisex_page'=>$i]); ?>#products'"><?php echo $i; ?></button>
            <?php endfor; ?>
            <?php if ($unisex_page < $unisex_pages): ?>
              <button class="pagination-btn" onclick="window.location.href='<?php echo pageUrl(['unisex_page'=>$unisex_page+1]); ?>#products'">&#10095;</button>
            <?php endif; ?>
          </div>
          <div class="pagination-info">Page <?php echo $unisex_page; ?>/<?php echo $unisex_pages; ?> — <?php echo $unisex_total; ?> product(s)</div>
          <?php endif; ?>
        </div>

      </div>
    </section>

    <!-- ======= Jewelry Types ======= -->
    <section id="categories" class="section">
      <header><h1>Jewelry Type Management</h1></header>
      <div class="user-actions">
        <a href="#" class="btn">Add Type</a>
        <a href="#" class="btn">Edit Type</a>
        <a href="#" class="btn">Delete / Hide Type</a>
      </div>
      <div class="user-list">
        <table>
          <tr><th>ID</th><th>Type Name</th><th>Description</th><th>Status</th><th>Action</th></tr>
          <tr>
            <td>1</td><td>Necklaces</td><td>Luxury gold and diamond necklaces</td><td>Visible</td>
            <td><a href="#" class="btn small">Edit</a> <a href="#" class="btn small">Hide</a></td>
          </tr>
          <tr>
            <td>2</td><td>Rings</td><td>Elegant diamond and platinum rings</td><td>Hidden</td>
            <td><a href="#" class="btn small">Edit</a> <a href="#" class="btn small">Show</a></td>
          </tr>
        </table>
      </div>
    </section>

    <!-- ======= Customers ======= -->
    <!-- Moved to standalone customer_management.php -->
    <section id="users" class="section" style="display:none !important;"></section>

    <!-- ======= Settings ======= -->
    <section id="settings" class="section">
      <header><h1>System Settings</h1></header>
      <div class="user-actions">
        <a href="admin_login.php?logout=1" class="btn logout-btn" onclick="return confirm('Are you sure you want to logout?')">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </section>

    <!-- ======= Stock ======= -->
    <section id="stock" class="section">
      <header><h1>Inventory Management</h1></header>
      <div class="user-actions">
        <a href="add_entry_form.php" class="btn">Add Stock Receipt</a>
        <a href="edit_entry_form.php" class="btn">Edit Stock Receipt</a>
        <a href="stocking_management.php" class="btn">Stocking Management</a>
      </div>
    </section>

  </div><!-- /.content -->

  <script>
    // Tab switching — remember active tab in localStorage
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(tab.dataset.tab).classList.add('active');
        localStorage.setItem('activeTabProducts', tab.dataset.tab);
      });
    });

    window.addEventListener('load', () => {
      const activeTab = localStorage.getItem('activeTabProducts');
      if (activeTab) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        const tabEl     = document.querySelector(`.tab[data-tab="${activeTab}"]`);
        const contentEl = document.getElementById(activeTab);
        if (tabEl)     tabEl.classList.add('active');
        if (contentEl) contentEl.classList.add('active');
      }
    });
  </script>
</body>
</html>