<?php
session_start();
require_once "../../config/config.php";

// ============================================================
// Sync stock hiện tại
// ============================================================
$conn->query("
    UPDATE products p SET p.stock = (
        COALESCE((SELECT SUM(gri.quantity) FROM goods_receipt_items gri
                  JOIN goods_receipt gr ON gri.receipt_id = gr.id
                  WHERE gri.product_id = p.id AND gr.status = 'Completed'), 0)
        - COALESCE((SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.product_id = p.id), 0)
    )
");

$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'stock-lookup';

// ============================================================
// TAB 1: Stock Lookup — hỗ trợ tra cứu theo ngày cụ thể
// ============================================================
$search_name     = trim($_GET['search_name']     ?? '');
$search_category = trim($_GET['search_category'] ?? 'All');
$search_date     = trim($_GET['search_date']      ?? ''); // ngày tra cứu

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

// Lấy danh sách sản phẩm cơ bản
$stmt1 = $conn->prepare("SELECT id, name, image, category, stock FROM products $where1_sql ORDER BY id ASC");
if ($stmt1 === false) {
    // Nếu query lỗi (thiều cột), fallback không lọc
    $stmt1 = $conn->prepare("SELECT id, name, image, '' AS category, 0 AS stock FROM products ORDER BY id ASC");
}
if ($params1) $stmt1->bind_param($types1, ...$params1);
$stmt1->execute();
$res1 = $stmt1->get_result();
$base_products = $res1 ? $res1->fetch_all(MYSQLI_ASSOC) : [];
$stmt1->close();

// Nếu có ngày → tính tồn tại ngày đó
// Tồn tại ngày D = tổng nhập (entry_date <= D, Completed) - tổng xuất (order_date <= D)
$products = [];
foreach ($base_products as $p) {
    if ($search_date !== '') {
        // Tổng nhập đến ngày D
        $s_in = $conn->prepare("
            SELECT COALESCE(SUM(gri.quantity), 0) AS qty
            FROM goods_receipt_items gri
            JOIN goods_receipt gr ON gri.receipt_id = gr.id
            WHERE gri.product_id = ? AND gr.status = 'Completed' AND gr.entry_date <= ?
        ");
        $s_in->bind_param('ss', $p['id'], $search_date);
        $s_in->execute();
        $in_qty = (int)$s_in->get_result()->fetch_assoc()['qty'];
        $s_in->close();

        // Tổng xuất đến ngày D
        $s_out = $conn->prepare("
            SELECT COALESCE(SUM(oi.quantity), 0) AS qty
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.product_id = ? AND o.order_date <= ?
        ");
        $s_out->bind_param('ss', $p['id'], $search_date);
        $s_out->execute();
        $out_qty = (int)$s_out->get_result()->fetch_assoc()['qty'];
        $s_out->close();

        $p['stock']       = max(0, $in_qty - $out_qty);
        $p['as_of_date']  = true;
        $p['in_qty']      = $in_qty;
        $p['out_qty']     = $out_qty;
    } else {
        $p['as_of_date'] = false;
    }
    $products[] = $p;
}

// ============================================================
// TAB 2: Low Stock Alert — ngưỡng do người dùng chỉnh
// ============================================================
// Lưu ngưỡng vào session
if (isset($_POST['set_threshold']) && isset($_POST['threshold'])) {
    $t = intval($_POST['threshold']);
    if ($t >= 0) {
        $_SESSION['low_stock_threshold'] = $t;
    }
    header("Location: stocking_management.php?tab=stock-alert");
    exit;
}
$min_stock = isset($_SESSION['low_stock_threshold']) ? (int)$_SESSION['low_stock_threshold'] : 10;

$stmt_low = $conn->prepare("SELECT id, name, image, category, stock FROM products WHERE stock <= ? ORDER BY stock ASC");
$stmt_low->bind_param('i', $min_stock);
$stmt_low->execute();
$low_products = $stmt_low->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_low->close();

// ============================================================
// TAB 3: Import - Export - Balance Report
// ============================================================
$report_product = trim($_GET['report_product'] ?? '');
$report_from    = trim($_GET['report_from']    ?? '');
$report_to      = trim($_GET['report_to']      ?? '');

$imp_where  = ["gr.status = 'Completed'"]; $imp_params = []; $imp_types = '';
if ($report_from !== '') { $imp_where[] = 'gr.entry_date >= ?'; $imp_params[] = $report_from; $imp_types .= 's'; }
if ($report_to   !== '') { $imp_where[] = 'gr.entry_date <= ?'; $imp_params[] = $report_to;   $imp_types .= 's'; }
if ($report_product !== '') { $imp_where[] = 'gri.product_name LIKE ?'; $imp_params[] = '%'.$report_product.'%'; $imp_types .= 's'; }

$stmt_imp = $conn->prepare("SELECT gr.entry_date AS report_date, SUM(gri.quantity) AS import_qty
    FROM goods_receipt gr JOIN goods_receipt_items gri ON gr.id = gri.receipt_id
    WHERE " . implode(' AND ', $imp_where) . " GROUP BY gr.entry_date ORDER BY gr.entry_date DESC");
if ($imp_params) $stmt_imp->bind_param($imp_types, ...$imp_params);
$stmt_imp->execute();
$import_by_date = $stmt_imp->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_imp->close();

$exp_where  = ['1=1']; $exp_params = []; $exp_types = '';
if ($report_from !== '') { $exp_where[] = 'o.order_date >= ?'; $exp_params[] = $report_from; $exp_types .= 's'; }
if ($report_to   !== '') { $exp_where[] = 'o.order_date <= ?'; $exp_params[] = $report_to;   $exp_types .= 's'; }
if ($report_product !== '') { $exp_where[] = 'oi.product_name LIKE ?'; $exp_params[] = '%'.$report_product.'%'; $exp_types .= 's'; }

$stmt_exp = $conn->prepare("SELECT o.order_date AS report_date, SUM(oi.quantity) AS export_qty
    FROM orders o JOIN order_items oi ON o.id = oi.order_id
    WHERE " . implode(' AND ', $exp_where) . " GROUP BY o.order_date ORDER BY o.order_date DESC");
if ($exp_params) $stmt_exp->bind_param($exp_types, ...$exp_params);
$stmt_exp->execute();
$export_by_date = $stmt_exp->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_exp->close();

$date_map = [];
foreach ($import_by_date as $r) { $date_map[$r['report_date']]['import'] = (int)$r['import_qty']; $date_map[$r['report_date']]['export'] ??= 0; }
foreach ($export_by_date as $r) { $date_map[$r['report_date']]['export'] = (int)$r['export_qty']; $date_map[$r['report_date']]['import'] ??= 0; }
krsort($date_map);

$stock_sql = "SELECT COALESCE(SUM(stock),0) AS total_stock FROM products" . ($report_product !== '' ? " WHERE name LIKE ?" : "");
$stmt_stock = $conn->prepare($stock_sql);
if ($report_product !== '') {
    $stock_like = '%' . $report_product . '%';
    $stmt_stock->bind_param('s', $stock_like);
}
$stmt_stock->execute();
$current_stock = (int)$stmt_stock->get_result()->fetch_assoc()['total_stock'];
$stmt_stock->close();

$report_rows = [];
foreach ($date_map as $date => $vals) {
    $report_rows[] = ['date' => $date, 'import' => $vals['import'], 'export' => $vals['export'], 'current_stock' => $current_stock];
}
$total_import = array_sum(array_column($report_rows, 'import'));
$total_export = array_sum(array_column($report_rows, 'export'));

// Helper status — dùng ngưỡng động $min_stock
function stockStatus($qty, $threshold = 10) {
    if ($qty <= 0)          return ['label' => 'Out of Stock', 'class' => 'status-danger'];
    if ($qty <= $threshold) return ['label' => 'Low Stock',    'class' => 'status-warning'];
    return                       ['label' => 'In Stock',      'class' => 'status-normal'];
}

// Lấy danh sách categories cho dropdown
$cat_list = $conn->query("SELECT DISTINCT category FROM products ORDER BY category")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Stocking Management</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
    body{display:flex;background:#f3f3f3;color:#333;min-height:100vh;}
    .sidebar{width:220px;background:#8e4b00;color:#f8ce86;display:flex;flex-direction:column;padding:20px;}
    .logo{text-align:center;margin-bottom:30px;}
    .logo img{width:80px;border-radius:50%;}
    .logo h2{font-size:18px;margin-top:10px;}
    .menu a{display:block;padding:12px;color:#f8ce86;text-decoration:none;border-radius:8px;margin-bottom:10px;font-weight:bold;transition:.3s;}
    .menu a:hover,.menu a.active{background:#f8ce86;color:#8e4b00;}
    main{flex:1;padding:25px;}
    h1{color:#8e4b00;margin-bottom:20px;font-size:24px;}

    /* TABS */
    .tabs{display:flex;gap:10px;margin-bottom:20px;border-bottom:2px solid #ddd;}
    .tab{padding:10px 18px;font-weight:600;color:#555;cursor:pointer;border-bottom:3px solid transparent;transition:all .3s;white-space:nowrap;}
    .tab.active{color:#8e4b00;border-bottom-color:#8e4b00;}
    .tab-content{display:none;animation:fadeIn .3s ease;}
    .tab-content.active{display:block;}
    @keyframes fadeIn{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}

    /* FORMS */
    .search-form{background:#fff;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:20px;margin-bottom:20px;display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;}
    .form-group{display:flex;flex-direction:column;flex:1;min-width:160px;}
    label{font-weight:bold;color:#333;margin-bottom:6px;font-size:14px;}
    input,select{padding:10px;border:1px solid #ccc;border-radius:6px;font-size:14px;outline:none;transition:border-color .2s;}
    input:focus,select:focus{border-color:#8e4b00;}

    /* BUTTONS */
    .btn{display:inline-block;background:#8e4b00;color:#f8ce86;border:none;border-radius:6px;padding:10px 16px;font-weight:bold;cursor:pointer;transition:.3s;text-decoration:none;font-size:14px;}
    .btn.small{padding:6px 12px;font-size:13px;}
    .btn:hover{background:#a3670b;}
    .btn.secondary{background:#ccc;color:#333;}
    .btn.secondary:hover{background:#aaa;}

    /* TABLE */
    table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.08);}
    th,td{padding:13px 14px;text-align:center;font-size:14px;border-bottom:1px solid #f0e2d0;}
    th{background:#8e4b00;color:#f8ce86;font-weight:600;}
    tr:hover td{background:#f9f2e7;}
    .product-img,table img{width:56px;height:56px;object-fit:cover;border-radius:6px;}

    /* STATUS BADGES */
    .status-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:13px;font-weight:600;}
    .status-normal {background:#e8f5e9;color:#2e7d32;}
    .status-warning{background:#fff8e1;color:#f57c00;}
    .status-danger {background:#ffebee;color:#c62828;}

    /* THRESHOLD BOX */
    .threshold-box{background:#fff;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.08);padding:18px 20px;margin-bottom:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
    .threshold-box .label{font-weight:600;color:#333;font-size:14px;white-space:nowrap;}
    .threshold-box input[type=number]{width:90px;padding:8px 10px;border:2px solid #8e4b00;border-radius:6px;font-size:15px;font-weight:600;text-align:center;color:#8e4b00;}
    .threshold-badge{display:inline-block;background:#fff3cd;color:#856404;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:600;}

    /* DATE BADGE */
    .date-info-box{background:#e8f4fd;border:1px solid #bee5eb;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:14px;color:#0c5460;display:flex;align-items:center;gap:8px;}

    /* ALERTS */
    .alert-section{background:#fffbe6;border-left:4px solid #8e4b00;padding:1rem;margin-top:1.5rem;border-radius:0 6px 6px 0;}
    .alert-title{font-weight:600;color:#8e4b00;margin-bottom:.5rem;}

    .stock-value{font-weight:bold;font-size:15px;color:#2e7d32;}
    .stock-note{font-size:12px;color:#888;display:block;}
    .no-data{text-align:center;padding:30px;color:#888;}

    /* Highlight tồn theo ngày */
    .stock-as-of{font-weight:bold;color:#185fa5;}
  </style>
</head>
<body>

<div class="sidebar">
  <div class="logo">
    <img src="../../images/Admin_login.jpg" alt="Admin Logo">
    <h2>Luxury Jewelry Admin</h2>
  </div>
  <div class="menu">
    <a href="../Administration_menu.php#products">Jewelry List</a>
    <a href="../product_management.php">Product Management</a>
    <a href="../Administration_menu.php#users">Customers</a>
    <a href="../Price Manage/pricing.php">Pricing Management</a>
    <a href="../Import_product/import_management.php">Import Management</a>
    <a href="../Order Manage/order_management.php">Order Management</a>
    <a href="stocking_management.php" class="active">Stocking Management</a>
    <a href="../Administration_menu.php#settings">Settings</a>
  </div>
</div>

<main>
  <h1>Stocking Management</h1>

  <div class="tabs">
    <div class="tab <?= $active_tab==='stock-lookup' ?'active':'' ?>" data-tab="stock-lookup">Stock Lookup</div>
    <div class="tab <?= $active_tab==='stock-alert'  ?'active':'' ?>" data-tab="stock-alert">Low Stock Alert</div>
    <div class="tab <?= $active_tab==='stock-report' ?'active':'' ?>" data-tab="stock-report">Import - Export - Balance</div>
  </div>

  <!-- ====== TAB 1: Stock Lookup ====== -->
  <div class="tab-content <?= $active_tab==='stock-lookup'?'active':'' ?>" id="stock-lookup">

    <form method="GET" action="stocking_management.php" class="search-form">
      <input type="hidden" name="tab" value="stock-lookup">
      <div class="form-group">
        <label>Product Name</label>
        <input type="text" name="search_name" placeholder="Enter product name..."
               value="<?= htmlspecialchars($search_name) ?>">
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="search_category">
          <option value="All">All</option>
          <?php foreach ($cat_list as $c): ?>
            <option value="<?= htmlspecialchars($c['category']) ?>"
                    <?= $search_category===$c['category']?'selected':'' ?>>
              <?= htmlspecialchars($c['category']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>
          As of Date
          <span style="font-weight:normal;color:#888;font-size:12px"> — để trống = tồn hiện tại</span>
        </label>
        <input type="date" name="search_date" value="<?= htmlspecialchars($search_date) ?>"
               max="<?= date('Y-m-d') ?>">
      </div>
      <button type="submit" class="btn">Search</button>
      <a href="stocking_management.php?tab=stock-lookup" class="btn secondary">Reset</a>
    </form>

    <!-- Thông báo khi đang xem tồn theo ngày cụ thể -->
    <?php if ($search_date !== ''): ?>
      <div class="date-info-box">
        <span style="font-size:16px">📅</span>
        Đang hiển thị tồn kho tại ngày <strong><?= htmlspecialchars($search_date) ?></strong>
        — tính theo nhập ≤ ngày đó trừ xuất ≤ ngày đó.
        <a href="stocking_management.php?tab=stock-lookup" style="margin-left:auto;color:#185fa5;font-size:13px;">Xem tồn hiện tại</a>
      </div>
    <?php endif; ?>

    <table>
      <thead>
        <tr>
          <th>Product Code</th>
          <th>Product Name</th>
          <th>Image</th>
          <th>Category</th>
          <th><?= $search_date !== '' ? 'Stock at ' . htmlspecialchars($search_date) : 'Current Stock' ?></th>
          <?php if ($search_date !== ''): ?>
            <th>Total Imported (≤ date)</th>
            <th>Total Sold (≤ date)</th>
          <?php endif; ?>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="<?= $search_date !== '' ? 8 : 6 ?>" class="no-data">No products found.</td></tr>
        <?php else: ?>
          <?php foreach ($products as $p):
            $st = stockStatus($p['stock'], $min_stock);
          ?>
            <tr>
              <td><?= htmlspecialchars($p['id']) ?></td>
              <td style="text-align:left"><?= htmlspecialchars($p['name']) ?></td>
              <td><img src="../../images/<?= htmlspecialchars($p['image']) ?>" onerror="this.style.opacity='.2'"></td>
              <td><?= htmlspecialchars($p['category']) ?></td>
              <td>
                <span class="<?= $search_date !== '' ? 'stock-as-of' : '' ?>">
                  <?= intval($p['stock']) ?>
                </span>
              </td>
              <?php if ($search_date !== ''): ?>
                <td style="color:#2e7d32"><?= $p['in_qty']  ?? 0 ?></td>
                <td style="color:#c62828"><?= $p['out_qty'] ?? 0 ?></td>
              <?php endif; ?>
              <td><span class="status-badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="alert-section" style="margin-top:16px;">
      <div class="alert-title">Note:</div>
      <?php if ($search_date !== ''): ?>
        Tồn kho tại <strong><?= htmlspecialchars($search_date) ?></strong>
        = Tổng nhập (Completed, entry_date ≤ ngày đó) − Tổng xuất (order_date ≤ ngày đó).
      <?php else: ?>
        Tồn kho hiện tại = Tổng nhập (Completed) − Tổng đã bán.
        Ngưỡng cảnh báo hiện tại: <strong><?= $min_stock ?></strong> — chỉnh ở tab
        <em>Low Stock Alert</em>.
      <?php endif; ?>
    </div>
  </div>

  <!-- ====== TAB 2: Low Stock Alert — ngưỡng tùy chỉnh ====== -->
  <div class="tab-content <?= $active_tab==='stock-alert'?'active':'' ?>" id="stock-alert">

    <!-- Form chỉnh ngưỡng -->
    <form method="POST" action="stocking_management.php?tab=stock-alert" class="threshold-box">
      <input type="hidden" name="set_threshold" value="1">
      <span class="label">Ngưỡng cảnh báo hết hàng:</span>
      <input type="number" name="threshold" value="<?= $min_stock ?>" min="0" max="9999">
      <span style="font-size:14px;color:#666">sản phẩm</span>
      <button type="submit" class="btn" style="padding:8px 18px;">Áp dụng</button>
      <span class="threshold-badge">Hiện tại: ≤ <?= $min_stock ?> sp = Low Stock</span>
      <span style="font-size:13px;color:#888;margin-left:auto;">
        * Ngưỡng được lưu trong phiên làm việc
      </span>
    </form>

    <!-- Cảnh báo -->
    <div class="alert-section" style="margin-bottom:16px;">
      <div class="alert-title">Low Stock Warning:</div>
      <?php if (empty($low_products)): ?>
        ✅ Tất cả sản phẩm đều có tồn kho trên ngưỡng <?= $min_stock ?>.
      <?php else: ?>
        Có <strong><?= count($low_products) ?></strong> sản phẩm có tồn kho ≤ <?= $min_stock ?> cần nhập thêm!
      <?php endif; ?>
    </div>

    <table>
      <thead>
        <tr>
          <th>Product Code</th><th>Product Name</th><th>Image</th>
          <th>Category</th><th>Current Stock</th>
          <th>Threshold</th><th>Need to Restock</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($low_products)): ?>
          <tr><td colspan="8" class="no-data">✅ All products are sufficiently stocked.</td></tr>
        <?php else: ?>
          <?php foreach ($low_products as $p):
            $st       = stockStatus($p['stock'], $min_stock);
            $need_qty = max(0, $min_stock - $p['stock'] + 1); // cần nhập tối thiểu để vượt ngưỡng
          ?>
            <tr>
              <td><?= htmlspecialchars($p['id']) ?></td>
              <td style="text-align:left"><?= htmlspecialchars($p['name']) ?></td>
              <td><img src="../../images/<?= htmlspecialchars($p['image']) ?>" class="product-img" onerror="this.style.opacity='.2'"></td>
              <td><?= htmlspecialchars($p['category']) ?></td>
              <td>
                <strong style="color:<?= $p['stock'] <= 0 ? '#c62828' : '#f57c00' ?>">
                  <?= intval($p['stock']) ?>
                </strong>
              </td>
              <td><?= $min_stock ?></td>
              <td>
                <span style="color:#c62828;font-weight:600">+<?= $need_qty ?></span>
              </td>
              <td><span class="status-badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ====== TAB 3: Import - Export - Balance Report ====== -->
  <div class="tab-content <?= $active_tab==='stock-report'?'active':'' ?>" id="stock-report">
    <form method="GET" action="stocking_management.php" class="search-form">
      <input type="hidden" name="tab" value="stock-report">
      <div class="form-group">
        <label>Product</label>
        <input type="text" name="report_product" placeholder="Enter product name..."
               value="<?= htmlspecialchars($report_product) ?>">
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
      <thead>
        <tr>
          <th>Date</th>
          <th>Import Qty</th>
          <th>Export Qty</th>
          <th>Current Stock <small style="font-weight:normal">(tồn thực tế)</small></th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
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
                <a href="IEP_report_detail.php?date=<?= urlencode($r['date']) ?>&product=<?= urlencode($report_product) ?>"
                   class="btn small">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if (!empty($report_rows)): ?>
    <div class="alert-section" style="margin-top:16px;">
      <div class="alert-title">Summary (filtered period):</div>
      <p>Total Imported: <strong><?= $total_import ?></strong> units</p>
      <p>Total Exported: <strong><?= $total_export ?></strong> units</p>
      <p>Current Stock (from DB): <strong style="color:#2e7d32"><?= $current_stock ?></strong> units</p>
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
