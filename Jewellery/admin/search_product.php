<?php
session_start();

$search_name     = trim($_GET['name']     ?? '');
$search_category = trim($_GET['category'] ?? '');

// TODO: Replace with real DB query
// Sample data
$all_products = [
    ['code'=>'R001','name'=>'Diamond Heart Necklace','img'=>'R001.jpg','price'=>100,'stock'=>13],
    ['code'=>'R002','name'=>'Diamond Heart Necklace','img'=>'R002.jpg','price'=>99, 'stock'=>7],
    ['code'=>'R003','name'=>'Diamond Heart Necklace','img'=>'R003.jpg','price'=>56, 'stock'=>6],
    ['code'=>'R004','name'=>'Diamond Heart Necklace','img'=>'R004.jpg','price'=>250,'stock'=>22],
    ['code'=>'R005','name'=>'Diamond Heart Necklace','img'=>'R005.jpg','price'=>22, 'stock'=>8],
];

$results = array_filter($all_products, function($p) use ($search_name, $search_category) {
    return ($search_name === '' || stripos($p['name'], $search_name) !== false);
});

// Handle delete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delCode = $_POST['code'] ?? '';
    // TODO: $pdo->prepare("DELETE FROM products WHERE code = ?")->execute([$delCode]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Results – Products</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
    body{display:flex;background:#f3f3f3;color:#333;min-height:100vh;}
    .sidebar{width:220px;background-color:#8e4b00;color:#f8ce86;display:flex;flex-direction:column;padding:20px;}
    .logo{text-align:center;margin-bottom:30px;}
    .logo img{width:80px;border-radius:50%;}
    .logo h2{font-size:18px;margin-top:10px;}
    .menu a{display:block;padding:12px;color:#f8ce86;text-decoration:none;border-radius:8px;margin-bottom:10px;font-weight:bold;transition:background 0.3s,color 0.3s;}
    .menu a:hover,.menu a.active{background-color:#f8ce86;color:#8e4b00;}
    main{flex:1;padding:25px;}
    h1{color:#8e4b00;margin-bottom:20px;font-size:24px;}
    .search-form{background:white;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,0.08);padding:20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;}
    .form-group{display:flex;flex-direction:column;flex:1;min-width:180px;}
    .form-group label{font-weight:bold;color:#333;margin-bottom:6px;}
    input,select{padding:10px;border:1px solid #ccc;border-radius:6px;font-size:15px;outline:none;transition:border-color 0.2s;}
    input:focus,select:focus{border-color:#8e4b00;}
    .btn{display:inline-block;background-color:#8e4b00;color:#f8ce86;border:none;border-radius:6px;padding:10px 16px;font-weight:bold;cursor:pointer;transition:0.3s;}
    .btn:hover{background-color:#f8ce86;color:#8e4b00;}
    .btn.secondary{background:#ccc;color:#333;}
    .btn.secondary:hover{background:#aaa;}
    .btn.small{padding:6px 10px;font-size:13px;}
    table{width:100%;border-collapse:collapse;background:white;border-radius:10px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.08);}
    th,td{padding:14px 16px;text-align:center;font-size:15px;border-bottom:1px solid #f0e2d0;}
    th{background:linear-gradient(195deg,#8e4b00,#a3670b);color:#f8ce86;font-weight:600;}
    tr:hover td{background:#f9f2e7;}
    .popup{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;justify-content:center;align-items:center;z-index:999;}
    .popup.active{display:flex;}
    .popup-content{background:white;border-radius:10px;padding:25px 30px;max-width:400px;width:90%;box-shadow:0 5px 15px rgba(0,0,0,0.2);text-align:center;}
    .toast{position:fixed;bottom:30px;right:30px;background-color:#8e4b00;color:#f8ce86;padding:14px 22px;border-radius:8px;opacity:0;pointer-events:none;transform:translateY(20px);transition:all 0.4s ease;z-index:1000;font-weight:600;}
    .toast.show{opacity:1;transform:translateY(0);}
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="logo">
      <img src="../images/Admin_login.jpg" alt="Admin Logo">
      <h2>Luxury Jewelry Admin</h2>
    </div>
    <div class="menu">
      <a href="Administration_menu.php#products">Jewelry List</a>
      <a href="product_management.php" class="active">Product Management</a>
      <a href="Price Manage/pricing.php">Pricing Management</a>
      <a href="Administration_menu.php#users">Customers</a>
      <a href="Order Manage/order_management.php">Order Management</a>
      <a href="Import product manage/import_management.php">Import Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking</a>
      <a href="Administration_menu.php#settings">Settings</a>
    </div>
  </div>

  <main>
    <h1>Search Results</h1>

    <form class="search-form" method="get" action="search_product.php">
      <div class="form-group">
        <label>Product Name</label>
        <input type="text" name="name" placeholder="Product Name"
               value="<?php echo htmlspecialchars($search_name); ?>">
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="category">
          <option value="">All</option>
          <option value="Male"   <?php echo $search_category === 'Male'   ? 'selected' : ''; ?>>Male</option>
          <option value="Female" <?php echo $search_category === 'Female' ? 'selected' : ''; ?>>Female</option>
          <option value="Unisex" <?php echo $search_category === 'Unisex' ? 'selected' : ''; ?>>Unisex</option>
        </select>
      </div>
      <button type="submit" class="btn">Search</button>
      <button type="button" class="btn secondary"
              onclick="window.location.href='product_management.php'">Back</button>
    </form>

    <table>
      <thead>
        <tr>
          <th>Product Code</th><th>Product Name</th><th>Image</th>
          <th>Price</th><th>Stock</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($results)): ?>
          <tr><td colspan="6" style="text-align:center;color:#999;">No products found.</td></tr>
        <?php else: ?>
          <?php foreach ($results as $p): ?>
          <tr>
            <td><?php echo htmlspecialchars($p['code']); ?></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><img src="../images/<?php echo htmlspecialchars($p['img']); ?>" alt="" width="60"></td>
            <td><?php echo htmlspecialchars($p['price']); ?></td>
            <td><?php echo htmlspecialchars($p['stock']); ?></td>
            <td>
              <a href="edit_product.php?code=<?php echo urlencode($p['code']); ?>"
                 class="btn small">Edit</a>
              <button class="btn small delete-btn"
                      data-code="<?php echo htmlspecialchars($p['code']); ?>">Delete</button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Delete Confirm Popup -->
    <div class="popup" id="deletePopup">
      <div class="popup-content">
        <h3>Delete Product</h3>
        <p>Are you sure you want to delete this product?</p>
        <button class="btn" id="confirmDeleteBtn">OK</button>
        <button class="btn secondary" onclick="closePopup('deletePopup')" style="margin-left:10px;">Cancel</button>
      </div>
    </div>

    <div id="toast" class="toast">Product deleted successfully.</div>
  </main>

  <script>
    let pendingCode = '';

    function openPopup(id)  { document.getElementById(id).classList.add('active'); }
    function closePopup(id) { document.getElementById(id).classList.remove('active'); }

    function showToast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 2500);
    }

    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        pendingCode = btn.dataset.code;
        openPopup('deletePopup');
      });
    });

    document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
      fetch('search_product.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=delete&code=${encodeURIComponent(pendingCode)}`
      })
      .then(r => r.json())
      .then(data => {
        closePopup('deletePopup');
        showToast(data.message);
        if (data.success) setTimeout(() => location.reload(), 1800);
      });
    });
  </script>
</body>
</html>
