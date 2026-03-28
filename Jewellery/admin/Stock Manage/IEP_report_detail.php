<?php
require_once "../../config/config.php";
// $conn → jewelry_db

// ============================================================
// Tham số từ URL
// ============================================================
$report_date    = isset($_GET['date'])    ? trim($_GET['date'])    : '';
$report_product = isset($_GET['product']) ? trim($_GET['product']) : '';

if ($report_date === '') {
    header('Location: stocking_management.php?tab=stock-report');
    exit;
}

// ============================================================
// Lấy dữ liệu IMPORT theo ngày từ goods_receipt_items
// goods_receipt.entry_date = $report_date, status = Completed
// ============================================================
$import_where  = ["gr.entry_date = ?", "gr.status = 'Completed'"];
$import_params = [$report_date];
$import_types  = 's';

if ($report_product !== '') {
    $import_where[]  = 'gri.product_name LIKE ?';
    $import_params[] = '%' . $report_product . '%';
    $import_types   .= 's';
}

$import_sql = "
    SELECT gri.product_id, gri.product_name, gri.quantity AS import_qty,
           p.image, p.category
    FROM goods_receipt gr
    JOIN goods_receipt_items gri ON gr.id = gri.receipt_id
    LEFT JOIN products p ON gri.product_id = p.id
    WHERE " . implode(' AND ', $import_where) . "
    ORDER BY gri.product_id ASC
";
$stmt_i = $conn->prepare($import_sql);
$stmt_i->bind_param($import_types, ...$import_params);
$stmt_i->execute();
$import_rows = $stmt_i->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_i->close();

// ============================================================
// Lấy dữ liệu EXPORT theo ngày từ order_items
// orders.order_date = $report_date
// ============================================================
$export_where  = ["o.order_date = ?"];
$export_params = [$report_date];
$export_types  = 's';

if ($report_product !== '') {
    $export_where[]  = 'oi.product_name LIKE ?';
    $export_params[] = '%' . $report_product . '%';
    $export_types   .= 's';
}

$export_sql = "
    SELECT oi.product_id, oi.product_name, SUM(oi.quantity) AS export_qty,
           p.image, p.category
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE " . implode(' AND ', $export_where) . "
    GROUP BY oi.product_id, oi.product_name, p.image, p.category
    ORDER BY oi.product_id ASC
";
$stmt_e = $conn->prepare($export_sql);
$stmt_e->bind_param($export_types, ...$export_params);
$stmt_e->execute();
$export_rows = $stmt_e->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_e->close();

// ============================================================
// Gộp import + export theo product_id
// ============================================================
$product_map = [];

foreach ($import_rows as $r) {
    $pid = $r['product_id'];
    $product_map[$pid]['product_id']   = $pid;
    $product_map[$pid]['product_name'] = $r['product_name'];
    $product_map[$pid]['image']        = $r['image']    ?? ($pid . '.jpg');
    $product_map[$pid]['category']     = $r['category'] ?? 'N/A';
    $product_map[$pid]['import_qty']   = (int)$r['import_qty'];
    $product_map[$pid]['export_qty']   = $product_map[$pid]['export_qty'] ?? 0;
}

foreach ($export_rows as $r) {
    $pid = $r['product_id'];
    $product_map[$pid]['product_id']   = $pid;
    $product_map[$pid]['product_name'] = $r['product_name'];
    $product_map[$pid]['image']        = $r['image']    ?? ($pid . '.jpg');
    $product_map[$pid]['category']     = $r['category'] ?? 'N/A';
    $product_map[$pid]['export_qty']   = (int)$r['export_qty'];
    $product_map[$pid]['import_qty']   = $product_map[$pid]['import_qty'] ?? 0;
}

// Tính balance
$detail_rows = [];
foreach ($product_map as $row) {
    $row['balance'] = $row['import_qty'] - $row['export_qty'];
    $detail_rows[]  = $row;
}
usort($detail_rows, fn($a, $b) => strcmp($a['product_id'], $b['product_id']));

// Tổng summary
$total_import  = array_sum(array_column($detail_rows, 'import_qty'));
$total_export  = array_sum(array_column($detail_rows, 'export_qty'));
$total_balance = $total_import - $total_export;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Import & Export Report Detail</title>
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

    .report-header { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); margin-bottom: 20px; }
    .report-header h2 { color: #8e4b00; margin-bottom: 10px; }
    .report-header p { font-size: 15px; color: #555; margin-bottom: 15px; }

    table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
    th, td { padding: 14px 16px; text-align: center; font-size: 15px; border-bottom: 1px solid #f0e2d0; }
    th { background: #8e4b00; color: #f8ce86; font-weight: 600; }
    tr:hover td { background: #f9f2e7; }

    .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }

    .btn { display: inline-block; background-color: #8e4b00; color: #f8ce86; border: none; border-radius: 6px; padding: 10px 16px; font-weight: bold; cursor: pointer; transition: 0.3s; text-decoration: none; }
    .btn:hover { background-color: #f8ce86; color: #8e4b00; }

    .summary { background: #fff; border-left: 4px solid #8e4b00; padding: 1rem; margin-top: 1.5rem; border-radius: 6px; }
    .summary p { font-size: 15px; margin-bottom: 6px; }
    .summary strong { color: #8e4b00; }

    .no-data { text-align: center; padding: 30px; color: #888; font-size: 15px; }
    .balance-pos { color: #2e7d32; font-weight: bold; }
    .balance-neg { color: #c62828; font-weight: bold; }
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
    <h1>Import & Export Report Detail</h1>

    <div class="report-header">
      <h2>Date: <?= htmlspecialchars($report_date) ?></h2>
      <p>
        Report showing detailed import and export of jewelry items on this date.
        <?= $report_product ? " — Filter: <strong>" . htmlspecialchars($report_product) . "</strong>" : '' ?>
      </p>
      <a href="stocking_management.php?tab=stock-report" class="btn">← Back</a>
    </div>

    <table>
      <tr>
        <th>Product Image</th>
        <th>Product Code</th>
        <th>Product Name</th>
        <th>Category</th>
        <th>Import Qty</th>
        <th>Export Qty</th>
        <th>Balance</th>
      </tr>
      <?php if (empty($detail_rows)): ?>
        <tr><td colspan="7" class="no-data">No data found for this date.</td></tr>
      <?php else: ?>
        <?php foreach ($detail_rows as $r): ?>
          <tr>
            <td>
              <img src="../../images/<?= htmlspecialchars($r['image']) ?>"
                   class="product-img"
                   onerror="this.style.opacity='0.2'">
            </td>
            <td><?= htmlspecialchars($r['product_id']) ?></td>
            <td><?= htmlspecialchars($r['product_name']) ?></td>
            <td><?= htmlspecialchars($r['category']) ?></td>
            <td><?= $r['import_qty'] ?></td>
            <td><?= $r['export_qty'] ?></td>
            <td class="<?= $r['balance'] >= 0 ? 'balance-pos' : 'balance-neg' ?>">
              <?= $r['balance'] ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>

    <div class="summary">
      <p><strong>Total Imported:</strong> <?= $total_import ?> units</p>
      <p><strong>Total Exported:</strong> <?= $total_export ?> units</p>
      <p><strong>Remaining Balance:</strong>
        <span class="<?= $total_balance >= 0 ? 'balance-pos' : 'balance-neg' ?>">
          <?= $total_balance ?> units
        </span>
      </p>
    </div>
  </main>
</body>
</html>
<?php $conn->close(); ?>
