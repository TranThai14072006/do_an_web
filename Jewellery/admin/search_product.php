<?php
session_start();
require_once '../config/config.php';

$search_name   = trim($_GET['name']   ?? '');
$search_gender = trim($_GET['gender'] ?? '');

// ── Handle DELETE (AJAX POST) ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $delId = trim($_POST['code'] ?? '');
    if ($delId !== '') {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("s", $delId);
        $ok = $stmt->execute();
        $stmt->close();
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Product deleted successfully.' : $conn->error]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing product ID.']);
    exit;
}

// ── Build search query ────────────────────────────────────────────────────
$sql = "SELECT id, name, image, price, stock, gender FROM products WHERE 1=1";
$params = [];
$types  = '';

if ($search_name !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = '%' . $search_name . '%';
    $types   .= 's';
}
if ($search_gender !== '') {
    $sql .= " AND gender = ?";
    $params[] = $search_gender;
    $types   .= 's';
}
$sql .= " ORDER BY id";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $results = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} else {
    $res = $conn->query($sql);
    $results = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search Products – Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
    body{display:flex;background:#f3f3f3;color:#333;min-height:100vh;}
    .sidebar{width:220px;background:#8e4b00;color:#f8ce86;display:flex;flex-direction:column;padding:20px;position:fixed;top:0;left:0;height:100vh;}
    .logo{text-align:center;margin-bottom:30px;}
    .logo img{width:80px;border-radius:50%;}
    .logo h2{font-size:16px;margin-top:10px;}
    .menu a{display:block;padding:11px 14px;color:#f8ce86;text-decoration:none;border-radius:8px;margin-bottom:8px;font-weight:600;font-size:14px;transition:background 0.3s,color 0.3s;}
    .menu a:hover,.menu a.active{background:#f8ce86;color:#8e4b00;}
    main{margin-left:220px;flex:1;padding:30px;}
    h1{color:#8e4b00;margin-bottom:24px;font-size:24px;}
    .search-form{background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:22px;margin-bottom:24px;display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;}
    .form-group{display:flex;flex-direction:column;flex:1;min-width:180px;}
    .form-group label{font-weight:600;color:#555;margin-bottom:6px;font-size:13px;}
    input,select{padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;outline:none;transition:border-color 0.2s;}
    input:focus,select:focus{border-color:#8e4b00;box-shadow:0 0 0 3px rgba(142,75,0,0.08);}
    .btn{display:inline-flex;align-items:center;gap:6px;background:#8e4b00;color:#f8ce86;border:none;border-radius:8px;padding:10px 18px;font-weight:600;cursor:pointer;transition:0.3s;font-size:14px;text-decoration:none;}
    .btn:hover{background:#a3670b;transform:translateY(-1px);}
    .btn.secondary{background:#e0e0e0;color:#555;}
    .btn.secondary:hover{background:#c8c8c8;}
    .btn.small{padding:6px 12px;font-size:13px;}
    .btn.danger{background:#d32f2f;color:white;}
    .btn.danger:hover{background:#b71c1c;}
    table{width:100%;border-collapse:collapse;background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);}
    th,td{padding:14px 16px;text-align:center;font-size:14px;border-bottom:1px solid #f0e2d0;}
    th{background:linear-gradient(135deg,#8e4b00,#a3670b);color:#f8ce86;font-weight:600;}
    tr:hover td{background:#fdf6ee;}
    td img{border-radius:6px;}
    .empty-row td{color:#aaa;padding:24px;}
    .popup{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;justify-content:center;align-items:center;z-index:999;}
    .popup.active{display:flex;}
    .popup-content{background:white;border-radius:12px;padding:28px 32px;max-width:400px;width:90%;box-shadow:0 8px 24px rgba(0,0,0,0.2);text-align:center;}
    .popup-content h3{margin-bottom:12px;color:#8e4b00;}
    .toast{position:fixed;bottom:30px;right:30px;background:#8e4b00;color:#f8ce86;padding:14px 22px;border-radius:8px;opacity:0;pointer-events:none;transform:translateY(20px);transition:all 0.4s ease;z-index:1000;font-weight:600;}
    .toast.show{opacity:1;transform:translateY(0);}
    .result-count{color:#888;font-size:13px;margin-bottom:12px;}
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
      <a href="Administration_menu.php#product-manage" class="active">Product Management</a>
      <a href="Price Manage/pricing.php">Pricing Management</a>
      <a href="Administration_menu.php#users">Customers</a>
      <a href="Order Manage/order_management.php">Order Management</a>
      <a href="Import_product/import_management.php">Import Management</a>
      <a href="Stock Manage/stocking_management.php">Stocking Management</a>
      <a href="Administration_menu.php#settings">Settings</a>
    </div>
  </div>

  <main>
    <h1><i class="fas fa-search" style="font-size:20px;margin-right:10px;"></i>Search Products</h1>

    <form class="search-form" method="get" action="search_product.php">
      <div class="form-group">
        <label>Product Name</label>
        <input type="text" name="name" placeholder="Search by name..."
               value="<?php echo htmlspecialchars($search_name); ?>">
      </div>
      <div class="form-group">
        <label>Category (Gender)</label>
        <select name="gender">
          <option value="">All</option>
          <option value="Male"   <?php echo $search_gender === 'Male'   ? 'selected' : ''; ?>>Male</option>
          <option value="Female" <?php echo $search_gender === 'Female' ? 'selected' : ''; ?>>Female</option>
          <option value="Unisex" <?php echo $search_gender === 'Unisex' ? 'selected' : ''; ?>>Unisex</option>
        </select>
      </div>
      <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
      <button type="button" class="btn secondary" onclick="window.location.href='Administration_menu.php#product-manage'">
        ← Back
      </button>
    </form>

    <p class="result-count"><?php echo count($results); ?> product(s) found</p>

    <table>
      <thead>
        <tr>
          <th>Code</th><th>Product Name</th><th>Image</th>
          <th>Price</th><th>Stock</th><th>Category</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($results)): ?>
          <tr class="empty-row"><td colspan="7">No products found.</td></tr>
        <?php else: ?>
          <?php foreach ($results as $p): ?>
          <tr>
            <td><?php echo htmlspecialchars($p['id']); ?></td>
            <td><?php echo htmlspecialchars($p['name']); ?></td>
            <td><img src="../images/<?php echo htmlspecialchars($p['image']); ?>" alt="" width="60"></td>
            <td>$<?php echo number_format($p['price'], 2); ?></td>
            <td><?php echo intval($p['stock']); ?></td>
            <td><?php echo htmlspecialchars($p['gender']); ?></td>
            <td>
              <a href="edit_product.php?code=<?php echo urlencode($p['id']); ?>" class="btn small">Edit</a>
              <button class="btn small danger delete-btn"
                      data-id="<?php echo htmlspecialchars($p['id']); ?>"
                      data-name="<?php echo htmlspecialchars($p['name']); ?>">Delete</button>
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
        <p>Are you sure you want to delete <strong id="deleteProductName"></strong>?</p>
        <button class="btn" id="confirmDeleteBtn" style="margin-top:16px;">Yes, Delete</button>
        <button class="btn secondary" onclick="closePopup('deletePopup')" style="margin-left:10px;">Cancel</button>
      </div>
    </div>

    <div id="toast" class="toast"></div>
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
        pendingCode = btn.dataset.id;
        document.getElementById('deleteProductName').textContent = btn.dataset.name;
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
        if (data.success) setTimeout(() => location.reload(), 1500);
      });
    });
  </script>
</body>
</html>