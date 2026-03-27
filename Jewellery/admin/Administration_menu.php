<?php
session_start();

require_once '../config/config.php';

// ── Handle DELETE product (AJAX POST) ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    $pid = trim($_POST['product_id'] ?? '');
    if ($pid !== '') {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("s", $pid);
        $ok = $stmt->execute();
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Product deleted.' : $conn->error]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing product ID.']);
    exit;
}

// ── Fetch products grouped by gender ──────────────────────────────────────
function fetchByGender($conn, $gender) {
    $stmt = $conn->prepare("SELECT id, name, image, category, gender, price, stock FROM products WHERE gender = ? ORDER BY id");
    $stmt->bind_param("s", $gender);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$male_products   = fetchByGender($conn, 'Male');
$female_products = fetchByGender($conn, 'Female');
$unisex_products = fetchByGender($conn, 'Unisex');

// ── Fetch all products for Product Management table ───────────────────────
$all_products = $conn->query("SELECT id, name, image, price, stock, gender, category FROM products ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// ── Fetch customers (join users) ──────────────────────────────────────────
$cust_result = $conn->query(
    "SELECT c.id, c.full_name AS name, u.email, u.id AS uid,
            COALESCE(u.status, 'Active') AS status
     FROM customers c
     JOIN users u ON c.user_id = u.id
     ORDER BY c.id"
);
$customers = $cust_result ? $cust_result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Luxury Jewelry Admin Panel</title>
  <link rel="stylesheet" href="admin_function.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="sidebar">
    <div class="logo">
      <img src="../images/Admin_login.jpg" alt="Admin Logo">
      <h2>Luxury Jewelry Admin</h2>
    </div>
    <div class="menu">
      <a href="#products">Jewelry List</a>
      <a href="#product-manage">Product Management</a>
      <a href="Price Manage/pricing.php">Pricing Management</a>
      <a href="#users">Customers</a>
      <a href="Order Manage/order_management.php">Order Management</a>
      <a href="Import_product/import_management.php">Import Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking Management</a>
      <a href="#settings">Settings</a>
    </div>
  </div>

  <div class="content">

    <!-- ======= Jewelry List ======= -->
    <section id="products" class="section">
      <header><h1>Jewelry Inventory</h1></header>

      <div class="tabs">
        <div class="tab active" data-tab="tab1">Male</div>
        <div class="tab" data-tab="tab2">Female</div>
        <div class="tab" data-tab="tab3">Unisex</div>
      </div>

      <div class="product-list">

        <!-- TAB1 – Male -->
        <div class="tab-content active" id="tab1">
          <table>
            <thead><tr><th>No.</th><th>Image</th><th>Product Name</th><th>Category</th></tr></thead>
            <tbody>
              <?php if (empty($male_products)): ?>
                <tr><td colspan="4" style="text-align:center;color:#999;">No male products found.</td></tr>
              <?php else: ?>
                <?php foreach ($male_products as $i => $p): ?>
                <tr>
                  <td><?php echo $i + 1; ?></td>
                  <td><img src="../images/<?php echo htmlspecialchars($p['image']); ?>" width="60" style="border-radius:6px;"></td>
                  <td><?php echo htmlspecialchars($p['name']); ?></td>
                  <td>Male</td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- TAB2 – Female -->
        <div class="tab-content" id="tab2">
          <table>
            <thead><tr><th>No.</th><th>Image</th><th>Product Name</th><th>Category</th></tr></thead>
            <tbody>
              <?php if (empty($female_products)): ?>
                <tr><td colspan="4" style="text-align:center;color:#999;">No female products found.</td></tr>
              <?php else: ?>
                <?php foreach ($female_products as $i => $p): ?>
                <tr>
                  <td><?php echo $i + 1; ?></td>
                  <td><img src="../images/<?php echo htmlspecialchars($p['image']); ?>" width="60" style="border-radius:6px;"></td>
                  <td><?php echo htmlspecialchars($p['name']); ?></td>
                  <td>Female</td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- TAB3 – Unisex -->
        <div class="tab-content" id="tab3">
          <table>
            <thead><tr><th>No.</th><th>Image</th><th>Product Name</th><th>Category</th></tr></thead>
            <tbody>
              <?php if (empty($unisex_products)): ?>
                <tr><td colspan="4" style="text-align:center;color:#999;">No unisex products found.</td></tr>
              <?php else: ?>
                <?php foreach ($unisex_products as $i => $p): ?>
                <tr>
                  <td><?php echo $i + 1; ?></td>
                  <td><img src="../images/<?php echo htmlspecialchars($p['image']); ?>" width="60" style="border-radius:6px;"></td>
                  <td><?php echo htmlspecialchars($p['name']); ?></td>
                  <td>Unisex</td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>
    </section>

    <!-- ======= Product Management ======= -->
    <section id="product-manage" class="section">
      <header><h1>Jewelry Product Management</h1></header>

      <div class="user-actions">
        <a href="add_product.php" class="btn">+ Add Product</a>
      </div>

      <div class="search-section">
        <div class="search-group">
          <label class="search-label" for="product-name">Search by Name</label>
          <input type="text" id="product-name" class="search-input" placeholder="Product Name">
        </div>
        <div class="search-group">
          <label class="search-label" for="product-category">Filter by Category</label>
          <select id="product-category" class="search-select">
            <option value="">All</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Unisex">Unisex</option>
          </select>
        </div>
        <button class="btn-search" onclick="window.location.href='search_product.php'">
          <i class="fas fa-search"></i>
        </button>
      </div>

      <div class="user-list">
        <table>
          <tr>
            <th>Product Code</th><th>Product Name</th><th>Image</th>
            <th>Price</th><th>Stock</th><th>Category</th><th>Action</th>
          </tr>
          <?php if (empty($all_products)): ?>
            <tr><td colspan="7" style="text-align:center;color:#999;">No products in database.</td></tr>
          <?php else: ?>
            <?php foreach ($all_products as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p['id']); ?></td>
              <td><?php echo htmlspecialchars($p['name']); ?></td>
              <td><img src="../images/<?php echo htmlspecialchars($p['image']); ?>" alt="" width="60" style="border-radius:6px;"></td>
              <td>$<?php echo number_format($p['price'], 2); ?></td>
              <td><?php echo intval($p['stock']); ?></td>
              <td><?php echo htmlspecialchars($p['gender']); ?></td>
              <td>
                <a href="edit_product.php?code=<?php echo urlencode($p['id']); ?>" class="btn small">Edit</a>
                <button class="btn small delete-product-btn" data-id="<?php echo htmlspecialchars($p['id']); ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>">Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </table>
      </div>
    </section>

    <!-- ======= Customers ======= -->
    <section id="users" class="section">
      <header><h1>Customer Management</h1></header>

      <div class="search-section">
        <div class="search-group">
          <label class="search-label" for="customer-name">Search by Name</label>
          <input type="text" id="customer-name" class="search-input" placeholder="Enter customer name">
        </div>
        <div class="search-group">
          <label class="search-label" for="customer-status">Filter by Status</label>
          <select id="customer-status" class="search-select">
            <option value="">All</option>
            <option value="Active">Active</option>
            <option value="Locked">Locked</option>
          </select>
        </div>
        <button class="btn-search" onclick="window.location.href='search_customer.php'">
          <i class="fas fa-search"></i>
        </button>
      </div>

      <div class="user-list">
        <table>
          <tr><th>ID</th><th>Customer Name</th><th>Email</th><th>Status</th><th>Action</th></tr>
          <?php if (empty($customers)): ?>
            <tr><td colspan="5" style="text-align:center;color:#999;">No customers found.</td></tr>
          <?php else: ?>
            <?php foreach ($customers as $c): ?>
            <tr>
              <td><?php echo intval($c['id']); ?></td>
              <td><?php echo htmlspecialchars($c['name']); ?></td>
              <td><?php echo htmlspecialchars($c['email']); ?></td>
              <td>
                <span class="status-badge <?php echo strtolower($c['status']); ?>">
                  <?php echo htmlspecialchars($c['status']); ?>
                </span>
              </td>
              <td>
                <button class="btn small view-customer-btn"
                        data-name="<?php echo htmlspecialchars($c['name']); ?>"
                        data-email="<?php echo htmlspecialchars($c['email']); ?>"
                        data-status="<?php echo htmlspecialchars($c['status']); ?>">View</button>
                <button class="btn small action-btn" data-action="reset" data-uid="<?php echo intval($c['uid']); ?>">Reset PW</button>
                <?php if ($c['status'] === 'Active'): ?>
                  <button class="btn small action-btn" data-action="lock" data-uid="<?php echo intval($c['uid']); ?>">Lock</button>
                <?php else: ?>
                  <button class="btn small action-btn" data-action="unlock" data-uid="<?php echo intval($c['uid']); ?>">Unlock</button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </table>
      </div>
    </section>

    <!-- ======= Settings ======= -->
    <section id="settings" class="section">
      <header><h1>System Settings</h1></header>
      <div class="user-actions">
        <a href="admin_login.php" class="btn logout-btn">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </section>

  </div><!-- /.content -->

  <!-- ── Delete Product Popup ── -->
  <div class="popup" id="deleteProductPopup">
    <div class="popup-content">
      <h3>Delete Product</h3>
      <p>Are you sure you want to delete <strong id="deleteProductName"></strong>?</p>
      <button class="btn" id="confirmDeleteProductBtn">Yes, Delete</button>
      <button class="btn secondary" onclick="closePopup('deleteProductPopup')" style="margin-left:10px;">Cancel</button>
    </div>
  </div>

  <!-- ── View Customer Popup ── -->
  <div class="popup" id="viewCustomerPopup">
    <div class="popup-content">
      <h3>Customer Details</h3>
      <p><strong>Name:</strong> <span id="viewCustName"></span></p>
      <p><strong>Email:</strong> <span id="viewCustEmail"></span></p>
      <p><strong>Status:</strong> <span id="viewCustStatus"></span></p>
      <button class="btn secondary" onclick="closePopup('viewCustomerPopup')" style="margin-top:15px;">Close</button>
    </div>
  </div>

  <!-- ── Confirm Action Popup ── -->
  <div class="popup" id="confirmActionPopup">
    <div class="popup-content">
      <h3 id="confirmActionTitle">Confirm</h3>
      <p id="confirmActionMsg">Are you sure?</p>
      <button class="btn" id="confirmActionOkBtn">Confirm</button>
      <button class="btn secondary" onclick="closePopup('confirmActionPopup')" style="margin-left:10px;">Cancel</button>
    </div>
  </div>

  <!-- ── Toast ── -->
  <div id="toast" class="toast"></div>

  <style>
    .status-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
    .status-badge.active  { background:#e8f5e9; color:#2e7d32; }
    .status-badge.locked  { background:#ffebee; color:#c62828; }
    .logout-btn {
      display:inline-flex; align-items:center; gap:10px; padding:12px 24px;
      background:linear-gradient(135deg,#d9534f,#c9302c); color:white;
      text-decoration:none; border-radius:8px; font-weight:600; transition:all 0.3s;
      box-shadow:0 4px 10px rgba(217,83,79,0.3); border:none; cursor:pointer;
    }
    .logout-btn:hover { transform:translateY(-2px); box-shadow:0 6px 15px rgba(217,83,79,0.4); }
    .popup { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);
             display:none; justify-content:center; align-items:center; z-index:999; }
    .popup.active { display:flex; }
    .popup-content { background:white; border-radius:10px; padding:28px 32px; max-width:420px;
                     width:90%; box-shadow:0 5px 20px rgba(0,0,0,0.2); text-align:center; }
    .popup-content h3 { margin-bottom:12px; color:#8e4b00; }
    .btn.secondary { background:#e0e0e0; color:#333; }
    .btn.secondary:hover { background:#c0c0c0; }
    .toast { position:fixed; bottom:30px; right:30px; background:#8e4b00; color:#f8ce86;
             padding:14px 22px; border-radius:8px; opacity:0; pointer-events:none;
             transform:translateY(20px); transition:all 0.4s ease; z-index:1000; font-weight:600; }
    .toast.show { opacity:1; transform:translateY(0); }
  </style>

  <script>
    // ── Tab switching ──
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
        const tabEl = document.querySelector(`.tab[data-tab="${activeTab}"]`);
        const contentEl = document.getElementById(activeTab);
        if (tabEl) tabEl.classList.add('active');
        if (contentEl) contentEl.classList.add('active');
      }
    });

    // ── Popup helpers ──
    function openPopup(id)  { document.getElementById(id).classList.add('active'); }
    function closePopup(id) { document.getElementById(id).classList.remove('active'); }

    function showToast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 2800);
    }

    // ── Delete Product ──
    let pendingDeleteId = '';
    document.querySelectorAll('.delete-product-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        pendingDeleteId = btn.dataset.id;
        document.getElementById('deleteProductName').textContent = btn.dataset.name;
        openPopup('deleteProductPopup');
      });
    });
    document.getElementById('confirmDeleteProductBtn').addEventListener('click', () => {
      fetch('Administration_menu.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=delete_product&product_id=${encodeURIComponent(pendingDeleteId)}`
      })
      .then(r => r.json())
      .then(data => {
        closePopup('deleteProductPopup');
        showToast(data.message);
        if (data.success) setTimeout(() => location.reload(), 1500);
      });
    });

    // ── View Customer ──
    document.querySelectorAll('.view-customer-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('viewCustName').textContent   = btn.dataset.name;
        document.getElementById('viewCustEmail').textContent  = btn.dataset.email;
        document.getElementById('viewCustStatus').textContent = btn.dataset.status;
        openPopup('viewCustomerPopup');
      });
    });

    // ── Customer Actions (lock / unlock / reset) ──
    let pendingAction = '', pendingUid = '';
    document.querySelectorAll('.action-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        pendingAction = btn.dataset.action;
        pendingUid    = btn.dataset.uid;
        const titles = { reset:'Reset Password', lock:'Lock Account', unlock:'Unlock Account' };
        const msgs   = {
          reset:  'Reset this customer\'s password to "default123"?',
          lock:   'Are you sure you want to lock this account?',
          unlock: 'Are you sure you want to unlock this account?',
        };
        document.getElementById('confirmActionTitle').textContent = titles[pendingAction] || 'Confirm';
        document.getElementById('confirmActionMsg').textContent   = msgs[pendingAction]   || 'Are you sure?';
        openPopup('confirmActionPopup');
      });
    });
    document.getElementById('confirmActionOkBtn').addEventListener('click', () => {
      fetch('search_customer.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=${encodeURIComponent(pendingAction)}&user_id=${encodeURIComponent(pendingUid)}`
      })
      .then(r => r.json())
      .then(data => {
        closePopup('confirmActionPopup');
        showToast(data.message);
        if (data.success) setTimeout(() => location.reload(), 1500);
      });
    });
  </script>

</body>
</html>