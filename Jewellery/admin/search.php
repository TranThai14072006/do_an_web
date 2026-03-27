<?php
session_start();

// Read search parameters
$search_name     = trim($_GET['name']     ?? '');
$search_category = trim($_GET['category'] ?? '');

// TODO: Replace with real DB query
// $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? AND (? = '' OR category = ?)");
// $stmt->execute(["%$search_name%", $search_category, $search_category]);
// $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sample data
$all_products = [
    ['no'=>2,'img'=>'R002.jpg','name'=>'Winston Anchor Ring',   'category'=>'Ring'],
    ['no'=>6,'img'=>'R006.jpg','name'=>'Arielle Princess CZ Ring','category'=>'Ring'],
    ['no'=>3,'img'=>'R005.jpg','name'=>'Ula Opal Teardrop Ring','category'=>'Ring'],
];

$results = array_filter($all_products, function($p) use ($search_name, $search_category) {
    $matchName = $search_name === '' || stripos($p['name'], $search_name) !== false;
    $matchCat  = $search_category === '' || strtolower($p['category']) === strtolower($search_category);
    return $matchName && $matchCat;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Luxury Jewelry Admin Panel</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
    body{display:flex;background-color:#f3f3f3;min-height:100vh;}
    .sidebar{width:220px;background-color:#8e4b00;color:#f8ce86;height:100vh;position:fixed;overflow-y:auto;display:flex;flex-direction:column;padding:20px;}
    .logo{text-align:center;margin-bottom:30px;}
    .logo img{width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:10px;}
    .logo h2{font-size:18px;margin-top:10px;}
    .menu{padding:20px 0;}
    .menu a{display:block;padding:12px;color:#f8ce86;text-decoration:none;border-radius:8px;margin-bottom:10px;font-weight:bold;transition:background 0.3s,color 0.3s;}
    .menu a:hover,.menu a.active{background-color:#f8ce86;color:#8e4b00;}
    .main-content{margin-left:220px;padding:25px;width:calc(100% - 220px);}
    .section{background:white;border-radius:10px;box-shadow:0 3px 8px rgba(0,0,0,0.1);padding:25px;margin-bottom:20px;}
    header h1{color:#8e4b00;margin-bottom:20px;font-size:22px;padding-bottom:10px;border-bottom:1px solid #eee;}
    .search-section{display:flex;flex-wrap:wrap;gap:15px;margin-bottom:20px;padding:15px;background:#f9f2e7;border-radius:8px;}
    .search-group{display:flex;flex-direction:column;flex:1;min-width:200px;}
    .search-label{font-weight:600;margin-bottom:5px;color:#8e4b00;}
    .search-input{padding:10px 12px;border:1px solid #ccc;border-radius:6px;font-size:15px;transition:border-color 0.2s;}
    .search-input:focus{border-color:#8e4b00;outline:none;}
    .btn-search,.btn-reset{padding:10px 20px;border:none;border-radius:6px;cursor:pointer;font-weight:600;transition:all 0.3s;align-self:flex-end;}
    .btn-search{background:linear-gradient(195deg,#a3670b,#8e4b00);color:white;box-shadow:0 2px 5px rgba(142,75,0,0.3);}
    .btn-search:hover{background:linear-gradient(195deg,#c17a0d,#8e4b00);transform:translateY(-2px);}
    .btn-reset{background:#e0e0e0;color:#333;}
    .btn-reset:hover{background:#bcbcbc;transform:translateY(-2px);}
    table{width:100%;border-collapse:collapse;margin-bottom:20px;background:white;border-radius:10px;overflow:hidden;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
    th,td{padding:12px 15px;text-align:left;border-bottom:1px solid #ddd;}
    th{background-color:#8e4b00;color:#f8ce86;font-weight:600;}
    tr:hover{background-color:#f9f2e7;}
    td img{border-radius:5px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
    .pagination{display:flex;justify-content:center;gap:5px;margin-top:20px;}
    .page-link{padding:8px 15px;border:1px solid #ccc;border-radius:6px;text-decoration:none;color:#8e4b00;transition:all 0.3s;background:white;}
    .page-link:hover{background:#f8ce86;color:#5a2d00;}
    .page-link.active{background:linear-gradient(195deg,#8e4b00,#b87127);color:white;border-color:#8e4b00;}
    .logout-btn{display:inline-flex;align-items:center;gap:10px;padding:12px 28px;background:linear-gradient(195deg,#a3670b,#8e4b00);color:white;text-decoration:none;border-radius:8px;font-weight:600;transition:all 0.3s ease;box-shadow:0 3px 8px rgba(142,75,0,0.3);border:none;cursor:pointer;margin-top:20px;}
    .logout-btn:hover{background:linear-gradient(195deg,#c17a0d,#a3670b);transform:translateY(-2px);}
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
      <a href="Administration_menu.php#product-manage">Product Management</a>
      <a href="Price Manage/pricing.php">Pricing Management</a>
      <a href="Administration_menu.php#users">Customers</a>
      <a href="Order Manage/order_management.php">Order Management</a>
      <a href="Import product manage/import_management.php">Import Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking Management</a>
      <a href="Administration_menu.php#settings">Settings</a>
    </div>
  </div>

  <div class="main-content">
    <section id="products" class="section">
      <header><h1>Search Results</h1></header>

      <div class="product-list">
        <form method="get" action="search.php">
          <div class="search-section">
            <div class="search-group">
              <label class="search-label">Product Name</label>
              <input type="text" name="name" class="search-input"
                     value="<?php echo htmlspecialchars($search_name); ?>">
            </div>
            <div class="search-group">
              <label class="search-label">Category</label>
              <select name="category" class="search-input">
                <option value="">All</option>
                <?php foreach (['Male','Female','Unisex','Ring'] as $cat): ?>
                <option value="<?php echo $cat; ?>"
                  <?php echo ($search_category === $cat) ? 'selected' : ''; ?>>
                  <?php echo $cat; ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn-search">Search</button>
            <button type="button" class="btn-reset"
                    onclick="window.location.href='search.php'">Reset</button>
          </div>
        </form>

        <table>
          <thead>
            <tr><th>No.</th><th>Image</th><th>Product Name</th><th>Category</th></tr>
          </thead>
          <tbody>
            <?php if (empty($results)): ?>
              <tr><td colspan="4" style="text-align:center;color:#999;">No products found.</td></tr>
            <?php else: ?>
              <?php foreach ($results as $p): ?>
              <tr>
                <td><?php echo $p['no']; ?></td>
                <td><img src="../images/<?php echo htmlspecialchars($p['img']); ?>" width="60"></td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['category']); ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
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

      <button class="logout-btn"
              onclick="window.location.href='Administration_menu.php#products'">Back</button>
    </section>
  </div>
</body>
</html>