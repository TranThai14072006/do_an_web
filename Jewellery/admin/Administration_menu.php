<?php
session_start();

// Optional: protect this page
// if (empty($_SESSION['admin_logged_in'])) {
//     header('Location: admin_login.php');
//     exit;
// }

// Sample data – replace with DB queries
$products = [
    ['code'=>'R001','name'=>'Diamond Heart Necklace','img'=>'R001.jpg','price'=>100,'stock'=>13],
    ['code'=>'R002','name'=>'Diamond Heart Necklace','img'=>'R002.jpg','price'=>99, 'stock'=>7],
    ['code'=>'R003','name'=>'Diamond Heart Necklace','img'=>'R003.jpg','price'=>56, 'stock'=>6],
    ['code'=>'R004','name'=>'Diamond Heart Necklace','img'=>'R004.jpg','price'=>250,'stock'=>22],
    ['code'=>'R005','name'=>'Diamond Heart Necklace','img'=>'R005.jpg','price'=>22, 'stock'=>8],
];

$customers = [
    ['id'=>1,'name'=>'Alice Nguyen','email'=>'alice@example.com','status'=>'Active'],
    ['id'=>2,'name'=>'Emma Tran',   'email'=>'emma@example.com', 'status'=>'Locked'],
    ['id'=>3,'name'=>'David Le',    'email'=>'david@example.com','status'=>'Active'],
    ['id'=>4,'name'=>'Olivia Pham', 'email'=>'olivia@example.com','status'=>'Active'],
    ['id'=>5,'name'=>'Lucas Hoang', 'email'=>'lucas@example.com','status'=>'Locked'],
    ['id'=>6,'name'=>'Sophia Vu',   'email'=>'sophia@example.com','status'=>'Active'],
    ['id'=>7,'name'=>'Henry Bui',   'email'=>'henry@example.com','status'=>'Locked'],
    ['id'=>8,'name'=>'Isabella Do', 'email'=>'isabella@example.com','status'=>'Active'],
    ['id'=>9,'name'=>'Ethan Tran',  'email'=>'ethan@example.com','status'=>'Active'],
    ['id'=>10,'name'=>'Chloe Nguyen','email'=>'chloe@example.com','status'=>'Locked'],
];

$male_products = [
    ['no'=>2,'img'=>'R002.jpg','name'=>'Winston Anchor Ring','category'=>'Ring'],
    ['no'=>6,'img'=>'R006.jpg','name'=>'Arielle Princess CZ Ring','category'=>'Ring'],
    ['no'=>3,'img'=>'R005.jpg','name'=>'Ula Opal Teardrop Ring','category'=>'Ring'],
];

$female_products = [
    ['no'=>1,'img'=>'R001.jpg','name'=>'Kane Moissanite Ring','category'=>'Ring'],
    ['no'=>2,'img'=>'R002.jpg','name'=>'Kane Moissanite Ring','category'=>'Ring'],
    ['no'=>3,'img'=>'R003.jpg','name'=>'Ula Opal Teardrop Ring','category'=>'Ring'],
    ['no'=>4,'img'=>'R004.jpg','name'=>'Platinum Clover Charm Ring','category'=>'Ring'],
    ['no'=>5,'img'=>'R005.jpg','name'=>'Paisley Moissanite Ring','category'=>'Ring'],
    ['no'=>7,'img'=>'R007.jpg','name'=>'Miracle Queen CZ Ring','category'=>'Ring'],
    ['no'=>8,'img'=>'R008.jpg','name'=>'Niche Crown Stack Ring','category'=>'Ring'],
    ['no'=>9,'img'=>'R009.jpg','name'=>'Rosemary Topaz Ring','category'=>'Ring'],
    ['no'=>10,'img'=>'R010.jpg','name'=>'Royal Moissanite Ring','category'=>'Ring'],
];

$unisex_products = [
    ['no'=>1,'img'=>'R006.jpg','name'=>'Kane Moissanite Ring','category'=>'Ring'],
    ['no'=>3,'img'=>'R005.jpg','name'=>'Ula Opal Teardrop Ring','category'=>'Ring'],
];
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
        <div class="tab active" data-tab="tab1">Male</div>
        <div class="tab" data-tab="tab2">Female</div>
        <div class="tab" data-tab="tab3">Unisex</div>
      </div>

      <div class="product-list">

        <!-- TAB1 – Male -->
        <div class="tab-content active" id="tab1">
          <div class="search-section">
            <div class="search-group"><label class="search-label">Product Name</label><input type="text" class="search-input"></div>
            <div class="search-group"><label class="search-label">Category</label>
              <select class="search-input"><option>All</option><option>Male</option><option>Female</option><option>Unisex</option></select>
            </div>
            <button class="btn-search" onclick="window.location.href='search.php'">Search</button>
            <button class="btn-reset"  onclick="window.location.href='search.php'">Reset</button>
          </div>
          <table>
            <thead><tr><th>No.</th><th>Image</th><th>Product Name</th><th>Category</th></tr></thead>
            <tbody>
              <?php foreach ($male_products as $p): ?>
              <tr>
                <td><?php echo $p['no']; ?></td>
                <td><img src="../images/<?php echo htmlspecialchars($p['img']); ?>" width="60"></td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['category']); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="pagination">
            <a href="#" class="page-link prev">← Previous</a>
            <a href="#" class="page-link active">1</a>
            <a href="#" class="page-link">2</a>
            <a href="#" class="page-link">3</a>
            <a href="#" class="page-link next">Next →</a>
          </div>
        </div>

        <!-- TAB2 – Female -->
        <div class="tab-content" id="tab2">
          <div class="search-section">
            <div class="search-group"><label class="search-label">Product Name</label><input type="text" class="search-input"></div>
            <div class="search-group"><label class="search-label">Category</label>
              <select class="search-input"><option>All</option><option>Male</option><option>Female</option><option>Unisex</option></select>
            </div>
            <button class="btn-search" onclick="window.location.href='search.php'">Search</button>
            <button class="btn-reset"  onclick="window.location.href='search.php'">Reset</button>
          </div>
          <table>
            <thead><tr><th>No.</th><th>Image</th><th>Product Name</th><th>Category</th></tr></thead>
            <tbody>
              <?php foreach ($female_products as $p): ?>
              <tr>
                <td><?php echo $p['no']; ?></td>
                <td><img src="../images/<?php echo htmlspecialchars($p['img']); ?>" width="60"></td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['category']); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="pagination">
            <a href="#" class="page-link prev">← Previous</a>
            <a href="#" class="page-link active">1</a>
            <a href="#" class="page-link">2</a>
            <a href="#" class="page-link">3</a>
            <a href="#" class="page-link next">Next →</a>
          </div>
        </div>

        <!-- TAB3 – Unisex -->
        <div class="tab-content" id="tab3">
          <div class="search-section">
            <div class="search-group"><label class="search-label">Product Name</label><input type="text" class="search-input"></div>
            <div class="search-group"><label class="search-label">Category</label>
              <select class="search-input"><option>All</option><option>Male</option><option>Female</option><option>Unisex</option></select>
            </div>
            <button class="btn-search" onclick="window.location.href='search.php'">Search</button>
            <button class="btn-reset"  onclick="window.location.href='search.php'">Reset</button>
          </div>
          <table>
            <thead><tr><th>No.</th><th>Image</th><th>Product Name</th><th>Category</th></tr></thead>
            <tbody>
              <?php foreach ($unisex_products as $p): ?>
              <tr>
                <td><?php echo $p['no']; ?></td>
                <td><img src="../images/<?php echo htmlspecialchars($p['img']); ?>" width="60"></td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['category']); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="pagination">
            <a href="#" class="page-link prev">← Previous</a>
            <a href="#" class="page-link active">1</a>
            <a href="#" class="page-link">2</a>
            <a href="#" class="page-link">3</a>
            <a href="#" class="page-link next">Next →</a>
          </div>
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

    <!-- ======= Product Management ======= -->
    <section id="product-manage" class="section">
      <header><h1>Jewelry Product Management</h1></header>

      <div class="user-actions">
        <a href="add_product.php" class="btn">Add Product</a>
      </div>

      <div class="search-section">
        <div class="search-group">
          <label class="search-label" for="product-name">Search by Name</label>
          <input type="text" id="product-name" class="search-input" placeholder="Product Name">
        </div>
        <div class="search-group">
          <label class="search-label" for="product-category">Search by Category</label>
          <select id="product-category" class="search-select">
            <option value="">All</option>
            <option value="1">Male</option>
            <option value="2">Female</option>
            <option value="3">Unisex</option>
          </select>
        </div>
        <button class="btn-search" onclick="window.location.href='search_product.php'">
          <i class="material-icons-round">search</i>
        </button>
      </div>

      <div class="user-list">
        <table>
          <tr>
            <th>Product Code</th><th>Product Name</th><th>Image</th>
            <th>Price</th><th>Stock</th><th>Action</th>
          </tr>
          <?php foreach ($products as $p): ?>
          <tr>
            <td><?php echo htmlspecialchars($p['code']); ?></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><img src="../images/<?php echo htmlspecialchars($p['img']); ?>" alt="" width="60"></td>
            <td><?php echo htmlspecialchars($p['price']); ?></td>
            <td><?php echo htmlspecialchars($p['stock']); ?></td>
            <td>
              <a href="edit_product.php?code=<?php echo urlencode($p['code']); ?>" class="btn small">Edit</a>
              <label for="deletePopup" class="btn small">Delete</label>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <div class="pagination">
        <button class="pagination-btn"><span class="arrow-icon">&#10094;</span></button>
        <button class="pagination-btn active">1</button>
        <button class="pagination-btn">2</button>
        <button class="pagination-btn">3</button>
        <button class="pagination-btn">4</button>
        <button class="pagination-btn">5</button>
        <button class="pagination-btn"><span class="arrow-icon">&#10095;</span></button>
      </div>

      <input type="checkbox" id="deletePopup" hidden>
      <div class="popup">
        <label for="deletePopup" class="overlay"></label>
        <div class="popup-box">
          <h3>Confirm Delete</h3>
          <p>Are you sure you want to delete this product?</p>
          <label for="deletePopup" class="btn close-btn">OK</label>
        </div>
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
            <option value="active">Active</option>
            <option value="locked">Locked</option>
          </select>
        </div>
        <button class="btn-search">
          <i class="material-icons-round" onclick="window.location.href='search_customer.php'">search</i>
        </button>
      </div>

      <input type="checkbox" id="toggleCustomerList" hidden>
      <div class="user-actions">
        <label for="toggleCustomerList" class="btn">Show / Hide List</label>
        <p> </p>
      </div>

      <div class="user-list">
        <table>
          <tr><th>ID</th><th>Customer Name</th><th>Email</th><th>Status</th><th>Action</th></tr>
          <?php foreach ($customers as $c): ?>
          <tr>
            <td><?php echo $c['id']; ?></td>
            <td><?php echo htmlspecialchars($c['name']); ?></td>
            <td><?php echo htmlspecialchars($c['email']); ?></td>
            <td><?php echo htmlspecialchars($c['status']); ?></td>
            <td>
              <label for="view" class="btn small">View</label>
              <label for="resetPopup" class="btn small">Reset</label>
              <?php if ($c['status'] === 'Active'): ?>
                <label for="lockPopup" class="btn small">Lock</label>
              <?php else: ?>
                <label for="unlockPopup" class="btn small">Unlock</label>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>

        <div class="pagination">
          <input type="radio" name="page" id="page1" checked hidden>
          <input type="radio" name="page" id="page2" hidden>
          <input type="radio" name="page" id="page3" hidden>
          <div class="page-buttons">
            <label for="page1" class="pagination-btn">1</label>
            <label for="page2" class="pagination-btn">2</label>
            <label for="page3" class="pagination-btn">3</label>
          </div>
          <div class="pagination-content">
            <div class="page page1"><p>Showing customers 1–5</p></div>
            <div class="page page2"><p>Showing customers 6–10</p></div>
            <div class="page page3"><p>Page 3</p></div>
          </div>
        </div>
      </div>

      <!-- Popups -->
      <input type="checkbox" id="resetPopup" hidden>
      <div class="popup">
        <label for="resetPopup" class="overlay"></label>
        <div class="popup-box">
          <h3>Password Reset</h3>
          <p>The customer's password has been successfully reset.</p>
          <label for="resetPopup" class="btn close-btn">OK</label>
        </div>
      </div>

      <input type="checkbox" id="lockPopup" hidden>
      <div class="popup">
        <label for="lockPopup" class="overlay"></label>
        <div class="popup-box">
          <h3>Account Locked</h3>
          <p>The customer's account has been locked successfully.</p>
          <label for="lockPopup" class="btn close-btn">OK</label>
        </div>
      </div>

      <input type="checkbox" id="unlockPopup" hidden>
      <div class="popup">
        <label for="unlockPopup" class="overlay"></label>
        <div class="popup-box">
          <h3>Account Unlocked</h3>
          <p>The customer's account has been successfully unlocked.</p>
          <label for="unlockPopup" class="btn close-btn">OK</label>
        </div>
      </div>

      <input type="checkbox" id="view" hidden>
      <div class="user-detail">
        <label for="view" class="overlay"></label>
        <div class="detail-box">
          <h3>Customer Details</h3>
          <p><strong>Name:</strong> Demo Customer</p>
          <p><strong>Email:</strong> demo@example.com</p>
          <p><strong>Phone:</strong> +84 900 123 456</p>
          <p><strong>Address:</strong> 123 Demo Street, District 1, HCMC</p>
          <label for="view" class="btn close-btn">Close</label>
        </div>
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

  <style>
    .logout-btn {
      display: inline-flex; align-items: center; gap: 10px;
      padding: 12px 24px;
      background: linear-gradient(135deg, #d9534f, #c9302c);
      color: white; text-decoration: none; border-radius: 8px;
      font-weight: 600; transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(217,83,79,0.3); border: none; cursor: pointer;
    }
    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(217,83,79,0.4);
      background: linear-gradient(135deg, #c9302c, #ac2925);
    }
    .logout-btn i { font-size: 18px; }
  </style>

  <script>
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
  </script>
</body>
</html>