<?php
require_once "../../config/config.php";

// ============================================================
// Tự động đồng bộ products.stock từ goods_receipt + order_items
// Công thức: stock = tổng nhập (Completed) - tổng xuất (orders)
// Chạy mỗi lần load trang để đảm bảo luôn chính xác
// ============================================================
$conn->query("
    UPDATE products p
    SET p.stock = (
        -- Tổng nhập từ goods_receipt Completed
        COALESCE((
            SELECT SUM(gri.quantity)
            FROM goods_receipt_items gri
            JOIN goods_receipt gr ON gri.receipt_id = gr.id
            WHERE gri.product_id = p.id
              AND gr.status = 'Completed'
        ), 0)
        -
        -- Tổng xuất từ order_items
        COALESCE((
            SELECT SUM(oi.quantity)
            FROM order_items oi
            WHERE oi.product_id = p.id
        ), 0)
    )
");

// ============================================================
// Tab đang active
// ============================================================
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'stock-lookup';

// ============================================================
// TAB 1: Stock Lookup
// ============================================================
$search_name     = isset($_GET['search_name'])     ? trim($_GET['search_name'])     : '';
$search_category = isset($_GET['search_category']) ? trim($_GET['search_category']) : 'All';

$where1  = [];
$params1 = [];
$types1  = '';

if ($search_name !== '') {
    $where1[]  = 'name LIKE ?';
    $params1[] = '%' . $search_name . '%';
    $types1   .= 's';
}
if ($search_category !== 'All') {
    $where1[]  = 'category = ?';
    $params1[] = $search_category;
    $types1   .= 's';
}

$where1_sql = $where1 ? 'WHERE ' . implode(' AND ', $where1) : '';
$stmt1 = $conn->prepare("SELECT id, name, image, category, stock FROM products $where1_sql ORDER BY id ASC");
if ($params1) $stmt1->bind_param($types1, ...$params1);
$stmt1->execute();
$products = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt1->close();

function stockStatus($qty) {
    if ($qty <= 0)  return ['label' => 'Out of Stock', 'class' => 'status-danger'];
    if ($qty <= 10) return ['label' => 'Low Stock',    'class' => 'status-warning'];
    return               ['label' => 'In Stock',      'class' => 'status-normal'];
}

// ============================================================
// TAB 2: Low Stock Alert
// ============================================================
$low_result   = $conn->query("SELECT id, name, image, category, stock FROM products WHERE stock <= 10 ORDER BY stock ASC");
$low_products = $low_result->fetch_all(MYSQLI_ASSOC);
$min_stock    = 10;

// ============================================================
// TAB 3: Import - Export - Balance Report
// Group theo ngày, Balance = products.stock (tồn thực tế hiện tại)
// ============================================================
$report_product = isset($_GET['report_product']) ? trim($_GET['report_product']) : '';
$report_from    = isset($_GET['report_from'])    ? trim($_GET['report_from'])    : '';
$report_to      = isset($_GET['report_to'])      ? trim($_GET['report_to'])      : '';

// Import theo ngày (Completed)
$imp_where  = ["gr.status = 'Completed'"];
$imp_params = [];
$imp_types  = '';
if ($report_from !== '') { $imp_where[] = 'gr.entry_date >= ?'; $imp_params[] = $report_from; $imp_types .= 's'; }
if ($report_to   !== '') { $imp_where[] = 'gr.entry_date <= ?'; $imp_params[] = $report_to;   $imp_types .= 's'; }
if ($report_product !== '') { $imp_where[] = 'gri.product_name LIKE ?'; $imp_params[] = '%'.$report_product.'%'; $imp_types .= 's'; }

$stmt_imp = $conn->prepare("
    SELECT gr.entry_date AS report_date, SUM(gri.quantity) AS import_qty
    FROM goods_receipt gr
    JOIN goods_receipt_items gri ON gr.id = gri.receipt_id
    WHERE " . implode(' AND ', $imp_where) . "
    GROUP BY gr.entry_date ORDER BY gr.entry_date DESC
");
if ($imp_params) $stmt_imp->bind_param($imp_types, ...$imp_params);
$stmt_imp->execute();
$import_by_date = $stmt_imp->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_imp->close();

// Export theo ngày
$exp_where  = ['1=1'];
$exp_params = [];
$exp_types  = '';
if ($report_from !== '') { $exp_where[] = 'o.order_date >= ?'; $exp_params[] = $report_from; $exp_types .= 's'; }
if ($report_to   !== '') { $exp_where[] = 'o.order_date <= ?'; $exp_params[] = $report_to;   $exp_types .= 's'; }
if ($report_product !== '') { $exp_where[] = 'oi.product_name LIKE ?'; $exp_params[] = '%'.$report_product.'%'; $exp_types .= 's'; }

$stmt_exp = $conn->prepare("
    SELECT o.order_date AS report_date, SUM(oi.quantity) AS export_qty
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE " . implode(' AND ', $exp_where) . "
    GROUP BY o.order_date ORDER BY o.order_date DESC
");
if ($exp_params) $stmt_exp->bind_param($exp_types, ...$exp_params);
$stmt_exp->execute();
$export_by_date = $stmt_exp->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_exp->close();

// Gộp theo ngày
$date_map = [];
foreach ($import_by_date as $r) {
    $date_map[$r['report_date']]['import'] = (int)$r['import_qty'];
    $date_map[$r['report_date']]['export'] = $date_map[$r['report_date']]['export'] ?? 0;
}
foreach ($export_by_date as $r) {
    $date_map[$r['report_date']]['export'] = (int)$r['export_qty'];
    $date_map[$r['report_date']]['import'] = $date_map[$r['report_date']]['import'] ?? 0;
}
krsort($date_map);

// Lấy tổng tồn kho hiện tại từ products.stock (đã sync ở trên)
// Nếu có filter product thì chỉ tính stock của product đó
$stock_sql = "SELECT COALESCE(SUM(stock), 0) AS total_stock FROM products";
$stock_params = [];
$stock_types  = '';
if ($report_product !== '') {
    $stock_sql   .= " WHERE name LIKE ?";
    $stock_params[] = '%' . $report_product . '%';
    $stock_types   .= 's';
}
$stmt_stock = $conn->prepare($stock_sql);
if ($stock_params) $stmt_stock->bind_param($stock_types, ...$stock_params);
$stmt_stock->execute();
$current_stock = (int)$stmt_stock->get_result()->fetch_assoc()['total_stock'];
$stmt_stock->close();

// Build report rows — Balance = tồn hiện tại (products.stock)
$report_rows = [];
foreach ($date_map as $date => $vals) {
    $report_rows[] = [
        'date'          => $date,
        'import'        => $vals['import'],
        'export'        => $vals['export'],
        'current_stock' => $current_stock, // tồn thực tế từ products.stock
    ];
}

$total_import = array_sum(array_column($report_rows, 'import'));
$total_export = array_sum(array_column($report_rows, 'export'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stocking Management - Luxury Jewelry</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", sans-serif; }
    body { display: flex; background: #f3f3f3; color: #333; min-height: 100vh; }

    .sidebar { width: 220px; background-color: #8e4b00; color: #f8ce86; display: flex; flex-direction: column; padding: 20px; }
    .logo { text-align: center; margin-bottom: 30px; }
    .logo img { width: 80px; border-radius: 50%; }
    .logo h2 { font-size: 18px; margin-top: 10px; }
    .menu a { display: block; padding: 12px; color: #f8ce86; text-decoration: none; border-radius: 8px; margin-bottom: 10px; font-weight: bold; transition: background 0.3s, color 0.3s; }
    .menu a:hover, .menu a.active { background-color: #f8ce86; color: #8e4b00; }

    main { flex: 1; padding: 25px; }
    h1 { color: #8e4b00; margin-bottom: 20px; font-size: 24px; }

    .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
    .tab { padding: 10px 18px; font-weight: 600; color: #555; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; }
    .tab.active { color: #8e4b00; border-bottom-color: #8e4b00; }
    .tab-content { display: none; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from {opacity:0;transform:translateY(10px);} to {opacity:1;transform:translateY(0);} }

    .search-form { background: white; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); padding: 20px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
    .form-group { display: flex; flex-direction: column; flex: 1; min-width: 180px; }
    label { font-weight: bold; color: #333; margin-bottom: 6px; }
    input, select { padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; outline: none; transition: border-color 0.2s; }
    input:focus, select:focus { border-color: #8e4b00; }

    .btn { display: inline-block; background-color: #8e4b00; color: #f8ce86; border: none; border-radius: 6px; padding: 10px 16px; font-weight: bold; cursor: pointer; transition: 0.3s; text-decoration: none; }
    .btn.small { padding: 6px 12px; font-size: 14px; }
    .btn:hover { background-color: #f8ce86; color: #8e4b00; }
    .btn.secondary { background: #ccc; color: #333; }
    .btn.secondary:hover { background: #aaa; }

    table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    th, td { padding: 14px 16px; text-align: center; font-size: 15px; border-bottom: 1px solid #f0e2d0; }
    th { background: #8e4b00; color: #f8ce86; font-weight: 600; }
    tr:hover td { background: #f9f2e7; }

    .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
    .status-normal  { background-color: #e8f5e9; color: #2e7d32; }
    .status-warning { background-color: #fff8e1; color: #f57c00; }
    .status-danger  { background-color: #ffebee; color: #c62828; }

    .alert-section { background: #fffbe6; border-left: 4px solid #8e4b00; padding: 1rem; margin-top: 1.5rem; border-radius: 6px; }
    .alert-title { font-weight: 600; color: #8e4b00; margin-bottom: 0.5rem; }

    .product-img, table img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 6px; margin-top: 20px; }
    .pagination .btn { padding: 6px 10px; border-radius: 8px; }
    .pagination .btn.active { background-color: #f8ce86; color: #8e4b00; font-weight: bold; }

    .no-data { text-align: center; padding: 30px; color: #888; font-size: 15px; }

    /* Stock badge trong Tab 3 */
    .stock-value { font-weight: bold; font-size: 15px; color: #2e7d32; }
    .stock-note  { font-size: 12px; color: #888; display: block; }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="logo">
      <img src="../../images/Admin_login.jpg" alt="Admin Logo">
      <h2>Luxury Jewelry Admin</h2>
    </div>
    <div class="menu">
      <a href="../Administration_menu.html#products">Jewelry List</a>
      <a href="../Administration_menu.html#product-manage">Product Management</a>
      <a href="../Price Manage/pricing.php">Pricing Management</a>
      <a href="../Administration_menu.html#users">Customers</a>
      <a href="../Order Manage/order_management.php">Order Management</a>
      <a href="../Import product manage/import_management.php">Import Management</a>
      <a href="stocking_management.php" class="active">Stocking Management</a>
      <a href="../Administration_menu.html#settings">Settings</a>
    </div>
  </div>

  <main>
    <h1>Stocking Management</h1>

    <div class="tabs">
      <div class="tab <?= $active_tab==='stock-lookup' ?'active':'' ?>" data-tab="stock-lookup">Stock Lookup</div>
      <div class="tab <?= $active_tab==='stock-alert'  ?'active':'' ?>" data-tab="stock-alert">Low Stock Alert</div>
      <div class="tab <?= $active_tab==='stock-report' ?'active':'' ?>" data-tab="stock-report">Import - Export - Balance Report</div>
    </div>

    <!-- TAB 1 -->
    <div class="tab-content <?= $active_tab==='stock-lookup'?'active':'' ?>" id="stock-lookup">
      <form method="GET" action="stocking_management.php" class="search-form">
        <input type="hidden" name="tab" value="stock-lookup">
        <div class="form-group">
          <label>Product Name</label>
          <input type="text" name="search_name" placeholder="Enter product name..." value="<?= htmlspecialchars($search_name) ?>">
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="search_category">
            <?php foreach (['All','Ring','Necklace','Bracelet','Earring'] as $cat): ?>
              <option value="<?= $cat ?>" <?= $search_category===$cat?'selected':'' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn">Search</button>
        <a href="stocking_management.php?tab=stock-lookup" class="btn secondary">Reset</a>
      </form>

      <table>
        <tr>
          <th>Product Code</th><th>Product Name</th><th>Product Image</th>
          <th>Category</th><th>Stock Quantity</th><th>Status</th>
        </tr>
        <?php if (empty($products)): ?>
          <tr><td colspan="6" class="no-data">No products found.</td></tr>
        <?php else: ?>
          <?php foreach ($products as $p): $st = stockStatus($p['stock']); ?>
            <tr>
              <td><?= htmlspecialchars($p['id']) ?></td>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><img src="../../images/<?= htmlspecialchars($p['image']) ?>" onerror="this.style.opacity='0.2'"></td>
              <td><?= htmlspecialchars($p['category']) ?></td>
              <td><?= intval($p['stock']) ?></td>
              <td><span class="status-badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </table>

      <div class="alert-section">
        <div class="alert-title">Note:</div>
        Products below 10 in quantity are considered low stock.
        Stock is automatically calculated: <strong>Total Imported (Completed) − Total Sold</strong>.
      </div>
    </div>

    <!-- TAB 2 -->
    <div class="tab-content <?= $active_tab==='stock-alert'?'active':'' ?>" id="stock-alert">
      <div class="alert-section" style="margin-bottom:20px;">
        <div class="alert-title">Low Stock Warning:</div>
        There are <strong><?= count($low_products) ?></strong> item(s) that need restocking soon.
      </div>
      <table>
        <tr>
          <th>Product Code</th><th>Product Name</th><th>Product Image</th>
          <th>Category</th><th>Stock</th><th>Minimum</th><th>Status</th>
        </tr>
        <?php if (empty($low_products)): ?>
          <tr><td colspan="7" class="no-data">✅ All products are sufficiently stocked.</td></tr>
        <?php else: ?>
          <?php foreach ($low_products as $p): $st = stockStatus($p['stock']); ?>
            <tr>
              <td><?= htmlspecialchars($p['id']) ?></td>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><img src="../../images/<?= htmlspecialchars($p['image']) ?>" class="product-img" onerror="this.style.opacity='0.2'"></td>
              <td><?= htmlspecialchars($p['category']) ?></td>
              <td><?= intval($p['stock']) ?></td>
              <td><?= $min_stock ?></td>
              <td><span class="status-badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </table>
    </div>

    <!-- TAB 3 -->
    <div class="tab-content <?= $active_tab==='stock-report'?'active':'' ?>" id="stock-report">
      <form method="GET" action="stocking_management.php" class="search-form">
        <input type="hidden" name="tab" value="stock-report">
        <div class="form-group">
          <label>Product</label>
          <input type="text" name="report_product" placeholder="Enter product name..." value="<?= htmlspecialchars($report_product) ?>">
        </div>
        <div class="form-group">
          <label>From</label>
          <input type="date" name="report_from" value="<?= htmlspecialchars($report_from) ?>">
        </div>
        <div class="form-group">
          <label>To</label>
          <input type="date" name="report_to" value="<?= htmlspecialchars($report_to) ?>">
        </div>
        <button type="submit" class="btn">Search</button>
        <a href="stocking_management.php?tab=stock-report" class="btn secondary">Reset</a>
      </form>

      <table>
        <tr>
          <th>Date</th>
          <th>Import Qty <small style="font-weight:normal">(kỳ này)</small></th>
          <th>Export Qty <small style="font-weight:normal">(kỳ này)</small></th>
          <th>Current Stock <small style="font-weight:normal">(tồn thực tế)</small></th>
          <th>Action</th>
        </tr>
        <?php if (empty($report_rows)): ?>
          <tr><td colspan="5" class="no-data">No report data found.</td></tr>
        <?php else: ?>
          <?php foreach ($report_rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['date']) ?></td>
              <td><?= $r['import'] ?></td>
              <td><?= $r['export'] ?></td>
              <td>
                <span class="stock-value"><?= $r['current_stock'] ?></span>
                <span class="stock-note">from products.stock</span>
              </td>
              <td>
                <a href="IEP_report_detail.php?date=<?= urlencode($r['date']) ?>&product=<?= urlencode($report_product) ?>" class="btn small">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </table>

      <?php if (!empty($report_rows)): ?>
      <div class="alert-section" style="margin-top:20px;">
        <div class="alert-title">Summary (filtered period):</div>
        <p>Total Imported: <strong><?= $total_import ?></strong> units</p>
        <p>Total Exported: <strong><?= $total_export ?></strong> units</p>
        <p>Current Stock (from DB): <strong style="color:#2e7d32"><?= $current_stock ?></strong> units
          <small style="color:#888"> — tổng tồn kho thực tế tất cả sản phẩm<?= $report_product ? ' khớp filter' : '' ?></small>
        </p>
      </div>
      <?php endif; ?>
    </div>

  </main>

  <script>
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab.dataset.tab);
        window.location.href = url.toString();
      });
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>