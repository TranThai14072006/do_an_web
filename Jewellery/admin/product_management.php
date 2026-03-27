<?php
session_start();
require_once '../config/config.php';

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Search variables
$search_name = isset($_GET['product_name']) ? $conn->real_escape_string($_GET['product_name']) : '';
$search_category = isset($_GET['product_category']) ? $conn->real_escape_string($_GET['product_category']) : '';

$where_clause = "WHERE 1=1";
if (!empty($search_name)) {
    $where_clause .= " AND name LIKE '%$search_name%'";
}
if (!empty($search_category) && $search_category != "All") {
    $where_clause .= " AND category = '$search_category'";
}

// Fetch products
$sql = "SELECT id as code, name, image, price, stock FROM products $where_clause ORDER BY id ASC LIMIT $start, $limit";
$result = $conn->query($sql);
$products = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Count total for pagination
$count_sql = "SELECT COUNT(id) as total FROM products $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Handle POST delete using the same file for simplicity
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_code'])) {
    $delete_code = $conn->real_escape_string($_POST['delete_code']);
    $conn->query("DELETE FROM products WHERE id = '$delete_code'");
    // Refresh page
    header("Location: product_management.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Management - Luxury Jewelry</title>
  <link rel="stylesheet" href="admin_function.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Material icons for the search box -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    /* Ensure the section is visible as it's the only one on this page */
    .section { display: block !important; }
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
      <a href="product_management.php">Product Management</a>
      <a href="Price Manage/pricing.php">Pricing Management</a>
      <a href="Administration_menu.php#users">Customers</a>
      <a href="Order Manage/order_management.php">Order Management</a>
      <a href="Import product manage/import_management.php">Import Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking Management</a>
      <a href="Administration_menu.php#settings">Settings</a>
    </div>
  </div>

  <div class="content">

    <!-- ======= Product Management ======= -->
    <section id="product-manage" class="section">
      <header><h1>Jewelry Product Management</h1></header>

      <div class="user-actions">
        <a href="add_product.php" class="btn">Add Product</a>
      </div>

      <form class="search-section" method="GET" action="product_management.php">
        <div class="search-group">
          <label class="search-label" for="product-name">Search by Name</label>
          <input type="text" id="product-name" name="product_name" class="search-input" placeholder="Product Name" value="<?php echo htmlspecialchars($_GET['product_name'] ?? ''); ?>">
        </div>
        <div class="search-group">
          <label class="search-label" for="product-category">Search by Category</label>
          <select id="product-category" name="product_category" class="search-select">
            <option value="">All</option>
            <option value="Male" <?php if(($search_category??'')=='Male') echo 'selected';?>>Male</option>
            <option value="Female" <?php if(($search_category??'')=='Female') echo 'selected';?>>Female</option>
            <option value="Unisex" <?php if(($search_category??'')=='Unisex') echo 'selected';?>>Unisex</option>
          </select>
        </div>
        <button type="submit" class="btn-search">
          <i class="material-icons-round">search</i>
        </button>
      </form>

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
              <form method="POST" action="product_management.php" style="display:inline;">
                 <input type="hidden" name="delete_code" value="<?php echo htmlspecialchars($p['code']); ?>">
                 <button type="submit" class="btn small" style="background:#dc3545; color:white; border:none; cursor:pointer;" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <div class="pagination">
        <?php if($page > 1): ?>
          <button class="pagination-btn" onclick="window.location.href='?page=<?php echo $page-1; ?>'"><span class="arrow-icon">&#10094;</span></button>
        <?php endif; ?>
        
        <?php for($i=1; $i<=$total_pages; $i++): ?>
          <button class="pagination-btn <?php echo ($i==$page)?'active':''; ?>" onclick="window.location.href='?page=<?php echo $i; ?>'"><?php echo $i; ?></button>
        <?php endfor; ?>
        
        <?php if($page < $total_pages): ?>
          <button class="pagination-btn" onclick="window.location.href='?page=<?php echo $page+1; ?>'"><span class="arrow-icon">&#10095;</span></button>
        <?php endif; ?>
      </div>
    </section>

  </div><!-- /.content -->

</body>
</html>
