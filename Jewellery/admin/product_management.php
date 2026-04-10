<?php
session_start();
require_once '../config/config.php';

// Handle POST delete FIRST (before any output)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_code'])) {
    $delete_code = $conn->real_escape_string($_POST['delete_code']);
    $conn->query("DELETE FROM products WHERE id = '$delete_code'");
    header("Location: product_management.php");
    exit();
}

// Pagination variables
$limit = 10;
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// Search variables
$search_name     = isset($_GET['product_name'])     ? trim($_GET['product_name'])     : '';
$search_category = isset($_GET['product_category']) ? trim($_GET['product_category']) : '';
$search_gender   = isset($_GET['product_gender'])   ? trim($_GET['product_gender'])   : '';

$where_clause = "WHERE 1=1";
if (!empty($search_name)) {
    $safe_name = $conn->real_escape_string($search_name);
    $where_clause .= " AND name LIKE '%$safe_name%'";
}
if (!empty($search_category) && $search_category != "All") {
    $safe_cat = $conn->real_escape_string($search_category);
    $where_clause .= " AND category = '$safe_cat'";
}
if (!empty($search_gender) && $search_gender != "All") {
    $safe_gen = $conn->real_escape_string($search_gender);
    $where_clause .= " AND gender = '$safe_gen'";
}

// Fetch products
$sql    = "SELECT id AS code, name, image, price, stock, category, gender FROM products $where_clause ORDER BY id ASC LIMIT $start, $limit";
$result = $conn->query($sql);
$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Count total for pagination
$count_sql    = "SELECT COUNT(id) AS total FROM products $where_clause";
$count_result = $conn->query($count_sql);
$total_records = $count_result ? (int)$count_result->fetch_assoc()['total'] : 0;
$total_pages   = $total_records > 0 ? (int)ceil($total_records / $limit) : 1;
if ($page > $total_pages) $page = $total_pages;

// Build query string for pagination (preserve search filters)
$q_params = [];
if (!empty($search_name))     $q_params['product_name']     = $search_name;
if (!empty($search_category) && $search_category !== 'All') $q_params['product_category'] = $search_category;
$q_string = !empty($q_params) ? '&' . http_build_query($q_params) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Management - Luxury Jewelry</title>
  <link rel="stylesheet" href="admin_function.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <style>
    .section { display: block !important; }
    .product-img { border-radius: 6px; object-fit: cover; width: 60px; height: 60px; }
    .no-image { width:60px; height:60px; border-radius:6px; background:#f0f0f0; display:inline-flex; align-items:center; justify-content:center; color:#aaa; font-size:11px; text-align:center; }
    .pagination-info { text-align:center; color:#888; font-size:13px; margin-top:6px; }
  </style>
</head>
<body>
<?php include 'sidebar_include.php'; ?>

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
          <input type="text" id="product-name" name="product_name" class="search-input"
                 placeholder="Product Name" value="<?php echo htmlspecialchars($search_name); ?>">
        </div>
        <div class="search-group">
          <label class="search-label" for="product-category">Category</label>
          <input type="text" id="product-category" name="product_category" class="search-input"
                 placeholder="Ring, Necklace..." value="<?php echo htmlspecialchars($search_category); ?>">
        </div>
        <div class="search-group">
          <label class="search-label" for="product-gender">Gender</label>
          <select id="product-gender" name="product_gender" class="search-select">
            <option value="">All</option>
            <option value="Male"   <?php if($search_gender=='Male')   echo 'selected'; ?>>Male</option>
            <option value="Female" <?php if($search_gender=='Female') echo 'selected'; ?>>Female</option>
            <option value="Unisex" <?php if($search_gender=='Unisex') echo 'selected'; ?>>Unisex</option>
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
            <th>Category</th><th>Gender</th><th>Price</th><th>Stock</th><th>Action</th>
          </tr>
          <?php if (empty($products)): ?>
          <tr><td colspan="6" style="text-align:center;color:#888;padding:20px;">No products found.</td></tr>
          <?php else: ?>
          <?php foreach ($products as $p): ?>
          <tr>
            <td><?php echo htmlspecialchars($p['code']); ?></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td>
              <?php if (!empty($p['image'])): ?>
                <img src="../images/<?php echo htmlspecialchars($p['image']); ?>"
                     alt="<?php echo htmlspecialchars($p['name']); ?>"
                     class="product-img"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                <span class="no-image" style="display:none;">No img</span>
              <?php else: ?>
                <span class="no-image">No img</span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($p['category']); ?></td>
            <td><?php echo htmlspecialchars($p['gender']); ?></td>
            <td>$<?php echo number_format((float)$p['price'], 2, '.', ','); ?></td>
            <td><?php echo htmlspecialchars($p['stock']); ?></td>
            <td>
              <a href="edit_product.php?code=<?php echo urlencode($p['code']); ?>" class="btn small">Edit</a>
              <form method="POST" action="product_management.php" style="display:inline;">
                 <input type="hidden" name="delete_code" value="<?php echo htmlspecialchars($p['code']); ?>">
                 <button type="submit" class="btn small"
                         style="background:#dc3545;color:white;border:none;cursor:pointer;"
                         onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <button class="pagination-btn" onclick="window.location.href='?page=<?php echo $page-1; ?><?php echo htmlspecialchars($q_string); ?>'">
            <span class="arrow-icon">&#10094;</span>
          </button>
        <?php endif; ?>

        <?php
          // Show at most 7 page buttons
          $range = 2;
          $start_btn = max(1, $page - $range);
          $end_btn   = min($total_pages, $page + $range);
          if ($start_btn > 1): ?>
            <button class="pagination-btn" onclick="window.location.href='?page=1<?php echo htmlspecialchars($q_string); ?>'">1</button>
            <?php if ($start_btn > 2): ?><span style="padding:0 4px;">…</span><?php endif; ?>
          <?php endif; ?>

        <?php for ($i = $start_btn; $i <= $end_btn; $i++): ?>
          <button class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>"
                  onclick="window.location.href='?page=<?php echo $i; ?><?php echo htmlspecialchars($q_string); ?>'">
            <?php echo $i; ?>
          </button>
        <?php endfor; ?>

        <?php if ($end_btn < $total_pages): ?>
            <?php if ($end_btn < $total_pages - 1): ?><span style="padding:0 4px;">…</span><?php endif; ?>
            <button class="pagination-btn" onclick="window.location.href='?page=<?php echo $total_pages; ?><?php echo htmlspecialchars($q_string); ?>'">
              <?php echo $total_pages; ?>
            </button>
        <?php endif; ?>

        <?php if ($page < $total_pages): ?>
          <button class="pagination-btn" onclick="window.location.href='?page=<?php echo $page+1; ?><?php echo htmlspecialchars($q_string); ?>'">
            <span class="arrow-icon">&#10095;</span>
          </button>
        <?php endif; ?>
      </div>
      <div class="pagination-info">
        Showing <?php echo min($start+1, $total_records); ?>–<?php echo min($start+$limit, $total_records); ?>
        of <?php echo $total_records; ?> product(s)
      </div>
      <?php endif; ?>

    </section>

  </div><!-- /.content -->

</body>
</html>
